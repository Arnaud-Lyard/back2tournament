<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Domain;

use App\Authentication\User\Domain\Entity\Password;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    public function test_accepts_a_valid_password(): void
    {
        $password = new Password('Password123!');

        self::assertSame('Password123!', $password->getValue());
    }

    public function test_rejects_password_that_is_too_short(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('at least 8 characters long');

        new Password('Pass1!');
    }

    public function test_rejects_password_without_digit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('at least one digit');

        new Password('Password!');
    }

    public function test_rejects_password_without_capital_letter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('at least one Capital Letter');

        new Password('password123!');
    }

    public function test_rejects_password_without_small_letter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('at least one small Letter');

        new Password('PASSWORD123!');
    }

    public function test_rejects_password_without_special_character(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('at least one special character');

        new Password('Password123');
    }

    public function test_rejects_password_with_white_space(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('should not contain any white space');

        new Password('Password 123!');
    }
}
