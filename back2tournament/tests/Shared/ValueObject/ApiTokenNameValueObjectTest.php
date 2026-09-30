<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ApiTokenNameValueObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApiTokenNameValueObjectTest extends TestCase
{
    public function test_the_name_is_trimmed_and_lower_cased(): void
    {
        $this->assertSame('hermes-news_2', new ApiTokenNameValueObject(' Hermes-News_2 ')->getValue());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNames(): iterable
    {
        yield 'one character' => ['h'];
        yield 'forty-one characters' => [str_repeat('h', 41)];
        yield 'a space inside' => ['hermes bot'];
        yield 'a leading hyphen' => ['-hermes'];
        yield 'an accent' => ['hermès'];
    }

    #[DataProvider('invalidNames')]
    public function test_a_name_breaking_the_rule_is_refused(string $name): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('must be 2 to 40 letters, digits, hyphens or underscores');

        new ApiTokenNameValueObject($name);
    }
}
