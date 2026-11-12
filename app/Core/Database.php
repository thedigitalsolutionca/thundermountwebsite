<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Thin PDO wrapper. All queries use prepared statements.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function init(array $config): void
    {
        $driver = $config['driver'] ?? 'mysql';
        self::$driver = $driver;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . ($config['sqlite_path'] ?? ':memory:');
            self::$pdo = new PDO($dsn, null, null, $options);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
            return;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new RuntimeException('Database has not been initialised.');
        }
        return self::$pdo;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    public static function isSqlite(): bool
    {
        return self::$driver === 'sqlite';
    }

    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [], mixed $default = null): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : $value;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $quoted = array_map(static fn ($c) => '`' . str_replace('`', '', (string) $c) . '`', $columns);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            str_replace('`', '', $table),
            implode(', ', $quoted),
            implode(', ', $placeholders)
        );
        $stmt = self::pdo()->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = '`' . str_replace('`', '', (string) $column) . '` = ?';
        }
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            str_replace('`', '', $table),
            implode(', ', $sets),
            $where
        );
        $stmt = self::pdo()->prepare($sql);
        // Positional placeholders throughout: named SET placeholders cannot be
        // mixed with positional WHERE placeholders in a native prepared statement.
        $stmt->execute(array_merge(array_values($data), array_values($whereParams)));
        return $stmt->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * SELECT ... FOR UPDATE on MySQL; plain select on SQLite (tests are single-process).
     */
    public static function lockFirst(string $sql, array $params = []): ?array
    {
        if (!self::isSqlite()) {
            $sql .= ' FOR UPDATE';
        }
        return self::first($sql, $params);
    }

    public static function tableExists(string $table): bool
    {
        try {
            if (self::isSqlite()) {
                return (bool) self::value(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                    [$table]
                );
            }
            return (bool) self::value(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$table]
            );
        } catch (PDOException) {
            return false;
        }
    }
}
