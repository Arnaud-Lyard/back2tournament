<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Model\ChangeArticleStatusCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/editor/articles/{id}/status', name: 'api_article_status_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Article')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Article ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Requires an editor or an administrator. `published` publishes the article: the authenticated user becomes its author, whoever wrote it, and `publishedAt` is set. `draft` takes it back off the blog: it loses its author and its publication date until it is published again.',
    content: new OA\JsonContent(
        required: ['status'],
        properties: [
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'published'),
        ],
    ),
)]
#[OA\Response(response: 200, description: 'The article in its new status', content: new OA\JsonContent(ref: '#/components/schemas/Article'))]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[OA\Response(response: 409, ref: '#/components/responses/Conflict')]
final class PatchArticleStatusController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        return JsonResponse::fromJsonString($this->handle(new ChangeArticleStatusCommand(
            $id,
            $parameters['status'],
        )));
    }
}
