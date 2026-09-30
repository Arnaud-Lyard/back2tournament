<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ApiTokenLifetimeValueObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApiTokenLifetimeValueObjectTest extends TestCase
{
    public function test_a_lifetime_holds_its_days(): void
    {
        $this->assertSame([1, 3650], [new ApiTokenLifetimeValueObject(1)->getValue(), new ApiTokenLifetimeValueObject(3650)->getValue()]);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidLifetimes(): iterable
    {
        yield 'no day' => [0];
        yield 'a negative count' => [-30];
        yield 'over ten years' => [3651];
    }

    #[DataProvider('invalidLifetimes')]
    public function test_a_lifetime_out_of_bounds_is_refused(int $days): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('An API token lasts from 1 to 3650 days');

        new ApiTokenLifetimeValueObject($days);
    }
}
