<?php

declare(strict_types=1);

namespace App\Tests\Competition\Shared\Domain;

use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistry;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CompetitorRegistryTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const USER_ID = '22222222-2222-4222-8222-222222222222';
    private const PLAYER_ID = '23232323-2323-4232-8232-232323232323';
    private const MATE_USER = '33333333-3333-4333-8333-333333333333';
    private const MATE_PLAYER = '34343434-3434-4343-8343-343434343434';
    private const CLAN_ID = '44444444-4444-4444-8444-444444444444';
    private const TEAM_ID = '55555555-5555-4555-8555-555555555555';
    private const PLAYER_COMPETITOR = '66666666-6666-4666-8666-666666666666';
    private const TEAM_COMPETITOR = '67676767-6767-4676-8676-676767676767';

    public function test_a_player_already_enlisted_keeps_its_competitor(): void
    {
        $competitorRepository = $this->repositoryMock(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::PLAYER_COMPETITOR, CompetitorType::PLAYER, self::PLAYER_ID),
        ]);
        $competitorRepository->expects($this->never())->method('save');

        $this->assertSame(self::PLAYER_COMPETITOR, $this->registry($competitorRepository)->enlistPlayer(self::PLAYER_ID));
    }

    public function test_a_team_is_enlisted_on_its_first_fight(): void
    {
        $saved = null;

        $competitorRepository = $this->repositoryMock(CompetitorRepositoryInterface::class, []);
        $competitorRepository->expects($this->once())->method('save')->willReturnCallback(
            static function (Competitor $competitor) use (&$saved): void {
                $saved = $competitor;
            }
        );

        $enlisted = $this->registry($competitorRepository)->enlistTeam(self::TEAM_ID);

        $this->assertInstanceOf(Competitor::class, $saved);
        $this->assertSame(CompetitorType::TEAM, $saved->getType());
        $this->assertSame(self::TEAM_ID, $saved->getReference());
        $this->assertSame($enlisted, $saved->getId()->getValue());
    }

    public function test_a_user_speaks_for_their_profile_and_the_teams_it_leads(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::PLAYER_COMPETITOR, CompetitorType::PLAYER, self::PLAYER_ID),
            self::aCompetitor(self::TEAM_COMPETITOR, CompetitorType::TEAM, self::TEAM_ID),
        ]));

        $this->assertEqualsCanonicalizing([self::PLAYER_COMPETITOR, self::TEAM_COMPETITOR], $registry->representedBy(self::USER_ID));
    }

    public function test_a_team_member_who_does_not_lead_speaks_for_the_team_not(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::TEAM_COMPETITOR, CompetitorType::TEAM, self::TEAM_ID),
        ]));

        $this->assertSame([], $registry->representedBy(self::MATE_USER));
    }

    public function test_sides_are_named_by_battletag_or_team_name(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::PLAYER_COMPETITOR, CompetitorType::PLAYER, self::PLAYER_ID),
            self::aCompetitor(self::TEAM_COMPETITOR, CompetitorType::TEAM, self::TEAM_ID),
        ]));

        $described = $registry->describe([self::PLAYER_COMPETITOR, self::TEAM_COMPETITOR]);

        $this->assertSame(['type' => 'player', 'reference' => self::PLAYER_ID, 'name' => 'Leader#0001'], $described[self::PLAYER_COMPETITOR]);
        $this->assertSame(['type' => 'team', 'reference' => self::TEAM_ID, 'name' => 'Falcons'], $described[self::TEAM_COMPETITOR]);
    }

    private function registry(CompetitorRepositoryInterface $competitorRepository): CompetitorRegistry
    {
        [$team] = self::aTeam(self::TEAM_ID, self::aClan(self::CLAN_ID, self::GAME_ID, self::PLAYER_ID), [self::PLAYER_ID, self::MATE_PLAYER]);

        return new CompetitorRegistry(
            $competitorRepository,
            $this->repositoryStub(PlayerRepositoryInterface::class, [
                self::aPlayer(self::PLAYER_ID, self::USER_ID, self::GAME_ID, 'Leader#0001'),
                self::aPlayer(self::MATE_PLAYER, self::MATE_USER, self::GAME_ID, 'Mate#0002'),
            ]),
            $this->repositoryStub(TeamRepositoryInterface::class, [$team]),
            $this->createStub(EventDispatcherInterface::class),
        );
    }
}
