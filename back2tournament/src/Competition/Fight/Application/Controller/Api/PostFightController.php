<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Event\OnFightCreationRequestedEvent;
use OpenApi\Attributes as OA;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fights/', name: 'api_fight_post', methods: ['POST'])]
#[OA\Tag(name: 'Fight')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['playerOne', 'playerTwo'],
        properties: [
            new OA\Property(property: 'playerOne', type: 'string', format: 'uuid', description: 'ID of the first player'),
            new OA\Property(property: 'playerTwo', type: 'string', format: 'uuid', description: 'ID of the second player'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Fight created',
    content: new OA\JsonContent(
        description: 'Identifiers are serialized as a `{value: string}` object (Value Object). playerOne/playerTwo are resolved into competitors (competitorOne/competitorTwo).',
        properties: [
            new OA\Property(property: 'id', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'competitorOne', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'competitorTwo', type: 'object', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')]),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
        ],
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
final class PostFightController extends AbstractController
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

        $this->eventDispatcher->dispatch(new OnFightCreationRequestedEvent(
            $parameters['playerOne'],
            $parameters['playerTwo'],
        ));

        return JsonResponse::fromJsonString(
            $request->getSession()->get('last_fight_created')
        );
    }
}
