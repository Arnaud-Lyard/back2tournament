<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Domain;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Entity\Score;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Event\FightSettledEvent;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;
use PHPUnit\Framework\TestCase;

final class FightTest extends TestCase
{
    private const FIGHT_ID = '11111111-1111-4111-8111-111111111111';
    private const RESULT_ID = '22222222-2222-4222-8222-222222222222';
    private const OTHER_RESULT_ID = '23232323-2323-4232-8232-232323232323';
    private const COMPETITOR_ONE = '33333333-3333-4333-8333-333333333333';
    private const COMPETITOR_TWO = '44444444-4444-4444-8444-444444444444';
    private const BYSTANDER = '45454545-4545-4545-8545-454545454545';
    private const GAME_ID = '55555555-5555-4555-8555-555555555555';
    private const TOURNAMENT_ID = '66666666-6666-4666-8666-666666666666';

    public function test_a_fresh_result_is_pending_with_no_score(): void
    {
        $result = Fight::createResult(
            $this->fight(),
            new ResultId(self::RESULT_ID),
            new CompetitorId(self::COMPETITOR_ONE),
        );

        $this->assertSame(ResultStatus::PENDING, $result->getStatus());
        $this->assertNull($result->getReportedStatus());
        $this->assertSame(0, $result->getScore());
    }

