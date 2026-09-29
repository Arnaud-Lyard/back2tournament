<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Event\OnUpdateFightResultsEvent;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/api/user/fights/{id}/results', name: 'api_fight_results_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Fight')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Fight ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'The scores, seen from the side of the caller, who declares them: the side is the player profile they own, or the team they lead, in this fight. The outcome follows from the scores: the higher one wins, equal scores draw — and a tournament fight cannot end in a draw. Until the other side confirms, the declaring side may declare again to correct itself; the other side cannot overwrite the declaration.',
    content: new OA\JsonContent(
        required: ['score', 'opponentScore'],
        properties: [
            new OA\Property(property: 'score', type: 'integer', minimum: 0, example: 3, description: 'Points of the caller\'s side'),
            new OA\Property(property: 'opponentScore', type: 'integer', minimum: 0, example: 1, description: 'Points of the other side'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Scores declared, waiting for the other side to confirm them: both sides are `reporting`, each with its claimed `score` and `reportedStatus`, and `declaredBy` names the caller\'s side.',
    content: new OA\JsonContent(ref: '#/components/schemas/FightSummary'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller stands on neither side of the fight')]
#[OA\Response(response: 404, description: 'The fight, or a result for one of its two sides, does not exist')]
#[OA\Response(response: 409, description: 'The fight is settled, or the other side already declared')]
final class PatchFightResultsController extends AbstractController
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        $event = $this->eventDispatcher->dispatch(new OnUpdateFightResultsEvent(
            $id,
            $parameters['score'],
            $parameters['opponentScore'],
        ));

        return JsonResponse::fromJsonString($event->getUpdatedFight());
    }
}
