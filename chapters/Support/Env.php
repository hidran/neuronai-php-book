<?php

declare(strict_types=1);

namespace NeuronBook\Support;

use RuntimeException;

use function array_key_exists;

/**
 * Tiny environment reader.
 *
 * The book's examples read configuration from the environment rather than
 * hard-coding keys, which is the same thing Chapter 4 argues for in prose.
 * Laravel users would reach for env()/config(); outside Laravel this is the
 * equivalent, and it keeps the examples framework-free.
 */
final class Env
{
    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        } else {
            $value = getenv($key);
        }

        if (!is_scalar($value) || $value === '' || $value === false) {
            return $default;
        }

        return (string) $value;
    }

    /**
     * Read a variable that the example cannot run without, and fail with a
     * message that says what to do rather than a stack trace.
     */
    public static function require(string $key): string
    {
        $value = self::get($key);

        if ($value === null) {
            throw new RuntimeException(
                "Missing environment variable {$key}. "
                . "Copy .env.example to .env and set it, or export it in your shell."
            );
        }

        return $value;
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * @param array<string, string> $vars
     */
    public static function hasAll(array $vars): bool
    {
        foreach (array_keys($vars) as $key) {
            if (!self::has($key)) {
                return false;
            }
        }

        return true;
    }

    public static function int(string $key, int $default): int
    {
        $value = self::get($key);

        return $value === null ? $default : (int) $value;
    }
}
