<?php

declare(strict_types=1);

namespace App\Shared\Exception;

interface DomainExceptionInterface
{
    public function getStatusCode(): int;
}
