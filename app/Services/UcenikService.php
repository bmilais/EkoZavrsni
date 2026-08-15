<?php
declare(strict_types=1);

namespace App\Services;

final class UcenikService
{
  public static function list(): array
  {
    $stmt = Db::connect()->prepare(
      'SELECT u.ID, u.IME, u.PREZIME, u.EMAIL, u.SMJER, r.NAZIV AS RAZRED_NAZIV
         FROM UCENICI u
         JOIN RAZREDI r ON r.ID = u.IDRAZRED
        WHERE u.DELETED = 0
        ORDER BY u.PREZIME, u.IME'
    );
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public static function get(int $id): ?array
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, IDRAZRED, IME, PREZIME, EMAIL, LOZINKA, SMJER, HASH
         FROM UCENICI
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
      'INSERT INTO UCENICI (IDRAZRED, IME, PREZIME, EMAIL, LOZINKA, SMJER)
       VALUES (:idrazred, :ime, :prezime, :email, :lozinka, :smjer)'
    );
    $stmt->execute([
      'idrazred' => (int)$data['idrazred'],
      'ime'      => $data['ime'],
      'prezime'  => $data['prezime'],
      'email'    => $data['email'],
      'lozinka'  => $data['lozinka'],
      'smjer'    => (int)$data['smjer'],
    ]);
    return Db::lastId('UCENICI_ID_SEQ');
  }

  public static function update(int $id, array $data): void
  {
    $stmt = Db::connect()->prepare(
      'UPDATE UCENICI
          SET IDRAZRED = :idrazred,
              IME      = :ime,
              PREZIME  = :prezime,
              EMAIL    = :email,
              LOZINKA  = :lozinka,
              SMJER    = :smjer
        WHERE ID = :id AND DELETED = 0'
    );
    $stmt->execute([
      'idrazred' => (int)$data['idrazred'],
      'ime'      => $data['ime'],
      'prezime'  => $data['prezime'],
      'email'    => $data['email'],
      'lozinka'  => $data['lozinka'],
      'smjer'    => (int)$data['smjer'],
      'id'       => $id,
    ]);
  }

  public static function delete(int $id): void
  {
    $stmt = Db::connect()->prepare('UPDATE UCENICI SET DELETED = 1 WHERE ID = :id');
    $stmt->execute(['id' => $id]);
  }

  public static function smjerLabel(int $smjer): string
  {
    return $smjer === 1 ? 'Ekonomist' : 'Trgovac';
  }
}
