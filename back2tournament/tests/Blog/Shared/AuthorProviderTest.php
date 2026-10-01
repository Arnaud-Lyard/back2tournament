<?php

declare(strict_types=1);

namespace App\Tests\Blog\Shared;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Blog\Shared\Domain\Provider\AuthorProvider;
use PHPUnit\Framework\TestCase;

final class AuthorProviderTest extends TestCase
{
    private const DEMO_ID = '11111111-1111-4111-8111-111111111111';
    private const RIVAL_ID = '22222222-2222-4222-8222-222222222222';

    public function test_the_usernames_are_keyed_by_user_id_and_each_user_is_asked_for_once(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [self::DEMO_ID, self::RIVAL_ID]])
            ->willReturn([
                new User(self::DEMO_ID)->setUsername('demo'),
                new User(self::RIVAL_ID)->setUsername('rival'),
            ]);

        $this->assertSame(
            [self::DEMO_ID => 'demo', self::RIVAL_ID => 'rival'],
            new AuthorProvider($userRepository)->usernames([self::DEMO_ID, self::RIVAL_ID, self::DEMO_ID]),
        );
    }

    public function test_a_deleted_account_names_no_author(): void
    {
        $deleted = new User(self::RIVAL_ID)->setUsername('rival');
        User::erase($deleted, 'unusable-hash');

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findBy')->willReturn([new User(self::DEMO_ID)->setUsername('demo'), $deleted]);

        $this->assertSame([self::DEMO_ID => 'demo'], new AuthorProvider($userRepository)->usernames([self::DEMO_ID, self::RIVAL_ID]));
    }

    public function test_a_user_who_no_longer_exists_is_left_out(): void
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findBy')->willReturn([new User(self::DEMO_ID)->setUsername('demo')]);

        $this->assertSame([self::DEMO_ID => 'demo'], new AuthorProvider($userRepository)->usernames([self::DEMO_ID, self::RIVAL_ID]));
    }

    public function test_no_user_asks_for_nothing(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('findBy');

        $this->assertSame([], new AuthorProvider($userRepository)->usernames([]));
    }
}
