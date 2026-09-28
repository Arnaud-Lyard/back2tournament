<?php

declare(strict_types=1);

namespace App\Media\Image\Domain\Service;

/**
 * Where the images are kept, by key.
 */
interface ImageStorageInterface
{
    public function put(string $key, string $content, string $type): void;

    /**
     * Forgets an image; a key that holds nothing is left as it is.
     */
    public function delete(string $key): void;
}
