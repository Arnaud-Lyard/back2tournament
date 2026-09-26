<?php

declare(strict_types=1);

namespace App\Blog\Category\Application\Controller;

use App\Blog\Category\Application\Model\FindCategoriesQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories/', name: 'api_category_list', methods: ['GET'])]
#[OA\Tag(name: 'Category')]
#[OA\Response(
    response: 200,
    description: 'Every category, by name. Public: the blog filters its articles by category. An empty array when no category has been created yet.',
    content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
)]
#[Security(name: null)]
final class GetCategoriesController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindCategoriesQuery()));
    }
}
