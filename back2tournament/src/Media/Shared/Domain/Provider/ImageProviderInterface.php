<?php

declare(strict_types=1);

namespace App\Media\Shared\Domain\Provider;

use App\Media\Image\Domain\Enum\ImageKind;
use App\Shared\ValueObject\UploadedImageValueObject;

/**
 * The images of the platform: an article's cover, a user's avatar, a game's
 * picture. Each context keeps the key of its images and asks here for the
 * address browsers load them from.
 */
interface ImageProviderInterface
{
    /**
     * Compresses an uploaded image for its kind and stores it under a key of
     * its own, which it returns.
     */
    public function store(UploadedImageValueObject $image, ImageKind $kind): string;

    /**
     * Forgets a stored image; null forgets nothing.
     */
    public function remove(?string $key): void;

    /**
     * The public address of a stored image; null when there is none.
     */
    public function url(?string $key): ?string;
}
