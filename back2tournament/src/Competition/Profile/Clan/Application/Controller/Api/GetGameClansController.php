<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\FindGameClansQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games/{id}/clans', name: 'api_clan_list', methods: ['GET'])]
#[OA\Tag(name: 'Clan')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Game ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Which page to read, 1 by default.', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1))]
#[OA\Parameter(name: 'limit', in: 'query', required: false, description: 'How many clans that page holds, 10 by default and 50 at most.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10))]
#[OA\Parameter(name: 'q', in: 'query', required: false, description: 'Keeps the clans whose name or tag contains this text, whatever the case. A blank value is no search.', schema: new OA\Schema(type: 'string'))]
#[OA\Response(
    response: 200,
    description: 'One page of the clans of this game, by name. `items` is empty when the game has no clan, when the search matches none, when the page is past the last one, and when no game has this id.',
    content: new OA\JsonContent(
        required: ['items', 'total', 'page', 'limit', 'pages'],
        properties: [
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/ClanSummary')),
            new OA\Property(property: 'total', type: 'integer', example: 12),
            new OA\Property(property: 'page', type: 'integer', example: 1),
            new OA\Property(property: 'limit', type: 'integer', example: 10),
            new OA\Property(property: 'pages', type: 'integer', example: 2),
        ],
        type: 'object',
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[Security(name: null)]
final class GetGameClansController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindGameClansQuery(
            $id,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10),
            $request->query->getString('q'),
        )));
    }
}
