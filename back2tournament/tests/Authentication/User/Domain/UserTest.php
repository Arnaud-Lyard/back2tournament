<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Domain;

use App\Authentication\User\Domain\Entity\Email;
use App\Authentication\User\Domain\Entity\Locale;
use App\Authentication\User\Domain\Entity\Password;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Entity\Username;
use App\Authentication\User\Domain\Event\UserAvatarChangedEvent;
use App\Authentication\User\Domain\Event\UserCreatedEvent;
use App\Authentication\User\Domain\Event\UserDeletedEvent;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_register_user_ok(): void
    {
        $user = User::registerUser(
            new Email('arnaud@back2tournament.fr'),
            new Username('arnaud'),
            ['ROLE_ADMIN'],
            new Password('Password123!'),
            new Locale('fr'),
        );

        self::assertNotNull($user->getId());
        self::assertSame('arnaud@back2tournament.fr', $user->getEmail());
        self::assertSame('arnaud', $user->getUsername());
        self::assertSame('arnaud', $user->getUserIdentifier());
        self::assertSame('Password123!', $user->getPassword());
        self::assertEqualsCanonicalizing(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertFalse($user->isVerified());
        self::assertNotNull($user->getVerificationToken());
    }

    public function test_an_erased_account_keeps_nothing_that_names_its_owner_and_says_so(): void
    {
        $user = User::registerUser(
            new Email('arnaud@back2tournament.fr'),
            new Username('arnaud'),
            ['ROLE_EDITOR'],
            new Password('Password123!'),
            new Locale('fr'),
        );
        User::changeAvatar($user, 'avatars/arnaud.webp');
        $user->pullDomainEvents();
        $id = (string) $user->getId();

        User::erase($user, 'unusable-hash');

        self::assertSame("deleted-{$id}@deleted.invalid", $user->getEmail());
        self::assertSame("deleted-{$id}", $user->getUsername());
        self::assertSame('unusable-hash', $user->getPassword());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertNull($user->getAvatar());
        self::assertNull($user->getVerificationToken());
        self::assertFalse($user->isVerified());
        self::assertTrue($user->isDeleted());
        self::assertNotNull($user->getDeletedAt());

        $events = $user->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserDeletedEvent::class, $events[0]);
        self::assertSame($id, $events[0]->getUserId());
    }

    public function test_verify_email_marks_user_as_verified_and_clears_token(): void
    {
        $user = User::registerUser(
            new Email('arnaud@back2tournament.fr'),
            new Username('arnaud'),
            [],
            new Password('Password123!'),
            new Locale('fr'),
        );
        $user->setVerificationToken('11111111-1111-1111-1111-111111111111');

        $user->verifyEmail();

        self::assertTrue($user->isVerified());
        self::assertNull($user->getVerificationToken());
    }

    public function test_a_user_is_given_a_picture_and_loses_it_and_both_are_announced(): void
    {
        $user = new User('11111111-1111-4111-8111-111111111111');
        self::assertNull($user->getAvatar());

        User::changeAvatar($user, 'avatars/me.webp');
        self::assertSame('avatars/me.webp', $user->getAvatar());
        self::assertInstanceOf(UserAvatarChangedEvent::class, $user->pullDomainEvents()[0]);

        User::changeAvatar($user, null);
        self::assertNull($user->getAvatar());
        self::assertSame('11111111-1111-4111-8111-111111111111', $user->pullDomainEvents()[0]->getUserId());
    }

    public function test_the_created_event_carries_the_signup_language(): void
    {
        $user = User::registerUser(
            new Email('arnaud@back2tournament.fr'),
            new Username('arnaud'),
            [],
            new Password('Password123!'),
            new Locale('en'),
        );

        $events = $user->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UserCreatedEvent::class, $events[0]);
        self::assertSame('en', $events[0]->getLocale()->getValue());
        self::assertSame($user->getVerificationToken(), $events[0]->getVerificationToken());
    }
}
