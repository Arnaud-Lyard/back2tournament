<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Model\FindArticleQuery;
use App\Blog\Article\Domain\Entity\ArticleId;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles/{id}', name: 'api_article', methods: ['GET'])]
#[OA\Tag(name: 'Article')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Article ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'The article with its comments, oldest first. A draft is found by an editor or an administrator only.',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/Article'),
            new OA\Schema(
                properties: [
                    new OA\Property(property: 'comments', type: 'array', items: new OA\Items(ref: '#/components/schemas/Comment')),
                ],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(response: 404, description: 'No article has this id, or it is a draft and the caller is not an editor', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[Security(name: null)]
final class GetArticleController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        $article = $this->handle(new FindArticleQuery(new ArticleId($id)->getValue()));

        return JsonResponse::fromJsonString($article);
    }
}
