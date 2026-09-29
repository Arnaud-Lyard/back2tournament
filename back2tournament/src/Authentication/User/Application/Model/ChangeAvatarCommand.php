<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Model;

final class ChangeAvatarCommand
{
    private ?string $image;

    public function __construct(?string $image)
    {
        $this->image = $image;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }
}
