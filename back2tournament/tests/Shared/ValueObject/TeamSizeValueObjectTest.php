<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;
use PHPUnit\Framework\TestCase;

final class TeamSizeValueObjectTest extends TestCase
{
    public function test_one_player_per_side_is_a_duel(): void
    {
        $this->assertSame(1, new TeamSizeValueObject(1)->getValue());
    }

    public function test_the_largest_format_is_accepted(): void
    {
        $this->assertSame(TeamSizeValueObject::MAX, new TeamSizeValueObject(TeamSizeValueObject::MAX)->getValue());
    }

    public function test_no_player_per_side_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('The team size <0> must be between 1 and 64');

        new TeamSizeValueObject(0);
    }

    public function test_more_players_than_the_largest_format_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        new TeamSizeValueObject(TeamSizeValueObject::MAX + 1);
    }
}
