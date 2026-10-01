<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Authentication\User\Domain\Event\UserDeletedEvent;
use App\Blog\Article\Application\EventSubscriber\UserDeletedEventSubscriber;
use App\Blog\Article\Domain\Entity\Comment;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class UserDeletedEventSubscriberTest extends TestCase
{
    private const USER_ID = '11111111-1111-4111-8111-111111111111';

    public function test_the_comments_of_a_deleted_account_are_removed(): void
    {
        $comments = [$this->createStub(Comment::class), $this->createStub(Comment::class)];
        $removed = [];

        $commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $commentRepository->expects($this->once())->method('findBy')->with(['author' => self::USER_ID])->willReturn($comments);
        $commentRepository->method('remove')->willReturnCallback(
            static function (Comment $comment) use (&$removed): void {
                $removed[] = $comment;
            }
        );

        new UserDeletedEventSubscriber($commentRepository)->removeComments(new UserDeletedEvent(self::USER_ID));

        $this->assertSame($comments, $removed);
    }
}
