<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Domain;

use App\Authentication\User\Domain\Entity\Username;
use PHPUnit\Framework\TestCase;

final class UsernameTest extends TestCase
{
    public function test_accepts_a_valid_username(): void
    {
        $username = new Username('john_doe');

        self::assertSame('john_doe', $username->getValue());
    }

    public function test_rejects_username_that_is_too_short(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('at least 3 characters long');

        new Username('jo');
    }

    public function test_rejects_username_that_is_too_long(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('at most 30 characters long');

        new Username(str_repeat('a', 31));
    }

    public function test_rejects_username_with_invalid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('letters, numbers, underscores and hyphens');

        new Username('john doe!');
    }
}
