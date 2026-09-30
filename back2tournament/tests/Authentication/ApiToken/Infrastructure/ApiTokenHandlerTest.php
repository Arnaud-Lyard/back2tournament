<?php

declare(strict_types=1);

namespace App\Tests\Authentication\ApiToken\Infrastructure;

use App\Authentication\ApiToken\Infrastructure\Security\ApiClient;
use App\Authentication\ApiToken\Infrastructure\Security\ApiTokenHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class ApiTokenHandlerTest extends TestCase
{
    private const TOKEN = '6f1c0e9b2a8d4f7e3b5c9a1d0e8f2b4c6a7d9e1f3b5c7a9d0e2f4b6c8a1d3e5f';

    public function test_the_token_of_the_environment_signs_the_tool_in_as_a_bot(): void
    {
        $badge = new ApiTokenHandler(self::TOKEN)->getUserBadgeFrom(self::TOKEN);
        $client = $badge->getUser();

        $this->assertSame('bot', $badge->getUserIdentifier());
        $this->assertInstanceOf(ApiClient::class, $client);
        $this->assertSame(['ROLE_BOT'], $client->getRoles());
    }

    public function test_another_token_is_refused(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API token');

        new ApiTokenHandler(self::TOKEN)->getUserBadgeFrom(self::TOKEN.'0');
    }

    public function test_every_token_is_refused_while_the_environment_sets_none(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API token');

        new ApiTokenHandler('')->getUserBadgeFrom(self::TOKEN);
    }
}
