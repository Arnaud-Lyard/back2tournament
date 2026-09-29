<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Domain;

use App\Competition\Fight\Domain\Entity\Score;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScoreTest extends TestCase
{
    /** @return iterable<string, array{int}> */
    public static function acceptedScores(): iterable
    {
        yield 'a side that scored nothing' => [0];
        yield 'a single point' => [1];
        yield 'a long set' => [21];
    }

    #[DataProvider('acceptedScores')]
    public function test_zero_and_every_positive_score_is_accepted(int $score): void
    {
        self::assertSame($score, new Score($score)->getValue());
    }

    /** @return iterable<string, array{int}> */
    public static function refusedScores(): iterable
    {
        yield 'just below zero' => [-1];
        yield 'far below zero' => [-42];
    }

    #[DataProvider('refusedScores')]
    public function test_a_negative_score_is_refused(int $score): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('cannot be negative');

        new Score($score);
    }

    public function test_the_refusal_names_the_offending_score(): void
    {
        $this->expectExceptionMessageIsOrContains('<-7>');

        new Score(-7);
    }
}
