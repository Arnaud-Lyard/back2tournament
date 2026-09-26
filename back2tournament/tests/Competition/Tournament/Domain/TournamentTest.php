<?php

declare(strict_types=1);

namespace App\Tests\Competition\Tournament\Domain;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Competition\Tournament\Domain\Entity\Matchup;
use App\Competition\Tournament\Domain\Entity\MatchupId;
use App\Competition\Tournament\Domain\Entity\OrganizerId;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\ParticipantId;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Entity\TournamentName;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TournamentTest extends TestCase
{
    private const TOURNAMENT_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const ORGANIZER_ID = '33333333-3333-4333-8333-333333333333';

    public function test_a_new_tournament_opens_its_registrations(): void
    {
        $tournament = $this->tournament(8);

        $this->assertSame(TournamentStatus::UPCOMING, $tournament->getStatus());
        $this->assertSame(8, $tournament->getCapacity());
        $this->assertNull($tournament->getWinner());
    }

    #[DataProvider('refusedCapacities')]
    public function test_a_tournament_holds_between_two_and_one_hundred_twenty_eight_participants(int $capacity): void
    {
        $this->expectException(ValidationException::class);

        $this->tournament($capacity);
    }

    public static function refusedCapacities(): iterable
    {
        yield 'alone' => [1];
        yield 'too many' => [129];
    }

    public function test_a_tournament_does_not_start_in_the_past(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('in the past');

        Tournament::create(
            new TournamentId(self::TOURNAMENT_ID),
            new TournamentName('Autumn Cup'),
            new GameId(self::GAME_ID),
            new TeamSize(1),
            8,
            new OrganizerId(self::ORGANIZER_ID),
            new \DateTimeImmutable('-1 day'),
        );
    }

    public function test_the_seed_is_the_registration_rank(): void
    {
        $tournament = $this->tournament(8);

        $participants = $this->register($tournament, 3);

        $this->assertSame([1, 2, 3], array_map(static fn (Participant $participant): int => $participant->getSeed(), $participants));
    }

    public function test_a_full_tournament_registers_nobody_else(): void
    {
        $tournament = $this->tournament(2);
        $this->register($tournament, 2);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('full');

        Tournament::register($tournament, new ParticipantId(Uuid::v4()->toString()), $this->competitor(), 2);
    }

    public function test_withdrawing_moves_the_later_seeds_up(): void
    {
        $tournament = $this->tournament(8);
        $participants = $this->register($tournament, 4);

        $reseeded = Tournament::withdraw($tournament, $participants[1], $participants);

        $this->assertCount(2, $reseeded);
        $this->assertSame(1, $participants[0]->getSeed());
        $this->assertSame(2, $participants[2]->getSeed());
        $this->assertSame(3, $participants[3]->getSeed());
    }

    public function test_a_tournament_needs_two_participants_to_start(): void
    {
        $tournament = $this->tournament(8);
        $participants = $this->register($tournament, 1);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('at least two participants');

        Tournament::start($tournament, $participants, $this->ids(1));
    }

    public function test_two_participants_meet_in_the_final_at_once(): void
    {
        $tournament = $this->tournament(8);
        $participants = $this->register($tournament, 2);

        $bracket = Tournament::start($tournament, $participants, $this->ids(1));

        $this->assertSame(TournamentStatus::ONGOING, $tournament->getStatus());
        $this->assertCount(1, $bracket);
        $this->assertCount(1, Tournament::readyForFight($bracket));
    }

    public function test_five_participants_draw_a_bracket_of_eight_with_byes_for_the_top_seeds(): void
    {
        $tournament = $this->tournament(8);
        $participants = $this->register($tournament, 5);
        [$one, $two, $three, $four, $five] = array_map(static fn (Participant $participant): string => $participant->getCompetitor()->getValue(), $participants);

        $bracket = Tournament::start($tournament, $participants, $this->ids(7));

        $this->assertCount(7, $bracket);
        $this->assertSame([1, 1, 1, 1, 2, 2, 3], array_map(static fn (Matchup $matchup): int => $matchup->getRound(), $bracket));

        // Round 1, top to bottom: 1-8, 4-5, 2-7, 3-6; seeds 6 to 8 do not exist.
        $this->assertSame([$one, null], $this->sides($bracket[0]));
        $this->assertSame([$four, $five], $this->sides($bracket[1]));
        $this->assertSame([$two, null], $this->sides($bracket[2]));
        $this->assertSame([$three, null], $this->sides($bracket[3]));

        // Byes move on at once.
        $this->assertSame($one, $bracket[0]->getWinner()?->getValue());
        $this->assertSame([$one, null], $this->sides($bracket[4]));
        $this->assertSame([$two, $three], $this->sides($bracket[5]));

        $ready = Tournament::readyForFight($bracket);
        $this->assertCount(2, $ready);
        $this->assertSame($bracket[1], $ready[0]);
        $this->assertSame($bracket[5], $ready[1]);
    }

    public function test_winners_move_on_until_the_final_decides_the_tournament(): void
    {
        $tournament = $this->tournament(4);
        $participants = $this->register($tournament, 4);
        [$one, $two, $three, $four] = array_map(static fn (Participant $participant): CompetitorId => $participant->getCompetitor(), $participants);
        $bracket = Tournament::start($tournament, $participants, $this->ids(3));

        // 1-4 and 2-3 play first.
        $this->assertSame([$one->getValue(), $four->getValue()], $this->sides($bracket[0]));
        $this->assertSame([$two->getValue(), $three->getValue()], $this->sides($bracket[1]));

        $final = Tournament::recordWinner($tournament, $bracket, $bracket[0], $four);
        $this->assertSame($bracket[2], $final);
        $this->assertSame([$four->getValue(), null], $this->sides($final));

        Tournament::recordWinner($tournament, $bracket, $bracket[1], $two);
        $this->assertSame([$four->getValue(), $two->getValue()], $this->sides($final));

        $this->assertNull(Tournament::recordWinner($tournament, $bracket, $final, $two));
        $this->assertSame(TournamentStatus::FINISHED, $tournament->getStatus());
        $this->assertSame($two->getValue(), $tournament->getWinner()?->getValue());
    }

    public function test_a_competitor_out_of_the_matchup_does_not_win_it(): void
    {
        $tournament = $this->tournament(4);
        $participants = $this->register($tournament, 4);
        $bracket = Tournament::start($tournament, $participants, $this->ids(3));

        $this->expectException(ValidationException::class);

        Tournament::recordWinner($tournament, $bracket, $bracket[0], $participants[1]->getCompetitor());
    }

    public function test_a_matchup_is_decided_once(): void
    {
        $tournament = $this->tournament(4);
        $participants = $this->register($tournament, 4);
        $bracket = Tournament::start($tournament, $participants, $this->ids(3));
        Tournament::recordWinner($tournament, $bracket, $bracket[0], $participants[0]->getCompetitor());

        $this->expectException(ConflictException::class);

        Tournament::recordWinner($tournament, $bracket, $bracket[0], $participants[3]->getCompetitor());
    }

    public function test_a_matchup_with_its_fight_is_no_longer_ready(): void
    {
        $tournament = $this->tournament(4);
        $bracket = Tournament::start($tournament, $this->register($tournament, 2), $this->ids(1));

        Tournament::attachFight($tournament, $bracket[0], new FightId(Uuid::v4()->toString()));

        $this->assertSame([], Tournament::readyForFight($bracket));
    }

    public function test_registrations_close_when_the_tournament_starts(): void
    {
        $tournament = $this->tournament(8);
        Tournament::start($tournament, $this->register($tournament, 2), $this->ids(1));

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('registrations are closed');

        Tournament::register($tournament, new ParticipantId(Uuid::v4()->toString()), $this->competitor(), 2);
    }

    public function test_a_cancelled_tournament_is_not_cancelled_again(): void
    {
        $tournament = $this->tournament(8);
        Tournament::cancel($tournament);

        $this->assertSame(TournamentStatus::CANCELLED, $tournament->getStatus());

        $this->expectException(ConflictException::class);

        Tournament::cancel($tournament);
    }

    private function tournament(int $capacity): Tournament
    {
        return Tournament::create(
            new TournamentId(self::TOURNAMENT_ID),
            new TournamentName('Autumn Cup'),
            new GameId(self::GAME_ID),
            new TeamSize(1),
            $capacity,
            new OrganizerId(self::ORGANIZER_ID),
            new \DateTimeImmutable('+1 week'),
        );
    }

    /**
     * @return list<Participant>
     */
    private function register(Tournament $tournament, int $count): array
    {
        $participants = [];
        for ($index = 0; $index < $count; ++$index) {
            $participants[] = Tournament::register($tournament, new ParticipantId(Uuid::v4()->toString()), $this->competitor(), $index);
        }

        return $participants;
    }

    private function competitor(): CompetitorId
    {
        return new CompetitorId(Uuid::v4()->toString());
    }

    /**
     * @return list<MatchupId>
     */
    private function ids(int $count): array
    {
        return array_map(static fn (): MatchupId => new MatchupId(Uuid::v4()->toString()), range(1, $count));
    }

    /**
     * @return array{?string, ?string}
     */
    private function sides(Matchup $matchup): array
    {
        return [$matchup->getCompetitorOne()?->getValue(), $matchup->getCompetitorTwo()?->getValue()];
    }
}
