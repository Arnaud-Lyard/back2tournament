<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Model\FindResultHistoryQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/results/clans/{id}', name: 'api_results_clan', methods: ['GET'])]
#[OA\Tag(name: 'Fight')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Clan ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Which page to read, 1 by default.', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1))]
#[OA\Parameter(name: 'limit', in: 'query', required: false, description: 'How many results that page holds, 10 by default and 50 at most.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10))]
#[OA\Response(
    response: 200,
    description: 'One page of the settled fights a clan played against other clans, newest first: the fights of its teams, and in 1v1 the duels of its members, as each fight recorded the clan of its sides when it opened. `side` is the clan\'s team, or the member who fought the duel. A fight between two sides of the clan, or against a player in no clan, is left out. `items` is empty when the clan finished no such fight, when the page is past the last one, and when no clan has this id.',
    content: new OA\JsonContent(ref: '#/components/schemas/SettledResultPage'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
final class GetClanResultsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(FindResultHistoryQuery::ofClan(
            $id,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10),
        )));
    }
}
