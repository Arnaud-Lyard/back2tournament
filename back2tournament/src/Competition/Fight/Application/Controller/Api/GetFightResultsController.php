<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Model\FindFightResultsQuery;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fights/{id}/results/{gameid}', name: 'api_fight_results', methods: ['GET'])]
#[OA\Tag(name: 'Fight')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Fight ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(
    name: 'gameid',
    in: 'path',
    required: true,
    description: 'Game the fight is played in. Together with the authenticated user it names which of the two sides is asking.',
    schema: new OA\Schema(type: 'string', format: 'uuid'),
)]
#[OA\Response(
    response: 200,
    description: 'The caller\'s own result in this fight. While it is `reporting`, `score` and `reportedStatus` hold what the declaring side claims — prefill the confirmation form with them.',
    content: new OA\JsonContent(ref: '#/components/schemas/Result'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, description: 'The game, the fight, or a result for the caller in it, does not exist')]
final class GetFightResultsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id, string $gameid): JsonResponse
    {
        $result = $this->handle(new FindFightResultsQuery(new FightId($id)->getValue(), new GameId($gameid)->getValue()));

        return JsonResponse::fromJsonString($result);
    }
}
