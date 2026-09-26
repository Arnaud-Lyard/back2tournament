<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Controller\Api;

use App\Authentication\User\Application\Model\RegisterUserCommand;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/register', name: 'api_register', methods: ['POST'])]
#[OA\Tag(name: 'Authentication')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['email', 'username', 'password', 'passwordConfirmation', 'locale'],
        properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
            new OA\Property(property: 'username', type: 'string', example: 'john_doe'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
            new OA\Property(property: 'passwordConfirmation', type: 'string', format: 'password', example: 'Password123!'),
            new OA\Property(property: 'locale', type: 'string', enum: ['fr', 'en'], example: 'fr', description: 'Language of the verification email.'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'User registered.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'username', type: 'string'),
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'verified', type: 'boolean', example: false),
        ],
    ),
)]
#[Security(name: null)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 409, ref: '#/components/responses/Conflict')]
final class PostRegisterController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $registerUserCommand = new RegisterUserCommand(
            $parameters['email'],
            $parameters['username'],
            $parameters['password'],
            $parameters['passwordConfirmation'],
            $parameters['locale']
        );

        return JsonResponse::fromJsonString($this->handle($registerUserCommand));
    }
}
