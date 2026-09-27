<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Controller\Api;

use App\Blog\Article\Application\Model\UpdateArticleCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles/{id}', name: 'api_article_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Article')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Article ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Requires an editor or an administrator. Every key is optional: a key left out keeps what the article has. `titleEn` and `bodyEn` sent empty or null remove the English version, which must stay whole: a title and a body, or neither. A draft and a published article are edited alike; editing does not change the status or the author.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Les résultats du week-end'),
            new OA\Property(property: 'body', type: 'string', example: 'Retour sur les matchs de samedi…'),
            new OA\Property(property: 'categorySlug', type: 'string', example: 'actualites', description: 'The category to file the article under, named by its slug.'),
            new OA\Property(property: 'titleEn', type: 'string', nullable: true, maxLength: 255, example: 'The weekend results'),
            new OA\Property(property: 'bodyEn', type: 'string', nullable: true, example: 'Looking back at Saturday\'s matches…'),
        ],
    ),
)]
#[OA\Response(response: 200, description: 'The article as edited', content: new OA\JsonContent(ref: '#/components/schemas/Article'))]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
final class PatchArticleController extends AbstractController
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

        return JsonResponse::fromJsonString($this->handle(new UpdateArticleCommand(
            $id,
            $parameters['title'] ?? null,
            $parameters['body'] ?? null,
            $parameters['categorySlug'] ?? null,
            self::englishField($parameters, 'titleEn'),
            self::englishField($parameters, 'bodyEn'),
        )));
    }

    /**
     * Null when the key is left out, to keep what the article has; an empty
     * string when it is sent empty or null, to remove the English version.
     *
     * @param array<string, mixed> $parameters
     */
    private static function englishField(array $parameters, string $key): ?string
    {
        return \array_key_exists($key, $parameters) ? ($parameters[$key] ?? '') : null;
    }
}
