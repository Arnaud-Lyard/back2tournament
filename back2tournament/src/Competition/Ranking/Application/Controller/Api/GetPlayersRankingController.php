<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Controller\Api;

use App\Competition\Ranking\Application\Model\FindRankingQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rankings/games/{gameId}/players', name: 'api_rankings_players', methods: ['GET'])]
#[OA\Tag(name: 'Ranking')]
#[OA\Parameter(name: 'gameId', in: 'path', required: true, description: 'Game ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Which page to read, 1 by default.', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1))]
#[OA\Parameter(name: 'limit', in: 'query', required: false, description: 'How many entries that page holds, 20 by default and 50 at most.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 20))]
#[OA\Response(
    response: 200,
    description: 'One page of the Elo ranking of a game\'s player profiles, the highest rating first. A profile enters it on its first settled 1v1 fight; team fights count for the clans. Equal ratings share a rank. `items` is empty when nobody is ranked yet, or when the page is past the last one.',
    content: new OA\JsonContent(ref: '#/components/schemas/RankingPage'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[Security(name: null)]
final class GetPlayersRankingController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $gameId): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(FindRankingQuery::ofPlayers(
            $gameId,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 20),
        )));
    }
}
