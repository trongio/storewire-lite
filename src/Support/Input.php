<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Support;

/**
 * Reads the loosely typed arguments an MCP client sends (a number may arrive
 * as a string, a flag as "yes", a name with stray spaces) into the types a
 * tool hands its contract. laravel/mcp does not enforce an input schema, so
 * every tool reads its arguments through here.
 */
final class Input
{
    /** A trimmed non-blank string, an int as its digits; null for anything else. */
    public static function string(mixed $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /** A number or numeric string as an int; null for anything else. */
    public static function int(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /** A number or numeric string as a float; null for anything else. */
    public static function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * A real bool, 1 / 0, or "true", "yes", "on", "1" (and their opposites)
     * in any case; null for anything else.
     */
    public static function bool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        return is_string($value) ? match (mb_strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        } : null;
    }

    /** A row count between 1 and `$max`; `$default` when none was given. */
    public static function limit(mixed $value, int $default, int $max): int
    {
        return self::clamp(self::int($value) ?? $default, $max);
    }

    /** A count already read as an int, held between 1 and `$max`. */
    public static function clamp(int $value, int $max): int
    {
        return max(1, min(max(1, $max), $value));
    }

    /** A whole number of at least 1 (a page); `$default` when none was given. */
    public static function positive(mixed $value, int $default = 1): int
    {
        return max(1, self::int($value) ?? $default);
    }

    /**
     * A list argument: a list as given, one scalar as a list of one, nothing
     * as an empty list.
     *
     * @return list<mixed>
     */
    public static function list(mixed $value): array
    {
        return match (true) {
            $value === null => [],
            is_array($value) => array_values($value),
            default => [$value],
        };
    }
}
