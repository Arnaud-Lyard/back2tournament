<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnUpdateFightResultsVerifiedEvent;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Event\OnUpdateFightResultsEvent;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateFightResultsEventSubscriber implements EventSubscriberInterface
{
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnUpdateFightResultsEvent::class => 'onUpdateFightResults',
        ];
    }

    public function onUpdateFightResults(OnUpdateFightResultsEvent $event): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$this->currentUserProvider->isGranted('ROLE_USER')) {
            throw new PermissionDeniedException('the user does not have the necessary permissions');
        }

        $this->eventDispatcher->dispatch(new OnUpdateFightResultsVerifiedEvent(
            $event->getFight(),
            (string) $user->getId(),
            $event->getGame(),
            $event->getCompetitorOneStatus(),
            $event->getCompetitorOneScore(),
            $event->getCompetitorTwoStatus(),
            $event->getCompetitorTwoScore()
        ));
    }
}
