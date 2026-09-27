<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\EventSubscriber;

use App\Blog\Article\Application\Model\CreateArticleCommand;
use App\Blog\Shared\Domain\Provider\CategoryIdProviderInterface;
use App\Authentication\User\Application\Event\OnPublicationRequestedUserVerifiedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class OnPublicationRequestedUserVerifiedEventSubscriber implements EventSubscriberInterface
{
    use HandleTrait;

    private CategoryIdProviderInterface $categoryIdProvider;

    public function __construct(
        MessageBusInterface $messageBus,
        CategoryIdProviderInterface $categoryIdProvider
    ) {
        $this->messageBus = $messageBus;
        $this->categoryIdProvider = $categoryIdProvider;
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
        $createArticleCommand->setCategory(
            $this->categoryIdProvider->bySlug($event->getCategorySlug())
        );

        // A new article is a draft: whoever publishes it later becomes its author.
        $event->setCreatedArticle($this->handle($createArticleCommand));
    }
}
