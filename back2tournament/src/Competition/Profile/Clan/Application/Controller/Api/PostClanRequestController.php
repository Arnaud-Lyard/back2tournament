<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\RequestClanMembershipCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/clans/{id}/requests', name: 'api_clan_request_post', methods: ['POST'])]
#[OA\Tag(name: 'Clan')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Clan ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Request sent: the player profile the authenticated user holds in the game of the clan asks to join it, and the membership is `requested` until the clan leader accepts it with `POST /api/user/clans/{id}/admissions`. `DELETE /api/user/clans/{id}/members/{playerid}` withdraws or declines it. A player profile asks one clan at a time; a user has a profile per game, so they ask one clan in each game. No body is read.',
    content: new OA\JsonContent(ref: '#/components/schemas/ClanMember'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 404, description: 'The clan does not exist, or the caller holds no profile in its game', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 409, description: 'The caller already is a member of this clan, invited to it or asking to join it, a member of another clan of the game, or asking to join another one: that request is withdrawn first', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class PostClanRequestController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new RequestClanMembershipCommand($id)));
    }
}
