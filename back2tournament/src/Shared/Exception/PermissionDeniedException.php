<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * The user is known but lacks the role required for this operation.
 *
 * Return code HTTP 403.
 */
final class PermissionDeniedException extends \InvalidArgumentException implements DomainExceptionInterface
{
    public function getStatusCode(): int
    {
        return 403;
    }
}
