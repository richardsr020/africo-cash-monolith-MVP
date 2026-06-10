<?php

declare(strict_types=1);

final class MigrationRunner
{
    public static function run(): void
    {
        $db = Database::getInstance()->getConnection();

        $db->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
            . 'name TEXT NOT NULL UNIQUE, '
            . 'applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)'
        );

        $migrationDirectory = dirname(__DIR__) . '/db/migration';
        $files = glob($migrationDirectory . '/*.sql');
        if ($files === false) {
            return;
        }

        sort($files, SORT_NATURAL);

        foreach ($files as $filePath) {
            $name = basename($filePath);
            $statement = $db->prepare('SELECT 1 FROM schema_migrations WHERE name = :name LIMIT 1');
            $statement->execute([':name' => $name]);
            if ($statement->fetchColumn() !== false) {
                continue;
            }

            $sql = file_get_contents($filePath);
            if ($sql === false) {
                throw new RuntimeException('Impossible de lire la migration ' . $name . '.');
            }

            $db->beginTransaction();
            try {
                $db->exec($sql);
                $insertStatement = $db->prepare('INSERT INTO schema_migrations (name, applied_at) VALUES (:name, CURRENT_TIMESTAMP)');
                $insertStatement->execute([':name' => $name]);
                $db->commit();
            } catch (Throwable $throwable) {
                $db->rollBack();
                throw $throwable;
            }
        }
    }
}
