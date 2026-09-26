<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Domain;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use PHPUnit\Framework\TestCase;

final class FightTest extends TestCase
{
    private const FIGHT_ID = '11111111-1111-4111-8111-111111111111';
    private const RESULT_ID = '22222222-2222-4222-8222-222222222222';
    private const COMPETITOR_ONE = '33333333-3333-4333-8333-333333333333';
    private const COMPETITOR_TWO = '44444444-4444-4444-8444-444444444444';

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

    public function test_declaring_an_outcome_stores_the_claim_and_awaits_the_opponent(): void
    {
        $fight = $this->fight();
        $result = $this->pendingResult($fight);

        Fight::updateResult(
            $fight,
            $result,
            3,
            new CompetitorId(self::COMPETITOR_ONE),
            ResultStatus::WIN,
        );

        $this->assertSame(ResultStatus::REPORTING, $result->getStatus());
        $this->assertSame(ResultStatus::WIN, $result->getReportedStatus());
        $this->assertSame(3, $result->getScore());
    }

    public function test_the_claim_is_what_the_declaring_side_said_not_the_transport_state(): void
    {
        $fight = $this->fight();
        $result = $this->pendingResult($fight);

        Fight::updateResult(
            $fight,
            $result,
            0,
            new CompetitorId(self::COMPETITOR_TWO),
            ResultStatus::LOSS,
        );

        $this->assertNotSame(ResultStatus::REPORTING, $result->getReportedStatus());
        $this->assertSame(ResultStatus::LOSS, $result->getReportedStatus());
    }

    public function test_confirming_settles_the_status_and_drops_the_claim(): void
    {
        $fight = $this->fight();
        $result = $this->pendingResult($fight);
        Fight::updateResult($fight, $result, 3, new CompetitorId(self::COMPETITOR_ONE), ResultStatus::WIN);

        Fight::confirmResult($fight, $result, ResultStatus::WIN);

        $this->assertSame(ResultStatus::WIN, $result->getStatus());
        $this->assertNull($result->getReportedStatus());
    }

    public function test_declaring_and_confirming_each_record_a_domain_event(): void
    {
        $fight = $this->fight();
        $result = $this->pendingResult($fight);
        $fight->pullDomainEvents();

        Fight::updateResult($fight, $result, 3, new CompetitorId(self::COMPETITOR_ONE), ResultStatus::WIN);
        Fight::confirmResult($fight, $result, ResultStatus::WIN);

        $this->assertCount(2, $fight->pullDomainEvents());
    }

    private function fight(): Fight
    {
        return Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::COMPETITOR_ONE),
            new CompetitorId(self::COMPETITOR_TWO),
        );
    }

    private function pendingResult(Fight $fight): Result
    {
        return Fight::createResult(
            $fight,
            new ResultId(self::RESULT_ID),
            new CompetitorId(self::COMPETITOR_ONE),
        );
    }
}
