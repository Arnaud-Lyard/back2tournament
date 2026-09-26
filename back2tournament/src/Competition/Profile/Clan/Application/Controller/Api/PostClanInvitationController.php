<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\InviteClanMemberCommand;
use App\Shared\Infrastructure\Http\JsonBody;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clans/{id}/invitations', name: 'api_clan_invitation_post', methods: ['POST'])]
#[OA\Tag(name: 'Clan')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Clan ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Only the clan leader invites. The player must play the game of the clan; they become a member once they accept with `POST /api/clans/{id}/members`.',
    content: new OA\JsonContent(
        required: ['player'],
        properties: [
            new OA\Property(property: 'player', type: 'string', format: 'uuid', description: 'Player profile invited'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Invitation sent: the membership is `invited`.',
    content: new OA\JsonContent(ref: '#/components/schemas/ClanMember'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller does not lead the clan')]
#[OA\Response(response: 404, description: 'The clan or the player does not exist')]
#[OA\Response(response: 409, description: 'The player already is a member of the clan, or invited to it')]
final class PostClanInvitationController extends AbstractController
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

        return JsonResponse::fromJsonString($this->handle(new InviteClanMemberCommand(
            $id,
            JsonBody::string($parameters, 'player'),
        )));
    }
}
