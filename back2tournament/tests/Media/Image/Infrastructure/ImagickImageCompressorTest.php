<?php

declare(strict_types=1);

namespace App\Tests\Media\Image\Infrastructure;

use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Image\Domain\ValueObject\CompressedImage;
use App\Media\Image\Infrastructure\Imagick\ImagickImageCompressor;
use App\Shared\ValueObject\UploadedImageValueObject;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('imagick')]
final class ImagickImageCompressorTest extends TestCase
{
    public function test_an_article_cover_is_brought_within_1600_pixels_and_keeps_its_proportions(): void
    {
        $compressed = $this->compress($this->image(3200, 1800, 'png'), ImageKind::ARTICLE);

        $this->assertSame(['image/webp', 'webp'], [$compressed->getType(), $compressed->getExtension()]);
        $this->assertSame([1600, 900], [$compressed->getWidth(), $compressed->getHeight()]);
        $this->assertSame(['WEBP', 1600, 900], $this->read($compressed));
    }

    public function test_a_game_picture_is_brought_within_1200_pixels(): void
    {
        $compressed = $this->compress($this->image(900, 2400, 'jpeg'), ImageKind::GAME);

        $this->assertSame(['WEBP', 450, 1200], $this->read($compressed));
    }

    public function test_a_smaller_image_is_never_enlarged(): void
    {
        $this->assertSame(['WEBP', 800, 600], $this->read($this->compress($this->image(800, 600, 'png'), ImageKind::GAME)));
    }

    public function test_an_avatar_is_cropped_to_a_centred_square_of_256_pixels(): void
    {
        $this->assertSame(['WEBP', 256, 256], $this->read($this->compress($this->image(1000, 600, 'jpeg'), ImageKind::AVATAR)));
    }

    public function test_a_small_avatar_makes_a_small_square(): void
    {
        $this->assertSame(['WEBP', 60, 60], $this->read($this->compress($this->image(100, 60, 'png'), ImageKind::AVATAR)));
    }

    public function test_a_photo_is_turned_upright_and_loses_its_metadata(): void
    {
        // Taken with the phone on its side: 400 x 200 pixels, to be turned a quarter.
        $photo = $this->image(400, 200, 'jpeg', comment: 'taken at home');

        $compressed = $this->compress($this->withExifOrientation($photo, 6), ImageKind::GAME);

        $this->assertSame(['WEBP', 200, 400], $this->read($compressed));
        $image = new \Imagick();
        $image->readImageBlob($compressed->getContent());
        $this->assertSame([], $image->getImageProfiles('*', false));
        $this->assertSame('', (string) $image->getImageProperty('comment'));
    }

    public function test_an_animation_keeps_its_first_frame(): void
    {
        $animation = new \Imagick();
        foreach (['red', 'blue'] as $colour) {
            $frame = new \Imagick();
            $frame->newImage(40, 30, new \ImagickPixel($colour));
            $frame->setImageFormat('gif');
            $animation->addImage($frame);
        }

        $compressed = $this->compress($animation->getImagesBlob(), ImageKind::GAME);

        $image = new \Imagick();
        $image->readImageBlob($compressed->getContent());
        $this->assertSame(1, $image->getNumberImages());
        // Red, give or take what a lossy encoding moves.
        $colour = $image->getImagePixelColor(20, 15)->getColor();
        $this->assertGreaterThan(240, $colour['r']);
        $this->assertLessThan(15, $colour['b']);
    }

    public function test_a_photo_weighs_far_less_once_compressed(): void
    {
        $photo = $this->image(1600, 900, 'png', 'plasma:fractal');

        $this->assertLessThan(\strlen($photo) / 4, \strlen($this->compress($photo, ImageKind::ARTICLE)->getContent()));
    }

    private function compress(string $content, ImageKind $kind): CompressedImage
    {
        return new ImagickImageCompressor()->compress(new UploadedImageValueObject($content), $kind);
    }

    private function image(int $width, int $height, string $format, string $pattern = 'gradient:red-blue', ?string $comment = null): string
    {
        $image = new \Imagick();
        $image->newPseudoImage($width, $height, $pattern);
        $image->setImageFormat($format);
        if (null !== $comment) {
            $image->setImageProperty('comment', $comment);
        }

        return $image->getImageBlob();
    }

    /**
     * A JPEG given the EXIF orientation a camera writes, right after its JFIF header.
     */
    private function withExifOrientation(string $jpeg, int $orientation): string
    {
        $tiff = "MM\x00\x2a".pack('N', 8).pack('n', 1).pack('nnN', 0x0112, 3, 1).pack('nn', $orientation, 0).pack('N', 0);
        $exif = "Exif\x00\x00".$tiff;
        $jfifLength = unpack('n', substr($jpeg, 4, 2))[1];

        return substr($jpeg, 0, 4 + $jfifLength)."\xff\xe1".pack('n', \strlen($exif) + 2).$exif.substr($jpeg, 4 + $jfifLength);
    }

    /**
     * @return array{string, int, int} the format and the size the compressed image reads as
     */
    private function read(CompressedImage $compressed): array
    {
        $image = new \Imagick();
        $image->readImageBlob($compressed->getContent());

        return [$image->getImageFormat(), $image->getImageWidth(), $image->getImageHeight()];
    }
}
