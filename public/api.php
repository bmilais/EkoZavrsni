<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

Env::load(__DIR__ . '/../.env');

header('Content-Type: application/json; charset=utf-8');

try {
  $router = new Router();

  $routes = require __DIR__ . '/../routes/api.php';
  $routes($router);

  $req = new Request();
  $res = new Response();

  $router->dispatch($req, $res);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error'   => 'Greška na serveru: ' . $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
}
