<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Domain\Entity;

use App\Shared\ValueObject\ApiTokenLifetimeValueObject;
use App\Shared\ValueObject\ApiTokenNameValueObject;

class ApiToken
{
    public const SECRET_PREFIX = 'b2t_';

    private string $id;

    private string $name;

    private string $tokenHash;

    private \DateTimeImmutable $createdAt;

    private ?\DateTimeImmutable $expiresAt;

    private ?\DateTimeImmutable $lastUsedAt = null;

    private function __construct(ApiTokenId $id, ApiTokenNameValueObject $name, string $tokenHash, \DateTimeImmutable $createdAt, ?\DateTimeImmutable $expiresAt)
    {
        $this->id = $id->getValue();
        $this->name = $name->getValue();
        $this->tokenHash = $tokenHash;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    public static function issue(ApiTokenId $id, ApiTokenNameValueObject $name, string $secret, \DateTimeImmutable $issuedAt, ?ApiTokenLifetimeValueObject $lifetime): self
    {
        $expiresAt = null === $lifetime ? null : $issuedAt->modify(\sprintf('+%d days', $lifetime->getValue()));

        return new self($id, $name, self::hash($secret), $issuedAt, $expiresAt);
    }

    public static function generateSecret(): string
    {
        return self::SECRET_PREFIX.bin2hex(random_bytes(24));
    }

    public static function hash(string $secret): string
    {
        return hash('sha256', $secret);
    }

    public function isValidAt(\DateTimeImmutable $moment): bool
    {
        return null === $this->expiresAt || $moment < $this->expiresAt;
    }

    public function markUsed(\DateTimeImmutable $moment): void
    {
        $this->lastUsedAt = $moment;
    }

    public function getId(): ApiTokenId
    {
        return new ApiTokenId($this->id);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }
}
