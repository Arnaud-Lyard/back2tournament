<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Application;

use App\Competition\Fight\Application\Model\UpdateFightResultsCommand;
use App\Competition\Fight\Application\Service\UpdateFightResultsHandler;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UpdateFightResultsHandlerTest extends TestCase
{
    private const FIGHT_ID = '11111111-1111-4111-8111-111111111111';
    private const RESULT_ONE_ID = '22222222-2222-4222-8222-222222222222';
    private const RESULT_TWO_ID = '55555555-5555-4555-8555-555555555555';
    private const COMPETITOR_ONE = '33333333-3333-4333-8333-333333333333';
    private const COMPETITOR_TWO = '44444444-4444-4444-8444-444444444444';

    public function test_the_declared_outcome_lands_on_the_result_as_the_reported_status(): void
    {
        [$fight, $resultOne, $resultTwo] = $this->openFight();

        $this->handler($fight, $resultOne, $resultTwo)($this->command('win', 3, 'loss', 1));

        $this->assertSame(ResultStatus::WIN, $resultOne->getReportedStatus());
        $this->assertSame(ResultStatus::LOSS, $resultTwo->getReportedStatus());
        $this->assertSame(ResultStatus::REPORTING, $resultOne->getStatus());
        $this->assertSame(3, $resultOne->getScore());
        $this->assertSame(1, $resultTwo->getScore());
    }

    public function test_the_declaring_side_is_recorded_on_the_fight(): void
    {
        [$fight, $resultOne, $resultTwo] = $this->openFight();

        $this->handler($fight, $resultOne, $resultTwo)($this->command('win', 3, 'loss', 1));

        $this->assertSame(self::COMPETITOR_ONE, $fight->getDeclaredBy()?->getValue());
    }

    /**
     * Which statuses are declarable is a rule of `DeclaredStatus`, tested there.
     * What matters here is that the handler builds it before touching the
     * database, so a bad payload costs no query.
     */
    public function test_an_invalid_status_costs_no_query(): void
    {
        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->never())->method('findOneBy');
        $fightRepository->expects($this->never())->method('save');

        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository->expects($this->never())->method('findOneBy');
        $resultRepository->expects($this->never())->method('save');

        $handler = new UpdateFightResultsHandler(
            $fightRepository,
            $resultRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
            $this->requestStack(),
        );

        $this->expectException(ValidationException::class);

        $handler($this->command('victory', 3, 'loss', 1));
    }

    public function test_an_unknown_fight_stops_the_handler(): void
    {
        $fightRepository = $this->createStub(FightRepositoryInterface::class);
        $fightRepository->method('findOneBy')->willReturn(null);

        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository->expects($this->never())->method('save');

        $handler = new UpdateFightResultsHandler(
            $fightRepository,
            $resultRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
            $this->requestStack(),
        );

        $this->expectException(NotFoundException::class);

        $handler($this->command('win', 3, 'loss', 1));
    }

    /**
     * Guards the copy-paste that had the second result loaded from the fight
     * repository: the fight is read once, and both results come from the result
     * repository.
     */
    public function test_each_aggregate_is_read_from_its_own_repository(): void
    {
        [$fight, $resultOne, $resultTwo] = $this->openFight();

        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => self::FIGHT_ID])
            ->willReturn($fight);

        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(
                static fn (array $criteria): Result => self::COMPETITOR_ONE === $criteria['competitor']
                    ? $resultOne
                    : $resultTwo
            );

        $handler = new UpdateFightResultsHandler(
            $fightRepository,
            $resultRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
            $this->requestStack(),
        );

        $handler($this->command('win', 3, 'loss', 1));
    }

    /**
     * @return array{Fight, Result, Result}
     */
    private function openFight(): array
    {
        $fight = Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::COMPETITOR_ONE),
            new CompetitorId(self::COMPETITOR_TWO),
        );

        $resultOne = Fight::createResult($fight, new ResultId(self::RESULT_ONE_ID), new CompetitorId(self::COMPETITOR_ONE));
        $resultTwo = Fight::createResult($fight, new ResultId(self::RESULT_TWO_ID), new CompetitorId(self::COMPETITOR_TWO));
        $fight->pullDomainEvents();

        return [$fight, $resultOne, $resultTwo];
    }

    private function handler(Fight $fight, Result $resultOne, Result $resultTwo): UpdateFightResultsHandler
    {
        $fightRepository = $this->createStub(FightRepositoryInterface::class);
        $fightRepository->method('findOneBy')->willReturn($fight);

        $resultRepository = $this->createStub(ResultRepositoryInterface::class);
        $resultRepository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): Result => self::COMPETITOR_ONE === $criteria['competitor']
                ? $resultOne
                : $resultTwo
        );

        return new UpdateFightResultsHandler(
            $fightRepository,
            $resultRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
            $this->requestStack(),
        );
    }

    private function command(
        string $statusOne,
        int $scoreOne,
        string $statusTwo,
        int $scoreTwo,
    ): UpdateFightResultsCommand {
        $command = new UpdateFightResultsCommand();
        $command->setFight(self::FIGHT_ID);
        $command->setUser('66666666-6666-4666-8666-666666666666');
        $command->setCompetitorOne(self::COMPETITOR_ONE);
        $command->setCompetitorTwo(self::COMPETITOR_TWO);
        $command->setCompetitorOneStatus($statusOne);
        $command->setCompetitorOneScore($scoreOne);
        $command->setCompetitorTwoStatus($statusTwo);
        $command->setCompetitorTwoScore($scoreTwo);

        return $command;
    }

    private function requestStack(): RequestStack
    {
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($this->createStub(SessionInterface::class));

        return $requestStack;
    }
}
