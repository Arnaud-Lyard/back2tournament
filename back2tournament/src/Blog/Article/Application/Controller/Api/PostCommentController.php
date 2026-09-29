<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Model\CreateCommentCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/comments/', name: 'api_comment_post', methods: ['POST'])]
#[OA\Tag(name: 'Comment')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['articleId', 'message'],
        properties: [
            new OA\Property(property: 'articleId', type: 'string', format: 'uuid', description: 'ID of the commented article'),
            new OA\Property(property: 'message', type: 'string', example: 'Great article!'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'The comment created, written by the authenticated user',
    content: new OA\JsonContent(ref: '#/components/schemas/Comment'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 404, description: 'No article has this id, or it is a draft and the caller is not an editor', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 409, description: 'The article is a draft: comments open once it is published', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class PostCommentController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        $createCommentCommand = new CreateCommentCommand();
        $createCommentCommand->setArticleId($parameters['articleId']);
        $createCommentCommand->setMessage($parameters['message']);

        $comment = $this->handle($createCommentCommand);

        return JsonResponse::fromJsonString($comment);
    }
}
