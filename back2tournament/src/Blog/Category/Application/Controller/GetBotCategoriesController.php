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

#[Route('/api/bot/categories/', name: 'api_bot_category_list', methods: ['GET'])]
#[OA\Tag(name: 'Category')]
#[Security(name: 'apiToken')]
#[OA\Response(
    response: 200,
    description: 'Every category, by name, as the public `GET /api/categories/` lists them, read with an API token: the `slug` of one of them is the `categorySlug` a draft is filed under (`POST /api/bot/articles/`). A tool sends its token to every route it calls, and the JWT firewall of `/api/categories/` refuses a token that is not a JWT. An empty array when no category has been created yet.',
    content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
)]
#[OA\Response(response: 401, ref: '#/components/responses/ApiTokenUnauthorized')]
final class GetBotCategoriesController extends AbstractController
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
