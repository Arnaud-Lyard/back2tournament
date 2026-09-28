<?php

declare(strict_types=1);

namespace App\Tests\Media\Image\Infrastructure;

use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Media\Image\Infrastructure\Serializer\StoredImageNormalizer;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use App\Shared\ValueObject\TeamSizeValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class StoredImageNormalizerTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';

    public function test_a_stored_image_reads_as_where_it_is_served(): void
    {
        $game = $this->game();
        Game::illustrate($game, 'games/rocket-league.webp');

        $normalized = $this->serializer()->normalize($game);

        $this->assertSame('https://images.back2tournament.fr/games/rocket-league.webp', $normalized['image']);
        $this->assertSame(['Rocket League', ['value' => self::GAME_ID], [1, 2]], [$normalized['title'], $normalized['id'], $normalized['teamSizes']]);
    }

    public function test_no_image_reads_as_null(): void
    {
        $this->assertNull($this->serializer()->normalize($this->game())['image']);
    }

    public function test_an_object_without_a_stored_image_is_left_to_the_other_normalizers(): void
    {
        $normalizer = new StoredImageNormalizer($this->createStub(ImageProviderInterface::class));

        $this->assertFalse($normalizer->supportsNormalization(new GameId(self::GAME_ID)));
        $this->assertFalse($normalizer->supportsNormalization('games/rocket-league.webp'));
        $this->assertTrue($normalizer->supportsNormalization($this->game()));
    }

    private function serializer(): Serializer
    {
        $imageProvider = $this->createStub(ImageProviderInterface::class);
        $imageProvider->method('url')->willReturnCallback(
            static fn (?string $key): ?string => null === $key ? null : 'https://images.back2tournament.fr/'.$key
        );

        return new Serializer([new StoredImageNormalizer($imageProvider), new DateTimeNormalizer(), new ObjectNormalizer()]);
    }

    private function game(): Game
    {
        return Game::create(new GameId(self::GAME_ID), 'Rocket League', [new TeamSizeValueObject(1), new TeamSizeValueObject(2)]);
    }
}
