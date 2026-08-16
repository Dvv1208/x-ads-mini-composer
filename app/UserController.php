<?php

declare(strict_types=1);

namespace App;

use PDOException;

final class UserController
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function index(): void
    {
        $this->json(['data' => $this->users->all()]);
    }

    public function show(string $id): void
    {
        $user = $this->users->find($this->entityId($id));
        if ($user === null) {
            $this->json(['error' => 'Account was not found.'], 404);
            return;
        }

        $this->json(['data' => $user]);
    }

    public function create(): void
    {
        [$accountId, $userId] = $this->validatedBody();

        try {
            $this->json(['data' => $this->users->create($accountId, $userId)], 201);
        } catch (PDOException $exception) {
            $this->databaseError($exception);
        }
    }

    public function update(string $id): void
    {
        $entityId = $this->entityId($id);
        if ($this->users->find($entityId) === null) {
            $this->json(['error' => 'Account was not found.'], 404);
            return;
        }

        [$accountId, $userId] = $this->validatedBody();

        try {
            $this->json(['data' => $this->users->update($entityId, $accountId, $userId)]);
        } catch (PDOException $exception) {
            $this->databaseError($exception);
        }
    }

    public function delete(string $id): void
    {
        if ($this->users->count() <= 1) {
            $this->json(['error' => 'The last account cannot be deleted.'], 422);
        }

        if (!$this->users->delete($this->entityId($id))) {
            $this->json(['error' => 'Account was not found.'], 404);
            return;
        }

        $this->json(['data' => ['deleted' => true]]);
    }

    private function validatedBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw === false ? '' : $raw, true);

        if (!is_array($data)) {
            $this->json(['error' => 'Invalid JSON request body.'], 400);
        }

        $accountId = trim((string)($data['account_id'] ?? ''));
        $userId = trim((string)($data['user_id'] ?? ''));

        if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/', $accountId)) {
            $this->json(['error' => 'Account ID must contain 1-32 letters, numbers, underscores, or hyphens.'], 422);
        }

        if (!preg_match('/^\d{1,32}$/', $userId)) {
            $this->json(['error' => 'User ID must contain 1-32 digits.'], 422);
        }

        return [$accountId, $userId];
    }

    private function entityId(string $id): int
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            $this->json(['error' => 'Invalid entity ID.'], 422);
        }

        return (int)$id;
    }

    private function databaseError(PDOException $exception): void
    {
        if ($exception->getCode() === '23000') {
            $this->json(['error' => 'This account ID and user ID already exist.'], 409);
            return;
        }

        error_log('X Ads CRUD failed: ' . $exception->getMessage());
        $this->json(['error' => 'Database operation failed.'], 500);
    }

    private function json(array $data, int $status = 200): never
    {
        \Flight::jsonHalt(
            $data,
            $status,
            true,
            'utf-8',
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}
