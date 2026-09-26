<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Model\FindCurrentUserQuery;
use App\Authentication\User\Application\Service\FindCurrentUserHandler;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Shared\Domain\Provider\PlayerProfileProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FindCurrentUserHandlerTest extends TestCase
{
    private const USER_ID = '00000000-0000-4000-8000-000000000000';
    private const PLAYER_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';

    public function test_the_profiles_read_are_those_of_the_authenticated_user(): void
    {
        $playerProfileProvider = $this->createMock(PlayerProfileProviderInterface::class);
        $playerProfileProvider
            ->expects($this->once())
            ->method('byUser')
            ->with(self::USER_ID)
            ->willReturn([]);

        $handler = new FindCurrentUserHandler($this->currentUserProvider(), $playerProfileProvider, $this->normalizer());

        $handler(new FindCurrentUserQuery());
    }

    public function test_the_user_is_answered_with_the_profiles_they_hold(): void
    {
        $profiles = [['id' => self::PLAYER_ID, 'battletag' => 'Alpha#1234', 'game' => self::GAME_ID]];

        $handler = new FindCurrentUserHandler(
            $this->currentUserProvider(),
            $this->playerProfileProvider($profiles),
            $this->normalizer(),
        );

        $this->assertSame(
            ['id' => self::USER_ID, 'username' => 'alpha', 'players' => $profiles],
            json_decode($handler(new FindCurrentUserQuery()), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function test_a_user_without_profile_reads_players_as_an_empty_json_array(): void
    {
        $handler = new FindCurrentUserHandler(
            $this->currentUserProvider(),
            $this->playerProfileProvider([]),
            $this->normalizer(),
        );

        $this->assertStringContainsString('"players":[]', $handler(new FindCurrentUserQuery()));
    }

    private function currentUserProvider(): CurrentUserProviderInterface
    {
        $user = new User(self::USER_ID);
        $user->setUsername('alpha');

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn($user);

        return $currentUserProvider;
    }

    /**
     * @param list<array{id: string, battletag: string, game: string}> $profiles
     */
    private function playerProfileProvider(array $profiles): PlayerProfileProviderInterface
    {
        $playerProfileProvider = $this->createStub(PlayerProfileProviderInterface::class);
        $playerProfileProvider->method('byUser')->willReturn($profiles);

        return $playerProfileProvider;
    }

    private function normalizer(): NormalizerInterface
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static fn (User $user): array => ['id' => $user->getId(), 'username' => $user->getUsername()]
        );

        return $normalizer;
    }
}
