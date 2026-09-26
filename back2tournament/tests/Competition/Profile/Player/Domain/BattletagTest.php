<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Domain;

use App\Competition\Profile\Player\Domain\Entity\Battletag;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BattletagTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function acceptedBattletags(): iterable
    {
        yield 'the usual shape' => ['PlayerOne#1234'];
        yield 'a single character' => ['a'];
        yield 'the longest one the column holds' => [str_repeat('a', 255)];
    }

    #[DataProvider('acceptedBattletags')]
    public function test_a_battletag_is_kept_as_it_was_given(string $battletag): void
    {
        self::assertSame($battletag, new Battletag($battletag)->getValue());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyBattletags(): iterable
    {
        yield 'nothing at all' => [''];
        yield 'spaces only' => ['   '];
    }

    #[DataProvider('emptyBattletags')]
    public function test_an_empty_battletag_is_refused(string $battletag): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('cannot be empty');

        new Battletag($battletag);
    }

    public function test_a_battletag_longer_than_the_column_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('at most 255 characters');

        new Battletag(str_repeat('a', 256));
    }
}
