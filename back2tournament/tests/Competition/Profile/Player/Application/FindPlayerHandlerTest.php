<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Application;

use App\Competition\Profile\Player\Application\Model\FindPlayerQuery;
use App\Competition\Profile\Player\Application\Service\FindPlayerHandler;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FindPlayerHandlerTest extends TestCase
{
    private const PLAYER_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const USER_ID = '33333333-3333-4333-8333-333333333333';

    public function test_the_profile_is_looked_up_by_its_id(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => self::PLAYER_ID])
            ->willReturn($this->player());

        $handler = new FindPlayerHandler($playerRepository, $this->normalizer());

        $handler(new FindPlayerQuery(self::PLAYER_ID));
    }

    public function test_the_profile_read_is_the_one_answered(): void
    {
        $handler = new FindPlayerHandler($this->playerRepository($this->player()), $this->normalizer());

        $answered = json_decode($handler(new FindPlayerQuery(self::PLAYER_ID)), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            ['id' => ['value' => self::PLAYER_ID], 'battletag' => 'Alpha#1234'],
            $answered,
        );
    }

    public function test_an_unknown_profile_is_not_found(): void
    {
        $handler = new FindPlayerHandler($this->playerRepository(null), $this->normalizer());

        $this->expectException(NotFoundException::class);

        $handler(new FindPlayerQuery(self::PLAYER_ID));
    }

    private function playerRepository(?Player $player): PlayerRepositoryInterface
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($player);

        return $playerRepository;
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

    private function player(): Player
    {
        return Player::create(
            new PlayerId(self::PLAYER_ID),
            'Alpha#1234',
            new GameId(self::GAME_ID),
            new UserId(self::USER_ID),
        );
    }
}
