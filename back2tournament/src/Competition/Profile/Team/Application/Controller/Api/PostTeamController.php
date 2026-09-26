<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Controller\Api;

use App\Competition\Profile\Team\Application\Event\OnTeamCreationRequestedEvent;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/api/teams/', name: 'api_team_post', methods: ['POST'])]
#[OA\Tag(name: 'Team')]
#[OA\RequestBody(
    required: true,
    description: 'A lineup the clan fields in one format. Only the clan leader composes teams, from the active members of the clan; the game of the clan must be played in that format.',
    content: new OA\JsonContent(
        required: ['clan', 'name', 'size', 'players', 'leader'],
        properties: [
            new OA\Property(property: 'clan', type: 'string', format: 'uuid', description: 'Clan the team plays for'),
            new OA\Property(property: 'name', type: 'string', maxLength: 50, example: 'Falcons Duo'),
            new OA\Property(property: 'size', type: 'integer', minimum: 1, maximum: 64, example: 2, description: 'Players per side: 2 for a 2v2 team'),
            new OA\Property(
                property: 'players',
                type: 'array',
                items: new OA\Items(type: 'string', format: 'uuid'),
                description: 'The whole lineup, exactly `size` player profiles, the leader included',
            ),
            new OA\Property(property: 'leader', type: 'string', format: 'uuid', description: 'The player of the lineup who opens fights, registers the team and declares or confirms its results'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Team created',
    content: new OA\JsonContent(ref: '#/components/schemas/Team'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller does not lead the clan')]
#[OA\Response(response: 404, description: 'The clan does not exist')]
final class PostTeamController extends AbstractController
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        $event = $this->eventDispatcher->dispatch(new OnTeamCreationRequestedEvent(
            $parameters['name'],
            $parameters['clan'],
            $parameters['size'],
            $parameters['players'],
            $parameters['leader'],
        ));

        return JsonResponse::fromJsonString($event->getCreatedTeam());
    }
}
