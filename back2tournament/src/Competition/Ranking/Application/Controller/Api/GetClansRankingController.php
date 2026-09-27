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

#[Route('/api/rankings/games/{gameId}/clans', name: 'api_rankings_clans', methods: ['GET'])]
#[OA\Tag(name: 'Ranking')]
#[OA\Parameter(name: 'gameId', in: 'path', required: true, description: 'Game ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'size', in: 'query', required: false, description: 'The format, as the number of players per side: 1 for the 1v1 ranking, 2 for the 2v2 one. One of the formats of the game, its smallest one by default.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 64))]
#[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Which page to read, 1 by default.', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1))]
#[OA\Parameter(name: 'limit', in: 'query', required: false, description: 'How many entries that page holds, 20 by default and 50 at most.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 20))]
#[OA\Response(
    response: 200,
    description: 'One page of the Elo ranking of a game\'s clans in one format, the highest rating first. A clan enters the ranking of a format on its first settled fight in it against another clan: a fight of one of its teams, or in 1v1 a duel of one of its members. Equal ratings share a rank. `items` is empty when no clan is ranked yet, or when the page is past the last one. A format the game is not played in is refused.',
    content: new OA\JsonContent(ref: '#/components/schemas/RankingPage'),
)]
#[OA\Response(response: 400, description: 'The game is not played in this format, or its id is no UUID', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[Security(name: null)]
final class GetClansRankingController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $gameId): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(FindRankingQuery::ofClans(
            $gameId,
            $request->query->has('size') ? $request->query->getInt('size') : null,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 20),
        )));
    }
}
