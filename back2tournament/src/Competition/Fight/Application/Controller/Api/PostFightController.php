<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Model\CreateFightCommand;
use App\Shared\Exception\ValidationException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fights/', name: 'api_fight_post', methods: ['POST'])]
#[OA\Tag(name: 'Fight')]
#[OA\RequestBody(
    required: true,
    description: 'Name either two player profiles, for a 1v1, or two teams of the same format, for an NvN. The caller stands on one side: they own one of the two profiles, or lead one of the two teams. The game must be played in that format.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'playerOne', type: 'string', format: 'uuid', description: 'First player profile of a 1v1'),
            new OA\Property(property: 'playerTwo', type: 'string', format: 'uuid', description: 'Second player profile of a 1v1'),
            new OA\Property(property: 'teamOne', type: 'string', format: 'uuid', description: 'First team of an NvN'),
            new OA\Property(property: 'teamTwo', type: 'string', format: 'uuid', description: 'Second team of an NvN'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Fight opened. Both sides are `pending` until one of them declares the scores.',
    content: new OA\JsonContent(ref: '#/components/schemas/FightSummary'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller stands on neither side')]
#[OA\Response(response: 404, description: 'A player profile or a team does not exist')]
final class PostFightController extends AbstractController
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

        $betweenTeams = isset($parameters['teamOne']) || isset($parameters['teamTwo']);
        $betweenPlayers = isset($parameters['playerOne']) || isset($parameters['playerTwo']);

        if ($betweenTeams === $betweenPlayers) {
            throw new ValidationException('name either playerOne and playerTwo, or teamOne and teamTwo');
        }

        return JsonResponse::fromJsonString($this->handle(new CreateFightCommand(
            $betweenTeams,
            $parameters[$betweenTeams ? 'teamOne' : 'playerOne'],
            $parameters[$betweenTeams ? 'teamTwo' : 'playerTwo'],
        )));
    }
}
