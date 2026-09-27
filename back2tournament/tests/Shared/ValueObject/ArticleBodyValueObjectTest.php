<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use PHPUnit\Framework\TestCase;

final class ArticleBodyValueObjectTest extends TestCase
{
    public function test_the_body_is_kept_as_written(): void
    {
        $this->assertSame("  First line\n\nSecond line\n", new ArticleBodyValueObject("  First line\n\nSecond line\n")->getValue());
    }

    public function test_a_blank_body_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Article body cannot be empty');

        new ArticleBodyValueObject(" \n\t ");
    }
}
