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
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
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
      'lozinka' => $data['lozinka'] !== '' ? password_hash($data['lozinka'], PASSWORD_DEFAULT) : null,
      'ovlasti' => (int)$data['ovlasti'],
    ]);
    Db::commit();
    return Db::lastId('PROFESORI_ID_SEQ');
  }

  /**
   * Prazna $data['lozinka'] = zadrži postojeću lozinku (kolona se ne dira).
   */
  public static function update(int $id, array $data): void
  {
    $stmt = Db::connect()->prepare(
      'UPDATE PROFESORI
          SET IME     = :ime,
              PREZIME = :prezime,
              EMAIL   = :email,
              OVLASTI = :ovlasti
        WHERE ID = :id AND DELETED = 0'
    );
    $stmt->execute([
      'ime'     => $data['ime'],
      'prezime' => $data['prezime'],
      'email'   => $data['email'] !== '' ? $data['email'] : null,
      'ovlasti' => (int)$data['ovlasti'],
      'id'      => $id,
    ]);
    Db::commit();

    if ($data['lozinka'] !== '') {
      self::postaviLozinku($id, $data['lozinka']);
    }
  }

  /**
   * Sprema bcrypt hash lozinke (60 znakova).
   * Preduvjet: ALTER TABLE PROFESORI MODIFY (LOZINKA VARCHAR2(255));
   */
  public static function postaviLozinku(int $id, string $lozinka): void
  {
    $stmt = Db::connect()->prepare('UPDATE PROFESORI SET LOZINKA = :lozinka WHERE ID = :id');
    $stmt->execute(['lozinka' => password_hash($lozinka, PASSWORD_DEFAULT), 'id' => $id]);
    Db::commit();
  }

  public static function delete(int $id): void
  {
    $stmt = Db::connect()->prepare('UPDATE PROFESORI SET DELETED = 1 WHERE ID = :id');
    $stmt->execute(['id' => $id]);
    Db::commit();
  }

  /** @param int[] $ids */
  public static function deleteMany(array $ids): int
  {
    return Db::softDeleteMany('PROFESORI', $ids);
  }

  public static function ulogaLabel(int $ovlasti): string
  {
    return $ovlasti === 0 ? 'Admin' : 'Nastavnik';
  }
}