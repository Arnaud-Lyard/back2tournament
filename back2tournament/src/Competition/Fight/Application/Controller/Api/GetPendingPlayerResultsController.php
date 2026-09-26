<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Model\FindPendingPlayerResultsQuery;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/results/pending/players/{gameid}', name: 'api_results_pending_players', methods: ['GET'])]
#[OA\Tag(name: 'Fight')]
#[OA\Parameter(
    name: 'gameid',
    in: 'path',
    required: true,
    description: 'Game to read. The results returned are those of the player profile the authenticated user holds in it.',
    schema: new OA\Schema(type: 'string', format: 'uuid'),
)]
#[OA\Response(
    response: 200,
    description: 'The unsettled results of the player profile the authenticated user owns in this game, oldest first: `pending` while nobody declared, `reporting` while one side waits for the other to confirm. An empty array when nothing is waiting.',
    content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Result')),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, description: 'The authenticated user holds no player profile competing in this game')]
final class GetPendingPlayerResultsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $gameid): JsonResponse
    {
        $result = $this->handle(
            new FindPendingPlayerResultsQuery(new GameId($gameid)->getValue())
        );

        return JsonResponse::fromJsonString($result);
    }
}
