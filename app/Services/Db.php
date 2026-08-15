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

    $tns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$cfg['host']})(PORT={$cfg['port']}))(CONNECT_DATA=(SID={$cfg['sid']})))";
    $dsn = "oci:dbname={$tns};charset={$cfg['charset']}";

    self::$conn = new \PDO($dsn, $cfg['username'], $cfg['password'], [
      \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    return self::$conn;
  }

  public static function ping(): bool
  {
    try {
      self::connect()->query('SELECT 1 FROM DUAL');
      return true;
    } catch (\Throwable) {
      return false;
    }
  }

  /**
   * Vraća ID zadnje umetnutog retka. OCI driver nema PDO::lastInsertId(),
   * pa se ID dohvaća preko sequence u istoj sesiji (CURRVAL).
   */
  public static function lastId(string $sequence): int
  {
    $sql = 'SELECT ' . $sequence . '.CURRVAL FROM DUAL';
    $val = self::connect()->query($sql)->fetchColumn();
    return (int)$val;
  }
}