<?php

declare(strict_types=1);

namespace App\Media\Image\Infrastructure\Imagick;

use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Image\Domain\Service\ImageCompressorInterface;
use App\Media\Image\Domain\ValueObject\CompressedImage;
use App\Shared\ValueObject\UploadedImageValueObject;

final class ImagickImageCompressor implements ImageCompressorInterface
{
    private const QUALITY = 82;

    public function compress(UploadedImageValueObject $image, ImageKind $kind): CompressedImage
    {
        $imagick = new \Imagick();
        $imagick->readImageBlob($image->getContent());

        $imagick->setIteratorIndex(0);
        $frame = $imagick->getImage();
        $imagick->clear();

        $frame->autoOrient();
        $frame->transformImageColorspace(\Imagick::COLORSPACE_SRGB);

        $maxSide = $kind->maxSide();
        if ($kind->isSquare()) {
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
