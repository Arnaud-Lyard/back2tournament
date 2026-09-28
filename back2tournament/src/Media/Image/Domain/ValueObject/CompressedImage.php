<?php

declare(strict_types=1);

namespace App\Media\Image\Domain\ValueObject;

final class CompressedImage
{
    private string $content;

    private string $type;

    private string $extension;

    private int $width;

    private int $height;

    public function __construct(string $content, string $type, string $extension, int $width, int $height)
    {
        $this->content = $content;
        $this->type = $type;
        $this->extension = $extension;
        $this->width = $width;
        $this->height = $height;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }
}
