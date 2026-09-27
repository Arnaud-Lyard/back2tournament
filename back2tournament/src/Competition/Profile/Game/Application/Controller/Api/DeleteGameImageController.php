<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Controller\Api;

use App\Competition\Profile\Game\Application\Model\ChangeGameImageCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games/{id}/image', name: 'api_game_image_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Game')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Game ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 200, description: 'Requires an administrator. The game, without a picture; one that had none is answered as it is.', content: new OA\JsonContent(ref: '#/components/schemas/Game'))]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
final class DeleteGameImageController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new ChangeGameImageCommand($id, null)));
    }
}
