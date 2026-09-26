<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Controller\Api;

use App\Competition\Profile\Team\Application\Event\OnTeamCreationRequestedEvent;
use OpenApi\Attributes as OA;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/teams/', name: 'api_team_post', methods: ['POST'])]
#[OA\Tag(name: 'Team')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'player', 'leader'],
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Falcons'),
            new OA\Property(property: 'player', type: 'string', format: 'uuid', description: 'ID of the player added to the team'),
            new OA\Property(property: 'leader', type: 'string', format: 'uuid', description: 'ID of the player designated as team captain'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Team created',
    content: new OA\JsonContent(
        description: 'Identifiers are serialized as a `{value: string}` object (Value Object)',
        properties: [
            new OA\Property(property: 'id', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Falcons'),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'leader', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
        ],
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
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

        $this->eventDispatcher->dispatch(new OnTeamCreationRequestedEvent(
            $parameters['name'],
            $parameters['player'],
            $parameters['leader'],
        ));

        return JsonResponse::fromJsonString(
            $request->getSession()->get('last_team_created')
        );
    }
}
