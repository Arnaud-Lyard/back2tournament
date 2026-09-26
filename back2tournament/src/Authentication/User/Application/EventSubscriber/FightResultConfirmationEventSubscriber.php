<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnFightResultsConfirmationUserVerified;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Event\OnFightResultConfirmationEvent;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FightResultConfirmationEventSubscriber implements EventSubscriberInterface
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
            OnFightResultConfirmationEvent::class => 'onFightResultConfirmation',
        ];
    }

    public function onFightResultConfirmation(OnFightResultConfirmationEvent $event): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$this->currentUserProvider->isGranted('ROLE_USER')) {
            throw new PermissionDeniedException('the user does not have the necessary permissions');
        }

        $verifiedEvent = $this->eventDispatcher->dispatch(new OnFightResultsConfirmationUserVerified(
            $event->getFight(),
            (string) $user->getId(),
        ));

        $event->setConfirmedFight($verifiedEvent->getConfirmedFight());
    }
}
