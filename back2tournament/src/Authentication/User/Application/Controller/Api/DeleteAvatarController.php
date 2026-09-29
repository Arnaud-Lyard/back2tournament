<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Controller\Api;

use App\Authentication\User\Application\Model\ChangeAvatarCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/me/avatar', name: 'api_user_avatar_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'User')]
#[OA\Response(response: 200, description: 'The signed-in user, as GET /api/user/me answers, without a picture; one who had none is answered as they are.', content: new OA\JsonContent(ref: '#/components/schemas/CurrentUser'))]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
final class DeleteAvatarController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(): JsonResponse
    {
        return JsonResponse::fromJsonString($this->handle(new ChangeAvatarCommand(null)));
    }
}
