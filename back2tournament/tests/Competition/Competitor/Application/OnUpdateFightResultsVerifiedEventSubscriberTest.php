<?php

declare(strict_types=1);

namespace App\Tests\Competition\Competitor\Application;

use App\Authentication\User\Application\Event\OnUpdateFightResultsVerifiedEvent;
use App\Competition\Competitor\Application\Event\OnFightCompetitorResolvedEvent;
use App\Competition\Competitor\Application\EventSubscriber\OnUpdateFightResultsVerifiedEventSubscriber;
use App\Competition\Shared\Domain\Provider\CompetitorIdProviderInterface;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class OnUpdateFightResultsVerifiedEventSubscriberTest extends TestCase
{
    private const USER_ID = '00000000-0000-4000-8000-000000000000';
    private const FIGHT_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const COMPETITOR_ID = '33333333-3333-4333-8333-333333333333';
    private const OPPONENT_ID = '44444444-4444-4444-8444-444444444444';

    public function test_the_declaring_side_comes_first_and_the_opponent_second(): void
    {
        $competitorIdProvider = $this->createMock(CompetitorIdProviderInterface::class);
        $competitorIdProvider
            ->expects($this->once())
            ->method('byUserAndGame')
            ->with(self::USER_ID, self::GAME_ID)
            ->willReturn(self::COMPETITOR_ID);
        $competitorIdProvider
            ->expects($this->once())
            ->method('byOpponentInFight')
            ->with(self::USER_ID, self::GAME_ID, self::FIGHT_ID)
            ->willReturn(self::OPPONENT_ID);

        $dispatched = $this->dispatch($competitorIdProvider);

        $this->assertInstanceOf(OnFightCompetitorResolvedEvent::class, $dispatched);
        $this->assertSame(self::COMPETITOR_ID, $dispatched->getCompetitorOne());
        $this->assertSame(self::OPPONENT_ID, $dispatched->getCompetitorTwo());
    }

    public function test_the_fight_the_user_and_the_declared_outcome_are_carried_over(): void
    {
        $dispatched = $this->dispatch($this->competitorIdProvider());

        $this->assertInstanceOf(OnFightCompetitorResolvedEvent::class, $dispatched);
        $this->assertSame(self::FIGHT_ID, $dispatched->getFight());
        $this->assertSame(self::USER_ID, $dispatched->getUser());
        $this->assertSame('win', $dispatched->getCompetitorOneStatus());
        $this->assertSame('loss', $dispatched->getCompetitorTwoStatus());
    }

    public function test_the_scores_stay_integers_all_the_way(): void
    {
        $dispatched = $this->dispatch($this->competitorIdProvider());

        $this->assertInstanceOf(OnFightCompetitorResolvedEvent::class, $dispatched);
        $this->assertSame(3, $dispatched->getCompetitorOneScore());
        $this->assertSame(1, $dispatched->getCompetitorTwoScore());
    }

    public function test_a_user_without_a_competitor_in_that_game_stops_the_chain(): void
    {
        $competitorIdProvider = $this->createStub(CompetitorIdProviderInterface::class);
        $competitorIdProvider
            ->method('byUserAndGame')
            ->willThrowException(new NotFoundException('user has no player profile in game'));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $subscriber = new OnUpdateFightResultsVerifiedEventSubscriber($competitorIdProvider, $eventDispatcher);

        $this->expectException(NotFoundException::class);

        $subscriber->resolveCompetitor($this->verifiedEvent());
    }

    private function competitorIdProvider(): CompetitorIdProviderInterface
    {
        $competitorIdProvider = $this->createStub(CompetitorIdProviderInterface::class);
        $competitorIdProvider->method('byUserAndGame')->willReturn(self::COMPETITOR_ID);
        $competitorIdProvider->method('byOpponentInFight')->willReturn(self::OPPONENT_ID);

        return $competitorIdProvider;
    }

    private function dispatch(CompetitorIdProviderInterface $competitorIdProvider): ?object
    {
        $dispatched = null;

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched = $event;

                return $event;
            }
        );

        $subscriber = new OnUpdateFightResultsVerifiedEventSubscriber($competitorIdProvider, $eventDispatcher);

        $subscriber->resolveCompetitor($this->verifiedEvent());

        return $dispatched;
    }

    private function verifiedEvent(): OnUpdateFightResultsVerifiedEvent
    {
        return new OnUpdateFightResultsVerifiedEvent(
            self::FIGHT_ID,
            self::USER_ID,
            self::GAME_ID,
            'win',
            3,
            'loss',
            1,
        );
    }
}
