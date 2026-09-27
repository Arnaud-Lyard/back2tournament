<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Model\FindArticleCommentsQuery;
use App\Blog\Article\Domain\Entity\ArticleId;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles/{id}/comments', name: 'api_comment_list', methods: ['GET'])]
#[OA\Tag(name: 'Comment')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Article ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Every comment on the article, oldest first. An empty array when nobody has commented yet.',
    content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Comment')),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 404, description: 'No article has this id, or it is a draft and the caller is not an editor', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[Security(name: null)]
final class GetArticleCommentsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindArticleCommentsQuery(new ArticleId($id)->getValue())));
    }
}
