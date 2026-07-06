<?php
declare(strict_types=1);

namespace App\Services;

final class Db
{
  private static ?\PDO $conn = null;

  public static function connect(): \PDO
  {
    if (self::$conn) return self::$conn;

    $cfg = require __DIR__ . '/../../config/database.php';

    $dsn = "oci:dbname=//{$cfg['host']}:{$cfg['port']}/{$cfg['service_name']};charset={$cfg['charset']}";

    self::$conn = new \PDO($dsn, $cfg['username'], $cfg['password'], [
      \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    return self::$conn;
  }

  public static function ping(): bool
  {
    try {
      self::connect()->query('SELECT 1');
      return true;
    } catch (\Throwable) {
      return false;
    }
  }
}