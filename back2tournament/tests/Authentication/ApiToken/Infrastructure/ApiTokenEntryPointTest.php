<?php

declare(strict_types=1);

namespace App\Tests\Authentication\ApiToken\Infrastructure;

use App\Authentication\ApiToken\Infrastructure\Security\ApiTokenEntryPoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class ApiTokenEntryPointTest extends TestCase
{
    public function test_a_request_without_a_token_is_asked_for_one(): void
    {
        $response = new ApiTokenEntryPoint()->start(new Request());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('Bearer', $response->headers->get('WWW-Authenticate'));
        $this->assertSame(['code' => 401, 'message' => 'API token not found'], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_a_refused_token_is_answered_with_the_reason(): void
    {
        $response = new ApiTokenEntryPoint()->onAuthenticationFailure(new Request(), new CustomUserMessageAuthenticationException('Expired API token'));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('Bearer error="invalid_token"', $response->headers->get('WWW-Authenticate'));
        $this->assertSame(['code' => 401, 'message' => 'Expired API token'], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
