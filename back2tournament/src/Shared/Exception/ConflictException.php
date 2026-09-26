<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * The operation clashes with a resource that already exists.
 *
 * Return code HTTP 409.
 */
final class ConflictException extends \InvalidArgumentException implements DomainExceptionInterface
{
    public function getStatusCode(): int
    {
        return 409;
    }
}
