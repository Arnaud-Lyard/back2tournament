<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Controller\Api;

use App\Competition\Tournament\Application\Model\FindTournamentsQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tournaments/', name: 'api_tournament_list', methods: ['GET'])]
#[OA\Tag(name: 'Tournament')]
#[OA\Parameter(name: 'game', in: 'query', required: false, description: 'Keeps the tournaments of this game', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'status', in: 'query', required: false, description: 'Keeps the tournaments in this state', schema: new OA\Schema(type: 'string', enum: ['upcoming', 'ongoing', 'finished', 'cancelled']))]
#[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Which page to read, 1 by default.', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1))]
#[OA\Parameter(name: 'limit', in: 'query', required: false, description: 'How many tournaments that page holds, 10 by default and 50 at most.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10))]
#[OA\Response(
    response: 200,
    description: 'One page of tournaments, soonest first. Public.',
    content: new OA\JsonContent(
        required: ['items', 'total', 'page', 'limit', 'pages'],
        properties: [
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/TournamentSummary')),
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
final class GetTournamentsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindTournamentsQuery(
            $request->query->getString('game'),
            $request->query->getString('status'),
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10),
        )));
    }
}
