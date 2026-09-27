<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ClanNameValueObject;
use PHPUnit\Framework\TestCase;

final class ClanNameValueObjectTest extends TestCase
{
    public function test_the_name_is_trimmed(): void
    {
        $this->assertSame('Demo Squad', new ClanNameValueObject('  Demo Squad  ')->getValue());
    }

    public function test_a_blank_name_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Clan name cannot be empty');

        new ClanNameValueObject('   ');
    }

    public function test_a_name_of_50_characters_is_accepted(): void
    {
        $this->assertSame(50, mb_strlen(new ClanNameValueObject(str_repeat('é', 50))->getValue()));
    }

    public function test_a_name_longer_than_50_characters_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Clan name must be at most 50 characters long');

        new ClanNameValueObject(str_repeat('a', 51));
    }
}
