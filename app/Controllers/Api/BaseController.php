<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;

abstract class BaseController
{
  protected function json(Response $res, array $data, int $status = 200): void
  {
    $res->json($data, $status);
  }

  protected function ok(Response $res, array $data = []): void
  {
    $res->json(array_merge(['success' => true], $data));
  }

  protected function fail(Response $res, string $msg, int $status = 400): void
  {
    $res->json(['success' => false, 'error' => $msg], $status);
  }

  /**
   * Provjera uloge koja vraca JSON (401/403) umjesto redirect/HTML.
   */
  protected function requireRole(Response $res, array $allowedRoles): void
  {
    if (!Auth::check()) {
      $res->json(['success' => false, 'error' => 'Niste prijavljeni.', 'auth' => false], 401);
      exit;
    }
    if (!in_array(Auth::role(), $allowedRoles, true)) {
      $res->json(['success' => false, 'error' => 'Nemate pristup.'], 403);
      exit;
    }
  }

  protected function intParam(Request $req, string $key, int $default = 0): int
  {
    $val = $req->input($key) ?? $req->query($key) ?? $default;
    return (int)$val;
  }
}
