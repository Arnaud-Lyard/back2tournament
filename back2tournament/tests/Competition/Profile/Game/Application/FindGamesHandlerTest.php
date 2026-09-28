<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Game\Application;

use App\Competition\Profile\Game\Application\Model\FindGamesQuery;
use App\Competition\Profile\Game\Application\Service\FindGamesHandler;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Shared\ValueObject\TeamSizeValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FindGamesHandlerTest extends TestCase
{
    private const FIRST_GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const SECOND_GAME_ID = '22222222-2222-4222-8222-222222222222';

    public function test_games_are_asked_for_by_title(): void
    {
        $gameRepository = $this->createMock(GameRepositoryInterface::class);
        $gameRepository
            ->expects($this->once())
            ->method('findBy')
            ->with([], ['title' => 'ASC', 'id' => 'ASC'])
            ->willReturn([]);

        $handler = new FindGamesHandler($gameRepository, $this->createStub(NormalizerInterface::class));

        $handler(new FindGamesQuery());
    }

    public function test_every_game_read_is_listed_in_that_order(): void
    {
        $handler = new FindGamesHandler(
            $this->gameRepository([
                Game::create(new GameId(self::FIRST_GAME_ID), 'Overwatch', [new TeamSizeValueObject(5)]),
                Game::create(new GameId(self::SECOND_GAME_ID), 'StarCraft', [new TeamSizeValueObject(1)]),
            ]),
            $this->normalizer(),
        );

        $listed = json_decode($handler(new FindGamesQuery()), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                ['id' => ['value' => self::FIRST_GAME_ID], 'title' => 'Overwatch'],
                ['id' => ['value' => self::SECOND_GAME_ID], 'title' => 'StarCraft'],
            ],
            $listed,
        );
    }

    public function test_no_game_reads_as_an_empty_json_array(): void
    {
        $handler = new FindGamesHandler($this->gameRepository([]), $this->normalizer());

        $this->assertSame('[]', $handler(new FindGamesQuery()));
    }

    /** @param list<Game> $games */
    private function gameRepository(array $games): GameRepositoryInterface
    {
        $gameRepository = $this->createStub(GameRepositoryInterface::class);
        $gameRepository->method('findBy')->willReturn($games);

        return $gameRepository;
    }

    private function normalizer(): NormalizerInterface
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static fn (Game $game): array => [
                'id' => ['value' => $game->getId()->getValue()],
                'title' => $game->getTitle(),
            ]
        );

        return $normalizer;
    }
}
