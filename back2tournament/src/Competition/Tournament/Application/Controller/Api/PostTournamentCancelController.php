<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Controller\Api;

use App\Competition\Tournament\Application\Model\CancelTournamentCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tournaments/{id}/cancel', name: 'api_tournament_cancel_post', methods: ['POST'])]
#[OA\Tag(name: 'Tournament')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tournament ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'The organizer cancels the tournament, before or while it is played. Its bracket stops moving; fights already opened stay as they are. No body is read.',
    content: new OA\JsonContent(ref: '#/components/schemas/TournamentDetail'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller does not organize the tournament')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[OA\Response(response: 409, description: 'The tournament is already finished or cancelled')]
final class PostTournamentCancelController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new CancelTournamentCommand($id)));
    }
}
