<?php

declare(strict_types=1);

namespace App\Tests\Competition\Shared\Domain;

use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\AccountErasureProvider;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;

final class AccountErasureProviderTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const USER_ID = '11111111-1111-4111-8111-111111111111';
    private const PLAYER_ID = '22222222-2222-4222-8222-222222222222';
    private const GAME_ID = '33333333-3333-4333-8333-333333333333';
    private const CLAN_ID = '44444444-4444-4444-8444-444444444444';

    public function test_an_account_without_a_clan_to_lead_or_a_tournament_to_run_is_erasable(): void
    {
        $this->expectNotToPerformAssertions();

        $this->provider([], null)->ensureErasable(self::USER_ID);
    }

    public function test_the_leader_of_a_clan_dissolves_it_first(): void
    {
        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('you lead a clan: dissolve it first');

        $this->provider([self::aClan(self::CLAN_ID, self::GAME_ID, self::PLAYER_ID)], null)->ensureErasable(self::USER_ID);
    }

    public function test_the_leader_of_a_dissolved_clan_is_erasable(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::PLAYER_ID);
        Clan::dissolve($clan);

        $this->expectNotToPerformAssertions();

        $this->provider([$clan], null)->ensureErasable(self::USER_ID);
    }

    public function test_the_organizer_of_a_tournament_open_for_registration_starts_or_cancels_it_first(): void
    {
        $tournamentRepository = $this->createMock(TournamentRepositoryInterface::class);
        $tournamentRepository->expects($this->once())->method('findOneBy')
            ->with(['organizer' => self::USER_ID, 'status' => TournamentStatus::UPCOMING])
            ->willReturn($this->createStub(Tournament::class));

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('you organize a tournament still open for registration: start or cancel it first');

        $this->provider([], $tournamentRepository)->ensureErasable(self::USER_ID);
    }

    /** @param list<Clan> $clans */
    private function provider(array $clans, ?TournamentRepositoryInterface $tournamentRepository): AccountErasureProvider
    {
        return new AccountErasureProvider(
            $this->repositoryStub(PlayerRepositoryInterface::class, [self::aPlayer(self::PLAYER_ID, self::USER_ID, self::GAME_ID)]),
            $this->repositoryStub(ClanRepositoryInterface::class, $clans),
            $tournamentRepository ?? $this->createStub(TournamentRepositoryInterface::class),
        );
    }
}
