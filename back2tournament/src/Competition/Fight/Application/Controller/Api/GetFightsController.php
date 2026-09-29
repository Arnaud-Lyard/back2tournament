<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Model\FindFightsQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/fights/', name: 'api_fight_list', methods: ['GET'])]
#[OA\Tag(name: 'Fight')]
#[OA\Parameter(name: 'status', in: 'query', required: false, description: '`reporting` keeps the fights whose declaration waits for its confirmation, where disputes are; `pending` those nobody declared yet; `finished` the settled ones; `all` by default.', schema: new OA\Schema(type: 'string', enum: ['all', 'pending', 'reporting', 'finished'], default: 'all'))]
#[OA\Parameter(name: 'game', in: 'query', required: false, description: 'Keeps the fights of this game.', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'q', in: 'query', required: false, description: 'A battletag or a team name, or part of one: keeps the fights of the player profiles and teams that bear it. A fight id finds that fight.', schema: new OA\Schema(type: 'string'))]
#[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Which page to read, 1 by default.', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1))]
#[OA\Parameter(name: 'limit', in: 'query', required: false, description: 'How many fights that page holds, 20 by default and 50 at most.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 20))]
#[OA\Response(
    response: 200,
    description: 'Requires an administrator. One page of every fight, the latest to have moved first, both sides named; `mySide` is always null.',
    content: new OA\JsonContent(ref: '#/components/schemas/FightPage'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
final class GetFightsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindFightsQuery(
            $request->query->getString('status'),
            $request->query->getString('game'),
            $request->query->getString('q'),
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 20),
        )));
    }
}
