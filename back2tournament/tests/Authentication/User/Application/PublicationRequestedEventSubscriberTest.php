<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Event\OnPublicationRequestedUserVerifiedEvent;
use App\Authentication\User\Application\EventSubscriber\PublicationRequestedEventSubscriber;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Event\OnPublicationRequestedEvent;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PublicationRequestedEventSubscriberTest extends TestCase
{
    private const EDITOR_ID = '22222222-2222-2222-2222-222222222222';
    private const PLAIN_ID = '33333333-3333-3333-3333-333333333333';
    private const CREATED_ARTICLE = '{"title":"Patch notes","status":"draft"}';

    public function test_an_editor_request_goes_on_with_the_article_to_write(): void
    {
        $dispatched = null;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (object $event) use (&$dispatched): object {
                    $dispatched = $event;

                    return self::createsTheArticle($event);
                }
            );

        $subscriber = new PublicationRequestedEventSubscriber($this->currentUserProvider($this->editor()), $eventDispatcher);
        $subscriber->validateUser($this->requestedEvent());

        $this->assertInstanceOf(OnPublicationRequestedUserVerifiedEvent::class, $dispatched);
        $this->assertSame('Patch notes', $dispatched->getTitle());
        $this->assertSame('Body', $dispatched->getBody());
        $this->assertSame('news', $dispatched->getCategorySlug());
        $this->assertSame(self::EDITOR_ID, $dispatched->getAuthor());
        $this->assertSame('Patch notes EN', $dispatched->getTitleEn());
        $this->assertSame('Body EN', $dispatched->getBodyEn());
    }

    public function test_the_created_article_travels_back_on_the_requested_event(): void
    {
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(self::createsTheArticle(...));

        $event = $this->requestedEvent();
        new PublicationRequestedEventSubscriber($this->currentUserProvider($this->editor()), $eventDispatcher)->validateUser($event);

        $this->assertSame(self::CREATED_ARTICLE, $event->getCreatedArticle());
    }

    public function test_a_user_who_is_not_an_editor_stops_the_chain(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $subscriber = new PublicationRequestedEventSubscriber($this->currentUserProvider(new User(self::PLAIN_ID)), $eventDispatcher);

        $this->expectException(PermissionDeniedException::class);

        $subscriber->validateUser($this->requestedEvent());
    }

    private static function createsTheArticle(object $event): object
    {
        if ($event instanceof OnPublicationRequestedUserVerifiedEvent) {
            $event->setCreatedArticle(self::CREATED_ARTICLE);
        }

        return $event;
    }

    private function editor(): User
    {
        return new User(self::EDITOR_ID)->setRoles(['ROLE_EDITOR']);
    }

    private function requestedEvent(): OnPublicationRequestedEvent
    {
        return new OnPublicationRequestedEvent('Patch notes', 'Body', 'news', 'Patch notes EN', 'Body EN');
    }

    private function currentUserProvider(User $user): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn($user);
        $currentUserProvider
            ->method('isGranted')
            ->willReturnCallback(static fn (string $role): bool => \in_array($role, $user->getRoles(), true));

        return $currentUserProvider;
    }
}
