<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Game\Domain;

use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Event\GameUpdatedEvent;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GameTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';

    public function test_a_game_lists_its_formats_once_and_in_order(): void
    {
        $game = Game::create(new GameId(self::GAME_ID), 'Rocket League', $this->sizes(3, 1, 2, 1));

        $this->assertSame([1, 2, 3], $game->getTeamSizes());
        $this->assertTrue($game->supportsTeamSize(2));
        $this->assertFalse($game->supportsTeamSize(5));
    }

    public function test_a_game_is_played_in_at_least_one_format(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('at least one format');

        Game::create(new GameId(self::GAME_ID), 'Valorant', []);
    }

    public function test_a_game_has_a_title(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('title cannot be empty');

        Game::create(new GameId(self::GAME_ID), '   ', $this->sizes(1));
    }

    public function test_an_update_keeps_what_it_does_not_name(): void
    {
        $game = Game::create(new GameId(self::GAME_ID), 'Valorant', $this->sizes(1));

        Game::update($game, null, $this->sizes(5));

        $this->assertSame('Valorant', $game->getTitle());
        $this->assertSame([5], $game->getTeamSizes());
    }

    public function test_an_update_is_announced(): void
    {
        $game = Game::create(new GameId(self::GAME_ID), 'Valorant', $this->sizes(1));
        $game->pullDomainEvents();

        Game::update($game, 'Valorant Champions', null);

        $events = $game->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GameUpdatedEvent::class, $events[0]);
        $this->assertSame('Valorant Champions', $game->getTitle());
    }

    #[DataProvider('refusedSizes')]
    public function test_a_format_counts_between_one_and_sixty_four_players(int $size): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains(\sprintf('<%d>', $size));

        new TeamSize($size);
    }

    public static function refusedSizes(): iterable
    {
        yield 'nobody' => [0];
        yield 'negative' => [-2];
        yield 'too many' => [65];
    }

    /**
     * @return list<TeamSize>
     */
    private function sizes(int ...$sizes): array
    {
        return array_map(static fn (int $size): TeamSize => new TeamSize($size), $sizes);
    }
}
