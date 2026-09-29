<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Model\FindArticleQuery;
use App\Blog\Article\Domain\Entity\ArticleId;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/editor/articles/{id}', name: 'api_editor_article', methods: ['GET'])]
#[OA\Tag(name: 'Article')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Article ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Requires an editor or an administrator. The article with its comments, oldest first, whether it is a draft or published.',
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
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, description: 'No article has this id', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class GetEditorArticleController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        $article = $this->handle(new FindArticleQuery(new ArticleId($id)->getValue(), true));

        return JsonResponse::fromJsonString($article);
    }
}
