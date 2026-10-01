<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Infrastructure;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Infrastructure\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    public function test_a_deleted_account_never_signs_in(): void
    {
        $user = new User('11111111-1111-4111-8111-111111111111')->setUsername('rival');
        $user->setVerified(true);
        User::erase($user, 'unusable-hash');

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('this account was deleted');

        new UserChecker()->checkPreAuth($user);
    }

    public function test_an_unverified_account_is_asked_to_verify_its_email(): void
    {
        $user = new User('11111111-1111-4111-8111-111111111111')->setUsername('rival');
        $user->setVerified(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('please verify your email before logging in');

        new UserChecker()->checkPreAuth($user);
    }
}
