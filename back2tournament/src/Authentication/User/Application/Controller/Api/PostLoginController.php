<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Controller\Api;

use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/login', name: 'api_login', methods: ['POST'])]
#[OA\Tag(name: 'Authentication')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['username', 'password'],
        properties: [
            new OA\Property(property: 'username', type: 'string', description: 'The account username.', example: 'john_doe'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Authenticated.',
    content: new OA\JsonContent(properties: [new OA\Property(property: 'token', type: 'string')]),
)]
#[OA\Response(response: 401, description: 'Invalid credentials.', content: new OA\JsonContent(ref: '#/components/schemas/AuthenticationError'))]
#[Security(name: null)]
final class PostLoginController
{
    public function __invoke(): never
    {
        throw new \LogicException('This route should be intercepted by the json_login authenticator.');
    }
}
