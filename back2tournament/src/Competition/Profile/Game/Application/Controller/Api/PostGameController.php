<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Controller\Api;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Competition\Profile\Game\Application\Event\OnGameCreationRequestedEvent;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games/', name: 'api_game_post', methods: ['POST'])]
#[OA\Tag(name: 'Game')]
#[OA\RequestBody(
    required: true,
    description: 'Requires a user with the administrator role (checked at the application layer)',
    content: new OA\JsonContent(
        required: ['title'],
        properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Street Fighter 6'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Game created',
    content: new OA\JsonContent(
        description: 'Identifiers are serialized as a `{value: string}` object (Value Object)',
        properties: [
            new OA\Property(property: 'id', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'title', type: 'string', nullable: true),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
        ],
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
final class PostGameController extends AbstractController
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

        $this->eventDispatcher->dispatch(new OnGameCreationRequestedEvent(
            $parameters['title'],
        ));

        return JsonResponse::fromJsonString(
            $request->getSession()->get('last_game_created')
        );
    }
}
