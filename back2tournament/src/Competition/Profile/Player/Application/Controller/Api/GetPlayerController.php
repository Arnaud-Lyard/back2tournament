<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Controller\Api;

use App\Competition\Profile\Player\Application\Model\FindPlayerQuery;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players/{id}', name: 'api_player', methods: ['GET'])]
#[OA\Tag(name: 'Player')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Player ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'One player profile: who they are in the game they registered in. Public, so that a visitor can read a profile before deciding to challenge it.',
    content: new OA\JsonContent(ref: '#/components/schemas/Player'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[Security(name: null)]
final class GetPlayerController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString(
            $this->handle(new FindPlayerQuery(new PlayerId($id)->getValue()))
        );
    }
}
