<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Controller\Api;

use App\Competition\Tournament\Application\Model\StartTournamentCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tournaments/{id}/start', name: 'api_tournament_start_post', methods: ['POST'])]
#[OA\Tag(name: 'Tournament')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tournament ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'The organizer closes registrations and the bracket is drawn: seed 1 meets the last seed, and top seeds get a bye when the participants do not fill a power of two. The fights of the first round are opened; each later fight opens once both its sides are known. Each fight is then declared and confirmed like any other, and its winner moves on. No body is read.',
    content: new OA\JsonContent(ref: '#/components/schemas/TournamentDetail'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller does not organize the tournament')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[OA\Response(response: 409, description: 'The tournament already started or is over, or fewer than two participants are registered')]
final class PostTournamentStartController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new StartTournamentCommand($id)));
    }
}
