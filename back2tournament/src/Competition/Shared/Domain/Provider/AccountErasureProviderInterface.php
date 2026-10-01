<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

interface AccountErasureProviderInterface
{
    public function ensureErasable(string $userId): void;
}
