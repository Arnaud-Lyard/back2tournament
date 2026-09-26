<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Controller\Api;

use App\Authentication\User\Application\Model\FindCurrentUserQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users/me', name: 'api_user_me', methods: ['GET'])]
#[OA\Tag(name: 'User')]
#[OA\Response(
    response: 200,
    description: 'The authenticated user, and the player profiles they hold: one per game at most, oldest first. `players` is an empty array when they hold none.',
    content: new OA\JsonContent(ref: '#/components/schemas/CurrentUser'),
)]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
final class GetCurrentUserController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new FindCurrentUserQuery()));
    }
}
