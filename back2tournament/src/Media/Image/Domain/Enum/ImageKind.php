<?php

declare(strict_types=1);

namespace App\Media\Image\Domain\Enum;

enum ImageKind: string
{
    case ARTICLE = 'articles';

    case AVATAR = 'avatars';

    case GAME = 'games';

    public function maxSide(): int
    {
        return match ($this) {
            self::ARTICLE => 1600,
            self::AVATAR => 256,
            self::GAME => 1200,
        };
    }

    public function isSquare(): bool
    {
        return self::AVATAR === $this;
    }
}
