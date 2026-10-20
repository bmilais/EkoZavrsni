<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
  private static bool $started = false;

  public static function start(): void
  {
    if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
      self::$started = true;
      return;
    }

    $cfg = require __DIR__ . '/../../config/security.php';
    $s   = $cfg['session'];

    session_set_cookie_params([
      'lifetime' => 0,
      'path'     => '/',
      'httponly' => $s['cookie_httponly'],
      'secure'   => $s['cookie_secure'],
      'samesite' => $s['same_site'],
    ]);

    session_start();
    self::$started = true;
  }

  public static function get(string $key, mixed $default = null): mixed
  {
    self::start();
    return $_SESSION[$key] ?? $default;
  }

  public static function set(string $key, mixed $value): void
  {
    self::start();
    $_SESSION[$key] = $value;
  }

  public static function remove(string $key): void
  {
    self::start();
    unset($_SESSION[$key]);
  }

  /**
   * Novi ID sesije, stara sesija se briše (zove se nakon prijave i odjave).
   * Tko je unaprijed znao/podmetnuo stari ID, s njim više ne dobiva pristup.
   */
  public static function regenerate(): void
  {
    self::start();
    session_regenerate_id(true);
  }

  public static function destroy(): void
  {
    self::start();
    $_SESSION = [];
    session_destroy();
  }
}