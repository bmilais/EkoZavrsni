<?php
declare(strict_types=1);

namespace App\Services;

final class PredmetService
{
  public static function list(): array
  {
    $stmt = Db::connect()->prepare(
      "SELECT ID, NAZIV, SMJER, RAZRED, \"LIMIT\", CREATED, UPDATED
         FROM PREDMETI
        WHERE DELETED = 0
        ORDER BY NAZIV"
    );
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public static function get(int $id): ?array
  {
    $stmt = Db::connect()->prepare(
      "SELECT ID, NAZIV, SMJER, RAZRED, \"LIMIT\"
         FROM PREDMETI
        WHERE ID = :id AND DELETED = 0"
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $db = Db::connect();
    $stmt = $db->prepare(
      "INSERT INTO PREDMETI (NAZIV, SMJER, RAZRED, \"LIMIT\")
       VALUES (:naziv, :smjer, :razred, :limit)"
    );
    $stmt->execute([
      'naziv'  => $data['naziv'],
      'smjer'  => (int)$data['smjer'],
      'razred' => $data['razred'] !== '' ? (int)$data['razred'] : null,
      'limit'  => $data['limit'] !== '' ? (int)$data['limit'] : null,
    ]);
    return Db::lastId('PREDMETI_ID_SEQ');
  }

  public static function update(int $id, array $data): void
  {
    $stmt = Db::connect()->prepare(
      "UPDATE PREDMETI
          SET NAZIV  = :naziv,
              SMJER  = :smjer,
              RAZRED = :razred,
              \"LIMIT\" = :limit
        WHERE ID = :id AND DELETED = 0"
    );
    $stmt->execute([
      'naziv'  => $data['naziv'],
      'smjer'  => (int)$data['smjer'],
      'razred' => $data['razred'] !== '' ? (int)$data['razred'] : null,
      'limit'  => $data['limit'] !== '' ? (int)$data['limit'] : null,
      'id'     => $id,
    ]);
  }

  public static function delete(int $id): void
  {
    $stmt = Db::connect()->prepare('UPDATE PREDMETI SET DELETED = 1 WHERE ID = :id');
    $stmt->execute(['id' => $id]);
  }

  public static function smjerLabel(int $smjer): string
  {
    return $smjer === 1 ? 'Ekonomist' : 'Trgovac';
  }
}
