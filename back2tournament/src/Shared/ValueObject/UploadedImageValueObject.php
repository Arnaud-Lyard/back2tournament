<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * An image as it was uploaded: a JPEG, PNG, WebP or GIF file of 8 MB at most,
 * between 16 pixels a side and 40 megapixels. Its type is read from its
 * content, never from the name or the type the client claimed.
 */
final class UploadedImageValueObject
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    public const MIN_SIDE = 16;
    public const MAX_PIXELS = 40_000_000;
    public const TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * Shorter than this, no image header fits.
     */
    private const MIN_BYTES = 12;

    private string $content;

    private string $type;

    private int $width;

    private int $height;

    public function __construct(string $content)
    {
        [$type, $width, $height] = $this->ensureIsValidUploadedImage($content);

        $this->content = $content;
        $this->type = $type;
        $this->width = $width;
        $this->height = $height;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * The MIME type, as the content tells it.
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return array{string, int, int} the type, the width and the height
     */
    private function ensureIsValidUploadedImage(string $content): array
    {
        // Nothing sent, or a file PHP turned down before reading it.
        if ('' === $content) {
            throw new ValidationException('No image was received: send a JPEG, PNG, WebP or GIF file of 8 MB at most');
        }
        if (\strlen($content) > self::MAX_BYTES) {
            throw new ValidationException(\sprintf('The image weighs more than %d MB', self::MAX_BYTES / 1024 / 1024));
        }

        $type = \strlen($content) < self::MIN_BYTES ? null : new \finfo(\FILEINFO_MIME_TYPE)->buffer($content);
        if (!\in_array($type, self::TYPES, true)) {
            throw new ValidationException('The file is not a JPEG, PNG, WebP or GIF image');
        }

        // The header only: a corrupt file must answer as one, without the
        // notice getimagesize raises on corrupt JPEG data.
        $size = @getimagesizefromstring($content);
        if (false === $size || $size['mime'] !== $type) {
            throw new ValidationException('The image cannot be read');
        }

        [$width, $height] = $size;
        if ($width < self::MIN_SIDE || $height < self::MIN_SIDE) {
            throw new ValidationException(\sprintf('The image must be %d pixels a side at least', self::MIN_SIDE));
        }
        if ($width * $height > self::MAX_PIXELS) {
            throw new ValidationException(\sprintf('The image must be %d megapixels at most', self::MAX_PIXELS / 1_000_000));
        }

        return [$type, $width, $height];
    }
}
