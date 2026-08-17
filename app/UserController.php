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
        }

        $this->json(['data' => $user]);
    }

    public function create(): void
    {
        [$accountId, $userId, $cookie] = $this->validatedBody(true);

        try {
            $this->json(['data' => $this->users->create($accountId, $userId, (string)$cookie)], 201);
        } catch (PDOException $exception) {
            $this->databaseError($exception);
        }
    }

    public function update(string $id): void
    {
        $entityId = $this->entityId($id);
        if ($this->users->find($entityId) === null) {
            $this->json(['error' => 'Account was not found.'], 404);
        }

        [$accountId, $userId, $cookie] = $this->validatedBody(false);

        try {
            $this->json(['data' => $this->users->update($entityId, $accountId, $userId, $cookie)]);
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
        }

        $this->json(['data' => ['deleted' => true]]);
    }

    private function validatedBody(bool $creating): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw === false ? '' : $raw, true);

        if (!is_array($data)) {
            $this->json(['error' => 'Invalid JSON request body.'], 400);
        }

        $accountId = trim((string)($data['account_id'] ?? ''));
        $userId = trim((string)($data['user_id'] ?? ''));
        $cookieInput = trim((string)($data['cookie'] ?? ''));

        if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/', $accountId)) {
            $this->json(['error' => 'Account ID must contain 1-32 letters, numbers, underscores, or hyphens.'], 422);
        }

        if (!preg_match('/^\d{1,32}$/', $userId)) {
            $this->json(['error' => 'User ID must contain 1-32 digits.'], 422);
        }

        if ($creating && $cookieInput === '') {
            $this->json(['error' => 'X Cookie is required for a new account.'], 422);
        }

        // Empty cookie while editing means "keep the existing cookie".
        $cookie = $cookieInput === '' ? null : $this->normalizeCookie($cookieInput);

        if ($cookie !== null && !$this->hasCt0($cookie)) {
            $this->json(['error' => 'X Cookie must contain a ct0 value.'], 422);
        }

        return [$accountId, $userId, $cookie];
    }

    private function normalizeCookie(string $cookie): string
    {
        $cookie = trim($cookie);
        if (stripos($cookie, 'cookie:') === 0) {
            $cookie = trim(substr($cookie, strlen('cookie:')));
        }

        return $cookie;
    }

    private function hasCt0(string $cookie): bool
    {
        return preg_match('/(?:^|;\s*)ct0=([^;]+)/', $cookie) === 1;
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
