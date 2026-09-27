<?php

declare(strict_types=1);

namespace App\Media\Image\Infrastructure\Imagick;

use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Image\Domain\Service\ImageCompressorInterface;
use App\Media\Image\Domain\ValueObject\CompressedImage;
use App\Shared\ValueObject\UploadedImageValueObject;

/**
 * Compresses with ImageMagick: turned upright, brought to sRGB, resized to its
 * kind, stripped of its metadata (EXIF, GPS…) and encoded as WebP.
 */
final class ImagickImageCompressor implements ImageCompressorInterface
{
    /**
     * WebP quality: visually close to the original for about a third of a JPEG's weight.
     */
    private const QUALITY = 82;

    public function compress(UploadedImageValueObject $image, ImageKind $kind): CompressedImage
    {
        $imagick = new \Imagick();
        $imagick->readImageBlob($image->getContent());

        // An animation keeps its first frame.
        $imagick->setIteratorIndex(0);
        $frame = $imagick->getImage();
        $imagick->clear();

        $frame->autoOrient();
        $frame->transformImageColorspace(\Imagick::COLORSPACE_SRGB);

        $maxSide = $kind->maxSide();
        if ($kind->isSquare()) {
            // Never enlarged: a small picture makes a small square.
            $side = min($maxSide, $frame->getImageWidth(), $frame->getImageHeight());
            $frame->cropThumbnailImage($side, $side);
            $frame->setImagePage(0, 0, 0, 0);
        } elseif ($frame->getImageWidth() > $maxSide || $frame->getImageHeight() > $maxSide) {
            $frame->thumbnailImage($maxSide, $maxSide, true);
        }

        $frame->stripImage();
        $frame->setImageFormat('webp');
        $frame->setImageCompressionQuality(self::QUALITY);
        $frame->setOption('webp:method', '6');

        $compressed = new CompressedImage(
            $frame->getImageBlob(),
            'image/webp',
            'webp',
            $frame->getImageWidth(),
            $frame->getImageHeight(),
        );
        $frame->clear();

        return $compressed;
    }
}
