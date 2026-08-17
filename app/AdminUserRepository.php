<?php

declare(strict_types=1);

namespace App;

use PDO;

final class AdminUserRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->database->prepare(
            'SELECT entity_id, username, password_hash, role, is_active, failed_attempts, locked_until
             FROM admin_user
             WHERE username = :username
             LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function recordFailure(int $entityId): void
    {
        $statement = $this->database->prepare(
            'UPDATE admin_user
             SET failed_attempts = failed_attempts + 1,
                 locked_until = CASE
                     WHEN failed_attempts + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                     ELSE locked_until
                 END
             WHERE entity_id = :entity_id'
        );
        $statement->execute(['entity_id' => $entityId]);
    }

    public function recordLogin(int $entityId): void
    {
        $statement = $this->database->prepare(
            'UPDATE admin_user
             SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW()
             WHERE entity_id = :entity_id'
        );
        $statement->execute(['entity_id' => $entityId]);
    }

    public function updatePasswordHash(int $entityId, string $passwordHash): void
    {
        $statement = $this->database->prepare(
            'UPDATE admin_user SET password_hash = :password_hash WHERE entity_id = :entity_id'
        );
        $statement->execute([
            'entity_id' => $entityId,
            'password_hash' => $passwordHash,
        ]);
    }
}
