<?php

declare(strict_types=1);

namespace App\Media\Image\Domain\Attribute;

/**
 * Marks an entity property holding the key of a stored image. Its entity
 * then reads, in the API, with the public address of the image instead.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class StoredImage
{
}
