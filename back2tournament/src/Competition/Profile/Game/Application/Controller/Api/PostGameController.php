<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Controller\Api;

use App\Competition\Profile\Game\Application\Model\CreateGameCommand;
use App\Shared\Infrastructure\Http\JsonBody;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
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
            new OA\Property(
                property: 'teamSizes',
                type: 'array',
                items: new OA\Items(type: 'integer', minimum: 1, maximum: 64),
                description: 'The formats the game is played in, as the number of players per side: `[1]` for 1v1 only, `[1, 2, 3]` for 1v1, 2v2 and 3v3. `[1]` when left out.',
                example: [1, 2, 3],
            ),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Game created',
    content: new OA\JsonContent(ref: '#/components/schemas/Game'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
final class PostGameController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        return JsonResponse::fromJsonString($this->handle(new CreateGameCommand(
            JsonBody::string($parameters, 'title'),
            JsonBody::optionalIntList($parameters, 'teamSizes') ?? [1],
        )));
    }
}
