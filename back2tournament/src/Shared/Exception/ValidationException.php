<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * The request was understood but its content breaks a business rule.
 *
 * Return code HTTP 400.
 */
final class ValidationException extends \InvalidArgumentException implements DomainExceptionInterface
{
    public function getStatusCode(): int
    {
        return 400;
    }
}
