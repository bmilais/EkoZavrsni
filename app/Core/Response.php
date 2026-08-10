<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
  public function text(string $body, int $status = 200): void
  {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $body;
  }

  public function html(string $body, int $status = 200): void
  {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo $body;
  }

  public function json(array $data, int $status = 200): void
  {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  public function url(string $path): string
  {
    $base = rtrim(getenv('APP_URL') ?: '', '/');
    return $base . '/' . ltrim($path, '/');
  }

  public function redirect(string $path): void
  {
    header('Location: ' . $this->url($path));
    exit;
  }
}