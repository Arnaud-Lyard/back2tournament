<?php

declare(strict_types=1);

namespace App\Media\Image\Domain\Service;

interface ImageStorageInterface
{
    public function put(string $key, string $content, string $type): void;

    public function delete(string $key): void;
}
