<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Controller\Api;

use App\Competition\Tournament\Application\Model\RegisterParticipantCommand;
use App\Shared\Exception\ValidationException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/tournaments/{id}/participants', name: 'api_tournament_participant_post', methods: ['POST'])]
#[OA\Tag(name: 'Tournament')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tournament ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Name one of the two: a player profile the authenticated user owns, for a 1v1 tournament, or a team they lead, of the tournament\'s format. Registrations close when the tournament is full or starts. A player plays for one team per tournament.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'player', type: 'string', format: 'uuid', description: 'Player profile, for a 1v1 tournament'),
            new OA\Property(property: 'team', type: 'string', format: 'uuid', description: 'Team, for an NvN tournament'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Registered. The seed is the registration rank.',
    content: new OA\JsonContent(ref: '#/components/schemas/TournamentParticipant'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller does not own the profile, or does not lead the team', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 404, description: 'The tournament, the profile or the team does not exist', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 409, description: 'Registrations are closed, the tournament is full, or the participant is already registered', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class PostTournamentParticipantController extends AbstractController
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

        $player = $parameters['player'] ?? null;
        $team = $parameters['team'] ?? null;
        if ((null === $player) === (null === $team)) {
            throw new ValidationException('name either a player or a team');
        }

        return JsonResponse::fromJsonString($this->handle(new RegisterParticipantCommand($id, $player, $team)));
    }
}
