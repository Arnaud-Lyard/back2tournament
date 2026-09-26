<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Event\OnUpdateFightResultsEvent;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fights/{id}/results', name: 'api_fight_results_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Fight')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Fight ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'The outcome for each side, in the order the fight holds its competitors — `GET /api/fights/{id}/results/{gameid}` returns them in that same order. The two statuses must agree: a win against a loss, or two draws. The declaring side is the authenticated user, never a field of the body.',
    content: new OA\JsonContent(
        required: ['fight', 'game', 'competitorOneStatus', 'competitorOneScore', 'competitorTwoStatus', 'competitorTwoScore'],
        properties: [
            new OA\Property(property: 'fight', type: 'string', format: 'uuid', description: 'Fight being declared. Repeat the `id` of the path here.'),
            new OA\Property(property: 'game', type: 'string', format: 'uuid', description: 'Game of the fight: with the authenticated user it names the competitor declaring the results'),
            new OA\Property(property: 'competitorOneStatus', type: 'string', enum: ['win', 'loss', 'draw'], description: 'Outcome claimed for the first competitor of the fight'),
            new OA\Property(property: 'competitorOneScore', type: 'integer', minimum: 0, example: 3),
            new OA\Property(property: 'competitorTwoStatus', type: 'string', enum: ['win', 'loss', 'draw'], description: 'Outcome claimed for the second competitor of the fight'),
            new OA\Property(property: 'competitorTwoScore', type: 'integer', minimum: 0, example: 1),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Results declared, waiting for the other side to confirm them. Both results move to `reporting`, carrying the claimed score in `score` and the claimed outcome in `reportedStatus`. The fight is returned, with `declaredBy` naming the side that just declared.',
    content: new OA\JsonContent(ref: '#/components/schemas/Fight'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, description: 'The fight, or a result for one of its two sides, does not exist')]
final class PatchFightResultsController extends AbstractController
{
    use HandleTrait;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(MessageBusInterface $messageBus, EventDispatcherInterface $eventDispatcher)
    {
        $this->messageBus = $messageBus;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        $this->eventDispatcher->dispatch(new OnUpdateFightResultsEvent(
            $parameters['fight'],
            $parameters['game'],
            $parameters['competitorOneStatus'],
            $parameters['competitorOneScore'],
            $parameters['competitorTwoStatus'],
            $parameters['competitorTwoScore'],
        ));

        return JsonResponse::fromJsonString(
            $request->getSession()->get('last_fight_result_updated')
        );
    }
}
