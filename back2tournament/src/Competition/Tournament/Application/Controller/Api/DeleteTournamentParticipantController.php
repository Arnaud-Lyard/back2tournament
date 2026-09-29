<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Controller\Api;

use App\Competition\Tournament\Application\Model\WithdrawParticipantCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/tournaments/{id}/participants/{participantid}', name: 'api_tournament_participant_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Tournament')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tournament ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'participantid', in: 'path', required: true, description: 'Participant ID, as the tournament lists it', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Registration withdrawn, by the participant or the organizer, before the tournament starts. Those registered after it move up one seed. It is returned one last time, as it stood.',
    content: new OA\JsonContent(ref: '#/components/schemas/TournamentParticipant'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller is neither the participant nor the organizer')]
#[OA\Response(response: 404, description: 'The tournament does not exist, or this participant is not registered in it')]
#[OA\Response(response: 409, description: 'The tournament has already started')]
final class DeleteTournamentParticipantController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id, string $participantid): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new WithdrawParticipantCommand($id, $participantid)));
    }
}