    public function test_a_fight_knows_its_game_and_format(): void
    {
        $fight = Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::COMPETITOR_ONE),
            new CompetitorId(self::COMPETITOR_TWO),
            new GameId(self::GAME_ID),
            new TeamSizeValueObject(5),
        );

        $this->assertSame(self::GAME_ID, $fight->getGame()->getValue());
        $this->assertSame(5, $fight->getTeamSize());
        $this->assertNull($fight->getTournament());
    }

    public function test_a_competitor_never_fights_itself(): void
    {
        $this->expectException(ValidationException::class);

        Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::COMPETITOR_ONE),
            new CompetitorId(self::COMPETITOR_ONE),
            new GameId(self::GAME_ID),
            new TeamSizeValueObject(1),
        );
    }

    public function test_the_side_of_a_caller_is_the_one_they_speak_for(): void
    {
        $fight = $this->fight();

        $this->assertSame(self::COMPETITOR_TWO, $fight->sideAmong([self::BYSTANDER, self::COMPETITOR_TWO])?->getValue());
        $this->assertNull($fight->sideAmong([self::BYSTANDER]));
    }

    public function test_declaring_stores_both_claims_derived_from_the_scores(): void
    {
        [$fight, $one, $two] = $this->openFight();

        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(3), $two, new Score(1));

        $this->assertSame(ResultStatus::REPORTING, $one->getStatus());
        $this->assertSame(ResultStatus::WIN, $one->getReportedStatus());
        $this->assertSame(3, $one->getScore());
        $this->assertSame(ResultStatus::REPORTING, $two->getStatus());
        $this->assertSame(ResultStatus::LOSS, $two->getReportedStatus());
        $this->assertSame(1, $two->getScore());
        $this->assertSame(self::COMPETITOR_ONE, $fight->getDeclaredBy()?->getValue());
    }

    public function test_equal_scores_are_declared_a_draw(): void
    {
        [$fight, $one, $two] = $this->openFight();

        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_TWO), $two, new Score(2), $one, new Score(2));

        $this->assertSame(ResultStatus::DRAW, $one->getReportedStatus());
        $this->assertSame(ResultStatus::DRAW, $two->getReportedStatus());
    }

    public function test_a_tournament_fight_is_never_declared_a_draw(): void
    {
        [$fight, $one, $two] = $this->openFight(new TournamentId(self::TOURNAMENT_ID));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('cannot end in a draw');

        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(1), $two, new Score(1));
    }

    public function test_a_bystander_declares_nothing(): void
    {
        [$fight, $one, $two] = $this->openFight();

        $this->expectException(PermissionDeniedException::class);

        Fight::declareOutcome($fight, new CompetitorId(self::BYSTANDER), $one, new Score(3), $two, new Score(0));
    }

    public function test_the_declaring_side_corrects_its_own_declaration(): void
    {
        [$fight, $one, $two] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(3), $two, new Score(1));

        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(1), $two, new Score(3));

        $this->assertSame(ResultStatus::LOSS, $one->getReportedStatus());
        $this->assertSame(ResultStatus::WIN, $two->getReportedStatus());
    }

    public function test_the_other_side_does_not_overwrite_a_declaration(): void
    {
        [$fight, $one, $two] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(3), $two, new Score(1));

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('already declared');

        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_TWO), $two, new Score(3), $one, new Score(1));
    }

    public function test_confirming_settles_both_sides_on_the_declared_outcome(): void
    {
        [$fight, $one, $two] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(1), $two, new Score(3));

        Fight::confirmOutcome($fight, new CompetitorId(self::COMPETITOR_TWO), $one, $two);

        $this->assertSame(ResultStatus::LOSS, $one->getStatus());
        $this->assertSame(ResultStatus::WIN, $two->getStatus());
        $this->assertNull($one->getReportedStatus());
        $this->assertNull($two->getReportedStatus());
    }

    public function test_confirming_announces_the_winner_and_the_tournament(): void
    {
        [$fight, $one, $two] = $this->openFight(new TournamentId(self::TOURNAMENT_ID));
        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(1), $two, new Score(3));
        $fight->pullDomainEvents();

        Fight::confirmOutcome($fight, new CompetitorId(self::COMPETITOR_TWO), $one, $two);

        $settled = array_values(array_filter(
            $fight->pullDomainEvents(),
            static fn (object $event): bool => $event instanceof FightSettledEvent,
        ));
        $this->assertCount(1, $settled);
        $this->assertSame(self::COMPETITOR_TWO, $settled[0]->getWinner()?->getValue());
        $this->assertSame(self::TOURNAMENT_ID, $settled[0]->getTournamentId()?->getValue());
    }

    public function test_a_draw_is_settled_without_a_winner(): void
    {
        [$fight, $one, $two] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(2), $two, new Score(2));
        $fight->pullDomainEvents();

        Fight::confirmOutcome($fight, new CompetitorId(self::COMPETITOR_TWO), $one, $two);

        $events = $fight->pullDomainEvents();
        $settled = end($events);
        $this->assertInstanceOf(FightSettledEvent::class, $settled);
        $this->assertNull($settled->getWinner());
        $this->assertSame(ResultStatus::DRAW, $one->getStatus());
    }

    public function test_nothing_declared_is_nothing_to_confirm(): void
    {
        [$fight, $one, $two] = $this->openFight();

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('no outcome has been declared');

        Fight::confirmOutcome($fight, new CompetitorId(self::COMPETITOR_TWO), $one, $two);
    }

    public function test_the_declaring_side_does_not_confirm_its_own_claim(): void
    {
        [$fight, $one, $two] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(3), $two, new Score(0));

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('cannot confirm its own outcome');

        Fight::confirmOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, $two);
    }

    public function test_a_bystander_confirms_nothing(): void
    {
        [$fight, $one, $two] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(3), $two, new Score(0));

        $this->expectException(PermissionDeniedException::class);

        Fight::confirmOutcome($fight, new CompetitorId(self::BYSTANDER), $one, $two);
    }

    public function test_a_settled_fight_is_neither_confirmed_nor_declared_again(): void
    {
        [$fight, $one, $two] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(3), $two, new Score(0));
        Fight::confirmOutcome($fight, new CompetitorId(self::COMPETITOR_TWO), $one, $two);

        try {
            Fight::confirmOutcome($fight, new CompetitorId(self::COMPETITOR_TWO), $one, $two);
            $this->fail('a settled fight was confirmed again');
        } catch (ConflictException $conflict) {
            $this->assertStringContainsString('not awaiting a confirmation', $conflict->getMessage());
        }

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('already settled');

        Fight::declareOutcome($fight, new CompetitorId(self::COMPETITOR_ONE), $one, new Score(0), $two, new Score(3));
    }

    private function fight(?TournamentId $tournamentId = null): Fight
    {
        return Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::COMPETITOR_ONE),
            new CompetitorId(self::COMPETITOR_TWO),
            new GameId(self::GAME_ID),
            new TeamSizeValueObject(1),
            $tournamentId,
        );
    }

    /**
     * @return array{Fight, Result, Result}
     */
    private function openFight(?TournamentId $tournamentId = null): array
    {
        $fight = $this->fight($tournamentId);

        return [
            $fight,
            Fight::createResult($fight, new ResultId(self::RESULT_ID), new CompetitorId(self::COMPETITOR_ONE)),
            Fight::createResult($fight, new ResultId(self::OTHER_RESULT_ID), new CompetitorId(self::COMPETITOR_TWO)),
        ];
    }
}
