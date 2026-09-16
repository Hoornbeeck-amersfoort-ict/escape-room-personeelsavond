<?php

namespace App;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $path = __DIR__.'/../storage/database.sqlite';
            self::$pdo = new PDO('sqlite:'.$path);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$pdo->exec('PRAGMA foreign_keys = ON;');
            // Meerdere teams spelen tegelijk: WAL laat lezers en de schrijver naast
            // elkaar werken, de timeout laat een wachtende schrijver even wachten
            // in plaats van meteen "database is locked" te gooien.
            self::$pdo->exec('PRAGMA journal_mode = WAL;');
            self::$pdo->exec('PRAGMA busy_timeout = 5000;');
        }

        return self::$pdo;
    }

    /**
     * Voert $callback uit binnen een BEGIN IMMEDIATE-transactie. Een gewone
     * transactie begint als lezer en moet later opwaarderen naar schrijver;
     * dat kan SQLite niet laten wachten en geeft meteen "database is locked".
     * IMMEDIATE pakt de schrijflock direct, zodat gelijktijdige teams netjes
     * op elkaar wachten (tot busy_timeout).
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->exec('BEGIN IMMEDIATE');

        try {
            $result = $callback();
            $pdo->exec('COMMIT');

            return $result;
        } catch (\Throwable $e) {
            $pdo->exec('ROLLBACK');

            throw $e;
        }
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
