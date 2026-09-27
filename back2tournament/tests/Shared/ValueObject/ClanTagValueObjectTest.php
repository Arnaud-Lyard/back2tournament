<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ClanTagValueObject;
use PHPUnit\Framework\TestCase;

final class ClanTagValueObjectTest extends TestCase
{
    public function test_the_tag_is_trimmed_and_upper_cased(): void
    {
        $this->assertSame('DMO', new ClanTagValueObject(' dmo ')->getValue());
    }

    public function test_a_tag_of_one_character_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        new ClanTagValueObject('D');
    }

    public function test_a_tag_of_six_characters_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        new ClanTagValueObject('DEMOSQ');
    }

    public function test_a_tag_with_anything_but_letters_and_digits_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('must be 2 to 5 letters or digits');

        new ClanTagValueObject('D-M');
    }
}
