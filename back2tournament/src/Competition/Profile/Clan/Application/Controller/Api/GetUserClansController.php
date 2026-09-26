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

#[Route('/api/users/me/clans', name: 'api_clan_mine', methods: ['GET'])]
#[OA\Tag(name: 'Clan')]
#[OA\Response(
    response: 200,
    description: 'Every place the authenticated user holds in a clan, whatever the game: memberships and invitations still to answer, oldest first. An empty array when there is none.',
    content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(
            required: ['clan', 'membership'],
            properties: [
                new OA\Property(property: 'clan', ref: '#/components/schemas/Clan'),
                new OA\Property(property: 'membership', ref: '#/components/schemas/ClanMember'),
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
