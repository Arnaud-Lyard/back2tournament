<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\EventSubscriber;

use App\Authentication\User\Domain\Event\UserDeletedEvent;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserDeletedEventSubscriber implements EventSubscriberInterface
{
    private CommentRepositoryInterface $commentRepository;

    public function __construct(CommentRepositoryInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserDeletedEvent::class => 'removeComments',
        ];
    }

    public function removeComments(UserDeletedEvent $event): void
    {
        foreach ($this->commentRepository->findBy(['author' => $event->getUserId()]) as $comment) {
            $this->commentRepository->remove($comment);
        }
    }
}
