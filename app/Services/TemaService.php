<?php
declare(strict_types=1);

namespace App\Services;

final class TemaService
{
  public static function list(): array
  {
    $stmt = Db::connect()->prepare(
      'SELECT t.ID, t.NAZIV,
              p.NAZIV AS PREDMET_NAZIV,
              pr.PREZIME || \' \' || pr.IME AS PROFESOR_NAZIV,
              r.NAZIV AS RAZRED_NAZIV,
              c.NAZIV AS CIKLUS_NAZIV
         FROM TEME t
         JOIN PREDMETI p  ON p.ID  = t.IDPREDMETA
         JOIN PROFESORI pr ON pr.ID = t.IDPROFESORA
         JOIN RAZREDI r    ON r.ID  = t.IDRAZRED
         LEFT JOIN CIKLUSI c ON c.ID = t.IDCIKLUSA
        WHERE t.DELETED = 0
        ORDER BY t.NAZIV'
    );
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public static function get(int $id): ?array
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, IDPREDMETA, IDPROFESORA, IDRAZRED, IDCIKLUSA, NAZIV
         FROM TEME
        WHERE ID = :id AND DELETED = 0'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $db = Db::connect();
    $stmt = $db->prepare(
      'INSERT INTO TEME (IDPREDMETA, IDPROFESORA, IDRAZRED, IDCIKLUSA, NAZIV)
       VALUES (:idpredmeta, :idprofesora, :idrazred, :idciklusa, :naziv)'
    );
    $stmt->execute([
      'idpredmeta'  => (int)$data['idpredmeta'],
      'idprofesora' => (int)$data['idprofesora'],
      'idrazred'    => (int)$data['idrazred'],
      'idciklusa'   => $data['idciklusa'] !== '' ? (int)$data['idciklusa'] : null,
      'naziv'       => $data['naziv'],
    ]);
    return Db::lastId('TEME_ID_SEQ');
  }

  public static function update(int $id, array $data): void
  {
    $stmt = Db::connect()->prepare(
      'UPDATE TEME
          SET IDPREDMETA  = :idpredmeta,
              IDPROFESORA = :idprofesora,
              IDRAZRED    = :idrazred,
              IDCIKLUSA   = :idciklusa,
              NAZIV       = :naziv
        WHERE ID = :id AND DELETED = 0'
    );
    $stmt->execute([
      'idpredmeta'  => (int)$data['idpredmeta'],
      'idprofesora' => (int)$data['idprofesora'],
      'idrazred'    => (int)$data['idrazred'],
      'idciklusa'   => $data['idciklusa'] !== '' ? (int)$data['idciklusa'] : null,
      'naziv'       => $data['naziv'],
      'id'          => $id,
    ]);
  }

  public static function delete(int $id): void
  {
    $stmt = Db::connect()->prepare('UPDATE TEME SET DELETED = 1 WHERE ID = :id');
    $stmt->execute(['id' => $id]);
  }
}
