<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\RemoveClanMemberCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/clans/{id}/members/{playerid}', name: 'api_clan_member_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Clan')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Clan ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'playerid', in: 'path', required: true, description: 'Player profile whose membership, invitation or request to join ends', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Membership ended: the member left, the invitation or the request to join was declined or withdrawn, or the leader let the member go. It is returned one last time, as it stood.',
    content: new OA\JsonContent(ref: '#/components/schemas/ClanMember'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller is neither this player nor the clan leader')]
#[OA\Response(response: 404, description: 'The clan does not exist, or the player has no place in it')]
#[OA\Response(response: 409, description: 'The leader cannot leave, and a member playing in a team of the clan stays until that team is disbanded')]
final class DeleteClanMemberController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id, string $playerid): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new RemoveClanMemberCommand($id, $playerid)));
    }
}
