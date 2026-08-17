<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    private const SESSION_NAME = 'x_ads_session';
    private const IDLE_TIMEOUT = 28800;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
        if ($lastActivity > 0 && time() - $lastActivity > self::IDLE_TIMEOUT) {
            self::logout();
            session_start();
        }

        $_SESSION['last_activity'] = time();
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = [
            'entity_id' => (int)$user['entity_id'],
            'username' => (string)$user['username'],
            'role' => (string)$user['role'],
        ];
        $_SESSION['last_activity'] = time();
        unset($_SESSION['csrf_token']);
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function user(): ?array
    {
        $user = $_SESSION['admin_user'] ?? null;

        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function csrfToken(): string
    {
        if (!is_string($_SESSION['csrf_token'] ?? null)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        $expected = $_SESSION['csrf_token'] ?? '';

        return is_string($token)
            && is_string($expected)
            && $expected !== ''
            && hash_equals($expected, $token);
    }
}
