<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class AdminPortalUser
{
    private PDO $db;
    private ?bool $tableExists = null;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getConnection();
    }

    public function hasUsersTable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        $stmt = $this->db->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
             LIMIT 1'
        );
        $stmt->execute(['table' => 'admin_portal_users']);

        $this->tableExists = (bool) $stmt->fetchColumn();

        return $this->tableExists;
    }

    public function verifyCredentials(string $username, string $password): ?array
    {
        if (!$this->hasUsersTable()) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash
             FROM admin_portal_users
             WHERE username = :username
               AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['username' => $username]);

        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
        ];
    }

    public function updatePasswordById(int $id, string $newPassword): bool
    {
        if ($id <= 0 || !$this->hasUsersTable()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE admin_portal_users
             SET password_hash = :password_hash,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND is_active = 1
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $id,
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return $stmt->rowCount() > 0;
    }
}
