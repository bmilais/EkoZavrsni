<?php
declare(strict_types=1);

namespace App\Services;

final class RazredService
{
  public static function list(): array
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, NAZIV, CREATED, UPDATED
         FROM RAZREDI
        WHERE DELETED = 0
        ORDER BY NAZIV'
    );
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public static function get(int $id): ?array
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, NAZIV
         FROM RAZREDI
        WHERE ID = :id AND DELETED = 0'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $db = Db::connect();
    $stmt = $db->prepare('INSERT INTO RAZREDI (NAZIV) VALUES (:naziv)');
    $stmt->execute(['naziv' => $data['naziv']]);
    return Db::lastId('RAZREDI_ID_SEQ');
  }

  public static function update(int $id, array $data): void
  {
    $stmt = Db::connect()->prepare('UPDATE RAZREDI SET NAZIV = :naziv WHERE ID = :id AND DELETED = 0');
    $stmt->execute(['naziv' => $data['naziv'], 'id' => $id]);
  }

  public static function delete(int $id): void
  {
    $stmt = Db::connect()->prepare('UPDATE RAZREDI SET DELETED = 1 WHERE ID = :id');
    $stmt->execute(['id' => $id]);
  }
}
