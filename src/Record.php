<?php

namespace App;

/**
 * The world's tiniest active-record substitute. No ORM, no magic — just
 * PDO with fewer keystrokes. Every row is a plain associative array.
 */
abstract class Record
{
    protected static string $table = '';

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM '.static::$table.' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(string $orderBy = 'id'): array
    {
        return Database::connection()->query('SELECT * FROM '.static::$table.' ORDER BY '.$orderBy)->fetchAll();
    }

    /**
     * @param  array<string, mixed>  $conditions  column => value (AND-ed together)
     * @return array<int, array<string, mixed>>
     */
    public static function where(array $conditions, string $orderBy = 'id'): array
    {
        [$sql, $params] = self::buildWhere($conditions);
        $stmt = Database::connection()->prepare('SELECT * FROM '.static::$table.$sql.' ORDER BY '.$orderBy);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @param  array<string, mixed>  $conditions */
    public static function first(array $conditions): ?array
    {
        $rows = self::where($conditions);

        return $rows[0] ?? null;
    }

    public static function count(array $conditions = []): int
    {
        [$sql, $params] = self::buildWhere($conditions);
        $stmt = Database::connection()->prepare('SELECT COUNT(*) AS c FROM '.static::$table.$sql);
        $stmt->execute($params);

        return (int) $stmt->fetch()['c'];
    }

    /** @param  array<string, mixed>  $data */
    public static function insert(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? Database::now();
        $data['updated_at'] = $data['updated_at'] ?? Database::now();

        $columns = array_keys($data);
        $placeholders = array_map(fn ($c) => ':'.$c, $columns);

        $sql = 'INSERT INTO '.static::$table.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $placeholders).')';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);

        return (int) Database::connection()->lastInsertId();
    }

    /** @param  array<string, mixed>  $data */
    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = Database::now();

        $sets = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
        $data['__id'] = $id;

        $stmt = Database::connection()->prepare('UPDATE '.static::$table." SET $sets WHERE id = :__id");
        $stmt->execute($data);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM '.static::$table.' WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** @param  array<string, mixed>  $conditions */
    private static function buildWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['', []];
        }

        $clauses = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $placeholders = [];
                foreach ($value as $i => $v) {
                    $key = $column.'_'.$i;
                    $placeholders[] = ':'.$key;
                    $params[$key] = $v;
                }
                $clauses[] = $column.' IN ('.implode(', ', $placeholders).')';
            } else {
                $clauses[] = "$column = :$column";
                $params[$column] = $value;
            }
        }

        return [' WHERE '.implode(' AND ', $clauses), $params];
    }

    /** Row locking is a no-op here: SQLite serializes writers anyway, and
     * this app runs single-process via PHP's built-in server. A real
     * multi-worker deployment would need SELECT ... FOR UPDATE (Postgres/MySQL). */
    public static function lockForUpdateNotice(): void {}
}
