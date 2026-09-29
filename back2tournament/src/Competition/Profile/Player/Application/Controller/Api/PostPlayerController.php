<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Controller\Api;

use App\Competition\Profile\Player\Application\Event\OnPlayerCreationRequestedEvent;
use OpenApi\Attributes as OA;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/players/', name: 'api_player_post', methods: ['POST'])]
#[OA\Tag(name: 'Player')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['battletag', 'game'],
        properties: [
            new OA\Property(property: 'battletag', type: 'string', example: 'PlayerOne#1234'),
            new OA\Property(property: 'game', type: 'string', format: 'uuid', description: 'Game ID'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Player created',
    content: new OA\JsonContent(
        description: 'Identifiers are serialized as a `{value: string}` object (Value Object)',
        properties: [
            new OA\Property(property: 'id', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'battletag', type: 'string', nullable: true),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'game', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'user', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
        ],
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[OA\Response(response: 409, ref: '#/components/responses/Conflict')]
final class PostPlayerController extends AbstractController
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

        $this->eventDispatcher->dispatch(new OnPlayerCreationRequestedEvent(
            $parameters['battletag'],
            $parameters['game'],
        ));

        return JsonResponse::fromJsonString(
            $request->getSession()->get('last_player_created')
        );
    }
}
