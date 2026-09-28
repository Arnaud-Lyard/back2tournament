<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\JoinClanCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/clans/{id}/members', name: 'api_clan_member_post', methods: ['POST'])]
#[OA\Tag(name: 'Clan')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Clan ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Invitation accepted: the player profile the authenticated user holds in the game of the clan is now an `active` member. No body is read.',
    content: new OA\JsonContent(ref: '#/components/schemas/ClanMember'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 404, description: 'The clan does not exist, the caller holds no profile in its game, or was not invited')]
#[OA\Response(response: 409, description: 'The caller already is a member of this clan, or of another one in the game')]
final class PostClanMemberController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $id): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new JoinClanCommand($id)));
    }
}
