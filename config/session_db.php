<?php
if (!getenv('DB_HOST')) { return; } // lokal (XAMPP) pakai session biasa

class DbSessionHandler implements SessionHandlerInterface
{
    private ?PDO $pdo = null;

    private function db(): PDO
    {
        if (!$this->pdo) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST'), getenv('DB_PORT') ?: '3306', getenv('DB_NAME'));
            $this->pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/ca.pem',
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]);
        }
        return $this->pdo;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $s = $this->db()->prepare('SELECT data FROM php_sessions WHERE id = ?');
        $s->execute([$id]);
        return (string) ($s->fetchColumn() ?: '');
    }

    public function write(string $id, string $data): bool
    {
        $s = $this->db()->prepare(
            'REPLACE INTO php_sessions (id, data, updated_at) VALUES (?, ?, ?)');
        return $s->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        return $this->db()->prepare('DELETE FROM php_sessions WHERE id = ?')->execute([$id]);
    }

    public function gc(int $max): int|false
    {
        $s = $this->db()->prepare('DELETE FROM php_sessions WHERE updated_at < ?');
        $s->execute([time() - $max]);
        return $s->rowCount();
    }
}

session_set_save_handler(new DbSessionHandler(), true);