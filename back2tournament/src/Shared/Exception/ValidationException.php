<?php

declare(strict_types=1);

namespace App\Shared\Exception;

final class ValidationException extends \InvalidArgumentException implements DomainExceptionInterface
{
    public function getStatusCode(): int
    {
        return 400;
    }
}
