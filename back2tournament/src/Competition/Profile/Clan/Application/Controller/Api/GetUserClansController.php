<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\FindUserClansQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/me/clans', name: 'api_clan_mine', methods: ['GET'])]
#[OA\Tag(name: 'Clan')]
#[OA\Response(
    response: 200,
    description: 'Every place the authenticated user holds in a clan, whatever the game: memberships, invitations still to answer and requests to join the leader has not answered yet, oldest first. An empty array when there is none.',
    content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(
            required: ['clan', 'membership', 'requests'],
            properties: [
                new OA\Property(property: 'clan', ref: '#/components/schemas/Clan'),
                new OA\Property(property: 'membership', ref: '#/components/schemas/ClanMember'),
                new OA\Property(property: 'requests', type: 'integer', minimum: 0, example: 2, description: 'How many players ask to join the clan, for a clan the user leads: they wait for the leader to accept them (`POST /api/user/clans/{id}/admissions`) or decline them. 0 for any other clan.'),
            ],
            type: 'object',
        ),
    ),
)]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
final class GetUserClansController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindUserClansQuery()));
    }
}
