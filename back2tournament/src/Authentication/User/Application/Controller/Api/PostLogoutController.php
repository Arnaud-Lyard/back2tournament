<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Controller\Api;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
#[OA\Tag(name: 'Authentication')]
#[OA\Response(
    response: 200,
    description: 'Logged out.',
    content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'logged out')]),
)]
#[OA\Response(response: 401, description: 'No JWT sent.')]
final class PostLogoutController extends AbstractController
{
    private EventDispatcherInterface $eventDispatcher;
    private TokenStorageInterface $tokenStorage;

    public function __construct(EventDispatcherInterface $eventDispatcher, TokenStorageInterface $tokenStorage)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->eventDispatcher->dispatch(new LogoutEvent($request, $this->tokenStorage->getToken()));

        return new JsonResponse(['message' => 'logged out']);
    }
}
