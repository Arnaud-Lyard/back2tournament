<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Model;

final class DissolveClanCommand
{
    private string $clanId;

    private string $password;

    public function __construct(string $clanId, #[\SensitiveParameter] string $password)
    {
        $this->clanId = $clanId;
        $this->password = $password;
    }

    public function getClanId(): string
    {
        return $this->clanId;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
