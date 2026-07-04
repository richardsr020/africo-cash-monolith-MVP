<?php

declare(strict_types=1);

final class Database
{
    private static ?self $instance = null;

    private PDO $connection;

    private string $databasePath;

    private function __construct()
    {
        $configuredPath = trim((string) getenv('AFRICO_DB_PATH'));
        $this->databasePath = $configuredPath !== ''
            ? $configuredPath
            : dirname(__DIR__) . '/db/africo_cash.sqlite';

        $directory = dirname($this->databasePath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le répertoire de base de données.');
        }

        if (!file_exists($this->databasePath)) {
            $touchResult = touch($this->databasePath);
            if ($touchResult === false) {
                throw new RuntimeException('Impossible de créer le fichier de base de données.');
            }
            chmod($this->databasePath, 0644);
        }

        $this->connection = new PDO('sqlite:' . $this->databasePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $this->connection->exec('PRAGMA foreign_keys = ON;');
        $this->connection->exec('PRAGMA journal_mode = WAL;');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function getDatabasePath(): string
    {
        return $this->databasePath;
    }
}
