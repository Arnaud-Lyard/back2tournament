<?php

declare(strict_types=1);

namespace App\Tests\Authentication\ApiToken\Infrastructure;

use App\Authentication\ApiToken\Domain\Entity\ApiToken;
use App\Authentication\ApiToken\Domain\Entity\ApiTokenId;
use App\Authentication\ApiToken\Domain\Repository\ApiTokenRepositoryInterface;
use App\Authentication\ApiToken\Infrastructure\Security\ApiClient;
use App\Authentication\ApiToken\Infrastructure\Security\ApiTokenHandler;
use App\Shared\ValueObject\ApiTokenLifetimeValueObject;
use App\Shared\ValueObject\ApiTokenNameValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class ApiTokenHandlerTest extends TestCase
{
    private const SECRET = 'b2t_0123456789abcdef0123456789abcdef0123456789abcdef';

    public function test_a_known_token_signs_the_tool_in_as_a_bot_and_records_the_use(): void
    {
        $apiToken = $this->token(new \DateTimeImmutable('-1 day'), null);

        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->expects($this->once())->method('findOneByTokenHash')->with(ApiToken::hash(self::SECRET))->willReturn($apiToken);
        $apiTokenRepository->expects($this->once())->method('save')->with($apiToken);

        $badge = new ApiTokenHandler($apiTokenRepository)->getUserBadgeFrom(self::SECRET);
        $client = $badge->getUser();

        $this->assertSame('hermes', $badge->getUserIdentifier());
        $this->assertInstanceOf(ApiClient::class, $client);
        $this->assertSame('hermes', $client->getUserIdentifier());
        $this->assertSame(['ROLE_BOT'], $client->getRoles());
        $this->assertNotNull($apiToken->getLastUsedAt());
    }

    public function test_an_unknown_token_is_refused(): void
    {
        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->method('findOneByTokenHash')->willReturn(null);
        $apiTokenRepository->expects($this->never())->method('save');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API token');

        new ApiTokenHandler($apiTokenRepository)->getUserBadgeFrom(self::SECRET);
    }

    public function test_an_expired_token_is_refused_and_not_marked_used(): void
    {
        $apiToken = $this->token(new \DateTimeImmutable('-31 days'), new ApiTokenLifetimeValueObject(30));

        $apiTokenRepository = $this->createMock(ApiTokenRepositoryInterface::class);
        $apiTokenRepository->method('findOneByTokenHash')->willReturn($apiToken);
        $apiTokenRepository->expects($this->never())->method('save');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Expired API token');

        new ApiTokenHandler($apiTokenRepository)->getUserBadgeFrom(self::SECRET);
    }

    private function token(\DateTimeImmutable $issuedAt, ?ApiTokenLifetimeValueObject $lifetime): ApiToken
    {
        return ApiToken::issue(
            new ApiTokenId('66666666-6666-4666-8666-666666666666'),
            new ApiTokenNameValueObject('hermes'),
            self::SECRET,
            $issuedAt,
            $lifetime,
        );
    }
}
