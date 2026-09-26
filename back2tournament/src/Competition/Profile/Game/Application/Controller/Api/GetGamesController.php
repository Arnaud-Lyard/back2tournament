<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Controller\Api;

use App\Competition\Profile\Game\Application\Model\FindGamesQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games/', name: 'api_game_list', methods: ['GET'])]
#[OA\Tag(name: 'Game')]
#[OA\Response(
    response: 200,
    description: 'Every game, by title. An empty array when no game has been created yet.',
    content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Game')),
)]
#[Security(name: null)]
final class GetGamesController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindGamesQuery()));
    }
}
