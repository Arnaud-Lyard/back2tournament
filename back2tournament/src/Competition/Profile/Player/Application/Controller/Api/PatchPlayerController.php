<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Controller\Api;

use App\Competition\Profile\Player\Application\Model\UpdatePlayerCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players/{id}', name: 'api_player_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Player')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Player ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'The battletag this profile is known by. The game it belongs to never changes: a profile is one account in one game, and the fights already opened for it point at that pair.',
    content: new OA\JsonContent(
        required: ['battletag'],
        properties: [
            new OA\Property(property: 'battletag', type: 'string', maxLength: 255, example: 'PlayerOne#1234'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Battletag updated. The profile is returned as it now stands.',
    content: new OA\JsonContent(ref: '#/components/schemas/Player'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The profile belongs to another account')]
#[OA\Response(response: 404, description: 'No player profile has this id')]
final class PatchPlayerController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        $updatePlayerCommand = new UpdatePlayerCommand();
        $updatePlayerCommand->setPlayerId($id);
        $updatePlayerCommand->setBattletag($parameters['battletag'] ?? '');

        return JsonResponse::fromJsonString($this->handle($updatePlayerCommand));
    }
}
