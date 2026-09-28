<?php

declare(strict_types=1);

namespace App\Tests\Media\Shared;

use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Image\Domain\Service\ImageCompressorInterface;
use App\Media\Image\Domain\Service\ImageStorageInterface;
use App\Media\Image\Domain\ValueObject\CompressedImage;
use App\Media\Shared\Domain\Provider\ImageProvider;
use App\Shared\ValueObject\UploadedImageValueObject;
use PHPUnit\Framework\TestCase;

final class ImageProviderTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAYCAIAAAAUMWhjAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAJklEQVRIiWM8oaHBQEvARFPTRy0YtWDUglELRi0YtWDUglELqAYA1J4BSIDvLE0AAAAASUVORK5CYII=';

    public function test_an_image_is_compressed_for_its_kind_and_stored_under_a_key_of_its_own(): void
    {
        $upload = new UploadedImageValueObject(base64_decode(self::PNG, true));

        $compressor = $this->createMock(ImageCompressorInterface::class);
        $compressor->expects($this->once())->method('compress')->with($upload, ImageKind::AVATAR)
            ->willReturn(new CompressedImage('webp bytes', 'image/webp', 'webp', 24, 24));

        $stored = [];
        $storage = $this->createStub(ImageStorageInterface::class);
        $storage->method('put')->willReturnCallback(static function (string $key, string $content, string $type) use (&$stored): void {
            $stored[] = [$key, $content, $type];
        });

        $provider = new ImageProvider($compressor, $storage, 'http://localhost:3902');
        $key = $provider->store($upload, ImageKind::AVATAR);

        $this->assertMatchesRegularExpression('#^avatars/[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\.webp$#', $key);
        $this->assertSame([[$key, 'webp bytes', 'image/webp']], $stored);
    }

    public function test_two_images_never_share_a_key(): void
    {
        $compressor = $this->createStub(ImageCompressorInterface::class);
        $compressor->method('compress')->willReturn(new CompressedImage('webp bytes', 'image/webp', 'webp', 32, 24));
        $provider = new ImageProvider($compressor, $this->createStub(ImageStorageInterface::class), 'http://localhost:3902');
        $upload = new UploadedImageValueObject(base64_decode(self::PNG, true));

        $this->assertNotSame($provider->store($upload, ImageKind::GAME), $provider->store($upload, ImageKind::GAME));
    }

    public function test_an_image_is_served_from_the_public_address_of_the_bucket(): void
    {
        $provider = new ImageProvider($this->createStub(ImageCompressorInterface::class), $this->createStub(ImageStorageInterface::class), 'https://images.back2tournament.fr/');

        $this->assertSame('https://images.back2tournament.fr/games/rl.webp', $provider->url('games/rl.webp'));
        $this->assertNull($provider->url(null));
    }

    public function test_removing_an_image_deletes_it_and_removing_none_does_nothing(): void
    {
        $storage = $this->createMock(ImageStorageInterface::class);
        $storage->expects($this->once())->method('delete')->with('articles/old.webp');
        $provider = new ImageProvider($this->createStub(ImageCompressorInterface::class), $storage, 'http://localhost:3902');

        $provider->remove('articles/old.webp');
        $provider->remove(null);
    }
}
