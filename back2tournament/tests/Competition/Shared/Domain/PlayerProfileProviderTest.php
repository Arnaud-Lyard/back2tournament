<?php

declare(strict_types=1);

namespace App\Tests\Competition\Shared\Domain;

use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\PlayerProfileProvider;
use PHPUnit\Framework\TestCase;

final class PlayerProfileProviderTest extends TestCase
{
    private const USER_ID = '00000000-0000-4000-8000-000000000000';
    private const FIRST_GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const SECOND_GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const FIRST_PLAYER_ID = '33333333-3333-4333-8333-333333333333';
    private const SECOND_PLAYER_ID = '44444444-4444-4444-8444-444444444444';

    public function test_only_the_profiles_of_the_requested_user_are_asked_for_oldest_first(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['user' => self::USER_ID], ['createdAt' => 'ASC', 'id' => 'ASC'])
            ->willReturn([]);

        new PlayerProfileProvider($playerRepository)->byUser(self::USER_ID);
    }

    public function test_each_profile_names_its_id_battletag_and_game(): void
    {
        $provider = new PlayerProfileProvider($this->playerRepository([
            self::player(self::FIRST_PLAYER_ID, 'Alpha#1234', self::FIRST_GAME_ID),
            self::player(self::SECOND_PLAYER_ID, 'Bravo#5678', self::SECOND_GAME_ID),
        ]));

        $this->assertSame(
            [
                ['id' => self::FIRST_PLAYER_ID, 'battletag' => 'Alpha#1234', 'game' => self::FIRST_GAME_ID],
                ['id' => self::SECOND_PLAYER_ID, 'battletag' => 'Bravo#5678', 'game' => self::SECOND_GAME_ID],
            ],
            $provider->byUser(self::USER_ID),
        );
    }

    public function test_a_user_without_profile_holds_none(): void
    {
        $provider = new PlayerProfileProvider($this->playerRepository([]));

        $this->assertSame([], $provider->byUser(self::USER_ID));
    }

    /** @param list<Player> $players */
    private function playerRepository(array $players): PlayerRepositoryInterface
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findBy')->willReturn($players);

        return $playerRepository;
    }

    private static function player(string $id, string $battletag, string $gameId): Player
    {
        return Player::create(new PlayerId($id), $battletag, new GameId($gameId), new UserId(self::USER_ID));
    }
}
