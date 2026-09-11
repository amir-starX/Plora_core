<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOStatement;

class Database
{
    private static ?PDO $connection = null;

    public static function isEnabled(): bool
    {
        return (bool) Config::get('app.database_enabled', false);
    }

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = Config::get('database', []);

        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $name = $config['dbname'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $user = $config['user'] ?? 'root';
        $pass = $config['pass'] ?? '';

        $dsn = "{$driver}:host={$host};port={$port};dbname={$name};charset={$charset}";

        self::$connection = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): mixed
    {
        $result = self::query($sql, $params)->fetch();
        return $result === false ? null : $result;
    }

    public static function insert(string $table, array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn ($key) => ':' . $key, array_keys($data)));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, $data);

        return self::connection()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn ($key) => "{$key} = :{$key}", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $stmt = self::query($sql, array_merge($data, $whereParams));
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return self::query($sql, $whereParams)->rowCount();
    }
}
