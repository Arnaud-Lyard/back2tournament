<?php

declare(strict_types=1);

namespace App\Media\Image\Domain\Enum;

/**
 * What an image illustrates, which sets the size it is stored at. Each kind
 * is stored under a folder of its own, named after its value.
 */
enum ImageKind: string
{
    /** The cover of a blog article, 1600 pixels at most a side. */
    case ARTICLE = 'articles';

    /** A user's picture, cropped to a square of 256 pixels at most. */
    case AVATAR = 'avatars';

    /** The picture of a game, 1200 pixels at most a side. */
    case GAME = 'games';

    /**
     * The longest side the stored image may have.
     */
    public function maxSide(): int
    {
        return match ($this) {
            self::ARTICLE => 1600,
            self::AVATAR => 256,
            self::GAME => 1200,
        };
    }

    /**
     * An avatar is cropped to a square, centred; the others keep their proportions.
     */
    public function isSquare(): bool
    {
        return self::AVATAR === $this;
    }
}
