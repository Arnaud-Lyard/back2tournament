<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Application;

use App\Competition\Fight\Application\Service\FightScheduler;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class FightSchedulerTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const ONE = '22222222-2222-4222-8222-222222222222';
    private const TWO = '33333333-3333-4333-8333-333333333333';
    private const TOURNAMENT_ID = '44444444-4444-4444-8444-444444444444';

    public function test_a_scheduled_fight_opens_a_pending_result_for_each_side(): void
    {
        $results = [];

        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->once())->method('save')->with($this->isInstanceOf(Fight::class));

        $resultRepository = $this->createStub(ResultRepositoryInterface::class);
        $resultRepository->method('save')->willReturnCallback(
            static function (Result $result) use (&$results): void {
                $results[] = $result;
            }
        );

        $fight = new FightScheduler($fightRepository, $resultRepository, $this->createStub(EventDispatcherInterface::class))
            ->schedule(self::ONE, self::TWO, self::GAME_ID, 3, self::TOURNAMENT_ID);

        $this->assertSame(3, $fight->getTeamSize());
        $this->assertSame(self::TOURNAMENT_ID, $fight->getTournament()?->getValue());
        $this->assertCount(2, $results);
        $this->assertSame([self::ONE, self::TWO], array_map(static fn (Result $result): string => $result->getCompetitor()->getValue(), $results));
        $this->assertSame([ResultStatus::PENDING, ResultStatus::PENDING], array_map(static fn (Result $result): ResultStatus => $result->getStatus(), $results));
    }
}
