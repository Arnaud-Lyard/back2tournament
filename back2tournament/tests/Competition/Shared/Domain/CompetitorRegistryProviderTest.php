<?php

declare(strict_types=1);

namespace App\Tests\Competition\Shared\Domain;

use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Provider\ClanTagProvider;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProvider;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CompetitorRegistryProviderTest extends TestCase
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
    private const MATE_COMPETITOR = '68686868-6868-4686-8686-686868686868';

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

    public function test_sides_are_named_by_battletag_or_team_name_and_tagged_with_their_clan(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::PLAYER_COMPETITOR, CompetitorType::PLAYER, self::PLAYER_ID),
            self::aCompetitor(self::TEAM_COMPETITOR, CompetitorType::TEAM, self::TEAM_ID),
        ]));

        $described = $registry->describe([self::PLAYER_COMPETITOR, self::TEAM_COMPETITOR]);

        $this->assertSame(['type' => 'player', 'reference' => self::PLAYER_ID, 'name' => 'Leader#0001', 'tag' => 'B2T'], $described[self::PLAYER_COMPETITOR]);
        $this->assertSame(['type' => 'team', 'reference' => self::TEAM_ID, 'name' => 'Falcons', 'tag' => 'B2T'], $described[self::TEAM_COMPETITOR]);
    }

    public function test_a_player_in_no_clan_has_no_tag(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::MATE_COMPETITOR, CompetitorType::PLAYER, self::MATE_PLAYER),
        ]));

        $this->assertSame(
            [self::MATE_COMPETITOR => ['type' => 'player', 'reference' => self::MATE_PLAYER, 'name' => 'Mate#0002', 'tag' => null]],
            $registry->describe([self::MATE_COMPETITOR]),
        );
    }

    public function test_a_player_has_played_as_itself_and_as_the_teams_it_is_fielded_in(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::PLAYER_COMPETITOR, CompetitorType::PLAYER, self::PLAYER_ID),
            self::aCompetitor(self::TEAM_COMPETITOR, CompetitorType::TEAM, self::TEAM_ID),
        ]));

        $this->assertEqualsCanonicalizing([self::PLAYER_COMPETITOR, self::TEAM_COMPETITOR], $registry->competitorsOfPlayer(self::PLAYER_ID));
        // A teammate who never played a duel competes only through the team.
        $this->assertSame([self::TEAM_COMPETITOR], $registry->competitorsOfPlayer(self::MATE_PLAYER));
    }

    public function test_a_player_who_never_competed_has_no_competitor(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, []));

        $this->assertSame([], $registry->competitorsOfPlayer(self::PLAYER_ID));
    }

    public function test_a_clan_competes_through_its_teams(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::PLAYER_COMPETITOR, CompetitorType::PLAYER, self::PLAYER_ID),
            self::aCompetitor(self::TEAM_COMPETITOR, CompetitorType::TEAM, self::TEAM_ID),
        ]));

        $this->assertSame([self::TEAM_COMPETITOR], $registry->competitorsOfClan(self::CLAN_ID));
        $this->assertSame([], $registry->competitorsOfClan(self::GAME_ID));
    }

    public function test_a_player_plays_alone_for_its_clan_and_a_team_fields_its_lineup_for_its_clan(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::PLAYER_COMPETITOR, CompetitorType::PLAYER, self::PLAYER_ID),
            self::aCompetitor(self::TEAM_COMPETITOR, CompetitorType::TEAM, self::TEAM_ID),
            self::aCompetitor(self::MATE_COMPETITOR, CompetitorType::PLAYER, self::MATE_PLAYER),
        ]));

        $this->assertSame(
            [
                self::PLAYER_COMPETITOR => ['players' => [self::PLAYER_ID], 'clan' => self::CLAN_ID],
                self::TEAM_COMPETITOR => ['players' => [self::PLAYER_ID, self::MATE_PLAYER], 'clan' => self::CLAN_ID],
                // In no clan: it ranks alone.
                self::MATE_COMPETITOR => ['players' => [self::MATE_PLAYER], 'clan' => null],
            ],
            $registry->lineups([self::PLAYER_COMPETITOR, self::TEAM_COMPETITOR, self::MATE_COMPETITOR, self::GAME_ID]),
        );
        $this->assertSame([], $registry->lineups([]));
    }

    public function test_a_name_finds_the_competitors_of_the_profiles_and_teams_bearing_it(): void
    {
        $registry = $this->registry($this->repositoryStub(CompetitorRepositoryInterface::class, [
            self::aCompetitor(self::PLAYER_COMPETITOR, CompetitorType::PLAYER, self::PLAYER_ID),
            self::aCompetitor(self::TEAM_COMPETITOR, CompetitorType::TEAM, self::TEAM_ID),
        ]));

        // The stubs name Leader#0001 for "lead", and the Falcons for "falc".
        $this->assertSame([self::PLAYER_COMPETITOR], $registry->named('lead'));
        $this->assertSame([self::TEAM_COMPETITOR], $registry->named('falc', self::GAME_ID));
        $this->assertSame([], $registry->named('nobody'));
    }

    private function registry(CompetitorRepositoryInterface $competitorRepository): CompetitorRegistryProvider
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::PLAYER_ID);
        [$team, $lineup] = self::aTeam(self::TEAM_ID, $clan, [self::PLAYER_ID, self::MATE_PLAYER]);
        $players = [
            self::aPlayer(self::PLAYER_ID, self::USER_ID, self::GAME_ID, 'Leader#0001'),
            self::aPlayer(self::MATE_PLAYER, self::MATE_USER, self::GAME_ID, 'Mate#0002'),
        ];

        $playerRepository = $this->repositoryStub(PlayerRepositoryInterface::class, $players);
        $playerRepository->method('findNamed')->willReturnCallback(static fn (string $search): array => array_values(array_filter(
            $players,
            static fn ($player): bool => str_contains(mb_strtolower((string) $player->getBattletag()), mb_strtolower($search)),
        )));
        $teamRepository = $this->repositoryStub(TeamRepositoryInterface::class, [$team]);
        $teamRepository->method('findNamed')->willReturnCallback(static fn (string $search): array => str_contains(mb_strtolower((string) $team->getName()), mb_strtolower($search)) ? [$team] : []);

        return new CompetitorRegistryProvider(
            $competitorRepository,
            $playerRepository,
            $teamRepository,
            $this->repositoryStub(TeamPlayerRepositoryInterface::class, $lineup),
            new ClanTagProvider(
                $this->repositoryStub(ClanMemberRepositoryInterface::class, [self::leadership($clan)]),
                $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            ),
            $this->createStub(EventDispatcherInterface::class),
        );
    }
}
