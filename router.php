<?php

declare(strict_types=1);

use App\Database;
use App\AdminUserRepository;
use App\Auth;
use App\AuthController;
use App\UserController;
use App\UserRepository;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (PHP_SAPI === 'cli-server' && $path !== '/') {
    $file = __DIR__ . $path;
    $publicFile = str_starts_with($path, '/assets/')
        || $path === '/favicon.ico'
        || $path === '/api.php';
    if ($publicFile && is_file($file)) {
        return false;
    }
}

if (PHP_SAPI === 'cli-server') {
    $_SERVER['SCRIPT_NAME'] = '/router.php';
}

require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

header('Cache-Control: no-store');
Auth::start();

try {
    $database = Database::connect($config);
    $controller = new UserController(new UserRepository($database));
    $authController = new AuthController(new AdminUserRepository($database));
} catch (Throwable $exception) {
    error_log('X Ads Flight bootstrap failed: ' . $exception->getMessage());
    Flight::jsonHalt(['error' => 'Could not connect to the X Ads database.'], 500);
}

$render = static function (string $view, array $variables = []): void {
    extract($variables, EXTR_SKIP);
    require __DIR__ . '/views/' . $view . '.php';
};

$requireLogin = static function (bool $accountManager = false): void {
    if (!Auth::check()) {
        $redirect = $accountManager ? '?redirect=admin' : '';
        header('Location: login' . $redirect, true, 303);
        exit;
    }

    if ($accountManager && !Auth::canManageAccounts()) {
        Flight::halt(403, 'Forbidden');
    }
};

$requireAccountApi = static function (bool $verifyCsrf = false, bool $adminOnly = false): void {
    if (!Auth::check()) {
        Flight::jsonHalt(['error' => 'Authentication required.'], 401);
    }

    if (!Auth::canManageAccounts() || ($adminOnly && !Auth::isAdmin())) {
        Flight::jsonHalt([
            'error' => $adminOnly
                ? 'Administrator permission required.'
                : 'Account management permission required.',
        ], 403);
    }

    if ($verifyCsrf && !Auth::verifyCsrf($_SERVER['HTTP_X_APP_CSRF_TOKEN'] ?? null)) {
        Flight::jsonHalt(['error' => 'Invalid CSRF token.'], 403);
    }
};

Flight::route('GET /login', [$authController, 'showLogin']);
Flight::route('POST /login', [$authController, 'login']);
Flight::route('POST /logout', [$authController, 'logout']);

Flight::route('GET /', static function () use ($requireLogin, $render): void {
    $requireLogin();
    $render('frontend', [
        'currentUser' => Auth::user(),
        'csrfToken' => Auth::csrfToken(),
    ]);
});

Flight::route('GET /admin', static function () use ($requireLogin, $render): void {
    $requireLogin(true);
    $render('admin', [
        'currentUser' => Auth::user(),
        'csrfToken' => Auth::csrfToken(),
        'canEditAccounts' => Auth::isAdmin(),
    ]);
});

Flight::route('GET /api/users', static function () use ($requireAccountApi, $controller): void {
    $requireAccountApi();
    $controller->index();
});
Flight::route('POST /api/users', static function () use ($requireAccountApi, $controller): void {
    $requireAccountApi(true, true);
    $controller->create();
});
Flight::route('GET /api/users/@id', static function (string $id) use ($requireAccountApi, $controller): void {
    $requireAccountApi();
    $controller->show($id);
});
Flight::route('PUT /api/users/@id', static function (string $id) use ($requireAccountApi, $controller): void {
    $requireAccountApi(true, true);
    $controller->update($id);
});
Flight::route('DELETE /api/users/@id', static function (string $id) use ($requireAccountApi, $controller): void {
    $requireAccountApi(true, true);
    $controller->delete($id);
});

Flight::map('notFound', static function (): void {
    Flight::jsonHalt(['error' => 'Route not found.'], 404);
});

Flight::start();
