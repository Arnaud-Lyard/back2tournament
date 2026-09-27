<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Controller\Api;

use App\Competition\Profile\Player\Application\Model\FindGamePlayersQuery;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players/{id}/games', name: 'api_player_list', methods: ['GET'])]
#[OA\Tag(name: 'Player')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Game ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(
    name: 'page',
    in: 'query',
    required: false,
    description: 'Which page to read, 1 by default.',
    schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
)]
#[OA\Parameter(
    name: 'limit',
    in: 'query',
    required: false,
    description: 'How many players that page holds, 10 by default and 50 at most.',
    schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10),
)]
#[OA\Parameter(
    name: 'q',
    in: 'query',
    required: false,
    description: 'Keeps the players whose battletag contains this text, whatever the case. A blank value is no search.',
    schema: new OA\Schema(type: 'string'),
)]
#[OA\Response(
    response: 200,
    description: 'One page of the players registered in this game, oldest profile first. `items` is empty when the game has no player, when the search matches none, when the page is past the last one, and when no game has this id.',
    content: new OA\JsonContent(
        required: ['items', 'total', 'page', 'limit', 'pages'],
        properties: [
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/GamePlayer')),
            new OA\Property(property: 'total', type: 'integer', description: 'Players the filters match, every page taken together', example: 42),
            new OA\Property(property: 'page', type: 'integer', description: 'The page these items come from', example: 1),
            new OA\Property(property: 'limit', type: 'integer', description: 'How many items a full page holds', example: 10),
            new OA\Property(property: 'pages', type: 'integer', description: 'How many pages the filters yield, 0 when nothing matches', example: 5),
        ],
        type: 'object',
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[Security(name: null)]
final class GetGamePlayersController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindGamePlayersQuery(
            new GameId($id)->getValue(),
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10),
            $request->query->getString('q'),
        )));
    }
}
