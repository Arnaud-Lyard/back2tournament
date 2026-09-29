<?php

declare(strict_types=1);

namespace App\Media\Image\Domain\Service;

use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Image\Domain\ValueObject\CompressedImage;
use App\Shared\ValueObject\UploadedImageValueObject;

interface ImageCompressorInterface
{
    public function compress(UploadedImageValueObject $image, ImageKind $kind): CompressedImage;
}
