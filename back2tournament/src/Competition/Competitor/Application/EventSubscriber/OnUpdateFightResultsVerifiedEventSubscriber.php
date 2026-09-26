<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnUpdateFightResultsVerifiedEvent;
use App\Competition\Competitor\Application\Event\OnFightCompetitorResolvedEvent;
use App\Competition\Shared\Domain\Provider\CompetitorIdProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OnUpdateFightResultsVerifiedEventSubscriber implements EventSubscriberInterface
{
    private CompetitorIdProviderInterface $competitorIdProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CompetitorIdProviderInterface $competitorIdProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->competitorIdProvider = $competitorIdProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnUpdateFightResultsVerifiedEvent::class => 'resolveCompetitor',
        ];
    }

    public function resolveCompetitor(OnUpdateFightResultsVerifiedEvent $event): void
    {
        $this->eventDispatcher->dispatch(new OnFightCompetitorResolvedEvent(
            $event->getFight(),
            $event->getUser(),
            $this->competitorIdProvider->byUserAndGame($event->getUser(), $event->getGame()),
            $this->competitorIdProvider->byOpponentInFight($event->getUser(), $event->getGame(), $event->getFight()),
            $event->getCompetitorOneStatus(),
            $event->getCompetitorOneScore(),
            $event->getCompetitorTwoStatus(),
            $event->getCompetitorTwoScore(),
        ));
    }
}
