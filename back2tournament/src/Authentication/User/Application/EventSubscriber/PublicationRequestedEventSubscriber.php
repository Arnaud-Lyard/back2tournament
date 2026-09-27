<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnPublicationRequestedUserVerifiedEvent;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Event\OnPublicationRequestedEvent;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PublicationRequestedEventSubscriber implements EventSubscriberInterface
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
            OnPublicationRequestedEvent::class => 'validateUser',
        ];
    }

    public function validateUser(OnPublicationRequestedEvent $event): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$this->currentUserProvider->isGranted('ROLE_EDITOR')) {
            throw new PermissionDeniedException('the author does not have the necessary permissions');
        }

        $verifiedEvent = $this->eventDispatcher->dispatch(new OnPublicationRequestedUserVerifiedEvent(
            $event->getTitle(),
            $event->getBody(),
            (string) $user->getId(),
            $event->getCategorySlug()
        ));

        $event->setCreatedArticle($verifiedEvent->getCreatedArticle());
    }
}
