<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Application;

use App\Competition\Profile\Player\Application\Model\FindGamePlayersQuery;
use App\Competition\Profile\Player\Application\Service\FindGamePlayersHandler;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\ClanTagProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FindGamePlayersHandlerTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const FIRST_PLAYER_ID = '22222222-2222-4222-8222-222222222222';
    private const SECOND_PLAYER_ID = '33333333-3333-4333-8333-333333333333';
    private const USER_ID = '44444444-4444-4444-8444-444444444444';
    private const CLAN_ID = '55555555-5555-4555-8555-555555555555';

    public function test_the_requested_game_the_search_and_the_offset_are_passed_to_the_repository(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository
            ->expects($this->once())
            ->method('findPage')
            ->with(self::GAME_ID, 'alpha', 10, 20)
            ->willReturn([]);
        $playerRepository
            ->expects($this->once())
            ->method('countPage')
            ->with(self::GAME_ID, 'alpha')
            ->willReturn(0);

        $handler = new FindGamePlayersHandler($playerRepository, $this->clans([]), $this->createStub(NormalizerInterface::class));

        $handler(new FindGamePlayersQuery(self::GAME_ID, 3, 10, 'alpha'));
    }

    public function test_every_player_read_is_listed_in_that_order(): void
    {
        $handler = new FindGamePlayersHandler(
            $this->playerRepository([
                $this->player(self::FIRST_PLAYER_ID, 'Alpha#1234'),
                $this->player(self::SECOND_PLAYER_ID, 'Bravo#5678'),
            ], 2),
            $this->clans([]),
            $this->normalizer(),
        );

        $page = $this->read($handler(new FindGamePlayersQuery(self::GAME_ID, 1, 10)));

        $this->assertSame(
            [
                ['id' => ['value' => self::FIRST_PLAYER_ID], 'battletag' => 'Alpha#1234', 'clanTag' => null],
                ['id' => ['value' => self::SECOND_PLAYER_ID], 'battletag' => 'Bravo#5678', 'clanTag' => null],
            ],
            $page['items'],
        );
    }

    public function test_a_profile_in_a_clan_is_listed_with_the_tag_of_its_clan(): void
    {
        $handler = new FindGamePlayersHandler(
            $this->playerRepository([
                $this->player(self::FIRST_PLAYER_ID, 'Alpha#1234'),
                $this->player(self::SECOND_PLAYER_ID, 'Bravo#5678'),
            ], 2),
            $this->clans([self::FIRST_PLAYER_ID => ['id' => self::CLAN_ID, 'tag' => 'B2T']]),
            $this->normalizer(),
        );

        $page = $this->read($handler(new FindGamePlayersQuery(self::GAME_ID, 1, 10)));

        $this->assertSame('B2T', $page['items'][0]['clanTag']);
        $this->assertNull($page['items'][1]['clanTag']);
    }

    public function test_the_page_carries_the_total_and_how_many_pages_it_yields(): void
    {
        $handler = new FindGamePlayersHandler(
            $this->playerRepository([$this->player(self::FIRST_PLAYER_ID, 'Alpha#1234')], 42),
            $this->clans([]),
            $this->normalizer(),
        );

        $page = $this->read($handler(new FindGamePlayersQuery(self::GAME_ID, 2, 10)));

        $this->assertSame(42, $page['total']);
        $this->assertSame(2, $page['page']);
        $this->assertSame(10, $page['limit']);
        $this->assertSame(5, $page['pages']);
    }

    public function test_a_game_without_player_reads_as_an_empty_page(): void
    {
        $handler = new FindGamePlayersHandler($this->playerRepository([], 0), $this->clans([]), $this->normalizer());

        $page = $this->read($handler(new FindGamePlayersQuery(self::GAME_ID, 1, 10)));

        $this->assertSame([], $page['items']);
        $this->assertSame(0, $page['total']);
        $this->assertSame(0, $page['pages']);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, limit: int, pages: int}
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<Player> $players
     */
    private function playerRepository(array $players, int $total): PlayerRepositoryInterface
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findPage')->willReturn($players);
        $playerRepository->method('countPage')->willReturn($total);

        return $playerRepository;
    }

    /**
     * @param array<string, array{id: string, tag: string}> $clans
     */
    private function clans(array $clans): ClanTagProviderInterface
    {
        $clanTagProvider = $this->createStub(ClanTagProviderInterface::class);
        $clanTagProvider->method('clansOfPlayers')->willReturn($clans);

        return $clanTagProvider;
    }

    private function normalizer(): NormalizerInterface
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static fn (Player $player): array => [
                'id' => ['value' => $player->getId()->getValue()],
                'battletag' => $player->getBattletag(),
            ]
        );

        return $normalizer;
    }

    private function player(string $id, string $battletag): Player
    {
        return Player::create(new PlayerId($id), $battletag, new GameId(self::GAME_ID), new UserId(self::USER_ID));
    }
}
