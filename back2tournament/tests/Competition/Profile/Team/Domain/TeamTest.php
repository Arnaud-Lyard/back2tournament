<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Team\Domain;

use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Entity\TeamName;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class TeamTest extends TestCase
{
    private const TEAM_ID = '11111111-1111-4111-8111-111111111111';
    private const CLAN_ID = '22222222-2222-4222-8222-222222222222';
    private const GAME_ID = '33333333-3333-4333-8333-333333333333';
    private const ONE = '44444444-4444-4444-8444-444444444444';
    private const TWO = '55555555-5555-4555-8555-555555555555';
    private const THREE = '66666666-6666-4666-8666-666666666666';

    public function test_a_team_fields_as_many_players_as_its_format(): void
    {
        $team = $this->team(2, self::ONE, [self::ONE, self::TWO]);

        $this->assertSame(2, $team->getSize());
        $this->assertSame(self::ONE, $team->getLeader()->getValue());
        $this->assertSame(self::CLAN_ID, $team->getClan()->getValue());
    }

    public function test_a_lineup_short_of_players_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('exactly 3 players, 2 given');

        $this->team(3, self::ONE, [self::ONE, self::TWO]);
    }

    public function test_a_player_is_not_counted_twice(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('appears twice');

        $this->team(2, self::ONE, [self::ONE, self::ONE]);
    }

    public function test_the_leader_plays_in_the_lineup(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('leader must play');

        $this->team(2, self::THREE, [self::ONE, self::TWO]);
    }

    public function test_a_team_has_a_name(): void
    {
        $this->expectException(ValidationException::class);

        new TeamName('');
    }

    /**
     * @param list<string> $players
     */
    private function team(int $size, string $leader, array $players): Team
    {
        return Team::create(
            new TeamId(self::TEAM_ID),
            new TeamName('Falcons'),
            new ClanId(self::CLAN_ID),
            new GameId(self::GAME_ID),
            new TeamSize($size),
            new PlayerId($leader),
            array_map(static fn (string $player): PlayerId => new PlayerId($player), $players),
        );
    }
}
