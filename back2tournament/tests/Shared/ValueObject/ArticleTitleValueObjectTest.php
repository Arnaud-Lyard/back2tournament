<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ArticleTitleValueObject;
use PHPUnit\Framework\TestCase;

final class ArticleTitleValueObjectTest extends TestCase
{
    public function test_the_title_is_trimmed(): void
    {
        $this->assertSame('Patch notes 2.1', new ArticleTitleValueObject('  Patch notes 2.1  ')->getValue());
    }

    public function test_a_blank_title_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Article title cannot be empty');

        new ArticleTitleValueObject('   ');
    }

    public function test_a_title_of_255_characters_is_accepted(): void
    {
        $this->assertSame(255, mb_strlen(new ArticleTitleValueObject(str_repeat('é', 255))->getValue()));
    }

    public function test_a_title_longer_than_255_characters_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Article title must be at most 255 characters long');

        new ArticleTitleValueObject(str_repeat('a', 256));
    }
}
