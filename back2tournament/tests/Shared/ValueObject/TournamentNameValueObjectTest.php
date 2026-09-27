<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TournamentNameValueObject;
use PHPUnit\Framework\TestCase;

final class TournamentNameValueObjectTest extends TestCase
{
    public function test_the_name_is_trimmed(): void
    {
        $this->assertSame('Coupe d\'automne', new TournamentNameValueObject('  Coupe d\'automne  ')->getValue());
    }

    public function test_a_blank_name_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Tournament name cannot be empty');

        new TournamentNameValueObject('   ');
    }

    public function test_a_name_of_100_characters_is_accepted(): void
    {
        $this->assertSame(100, mb_strlen(new TournamentNameValueObject(str_repeat('é', 100))->getValue()));
    }

    public function test_a_name_longer_than_100_characters_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Tournament name must be at most 100 characters long');

        new TournamentNameValueObject(str_repeat('a', 101));
    }
}
