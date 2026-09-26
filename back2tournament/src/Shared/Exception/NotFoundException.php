<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * A resource referenced by the request does not exist.
 *
 * Return code HTTP 404.
 */
final class NotFoundException extends \InvalidArgumentException implements DomainExceptionInterface
{
    public function getStatusCode(): int
    {
        return 404;
    }
}
