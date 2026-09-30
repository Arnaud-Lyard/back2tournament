<?php

declare(strict_types=1);

namespace App\Tests\Authentication\ApiToken\Application;

use App\Authentication\ApiToken\Application\Service\ApiTokenService;
use App\Authentication\ApiToken\Domain\Entity\ApiToken;
use App\Authentication\ApiToken\Domain\Entity\ApiTokenId;
use App\Authentication\ApiToken\Domain\Repository\ApiTokenRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ApiTokenNameValueObject;
use PHPUnit\Framework\TestCase;

final class ApiTokenServiceTest extends TestCase
{
    public function test_issuing_a_token_keeps_the_hash_of_the_secret_it_returns(): void
    {
        $saved = null;

        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->method('findOneByName')->willReturn(null);
        $apiTokenRepository->expects($this->once())->method('save')->willReturnCallback(
            static function (ApiToken $apiToken) use (&$saved): void {
                $saved = $apiToken;
            }
        );

        $secret = new ApiTokenService($apiTokenRepository)->issue(' Hermes ', null);

        $this->assertMatchesRegularExpression('/^b2t_[0-9a-f]{48}$/', $secret);
        $this->assertInstanceOf(ApiToken::class, $saved);
        $this->assertSame('hermes', $saved->getName());
        $this->assertSame(ApiToken::hash($secret), $saved->getTokenHash());
        $this->assertNull($saved->getExpiresAt());
    }

    public function test_a_token_issued_for_some_days_expires_that_many_days_later(): void
    {
        $saved = null;

        $apiTokenRepository = $this->createStub(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->method('save')->willReturnCallback(
            static function (ApiToken $apiToken) use (&$saved): void {
                $saved = $apiToken;
            }
        );

        new ApiTokenService($apiTokenRepository)->issue('hermes', 90);

        $this->assertInstanceOf(ApiToken::class, $saved);
        $this->assertEquals($saved->getCreatedAt()->modify('+90 days'), $saved->getExpiresAt());
    }

    public function test_a_name_already_issued_is_not_issued_twice(): void
    {
        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->expects($this->once())->method('findOneByName')->with('hermes')->willReturn($this->token('hermes'));
        $apiTokenRepository->expects($this->never())->method('save');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('an API token is already named hermes');

        new ApiTokenService($apiTokenRepository)->issue('Hermes', null);
    }

    public function test_an_invalid_name_issues_nothing(): void
    {
        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->expects($this->never())->method('findOneByName');
        $apiTokenRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);

        new ApiTokenService($apiTokenRepository)->issue('hermes bot', null);
    }

    public function test_a_lifetime_of_no_day_issues_nothing(): void
    {
        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);

        new ApiTokenService($apiTokenRepository)->issue('hermes', 0);
    }

    public function test_the_tokens_are_listed_as_the_repository_orders_them(): void
    {
        $tokens = [$this->token('hermes'), $this->token('zapier')];

        $apiTokenRepository = $this->createStub(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->method('findAllByCreation')->willReturn($tokens);

        $this->assertSame($tokens, new ApiTokenService($apiTokenRepository)->all());
    }

    public function test_revoking_a_token_removes_it(): void
    {
        $token = $this->token('hermes');

        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->expects($this->once())->method('findOneByName')->with('hermes')->willReturn($token);
        $apiTokenRepository->expects($this->once())->method('remove')->with($token);

        new ApiTokenService($apiTokenRepository)->revoke(' HERMES ');
    }

    public function test_revoking_an_unknown_name_removes_nothing(): void
    {
        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->method('findOneByName')->willReturn(null);
        $apiTokenRepository->expects($this->never())->method('remove');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('no API token is named hermes');

        new ApiTokenService($apiTokenRepository)->revoke('hermes');
    }

    private function token(string $name): ApiToken
    {
        return ApiToken::issue(
            new ApiTokenId('55555555-5555-4555-8555-555555555555'),
            new ApiTokenNameValueObject($name),
            ApiToken::generateSecret(),
            new \DateTimeImmutable('2026-09-30 12:00:00'),
            null,
        );
    }
}
