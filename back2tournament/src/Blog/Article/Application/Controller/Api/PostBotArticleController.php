<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Model\CreateArticleCommand;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/bot/articles/', name: 'api_bot_article_post', methods: ['POST'])]
#[OA\Tag(name: 'Article')]
#[Security(name: 'apiToken')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['title', 'body', 'categorySlug'],
        properties: [
            new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Les résultats du week-end'),
            new OA\Property(property: 'body', type: 'string', example: 'Article content...'),
            new OA\Property(property: 'categorySlug', type: 'string', description: 'Slug of an existing category, as `GET /api/bot/categories/` lists them', example: 'news'),
            new OA\Property(property: 'titleEn', type: 'string', nullable: true, maxLength: 255, example: 'The weekend results', description: 'The English title. Optional, but the English version is whole: send it with `bodyEn`, or neither. Blank reads as absent.'),
            new OA\Property(property: 'bodyEn', type: 'string', nullable: true, example: 'Article content...', description: 'The English body, sent with `titleEn`.'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'The article a tool wrote, such as Hermes, saved as a draft: it shows nowhere until an editor reviews it and publishes it (`PATCH /api/editor/articles/{id}/status`). The tool authenticates with an API token issued by `bin/console app:api-token:create`, which opens the routes under `/api/bot` and no other.',
    content: new OA\JsonContent(ref: '#/components/schemas/Article'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/ApiTokenUnauthorized')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
final class PostBotArticleController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $createArticleCommand = new CreateArticleCommand();
        $createArticleCommand->setTitle($parameters['title'] ?? '');
        $createArticleCommand->setBody($parameters['body'] ?? '');
        $createArticleCommand->setCategorySlug($parameters['categorySlug'] ?? '');
        $createArticleCommand->setTitleEn($parameters['titleEn'] ?? null);
        $createArticleCommand->setBodyEn($parameters['bodyEn'] ?? null);

        return JsonResponse::fromJsonString($this->handle($createArticleCommand));
    }
}
