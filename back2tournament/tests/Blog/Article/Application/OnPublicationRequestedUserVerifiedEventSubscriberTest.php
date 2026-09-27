<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Authentication\User\Application\Event\OnPublicationRequestedUserVerifiedEvent;
use App\Blog\Article\Application\EventSubscriber\OnPublicationRequestedUserVerifiedEventSubscriber;
use App\Blog\Article\Application\Model\CreateArticleCommand;
use App\Blog\Shared\Domain\Provider\CategoryIdProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class OnPublicationRequestedUserVerifiedEventSubscriberTest extends TestCase
{
    private const EDITOR_ID = '11111111-1111-4111-8111-111111111111';
    private const CATEGORY_ID = '22222222-2222-4222-8222-222222222222';
    private const CREATED_ARTICLE = '{"title":"Notes de patch","status":"draft"}';

    public function test_the_verified_request_writes_the_article_in_both_languages(): void
    {
        $dispatched = null;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->willReturnCallback(
            static function (object $command) use (&$dispatched): Envelope {
                $dispatched = $command;

                return new Envelope($command, [new HandledStamp(self::CREATED_ARTICLE, 'CreateArticleHandler::__invoke')]);
            }
        );

        $categoryIdProvider = $this->createStub(CategoryIdProviderInterface::class);
        $categoryIdProvider->method('bySlug')->willReturn(self::CATEGORY_ID);

        $event = new OnPublicationRequestedUserVerifiedEvent('Notes de patch', 'Contenu', self::EDITOR_ID, 'news', 'Patch notes', 'Body');
        new OnPublicationRequestedUserVerifiedEventSubscriber($messageBus, $categoryIdProvider)->createArticle($event);

        $this->assertInstanceOf(CreateArticleCommand::class, $dispatched);
        $this->assertSame(
            ['Notes de patch', 'Contenu', self::CATEGORY_ID, 'Patch notes', 'Body'],
            [$dispatched->getTitle(), $dispatched->getBody(), $dispatched->getCategory(), $dispatched->getTitleEn(), $dispatched->getBodyEn()],
        );
        $this->assertSame(self::CREATED_ARTICLE, $event->getCreatedArticle());
    }
}
