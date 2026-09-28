<?php

namespace App;

use PDO;
use RuntimeException;

class Database
{
    private static ?PDO $pdo = null;

    private static ?string $driver = null;

    /** Welke driver er in .env staat: 'mysql' (standaard) of 'sqlite'. */
    public static function driver(): string
    {
        if (self::$driver === null) {
            self::$driver = strtolower((string) Env::get('DB_CONNECTION', 'mysql'));
        }

        return self::$driver;
    }

    public static function isMysql(): bool
    {
        return self::driver() === 'mysql';
    }

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::isMysql() ? self::connectMysql() : self::connectSqlite();
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return self::$pdo;
    }

    private static function connectMysql(): PDO
    {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        if ($database === null) {
            throw new RuntimeException('DB_DATABASE ontbreekt in .env; zonder databasenaam kan er niet verbonden worden.');
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";

        if (($socket = Env::get('DB_SOCKET')) !== null) {
            $dsn = "mysql:unix_socket=$socket;dbname=$database;charset=$charset";
        }

        $pdo = new PDO($dsn, Env::get('DB_USERNAME', 'root'), Env::get('DB_PASSWORD', ''), [
            // Echte prepared statements: anders plakt PDO de waarden zelf in de
            // query en gaan integers als string naar de server.
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Dezelfde strengheid als SQLite met foreign_keys aan: stille afgekapte
        // waarden of ongeldige datums worden fouten in plaats van verrassingen.
        $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");

        return $pdo;
    }

    private static function connectSqlite(): PDO
    {
        $path = Env::get('DB_DATABASE', __DIR__.'/../storage/database.sqlite');

        if (! str_starts_with($path, '/')) {
            $path = __DIR__.'/../'.$path;
        }

        $pdo = new PDO('sqlite:'.$path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        // Meerdere teams spelen tegelijk: WAL laat lezers en de schrijver naast
        // elkaar werken, de timeout laat een wachtende schrijver even wachten
        // in plaats van meteen "database is locked" te gooien.
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');

        return $pdo;
    }

    /**
     * Voert $callback uit binnen een transactie.
     *
     * SQLite: BEGIN IMMEDIATE. Een gewone transactie begint als lezer en moet
     * later opwaarderen naar schrijver; dat kan SQLite niet laten wachten en
     * geeft meteen "database is locked". IMMEDIATE pakt de schrijflock direct,
     * zodat gelijktijdige teams netjes op elkaar wachten (tot busy_timeout).
     *
     * MySQL: InnoDB regelt rijvergrendeling zelf, dus een gewone transactie.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();

        if (self::isMysql()) {
            $pdo->beginTransaction();

            try {
                $result = $callback();
                $pdo->commit();

                return $result;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;
            }
        }

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

    /** Draait het volledige schema voor de ingestelde driver. */
    public static function createSchema(): void
    {
        $file = __DIR__.'/../database/'.(self::isMysql() ? 'schema.mysql.sql' : 'schema.sql');
        self::connection()->exec(file_get_contents($file));
    }

    /** Staan de tabellen er al? Zo niet, dan is dit een verse database. */
    public static function hasTables(): bool
    {
        $sql = self::isMysql()
            ? "SHOW TABLES LIKE 'games'"
            : "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'games'";

        return self::connection()->query($sql)->fetch() !== false;
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
