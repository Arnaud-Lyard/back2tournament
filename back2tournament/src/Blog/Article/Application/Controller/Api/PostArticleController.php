<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Event\OnPublicationRequestedEvent;
use OpenApi\Attributes as OA;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles/', name: 'api_article_post', methods: ['POST'])]
#[OA\Tag(name: 'Article')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['title', 'body', 'categorySlug'],
        properties: [
            new OA\Property(property: 'title', type: 'string', example: 'My article'),
            new OA\Property(property: 'body', type: 'string', example: 'Article content...'),
            new OA\Property(property: 'categorySlug', type: 'string', description: 'Slug of an existing category', example: 'news'),
            new OA\Property(property: 'titleEn', type: 'string', nullable: true, maxLength: 255, example: 'My article', description: 'The English title. Optional, but the English version is whole: send it with `bodyEn`, or neither. Blank reads as absent.'),
            new OA\Property(property: 'bodyEn', type: 'string', nullable: true, example: 'Article content...', description: 'The English body, sent with `titleEn`.'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'The article created, as a draft: it has no author until an editor publishes it (`PATCH /api/articles/{id}/status`)',
    content: new OA\JsonContent(ref: '#/components/schemas/Article'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
final class PostArticleController extends AbstractController
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        $event = $this->eventDispatcher->dispatch(new OnPublicationRequestedEvent(
            $parameters['title'],
            $parameters['body'],
            $parameters['categorySlug'],
            $parameters['titleEn'] ?? null,
            $parameters['bodyEn'] ?? null,
        ));

        return JsonResponse::fromJsonString($event->getCreatedArticle());
    }
}
