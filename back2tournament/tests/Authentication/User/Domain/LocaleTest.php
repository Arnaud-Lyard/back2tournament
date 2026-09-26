<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Domain;

use App\Authentication\User\Domain\Entity\Locale;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class LocaleTest extends TestCase
{
    public function test_a_supported_tag_gives_its_locale(): void
    {
        self::assertSame('en', new Locale('en')->getValue());
        self::assertSame('fr', new Locale('fr')->getValue());
    }

    public function test_an_unsupported_tag_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        new Locale('de');
    }
}
