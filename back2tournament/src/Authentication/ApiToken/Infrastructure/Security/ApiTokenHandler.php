<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Infrastructure\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiTokenHandler implements AccessTokenHandlerInterface
{
    private string $botApiToken;

    public function __construct(#[Autowire(env: 'BOT_API_TOKEN'), \SensitiveParameter] string $botApiToken)
    {
        $this->botApiToken = $botApiToken;
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        if ('' === $this->botApiToken || !hash_equals($this->botApiToken, $accessToken)) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        return new UserBadge(
            ApiClient::NAME,
            static fn (string $name): ApiClient => new ApiClient($name),
        );
    }
}
