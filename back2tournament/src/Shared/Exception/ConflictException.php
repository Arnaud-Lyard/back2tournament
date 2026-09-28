<?php

declare(strict_types=1);

namespace App\Shared\Exception;

final class ConflictException extends \InvalidArgumentException implements DomainExceptionInterface
{
    public function getStatusCode(): int
    {
        return 409;
    }
}
