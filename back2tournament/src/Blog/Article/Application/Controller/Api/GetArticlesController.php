<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Model\FindArticlesQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles/', name: 'api_article_list', methods: ['GET'])]
#[OA\Tag(name: 'Article')]
#[OA\Parameter(
    name: 'page',
    in: 'query',
    required: false,
    description: 'Which page to read, 1 by default.',
    schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
)]
#[OA\Parameter(
    name: 'limit',
    in: 'query',
    required: false,
    description: 'How many articles that page holds, 10 by default and 50 at most.',
    schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10),
)]
#[OA\Parameter(
    name: 'q',
    in: 'query',
    required: false,
    description: 'Keeps the articles whose title or body contains this text, whatever the case. A blank value is no search.',
    schema: new OA\Schema(type: 'string'),
)]
#[OA\Parameter(
    name: 'category',
    in: 'query',
    required: false,
    description: 'Keeps the articles filed under this category, named by its slug.',
    schema: new OA\Schema(type: 'string', example: 'actualites'),
)]
#[OA\Response(
    response: 200,
    description: 'One page of published articles, newest first by their publication date. Comments are not included: `GET /api/articles/{id}` returns an article with its comments. `items` is empty when the filters match nothing, or when the page is past the last one.',
    content: new OA\JsonContent(
        required: ['items', 'total', 'page', 'limit', 'pages'],
        properties: [
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Article')),
            new OA\Property(property: 'total', type: 'integer', description: 'Articles the filters match, every page taken together', example: 42),
            new OA\Property(property: 'page', type: 'integer', description: 'The page these items come from', example: 1),
            new OA\Property(property: 'limit', type: 'integer', description: 'How many items a full page holds', example: 10),
            new OA\Property(property: 'pages', type: 'integer', description: 'How many pages the filters yield, 0 when nothing matches', example: 5),
        ],
        type: 'object',
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 404, description: 'No category has the requested slug', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[Security(name: null)]
final class GetArticlesController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindArticlesQuery(
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10),
            $request->query->getString('q'),
            $request->query->getString('category'),
        )));
    }
}
