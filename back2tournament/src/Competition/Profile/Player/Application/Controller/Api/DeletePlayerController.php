<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Controller\Api;

use App\Competition\Profile\Player\Application\Model\DeletePlayerCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players/{id}', name: 'api_player_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Player')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Player ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Profile deleted. It is returned one last time, as it stood.',
    content: new OA\JsonContent(ref: '#/components/schemas/Player'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The profile belongs to another account')]
#[OA\Response(response: 404, description: 'No player profile has this id')]
#[OA\Response(response: 409, description: 'The profile takes part in fights: deleting it would leave that history hanging')]
final class DeletePlayerController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        $deletePlayerCommand = new DeletePlayerCommand();
        $deletePlayerCommand->setPlayerId($id);

        return JsonResponse::fromJsonString($this->handle($deletePlayerCommand));
    }
}
