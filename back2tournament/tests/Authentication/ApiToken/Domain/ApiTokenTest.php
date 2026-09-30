<?php

declare(strict_types=1);

namespace App\Tests\Authentication\ApiToken\Domain;

use App\Authentication\ApiToken\Domain\Entity\ApiToken;
use App\Authentication\ApiToken\Domain\Entity\ApiTokenId;
use App\Shared\ValueObject\ApiTokenLifetimeValueObject;
use App\Shared\ValueObject\ApiTokenNameValueObject;
use PHPUnit\Framework\TestCase;

final class ApiTokenTest extends TestCase
{
    private const ID = '44444444-4444-4444-8444-444444444444';
    private const SECRET = 'b2t_0123456789abcdef0123456789abcdef0123456789abcdef';

    public function test_a_secret_is_the_prefix_and_48_random_hexadecimal_digits(): void
    {
        $secret = ApiToken::generateSecret();

        $this->assertMatchesRegularExpression('/^b2t_[0-9a-f]{48}$/', $secret);
        $this->assertNotSame($secret, ApiToken::generateSecret());
    }

    public function test_a_token_is_found_again_by_the_hash_of_its_secret(): void
    {
        $this->assertSame(hash('sha256', self::SECRET), ApiToken::hash(self::SECRET));
        $this->assertNotSame(ApiToken::hash(self::SECRET), ApiToken::hash(self::SECRET.'0'));
    }

    public function test_an_issued_token_keeps_its_name_and_never_expires_without_a_lifetime(): void
    {
        $issuedAt = new \DateTimeImmutable('2026-09-30 12:00:00');

        $apiToken = $this->issue($issuedAt, null);

        $this->assertSame(self::ID, $apiToken->getId()->getValue());
        $this->assertSame('hermes', $apiToken->getName());
        $this->assertSame(ApiToken::hash(self::SECRET), $apiToken->getTokenHash());
        $this->assertEquals($issuedAt, $apiToken->getCreatedAt());
        $this->assertNull($apiToken->getExpiresAt());
        $this->assertNull($apiToken->getLastUsedAt());
        $this->assertTrue($apiToken->isValidAt(new \DateTimeImmutable('2126-09-30 12:00:00')));
    }

    public function test_a_token_with_a_lifetime_expires_that_many_days_after_it_was_issued(): void
    {
        $apiToken = $this->issue(new \DateTimeImmutable('2026-09-30 12:00:00'), new ApiTokenLifetimeValueObject(30));

        $this->assertEquals(new \DateTimeImmutable('2026-10-30 12:00:00'), $apiToken->getExpiresAt());
        $this->assertTrue($apiToken->isValidAt(new \DateTimeImmutable('2026-10-30 11:59:59')));
        $this->assertFalse($apiToken->isValidAt(new \DateTimeImmutable('2026-10-30 12:00:00')));
    }

    public function test_a_token_records_when_it_was_last_used(): void
    {
        $apiToken = $this->issue(new \DateTimeImmutable('2026-09-30 12:00:00'), null);
        $usedAt = new \DateTimeImmutable('2026-10-01 08:30:00');

        $apiToken->markUsed($usedAt);

        $this->assertEquals($usedAt, $apiToken->getLastUsedAt());
    }

    private function issue(\DateTimeImmutable $issuedAt, ?ApiTokenLifetimeValueObject $lifetime): ApiToken
    {
        return ApiToken::issue(new ApiTokenId(self::ID), new ApiTokenNameValueObject('hermes'), self::SECRET, $issuedAt, $lifetime);
    }
}
