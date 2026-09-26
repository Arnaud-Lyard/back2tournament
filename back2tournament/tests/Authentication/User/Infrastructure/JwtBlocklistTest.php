<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Infrastructure;

use App\Authentication\User\Domain\Entity\Email;
use App\Authentication\User\Domain\Entity\Locale;
use App\Authentication\User\Domain\Entity\Password;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Entity\Username;
use Lexik\Bundle\JWTAuthenticationBundle\Services\BlockedTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class JwtBlocklistTest extends KernelTestCase
{
    private function createToken(): array
    {
        $container = static::getContainer();

        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        $user = User::registerUser(
            new Email('blocklist@back2tournament.fr'),
            new Username('blocklist_user'),
            ['ROLE_USER'],
            new Password('Password123!'),
            new Locale('fr'),
        );

        return $jwtManager->parse($jwtManager->create($user));
    }

    public function test_issued_tokens_carry_a_jti_claim(): void
    {
        self::bootKernel();

        $payload = $this->createToken();

        self::assertArrayHasKey('jti', $payload, 'Tokens must carry a jti, otherwise logout cannot revoke them.');
        self::assertNotEmpty($payload['jti']);
    }

    public function test_token_is_rejected_once_blocked(): void
    {
        self::bootKernel();

        /** @var BlockedTokenManagerInterface $blockedTokenManager */
        $blockedTokenManager = static::getContainer()->get('lexik_jwt_authentication.blocked_token_manager');

        $payload = $this->createToken();

        self::assertFalse($blockedTokenManager->has($payload), 'A freshly issued token must not be blocked.');

        self::assertTrue($blockedTokenManager->add($payload));
        self::assertTrue($blockedTokenManager->has($payload), 'The token must be blocked after logout.');

        $blockedTokenManager->remove($payload);
    }

    public function test_two_tokens_get_distinct_jti_so_logout_only_revokes_one(): void
    {
        self::bootKernel();

        /** @var BlockedTokenManagerInterface $blockedTokenManager */
        $blockedTokenManager = static::getContainer()->get('lexik_jwt_authentication.blocked_token_manager');

        $first = $this->createToken();
        $second = $this->createToken();

        self::assertNotSame($first['jti'], $second['jti']);

        $blockedTokenManager->add($first);

        self::assertTrue($blockedTokenManager->has($first));
        self::assertFalse($blockedTokenManager->has($second), 'Logging out one session must not revoke the others.');

        $blockedTokenManager->remove($first);
    }
}
