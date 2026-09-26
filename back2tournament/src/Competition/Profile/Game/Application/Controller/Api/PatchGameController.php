<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Controller\Api;

use App\Competition\Profile\Game\Application\Model\UpdateGameCommand;
use App\Shared\Infrastructure\Http\JsonBody;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games/{id}', name: 'api_game_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Game')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Game ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Requires a user with the administrator role. Every key is optional: a key left out keeps its current value. Dropping a format stops new teams, fights and tournaments from using it; what already exists in it is kept.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Rocket League'),
            new OA\Property(
                property: 'teamSizes',
                type: 'array',
                items: new OA\Items(type: 'integer', minimum: 1, maximum: 64),
                description: 'The formats the game is played in, as the number of players per side. At least one.',
                example: [1, 2, 3],
            ),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Game updated. It is returned as it now stands.',
    content: new OA\JsonContent(ref: '#/components/schemas/Game'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
final class PatchGameController extends AbstractController
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

        return JsonResponse::fromJsonString($this->handle(new UpdateGameCommand(
            $id,
            JsonBody::optionalString($parameters, 'title'),
            JsonBody::optionalIntList($parameters, 'teamSizes'),
        )));
    }
}
