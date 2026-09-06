<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use SessionHandlerInterface;

final class PdoSessionHandler implements SessionHandlerInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare('SELECT data FROM php_sessions WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $data = $stmt->fetchColumn();

        return is_string($data) ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare(\App\Core\Sql::sessionUpsertSql());

        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM php_sessions WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM php_sessions WHERE last_activity < ?');
        $stmt->execute([time() - $max_lifetime]);

        return $stmt->rowCount();
    }
}
