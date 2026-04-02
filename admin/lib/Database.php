<?php
/**
 * Database — Wrapper SQLite via PDO
 */

date_default_timezone_set('America/Sao_Paulo');

class Database
{
    private static ?PDO $instance = null;
    private const DB_PATH = __DIR__ . '/../data/cms.sqlite';

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dir = dirname(self::DB_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0750, true);
            }

            $isNew = !file_exists(self::DB_PATH);

            self::$instance = new PDO('sqlite:' . self::DB_PATH, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            self::$instance->exec('PRAGMA journal_mode = WAL');
            self::$instance->exec('PRAGMA foreign_keys = ON');

            if ($isNew) {
                self::migrate();
            } else {
                self::applyAlterMigrations();
            }
        }

        return self::$instance;
    }

    private static function migrate(): void
    {
        $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
        self::$instance->exec($schema);
    }

    private static function applyAlterMigrations(): void
    {
        $migrations = [
            ['news', 'is_pinned', 'ALTER TABLE news ADD COLUMN is_pinned INTEGER DEFAULT 0'],
            ['news', 'carousel_order', 'ALTER TABLE news ADD COLUMN carousel_order INTEGER DEFAULT 0'],
            ['carousel', 'is_in_gallery', 'ALTER TABLE carousel ADD COLUMN is_in_gallery INTEGER DEFAULT 0'],
            ['carousel', 'video_path', 'ALTER TABLE carousel ADD COLUMN video_path TEXT'],
            ['news', 'video_path', 'ALTER TABLE news ADD COLUMN video_path TEXT'],
        ];
        foreach ($migrations as [$table, $column, $sql]) {
            $cols = self::$instance->query("PRAGMA table_info({$table})")->fetchAll();
            $exists = false;
            foreach ($cols as $c) {
                if ($c['name'] === $column) { $exists = true; break; }
            }
            if (!$exists) {
                self::$instance->exec($sql);
            }
        }

        // Inserir hero como slide personalizado (uma vez)
        $heroMigrated = self::fetchOne("SELECT value FROM settings WHERE key = 'hero_migrated'");
        if (!$heroMigrated) {
            self::query(
                "INSERT INTO carousel (image, is_in_gallery, is_visible, display_order, is_pinned) VALUES (?, 1, 1, 1, 1)",
                ['assets/img/destaque/save-the-date-forum.jpg']
            );
            self::query(
                "INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES ('hero_migrated', '1', datetime('now', 'localtime'))"
            );
        }
    }

    /**
     * Atalhos para queries comuns
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set}, updated_at = datetime('now', 'localtime') WHERE {$where}";
        $stmt = self::query($sql, [...array_values($data), ...$whereParams]);
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $stmt = self::query("DELETE FROM {$table} WHERE {$where}", $params);
        return $stmt->rowCount();
    }

    public static function getSetting(string $key): ?string
    {
        $row = self::fetchOne("SELECT value FROM settings WHERE key = ?", [$key]);
        return $row ? $row['value'] : null;
    }

    public static function setSetting(string $key, ?string $value): void
    {
        self::query(
            "INSERT INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now', 'localtime'))
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at",
            [$key, $value]
        );
    }
}
