<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Application;

use App\Competition\Fight\Application\Model\ConfirmFightResultsCommand;
use App\Competition\Fight\Application\Service\ConfirmFightResultsHandler;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ConfirmFightResultsHandlerTest extends TestCase
{
    private const FIGHT_ID = '11111111-1111-4111-8111-111111111111';
    private const RESULT_ONE_ID = '22222222-2222-4222-8222-222222222222';
    private const RESULT_TWO_ID = '55555555-5555-4555-8555-555555555555';
    private const COMPETITOR_ONE = '33333333-3333-4333-8333-333333333333';
    private const COMPETITOR_TWO = '44444444-4444-4444-8444-444444444444';
    private const GAME_ID = '66666666-6666-4666-8666-666666666666';

    public function test_a_fight_with_no_declaration_cannot_be_confirmed(): void
    {
        [$fight, $resultOne, $resultTwo] = $this->openFight();

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('no outcome has been declared');

        $this->handler($fight, $resultOne, $resultTwo)($this->command(self::COMPETITOR_TWO, self::COMPETITOR_ONE));
    }

    public function test_the_declaring_side_cannot_confirm_its_own_outcome(): void
    {
        [$fight, $resultOne, $resultTwo] = $this->declaredFight();

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('cannot confirm its own outcome');

        $this->handler($fight, $resultOne, $resultTwo)($this->command(self::COMPETITOR_ONE, self::COMPETITOR_TWO));
    }

    public function test_the_opposing_side_settles_the_outcome_from_the_scores(): void
    {
        [$fight, $resultOne, $resultTwo] = $this->declaredFight();

        $this->handler($fight, $resultOne, $resultTwo)($this->command(self::COMPETITOR_TWO, self::COMPETITOR_ONE));

        $this->assertSame(ResultStatus::LOSS, $resultOne->getStatus());
        $this->assertSame(ResultStatus::WIN, $resultTwo->getStatus());
        $this->assertNull($resultOne->getReportedStatus());
        $this->assertNull($resultTwo->getReportedStatus());
    }

    public function test_an_already_settled_fight_cannot_be_confirmed_again(): void
    {
        [$fight, $resultOne, $resultTwo] = $this->declaredFight();
        Fight::confirmResult($fight, $resultOne, ResultStatus::LOSS);
        Fight::confirmResult($fight, $resultTwo, ResultStatus::WIN);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('not awaiting a confirmation');

        $this->handler($fight, $resultOne, $resultTwo)($this->command(self::COMPETITOR_TWO, self::COMPETITOR_ONE));
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

    /**
     * Competitor one declared a 1-3 loss.
     *
     * @return array{Fight, Result, Result}
     */
    private function declaredFight(): array
    {
        [$fight, $resultOne, $resultTwo] = $this->openFight();

        Fight::updateResult($fight, $resultOne, 1, new CompetitorId(self::COMPETITOR_ONE), ResultStatus::LOSS);
        Fight::updateResult($fight, $resultTwo, 3, new CompetitorId(self::COMPETITOR_TWO), ResultStatus::WIN);
        $fight->setDeclaredBy(new CompetitorId(self::COMPETITOR_ONE));
        $fight->pullDomainEvents();

        return [$fight, $resultOne, $resultTwo];
    }

    private function handler(Fight $fight, Result $resultOne, Result $resultTwo): ConfirmFightResultsHandler
    {
        $fightRepository = $this->createStub(FightRepositoryInterface::class);
        $fightRepository->method('findOneBy')->willReturn($fight);

        $resultRepository = $this->createStub(ResultRepositoryInterface::class);
        $resultRepository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): Result => self::COMPETITOR_ONE === $criteria['competitor']
                ? $resultOne
                : $resultTwo
        );

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($this->createStub(SessionInterface::class));

        return new ConfirmFightResultsHandler(
            $resultRepository,
            $fightRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
            $requestStack,
        );
    }

    private function command(string $confirming, string $opponent): ConfirmFightResultsCommand
    {
        $command = new ConfirmFightResultsCommand();
        $command->setFight(self::FIGHT_ID);
        $command->setGame(self::GAME_ID);
        $command->setCompetitorOne($confirming);
        $command->setCompetitorTwo($opponent);

        return $command;
    }
}
