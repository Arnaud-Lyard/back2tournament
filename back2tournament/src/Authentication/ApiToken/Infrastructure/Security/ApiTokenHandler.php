<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Infrastructure\Security;

use App\Authentication\ApiToken\Domain\Entity\ApiToken;
use App\Authentication\ApiToken\Domain\Repository\ApiTokenRepositoryInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiTokenHandler implements AccessTokenHandlerInterface
{
    private ApiTokenRepositoryInterface $apiTokenRepository;

    public function __construct(ApiTokenRepositoryInterface $apiTokenRepository)
    {
        $this->apiTokenRepository = $apiTokenRepository;
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $apiToken = $this->apiTokenRepository->findOneByTokenHash(ApiToken::hash($accessToken));
        if (!$apiToken) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        $now = new \DateTimeImmutable('now');
        if (!$apiToken->isValidAt($now)) {
            throw new CustomUserMessageAuthenticationException('Expired API token');
        }

        $apiToken->markUsed($now);
        $this->apiTokenRepository->save($apiToken);

        return new UserBadge(
            $apiToken->getName(),
            static fn (string $name): ApiClient => new ApiClient($name),
        );
    }
}
