<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Controller\Api;

use App\Competition\Profile\Team\Application\Model\DisbandTeamCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/teams/{id}', name: 'api_team_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Team')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Team ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Team disbanded. It is returned one last time, as it stood.',
    content: new OA\JsonContent(ref: '#/components/schemas/Team'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller does not lead the clan of the team')]
#[OA\Response(response: 404, description: 'No team has this id')]
#[OA\Response(response: 409, description: 'The team has competed, in a fight or a tournament: it is kept for the record')]
final class DeleteTeamController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new DisbandTeamCommand($id)));
    }
}
