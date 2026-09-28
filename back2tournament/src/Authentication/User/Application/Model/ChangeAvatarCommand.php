<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Model;

/**
 * Gives the signed-in user their picture, from the bytes of an uploaded
 * image, or takes it away.
 */
final class ChangeAvatarCommand
{
    private ?string $image;

    /**
     * @param string|null $image the uploaded bytes; null takes the picture away
     */
    public function __construct(?string $image)
    {
        $this->image = $image;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }
}
