<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Controller\Api;

use App\Competition\Tournament\Application\Model\CreateTournamentCommand;
use App\Shared\Infrastructure\Http\JsonBody;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tournaments/', name: 'api_tournament_post', methods: ['POST'])]
#[OA\Tag(name: 'Tournament')]
#[OA\RequestBody(
    required: true,
    description: 'Organizes a single-elimination tournament. The authenticated user is its organizer: they start it once registrations are in, or cancel it. The game must be played in the format.',
    content: new OA\JsonContent(
        required: ['name', 'game', 'teamSize', 'capacity', 'startsAt'],
        properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Autumn Cup'),
            new OA\Property(property: 'game', type: 'string', format: 'uuid', description: 'Game ID'),
            new OA\Property(property: 'teamSize', type: 'integer', minimum: 1, maximum: 64, example: 1, description: 'Players per side: 1 registers player profiles, more registers teams of that size'),
            new OA\Property(property: 'capacity', type: 'integer', minimum: 2, maximum: 128, example: 16, description: 'How many participants may register'),
            new OA\Property(property: 'startsAt', type: 'string', format: 'date-time', example: '2026-10-01T18:00:00+02:00', description: 'When the tournament is planned to start. Not in the past.'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Tournament created, `upcoming`: registrations are open.',
    content: new OA\JsonContent(ref: '#/components/schemas/TournamentDetail'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 404, description: 'The game does not exist')]
final class PostTournamentController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        return JsonResponse::fromJsonString($this->handle(new CreateTournamentCommand(
            JsonBody::string($parameters, 'name'),
            JsonBody::string($parameters, 'game'),
            JsonBody::int($parameters, 'teamSize'),
            JsonBody::int($parameters, 'capacity'),
            JsonBody::string($parameters, 'startsAt'),
        )));
    }
}
