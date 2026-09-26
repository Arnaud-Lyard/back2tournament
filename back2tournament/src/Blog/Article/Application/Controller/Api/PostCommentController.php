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

#[Route('/api/comments/', name: 'api_comment_post', methods: ['POST'])]
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
    description: 'Comment created',
    content: new OA\JsonContent(
        description: 'Identifiers are serialized as a `{value: string}` object (Value Object)',
        properties: [
            new OA\Property(property: 'id', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'articleId', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
        ],
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
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
