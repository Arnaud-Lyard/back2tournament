<?php

declare(strict_types=1);

namespace App\Media\Shared\Domain\Provider;

use App\Media\Image\Domain\Enum\ImageKind;
use App\Shared\ValueObject\UploadedImageValueObject;

interface ImageProviderInterface
{
    public function store(UploadedImageValueObject $image, ImageKind $kind): string;

    public function remove(?string $key): void;

    public function url(?string $key): ?string;
}
