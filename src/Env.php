<?php

namespace App;

/**
 * Leest .env in: geen Dotenv-pakket, alleen wat we nodig hebben (KEY=value,
 * commentaarregels, aanhalingstekens eromheen). Echte omgevingsvariabelen
 * (Apache SetEnv, docker, export) hebben voorrang op het bestand.
 */
class Env
{
    /** @var array<string, string>|null */
    private static ?array $values = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$values === null) {
            self::$values = self::load(__DIR__.'/../.env');
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: null;

        if ($value === null || $value === false) {
            $value = self::$values[$key] ?? null;
        }

        if ($value === null || $value === '' || $value === 'null') {
            return $default;
        }

        return (string) $value;
    }

    /** @return array<string, string> */
    private static function load(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $values = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim(trim($value), "\"'");
        }

        return $values;
    }
}
