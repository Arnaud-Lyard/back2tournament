<?php

declare(strict_types=1);

namespace App\Tests\Competition\Ranking\Application;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Ranking\Application\Model\RateFightCommand;
use App\Competition\Ranking\Application\Service\RebuildRankingsService;
use App\Competition\Ranking\Domain\Repository\RatingChangeRepositoryInterface;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Shared\ValueObject\TeamSizeValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class RebuildRankingsServiceTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const EARLIER_FIGHT = '22222222-2222-4222-8222-222222222222';
    private const LATER_FIGHT = '33333333-3333-4333-8333-333333333333';

    public function test_the_rankings_are_emptied_then_every_settled_fight_is_counted_once_in_the_order_it_was_settled(): void
    {
        $steps = [];

        $ratingChangeRepository = $this->createMock(RatingChangeRepositoryInterface::class);
        $ratingChangeRepository->expects($this->once())->method('removeAll')->willReturnCallback(static function () use (&$steps): void {
            $steps[] = 'forget the counted fights';
        });

        $ratingRepository = $this->createMock(RatingRepositoryInterface::class);
        $ratingRepository->expects($this->once())->method('removeAll')->willReturnCallback(static function () use (&$steps): void {
            $steps[] = 'empty the rankings';
        });
        $ratingRepository->method('count')->willReturn(4);

        // Settled results, oldest first: each fight shows twice.
        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['status' => [ResultStatus::WIN, ResultStatus::LOSS, ResultStatus::DRAW]], ['updatedAt' => 'ASC', 'id' => 'ASC'])
            ->willReturn([
                $this->resultOf(self::EARLIER_FIGHT),
                $this->resultOf(self::EARLIER_FIGHT),
                $this->resultOf(self::LATER_FIGHT),
                $this->resultOf(self::LATER_FIGHT),
            ]);

        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(static function (object $message) use (&$steps): Envelope {
            $steps[] = $message instanceof RateFightCommand ? 'rate '.$message->getFightId() : $message::class;

            return new Envelope($message);
        });

        $rebuilt = new RebuildRankingsService($ratingRepository, $ratingChangeRepository, $resultRepository, $messageBus)->rebuild();

        $this->assertSame(
            ['forget the counted fights', 'empty the rankings', 'rate '.self::EARLIER_FIGHT, 'rate '.self::LATER_FIGHT],
            $steps,
        );
        $this->assertSame(['fights' => 2, 'ratings' => 4], $rebuilt);
    }

    private function resultOf(string $fightId): Result
    {
        $fight = Fight::create(new FightId($fightId), new CompetitorId(Uuid::v4()->toString()), new CompetitorId(Uuid::v4()->toString()), new GameId(self::GAME_ID), new TeamSizeValueObject(1));

        return Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), $fight->getCompetitorOne());
    }
}
