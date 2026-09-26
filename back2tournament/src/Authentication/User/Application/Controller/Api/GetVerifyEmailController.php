<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Controller\Api;

use App\Authentication\User\Application\Model\VerifyEmailCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/verify-email/{token}', name: 'api_verify_email', methods: ['GET'])]
#[OA\Tag(name: 'Authentication')]
#[OA\Response(
    response: 200,
    description: 'Email verified.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'username', type: 'string'),
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'verified', type: 'boolean', example: true),
        ],
    ),
)]
#[OA\Response(
    response: 400,
    description: 'Token is invalid.',
    content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string')]),
)]
final class GetVerifyEmailController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(string $token): JsonResponse
    {
        $verifiedToken = $this->handle(new VerifyEmailCommand($token));

        return JsonResponse::fromJsonString($verifiedToken);
    }
}
