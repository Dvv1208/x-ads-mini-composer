<?php

declare(strict_types=1);

namespace App;

final class AuthController
{
    public function __construct(private readonly AdminUserRepository $users)
    {
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isAdmin() ? 'admin' : './');
        }

        $this->render('login', [
            'error' => null,
            'target' => $this->target((string)($_GET['redirect'] ?? '')),
            'csrfToken' => Auth::csrfToken(),
        ]);
    }

    public function login(): void
    {
        $target = $this->target((string)($_POST['redirect'] ?? ''));

        if (!Auth::verifyCsrf((string)($_POST['csrf_token'] ?? ''))) {
            $this->renderLoginError('Your session expired. Please try again.', $target, 403);
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $user = $username !== '' ? $this->users->findByUsername($username) : null;

        $lockedUntil = is_array($user) ? strtotime((string)($user['locked_until'] ?? '')) : false;
        if ($lockedUntil !== false && $lockedUntil > time()) {
            $this->renderLoginError('Too many failed attempts. Try again later.', $target, 429);
        }

        $valid = is_array($user)
            && (int)$user['is_active'] === 1
            && password_verify($password, (string)$user['password_hash']);

        if (!$valid) {
            if (is_array($user)) {
                $this->users->recordFailure((int)$user['entity_id']);
            }
            usleep(250000);
            $this->renderLoginError('Invalid username or password.', $target, 401);
        }

        if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash(
                (int)$user['entity_id'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        $this->users->recordLogin((int)$user['entity_id']);
        Auth::login($user);

        if ($target === 'admin' && !Auth::isAdmin()) {
            $target = './';
        }

        $this->redirect($target);
    }

    public function logout(): void
    {
        if (!Auth::verifyCsrf((string)($_POST['csrf_token'] ?? ''))) {
            \Flight::jsonHalt(['error' => 'Invalid CSRF token.'], 403);
        }

        Auth::logout();
        $this->redirect('login');
    }

    private function renderLoginError(string $error, string $target, int $status): never
    {
        http_response_code($status);
        $this->render('login', [
            'error' => $error,
            'target' => $target,
            'csrfToken' => Auth::csrfToken(),
        ]);
        exit;
    }

    private function render(string $view, array $variables = []): void
    {
        extract($variables, EXTR_SKIP);
        require dirname(__DIR__) . '/views/' . $view . '.php';
    }

    private function target(string $target): string
    {
        return $target === 'admin' ? 'admin' : './';
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }
}
