<?php

declare(strict_types=1);

namespace App;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function all(): array
    {
        return $this->database
            ->query('SELECT entity_id, account_id, user_id, created_at, updated_at FROM `user` ORDER BY entity_id ASC')
            ->fetchAll();
    }

    public function find(int $entityId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT entity_id, account_id, user_id, created_at, updated_at FROM `user` WHERE entity_id = :entity_id'
        );
        $statement->execute(['entity_id' => $entityId]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function create(string $accountId, string $userId): array
    {
        $statement = $this->database->prepare(
            'INSERT INTO `user` (account_id, user_id) VALUES (:account_id, :user_id)'
        );
        $statement->execute([
            'account_id' => $accountId,
            'user_id' => $userId,
        ]);

        return $this->find((int)$this->database->lastInsertId());
    }

    public function update(int $entityId, string $accountId, string $userId): ?array
    {
        $statement = $this->database->prepare(
            'UPDATE `user` SET account_id = :account_id, user_id = :user_id WHERE entity_id = :entity_id'
        );
        $statement->execute([
            'entity_id' => $entityId,
            'account_id' => $accountId,
            'user_id' => $userId,
        ]);

        return $this->find($entityId);
    }

    public function delete(int $entityId): bool
    {
        $statement = $this->database->prepare('DELETE FROM `user` WHERE entity_id = :entity_id');
        $statement->execute(['entity_id' => $entityId]);

        return $statement->rowCount() > 0;
    }

    public function count(): int
    {
        return (int)$this->database->query('SELECT COUNT(*) FROM `user`')->fetchColumn();
    }
}
