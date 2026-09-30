<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Authentication\User\Application\Event\OnPublicationRequestedUserVerifiedEvent;
use App\Blog\Article\Application\EventSubscriber\OnPublicationRequestedUserVerifiedEventSubscriber;
use App\Blog\Article\Application\Model\CreateArticleCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class OnPublicationRequestedUserVerifiedEventSubscriberTest extends TestCase
{
    private const EDITOR_ID = '11111111-1111-4111-8111-111111111111';
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

        $event = new OnPublicationRequestedUserVerifiedEvent('Notes de patch', 'Contenu', self::EDITOR_ID, 'news', 'Patch notes', 'Body');
        new OnPublicationRequestedUserVerifiedEventSubscriber($messageBus)->createArticle($event);

        $this->assertInstanceOf(CreateArticleCommand::class, $dispatched);
        $this->assertSame(
            ['Notes de patch', 'Contenu', 'news', 'Patch notes', 'Body'],
            [$dispatched->getTitle(), $dispatched->getBody(), $dispatched->getCategorySlug(), $dispatched->getTitleEn(), $dispatched->getBodyEn()],
        );
        $this->assertSame(self::CREATED_ARTICLE, $event->getCreatedArticle());
    }
}
