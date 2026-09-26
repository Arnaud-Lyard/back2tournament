<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnFightResultsConfirmationUserVerified;
use App\Competition\Competitor\Application\Event\OnFightResultConfirmationCompetitorResolvedEvent;
use App\Competition\Shared\Domain\Provider\CompetitorIdProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OnFightResultConfirmationEventSubscriber implements EventSubscriberInterface
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
            OnFightResultsConfirmationUserVerified::class => 'resolveCompetitor',
        ];
    }

    public function resolveCompetitor(OnFightResultsConfirmationUserVerified $event): void
    {
        $this->eventDispatcher->dispatch(new OnFightResultConfirmationCompetitorResolvedEvent(
            $event->getFight(),
            $event->getUser(),
            $event->getGame(),
            $this->competitorIdProvider->byUserAndGame($event->getUser(), $event->getGame()),
            $this->competitorIdProvider->byOpponentInFight($event->getUser(), $event->getGame(), $event->getFight()),
        ));
    }
}
