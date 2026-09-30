<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Application\Service;

use App\Authentication\ApiToken\Domain\Entity\ApiToken;
use App\Authentication\ApiToken\Domain\Entity\ApiTokenId;
use App\Authentication\ApiToken\Domain\Repository\ApiTokenRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\ApiTokenLifetimeValueObject;
use App\Shared\ValueObject\ApiTokenNameValueObject;
use Symfony\Component\Uid\Uuid;

final class ApiTokenService
{
    private ApiTokenRepositoryInterface $apiTokenRepository;

    public function __construct(ApiTokenRepositoryInterface $apiTokenRepository)
    {
        $this->apiTokenRepository = $apiTokenRepository;
    }

    public function issue(string $name, ?int $days): string
    {
        $tokenName = new ApiTokenNameValueObject($name);
        $lifetime = null === $days ? null : new ApiTokenLifetimeValueObject($days);

        if ($this->apiTokenRepository->findOneByName($tokenName->getValue())) {
            throw new ConflictException(\sprintf('an API token is already named %s: revoke it first to issue a new one', $tokenName->getValue()));
        }

        $secret = ApiToken::generateSecret();

        $this->apiTokenRepository->save(ApiToken::issue(
            new ApiTokenId(Uuid::v4()->toString()),
            $tokenName,
            $secret,
            new \DateTimeImmutable('now'),
            $lifetime,
        ));

        return $secret;
    }

    /** @return list<ApiToken> */
    public function all(): array
    {
        return $this->apiTokenRepository->findAllByCreation();
    }

    public function revoke(string $name): void
    {
        $tokenName = new ApiTokenNameValueObject($name);

        $apiToken = $this->apiTokenRepository->findOneByName($tokenName->getValue());
        if (!$apiToken) {
            throw new NotFoundException(\sprintf('no API token is named %s', $tokenName->getValue()));
        }

        $this->apiTokenRepository->remove($apiToken);
    }
}
