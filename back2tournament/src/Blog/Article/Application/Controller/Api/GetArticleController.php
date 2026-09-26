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
    description: 'Article found, including its comments',
    content: new OA\JsonContent(
        description: 'Identifiers are serialized as a `{value: string}` object (Value Object)',
        properties: [
            new OA\Property(property: 'category', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'id', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'body', type: 'string', nullable: true),
            new OA\Property(property: 'title', type: 'string', nullable: true),
            new OA\Property(property: 'author', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(
                property: 'comments',
                type: 'array',
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'articleId', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
                    ],
                    type: 'object',
                ),
            ),
        ],
    ),
)]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
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
