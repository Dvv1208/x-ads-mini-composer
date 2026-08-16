<?php

declare(strict_types=1);

use App\Database;
use App\UserController;
use App\UserRepository;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (PHP_SAPI === 'cli-server' && $path !== '/') {
    $file = __DIR__ . $path;
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

header('Cache-Control: no-store');

try {
    $controller = new UserController(new UserRepository(Database::connect($config)));
} catch (Throwable $exception) {
    error_log('X Ads Flight bootstrap failed: ' . $exception->getMessage());
    Flight::jsonHalt(['error' => 'Could not connect to the X Ads database.'], 500);
}

Flight::route('GET /', static function (): void {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/index.html');
});

Flight::route('GET /api/users', [$controller, 'index']);
Flight::route('POST /api/users', [$controller, 'create']);
Flight::route('GET /api/users/@id', [$controller, 'show']);
Flight::route('PUT /api/users/@id', [$controller, 'update']);
Flight::route('DELETE /api/users/@id', [$controller, 'delete']);

Flight::map('notFound', static function (): void {
    Flight::jsonHalt(['error' => 'Route not found.'], 404);
});

Flight::start();
