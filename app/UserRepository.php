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
        $rows = $this->database
            ->query(
                'SELECT entity_id, account_id, user_id,
                        CASE WHEN x_cookie IS NOT NULL AND x_cookie <> \'\' THEN 1 ELSE 0 END AS cookie_configured,
                        updated_at, created_at
                 FROM `user`
                 ORDER BY entity_id ASC'
            )
            ->fetchAll();

        return array_map([$this, 'normalize'], $rows);
    }

    public function find(int $entityId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT entity_id, account_id, user_id,
                    CASE WHEN x_cookie IS NOT NULL AND x_cookie <> \'\' THEN 1 ELSE 0 END AS cookie_configured,
                    created_at, updated_at
             FROM `user`
             WHERE entity_id = :entity_id'
        );
        $statement->execute(['entity_id' => $entityId]);
        $user = $statement->fetch();

        return is_array($user) ? $this->normalize($user) : null;
    }

    /**
     * Internal lookup used by the X API proxy. Credentials are never returned by
     * the public CRUD endpoints.
     */
    public function findWithCredentials(int $entityId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT entity_id, account_id, user_id, x_cookie, created_at, updated_at
             FROM `user`
             WHERE entity_id = :entity_id'
        );
        $statement->execute(['entity_id' => $entityId]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function create(string $accountId, string $userId, string $cookie): array
    {
        $statement = $this->database->prepare(
            'INSERT INTO `user` (account_id, user_id, x_cookie)
             VALUES (:account_id, :user_id, :x_cookie)'
        );
        $statement->execute([
            'account_id' => $accountId,
            'user_id' => $userId,
            'x_cookie' => $cookie,
        ]);

        return $this->find((int)$this->database->lastInsertId());
    }

    public function update(int $entityId, string $accountId, string $userId, ?string $cookie): ?array
    {
        if ($cookie === null) {
            $statement = $this->database->prepare(
                'UPDATE `user`
                 SET account_id = :account_id, user_id = :user_id
                 WHERE entity_id = :entity_id'
            );
            $statement->execute([
                'entity_id' => $entityId,
                'account_id' => $accountId,
                'user_id' => $userId,
            ]);
        } else {
            $statement = $this->database->prepare(
                'UPDATE `user`
                 SET account_id = :account_id, user_id = :user_id, x_cookie = :x_cookie
                 WHERE entity_id = :entity_id'
            );
            $statement->execute([
                'entity_id' => $entityId,
                'account_id' => $accountId,
                'user_id' => $userId,
                'x_cookie' => $cookie,
            ]);
        }

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

    private function normalize(array $row): array
    {
        $row['entity_id'] = (int)$row['entity_id'];
        $row['cookie_configured'] = (bool)($row['cookie_configured'] ?? false);

        return $row;
    }
}
