<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\DeclaredStatusValueObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeclaredStatusValueObjectTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function declarableOutcomes(): iterable
    {
        yield 'a win' => ['win'];
        yield 'a loss' => ['loss'];
        yield 'a draw' => ['draw'];
    }

    #[DataProvider('declarableOutcomes')]
    public function test_an_outcome_a_side_may_claim_is_accepted(string $status): void
    {
        self::assertSame($status, new DeclaredStatusValueObject($status)->getValue());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function undeclarableStatuses(): iterable
    {
        yield 'the state a fresh result starts in' => ['pending'];
        yield 'the state the domain sets while awaiting the opponent' => ['reporting'];
        yield 'a word that is not a status at all' => ['victory'];
        yield 'nothing' => [''];
        yield 'the right word in the wrong case' => ['WIN'];
    }

    #[DataProvider('undeclarableStatuses')]
    public function test_a_status_a_side_may_not_claim_is_refused(string $status): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('is not a declarable outcome');

        new DeclaredStatusValueObject($status);
    }

    public function test_the_refusal_names_the_offending_status(): void
    {
        $this->expectExceptionMessageIsOrContains('<reporting>');

        new DeclaredStatusValueObject('reporting');
    }
}
