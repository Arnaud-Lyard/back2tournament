<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Controller\Api;

use App\Competition\Ranking\Application\Model\FindRatingQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rankings/players/{id}', name: 'api_rankings_player', methods: ['GET'])]
#[OA\Tag(name: 'Ranking')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Player profile ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'The Elo ratings of a player profile, one per format of its game, and its rank in each. In a format where the profile has not settled a fight yet, it stands at the initial 1000, with `rank` null.',
    content: new OA\JsonContent(ref: '#/components/schemas/SubjectRating'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 404, description: 'No player profile has this id', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[Security(name: null)]
final class GetPlayerRatingController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(FindRatingQuery::ofPlayer($id)));
    }
}
