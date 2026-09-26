<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Exception\ValidationException;

/**
 * Reads typed fields out of a decoded JSON request body, so that a key of the
 * wrong type answers 400 instead of surfacing as a TypeError deeper down.
 */
final class JsonBody
{
    /**
     * A required string. A missing key reads as '' and is left to the value
     * object that receives it to refuse.
     */
    public static function string(mixed $body, string $key): string
    {
        return self::optionalString($body, $key) ?? '';
    }

    public static function optionalString(mixed $body, string $key): ?string
    {
        $value = self::fields($body)[$key] ?? null;
        if (null !== $value && !\is_string($value)) {
            throw new ValidationException(sprintf('"%s" must be a string', $key));
        }

        return $value;
    }

    public static function int(mixed $body, string $key): int
    {
        $value = self::fields($body)[$key] ?? null;
        if (!\is_int($value)) {
            throw new ValidationException(sprintf('"%s" must be an integer', $key));
        }

        return $value;
    }

    /**
     * @return list<int>|null
     */
    public static function optionalIntList(mixed $body, string $key): ?array
    {
        $value = self::fields($body)[$key] ?? null;
        if (null === $value) {
            return null;
        }
        if (!\is_array($value) || !array_is_list($value) || \count(array_filter($value, '\is_int')) !== \count($value)) {
            throw new ValidationException(sprintf('"%s" must be a list of integers', $key));
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    public static function stringList(mixed $body, string $key): array
    {
        $value = self::fields($body)[$key] ?? null;
        if (!\is_array($value) || !array_is_list($value) || \count(array_filter($value, '\is_string')) !== \count($value)) {
            throw new ValidationException(sprintf('"%s" must be a list of strings', $key));
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function fields(mixed $body): array
    {
        if (!\is_array($body) || (array_is_list($body) && [] !== $body)) {
            throw new ValidationException('The request body must be a JSON object');
        }

        return $body;
    }
}
