<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Model\FindPendingUserFightResultsQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/results/users/fights', name: 'api_results_pending_user_fights', methods: ['GET'])]
#[OA\Tag(name: 'Fight')]
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
    description: 'How many results that page holds, 10 by default and 50 at most.',
    schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10),
)]
#[OA\Response(
    response: 200,
    description: 'One page of the unsettled results of every competitor the authenticated user speaks for — their player profiles, and the teams one of them leads — whatever the game, oldest first: `pending` while nobody declared, `reporting` while one side waits for the other to confirm. Each item carries the caller\'s own side, the fight it belongs to, and the other side. With `declaredBy` it tells what the caller may do: declare (`pending`), correct (`reporting`, declared by their side) or confirm (`reporting`, declared by the other side). `items` is empty when nothing is waiting, when the page is past the last one, and when the caller speaks for no competitor at all.',
    content: new OA\JsonContent(
        required: ['items', 'total', 'page', 'limit', 'pages'],
        properties: [
            new OA\Property(
                property: 'items',
                type: 'array',
                items: new OA\Items(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/Result'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'game',
                                description: 'The game the fight is played in.',
                                properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')],
                                type: 'object',
                                nullable: true,
                            ),
                            new OA\Property(property: 'teamSize', type: 'integer', minimum: 1, example: 1, description: 'Players per side: 1 for a 1v1'),
                            new OA\Property(
                                property: 'tournament',
                                description: 'The tournament whose bracket holds the fight. Null for a challenge.',
                                properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')],
                                type: 'object',
                                nullable: true,
                            ),
                            new OA\Property(
                                property: 'declaredBy',
                                description: 'The side that declared the scores. Null while nothing has been declared.',
                                properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')],
                                type: 'object',
                                nullable: true,
                            ),
                            new OA\Property(
                                property: 'side',
                                description: 'The caller\'s own side: the competitor this result belongs to.',
                                properties: [
                                    new OA\Property(property: 'competitor', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')], type: 'object'),
                                    new OA\Property(property: 'type', type: 'string', enum: ['player', 'team'], nullable: true),
                                    new OA\Property(property: 'name', type: 'string', nullable: true, description: 'Battletag or team name'),
                                    new OA\Property(property: 'tag', type: 'string', nullable: true, example: 'B2T', description: 'The tag of the clan the side plays for; null for a profile in no clan'),
                                ],
                                type: 'object',
                            ),
                            new OA\Property(
                                property: 'player',
                                description: 'The caller\'s own player profile, for a 1v1. Null for a team.',
                                ref: '#/components/schemas/PlayerProfile',
                                nullable: true,
                            ),
                            new OA\Property(
                                property: 'opponent',
                                description: 'The other side of the fight. `player` is null for a team, and for a profile that no longer exists.',
                                properties: [
                                    new OA\Property(property: 'competitor', properties: [new OA\Property(property: 'value', type: 'string', format: 'uuid')], type: 'object'),
                                    new OA\Property(property: 'type', type: 'string', enum: ['player', 'team'], nullable: true),
                                    new OA\Property(property: 'name', type: 'string', nullable: true, description: 'Battletag or team name'),
                                    new OA\Property(property: 'tag', type: 'string', nullable: true, example: 'B2T', description: 'The tag of the clan the side plays for; null for a profile in no clan'),
                                    new OA\Property(property: 'player', ref: '#/components/schemas/PlayerProfile', nullable: true),
                                    new OA\Property(property: 'score', type: 'integer', minimum: 0, example: 1, description: 'Points the other side scored, as declared. Zero until a declaration.'),
                                ],
                                type: 'object',
                                nullable: true,
                            ),
                        ]),
                    ],
                ),
            ),
            new OA\Property(property: 'total', type: 'integer', description: 'Unsettled results the caller has, every page taken together', example: 42),
            new OA\Property(property: 'page', type: 'integer', description: 'The page these items come from', example: 1),
            new OA\Property(property: 'limit', type: 'integer', description: 'How many items a full page holds', example: 10),
            new OA\Property(property: 'pages', type: 'integer', description: 'How many pages the caller has, 0 when nothing is waiting', example: 5),
        ],
        type: 'object',
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
final class GetPendingUserFightResultsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindPendingUserFightResultsQuery(
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10),
        )));
    }
}
