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

    $lastError = null;
    for ($attempt = 1; $attempt <= 3; $attempt++) {
      try {
        self::$conn = new \PDO($dsn, $cfg['username'], $cfg['password'], [
          \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
          \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
          \PDO::ATTR_AUTOCOMMIT         => true,
        ]);
        return self::$conn;
      } catch (\PDOException $e) {
        $lastError = $e;
        self::$conn = null;
        if ($attempt < 3) usleep(500_000);
      }
    }
    throw $lastError;
  }

  /**
   * Eksplicitni COMMIT nakon INSERT/UPDATE/DELETE.
   * PDO_OCI ne poštuje pouzdano PDO::ATTR_AUTOCOMMIT niti interno
   * praćenje transakcije (inTransaction()/PDO::commit()), pa se ovdje
   * šalje gola SQL naredba COMMIT koju Oracle uvijek izvrši bez obzira
   * na to kako driver misli da stoji stanje transakcije.
   */
  public static function commit(): void
  {
    self::connect()->exec('COMMIT');
  }

  /**
   * Skupno "soft delete" (DELETED = 1) za više ID-eva odjednom.
   * Ime tablice dolazi isključivo iz koda servisa (nikad od korisnika),
   * a ID-evi se šalju kao bind parametri. Oracle IN lista ima limit od
   * 1000 elemenata pa se radi u paketima po 500. Jedan COMMIT na kraju.
   *
   * @param int[] $ids
   * @return int broj stvarno obrisanih redaka
   */
  public static function softDeleteMany(string $table, array $ids): int
  {
    if (!preg_match('/^[A-Z_]+$/', $table)) {
      throw new \InvalidArgumentException('Neispravno ime tablice.');
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
    if (!$ids) return 0;

    $db      = self::connect();
    $deleted = 0;

    foreach (array_chunk($ids, 500) as $chunk) {
      $params = [];
      $marks  = [];
      foreach ($chunk as $i => $id) {
        $marks[]           = ':id' . $i;
        $params['id' . $i] = $id;
      }
      $stmt = $db->prepare(
        "UPDATE {$table} SET DELETED = 1 WHERE DELETED = 0 AND ID IN (" . implode(',', $marks) . ')'
      );
      $stmt->execute($params);
      $deleted += $stmt->rowCount();
    }

    self::commit();
    return $deleted;
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