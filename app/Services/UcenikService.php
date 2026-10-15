<?php
declare(strict_types=1);

namespace App\Services;

final class UcenikService
{
  /**
   * @param int|null $ciklusId Ako je zadan, vraća samo učenike tog ciklusa.
   *                           Ako je null, vraća učenike svih ciklusa (npr. za export/pregled).
   */
  public static function list(?int $ciklusId = null): array
  {
    $sql = 'SELECT u.ID, u.IME, u.PREZIME, u.EMAIL, u.SMJER, u.IDCIKLUS, r.NAZIV AS RAZRED_NAZIV
              FROM UCENICI u
              JOIN RAZREDI r ON r.ID = u.IDRAZRED
             WHERE u.DELETED = 0';
    $params = [];
    if ($ciklusId !== null) {
      $sql .= ' AND u.IDCIKLUS = :ciklus';
      $params['ciklus'] = $ciklusId;
    }
    $sql .= ' ORDER BY u.PREZIME, u.IME';

    $stmt = Db::connect()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public static function get(int $id): ?array
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, IDRAZRED, IME, PREZIME, EMAIL, LOZINKA, SMJER, HASH, IDCIKLUS
         FROM UCENICI
        WHERE ID = :id AND DELETED = 0'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
  }

  /**
   * $data['idciklus'] je obavezan - svaki učenik pripada točno jednom ciklusu.
   */
  public static function create(array $data): int
  {
    $db = Db::connect();
    $stmt = $db->prepare(
      'INSERT INTO UCENICI (IDRAZRED, IME, PREZIME, EMAIL, LOZINKA, SMJER, IDCIKLUS)
       VALUES (:idrazred, :ime, :prezime, :email, :lozinka, :smjer, :idciklus)'
    );
    $stmt->execute([
      'idrazred' => (int)$data['idrazred'],
      'ime'      => $data['ime'],
      'prezime'  => $data['prezime'],
      'email'    => $data['email'],
      'lozinka'  => $data['lozinka'],
      'smjer'    => (int)$data['smjer'],
      'idciklus' => (int)$data['idciklus'],
    ]);
    Db::commit();
    return Db::lastId('UCENICI_ID_SEQ');
  }

  /**
   * NAPOMENA: IDCIKLUS se namjerno ne mijenja pri uređivanju - učenik
   * ostaje vezan uz ciklus u koji je uvezen/kreiran.
   */
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
    Db::commit();
  }

  public static function delete(int $id): void
  {
    $stmt = Db::connect()->prepare('UPDATE UCENICI SET DELETED = 1 WHERE ID = :id');
    $stmt->execute(['id' => $id]);
    Db::commit();
  }

  /** @param int[] $ids */
  public static function deleteMany(array $ids): int
  {
    return Db::softDeleteMany('UCENICI', $ids);
  }

  public static function smjerLabel(int $smjer): string
  {
    return $smjer === 1 ? 'Ekonomist' : 'Trgovac';
  }
}
