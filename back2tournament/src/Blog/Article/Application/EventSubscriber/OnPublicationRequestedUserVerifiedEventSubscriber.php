<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\EventSubscriber;

use App\Blog\Article\Application\Model\CreateArticleCommand;
use App\Authentication\User\Application\Event\OnPublicationRequestedUserVerifiedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class OnPublicationRequestedUserVerifiedEventSubscriber implements EventSubscriberInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnPublicationRequestedUserVerifiedEvent::class => 'createArticle',
        ];
    }

    public function createArticle(OnPublicationRequestedUserVerifiedEvent $event): void
    {
        $createArticleCommand = new CreateArticleCommand();
        $createArticleCommand->setTitle($event->getTitle());
        $createArticleCommand->setBody($event->getBody());
        $createArticleCommand->setTitleEn($event->getTitleEn());
        $createArticleCommand->setBodyEn($event->getBodyEn());
        $createArticleCommand->setCategorySlug($event->getCategorySlug());

        $event->setCreatedArticle($this->handle($createArticleCommand));
    }
}
