<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamNameValueObject;
use PHPUnit\Framework\TestCase;

final class TeamNameValueObjectTest extends TestCase
{
    public function test_the_name_is_trimmed(): void
    {
        $this->assertSame('Falcons Duo', new TeamNameValueObject('  Falcons Duo  ')->getValue());
    }

    public function test_a_blank_name_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Team name cannot be empty');

        new TeamNameValueObject('   ');
    }

    public function test_a_name_of_50_characters_is_accepted(): void
    {
        $this->assertSame(50, mb_strlen(new TeamNameValueObject(str_repeat('é', 50))->getValue()));
    }

    public function test_a_name_longer_than_50_characters_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Team name must be at most 50 characters long');

        new TeamNameValueObject(str_repeat('a', 51));
    }
}
