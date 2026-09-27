<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Controller\Api;

use App\Competition\Profile\Team\Application\Model\FindTeamQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/teams/{id}', name: 'api_team', methods: ['GET'])]
#[OA\Tag(name: 'Team')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Team ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'One team and its lineup, leader first. Public.',
    content: new OA\JsonContent(ref: '#/components/schemas/Team'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[Security(name: null)]
final class GetTeamController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindTeamQuery($id)));
    }
}
