<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiTokenEntryPoint implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->unauthorized('API token not found', 'Bearer');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->unauthorized(
            strtr($exception->getMessageKey(), $exception->getMessageData()),
            'Bearer error="invalid_token"',
        );
    }

    private function unauthorized(string $message, string $challenge): JsonResponse
    {
        return new JsonResponse(
            ['code' => Response::HTTP_UNAUTHORIZED, 'message' => $message],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => $challenge],
        );
    }
}
