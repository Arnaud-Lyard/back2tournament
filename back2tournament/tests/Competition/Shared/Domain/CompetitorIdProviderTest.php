<?php

declare(strict_types=1);

namespace App\Tests\Competition\Shared\Domain;

use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId as FightGameId;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Provider\CompetitorIdProvider;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\TeamSizeValueObject;
use PHPUnit\Framework\TestCase;

final class CompetitorIdProviderTest extends TestCase
{
    private const USER_ID = '00000000-0000-4000-8000-000000000000';
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const PLAYER_ID = '22222222-2222-4222-8222-222222222222';
    private const COMPETITOR_ID = '33333333-3333-4333-8333-333333333333';
    private const FIGHT_ID = '44444444-4444-4444-8444-444444444444';
    private const OPPONENT_ID = '55555555-5555-4555-8555-555555555555';

    public function test_the_pair_user_game_names_one_competitor(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => self::USER_ID, 'game' => self::GAME_ID])
            ->willReturn(self::player());

        $competitorRepository = $this->createMock(CompetitorRepositoryInterface::class);
        $competitorRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['type' => CompetitorType::PLAYER, 'reference' => self::PLAYER_ID])
            ->willReturn(self::competitor());

        $provider = new CompetitorIdProvider($competitorRepository, $playerRepository, $this->createStub(FightRepositoryInterface::class));

        $this->assertSame(self::COMPETITOR_ID, $provider->byUserAndGame(self::USER_ID, self::GAME_ID));
    }

    public function test_a_user_without_a_profile_in_that_game_is_not_found(): void
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn(null);

        $competitorRepository = $this->createMock(CompetitorRepositoryInterface::class);
        $competitorRepository->expects($this->never())->method('findOneBy');

        $provider = new CompetitorIdProvider($competitorRepository, $playerRepository, $this->createStub(FightRepositoryInterface::class));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('has no player profile in game');

        $provider->byUserAndGame(self::USER_ID, self::GAME_ID);
    }

    public function test_a_profile_that_never_competed_is_not_found(): void
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn(self::player());

        $competitorRepository = $this->createStub(CompetitorRepositoryInterface::class);
        $competitorRepository->method('findOneBy')->willReturn(null);

        $provider = new CompetitorIdProvider($competitorRepository, $playerRepository, $this->createStub(FightRepositoryInterface::class));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('does not compete yet');

        $provider->byUserAndGame(self::USER_ID, self::GAME_ID);
    }

    public function test_a_profile_that_was_never_enrolled_takes_part_in_no_fight(): void
    {
        $competitorRepository = $this->createStub(CompetitorRepositoryInterface::class);
        $competitorRepository->method('findOneBy')->willReturn(null);

        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->never())->method('findOneBy');

        $provider = new CompetitorIdProvider(
            $competitorRepository,
            $this->createStub(PlayerRepositoryInterface::class),
            $fightRepository,
        );

        $this->assertFalse($provider->takesPartInFights(self::PLAYER_ID));
    }

    public function test_a_competitor_no_fight_ever_held_takes_part_in_no_fight(): void
    {
        $this->assertFalse($this->fightingProvider([])->takesPartInFights(self::PLAYER_ID));
    }

    public function test_a_profile_held_as_the_first_side_takes_part_in_fights(): void
    {
        $provider = $this->fightingProvider([['competitorOne' => self::COMPETITOR_ID]]);

        $this->assertTrue($provider->takesPartInFights(self::PLAYER_ID));
    }

    public function test_a_profile_held_as_the_second_side_takes_part_in_fights(): void
    {
        $provider = $this->fightingProvider([['competitorTwo' => self::COMPETITOR_ID]]);

        $this->assertTrue($provider->takesPartInFights(self::PLAYER_ID));
    }

    /**
     *
     * @param list<array<string, string>> $matching
     */
    private function fightingProvider(array $matching): CompetitorIdProvider
    {
        $fightRepository = $this->createStub(FightRepositoryInterface::class);
        $fightRepository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?Fight => \in_array($criteria, $matching, true) ? self::fight() : null
        );

        $competitorRepository = $this->createStub(CompetitorRepositoryInterface::class);
        $competitorRepository->method('findOneBy')->willReturn(self::competitor());

        return new CompetitorIdProvider(
            $competitorRepository,
            $this->createStub(PlayerRepositoryInterface::class),
            $fightRepository,
        );
    }

    private static function fight(): Fight
    {
        return Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::COMPETITOR_ID),
            new CompetitorId(self::OPPONENT_ID),
            new FightGameId(self::GAME_ID),
            new TeamSizeValueObject(1),
        );
    }

    private static function competitor(): Competitor
    {
        return Competitor::create(new CompetitorId(self::COMPETITOR_ID), CompetitorType::PLAYER, self::PLAYER_ID);
    }

    private static function player(): Player
    {
        return Player::create(
            new PlayerId(self::PLAYER_ID),
            'Player#1234',
            new GameId(self::GAME_ID),
            new UserId(self::USER_ID),
        );
    }
}
