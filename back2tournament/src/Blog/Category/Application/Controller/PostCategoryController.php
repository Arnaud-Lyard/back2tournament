<?php

declare(strict_types=1);

namespace App\Blog\Category\Application\Controller;

use App\Blog\Category\Application\Model\CreateCategoryCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

 #[Route('/api/admin/categories/', name: 'api_category_post', methods: ['POST'])]
#[OA\Tag(name: 'Category')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'slug'],
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'News'),
            new OA\Property(property: 'slug', type: 'string', example: 'news'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Category created',
    content: new OA\JsonContent(
        description: 'Unlike other resources, the id here is a plain string (no nested Value Object)',
        properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'slug', type: 'string'),
        ],
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
final class PostCategoryController extends AbstractController
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

        $createCategoryCommand = new CreateCategoryCommand(
            $parameters['name'],
            $parameters['slug']
        );

        return JsonResponse::fromJsonString($this->handle($createCategoryCommand));
    }
}
