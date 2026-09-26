<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * Marks an exception as an expected business failure that the API must answer
 * with a specific HTTP status instead of a 500.
 *
 */
interface DomainExceptionInterface
{
    public function getStatusCode(): int;
}
