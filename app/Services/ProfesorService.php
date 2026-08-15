<?php
declare(strict_types=1);

namespace App\Services;

final class ProfesorService
{
  public static function list(): array
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, IME, PREZIME, EMAIL, OVLASTI, CREATED, UPDATED
         FROM PROFESORI
        WHERE DELETED = 0
        ORDER BY PREZIME, IME'
    );
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public static function get(int $id): ?array
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, IME, PREZIME, EMAIL, LOZINKA, OVLASTI
         FROM PROFESORI
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
      'INSERT INTO PROFESORI (IME, PREZIME, EMAIL, LOZINKA, OVLASTI)
       VALUES (:ime, :prezime, :email, :lozinka, :ovlasti)'
    );
    $stmt->execute([
      'ime'     => $data['ime'],
      'prezime' => $data['prezime'],
      'email'   => $data['email'] !== '' ? $data['email'] : null,
      'lozinka' => $data['lozinka'] !== '' ? $data['lozinka'] : null,
      'ovlasti' => (int)$data['ovlasti'],
    ]);
    return Db::lastId('PROFESORI_ID_SEQ');
  }

  public static function update(int $id, array $data): void
  {
    $stmt = Db::connect()->prepare(
      'UPDATE PROFESORI
          SET IME     = :ime,
              PREZIME = :prezime,
              EMAIL   = :email,
              LOZINKA = :lozinka,
              OVLASTI = :ovlasti
        WHERE ID = :id AND DELETED = 0'
    );
    $stmt->execute([
      'ime'     => $data['ime'],
      'prezime' => $data['prezime'],
      'email'   => $data['email'] !== '' ? $data['email'] : null,
      'lozinka' => $data['lozinka'] !== '' ? $data['lozinka'] : null,
      'ovlasti' => (int)$data['ovlasti'],
      'id'      => $id,
    ]);
  }

  public static function delete(int $id): void
  {
    $stmt = Db::connect()->prepare('UPDATE PROFESORI SET DELETED = 1 WHERE ID = :id');
    $stmt->execute(['id' => $id]);
  }

  public static function ulogaLabel(int $ovlasti): string
  {
    return $ovlasti === 0 ? 'Admin' : 'Nastavnik';
  }
}
