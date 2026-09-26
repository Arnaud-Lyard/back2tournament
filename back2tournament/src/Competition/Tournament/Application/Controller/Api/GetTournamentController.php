<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Controller\Api;

use App\Competition\Tournament\Application\Model\FindTournamentQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tournaments/{id}', name: 'api_tournament', methods: ['GET'])]
#[OA\Tag(name: 'Tournament')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tournament ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'One tournament, its participants by seed and, once it started, its bracket round by round. Public.',
    content: new OA\JsonContent(ref: '#/components/schemas/TournamentDetail'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[Security(name: null)]
final class GetTournamentController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindTournamentQuery($id)));
    }
}
