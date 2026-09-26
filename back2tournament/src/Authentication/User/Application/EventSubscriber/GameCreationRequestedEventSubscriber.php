<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnGameCreationAdminVerifiedEvent;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Game\Application\Event\OnGameCreationRequestedEvent;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GameCreationRequestedEventSubscriber implements EventSubscriberInterface
{
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnGameCreationRequestedEvent::class => 'validateUser',
        ];
    }

    public function validateUser(OnGameCreationRequestedEvent $event): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$this->currentUserProvider->isGranted('ROLE_ADMIN')) {
            throw new PermissionDeniedException('the user does not have the necessary permissions');
        }

        $verifiedEvent = $this->eventDispatcher->dispatch(new OnGameCreationAdminVerifiedEvent(
            $event->getTitle(),
            (string) $user->getId(),
            $event->getTeamSizes(),
        ));

        $event->setCreatedGame($verifiedEvent->getCreatedGame());
    }
}
