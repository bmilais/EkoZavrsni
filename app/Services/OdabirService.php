<?php
declare(strict_types=1);

namespace App\Services;

final class OdabirService
{
  public static function openCiklus(): ?array
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, NAZIV, STATUS, MAX_UCENIKA_PO_MENTORU, MAX_TEMA_PO_PREDMETU,
              TO_CHAR(DATUM_OTVARANJA, \'YYYY-MM-DD HH24:MI\') AS DATUM_OTVARANJA,
              TO_CHAR(DATUM_ZATVARANJA, \'YYYY-MM-DD HH24:MI\') AS DATUM_ZATVARANJA
         FROM CIKLUSI
        WHERE STATUS = \'otvoreno\' AND DELETED = 0
        ORDER BY ID
        FETCH FIRST 1 ROW ONLY'
    );
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ?: null;
  }

  /**
   * Dostupne teme za učenika: njegov razred + otvoreni ciklus,
   * bez tema koje je učenik već aktivno odabrao. Uz brojače popunjenosti
   * predmeta i mentora da frontend može onemogućiti odabir.
   *
   * $ucenikCiklusId je ciklus u koji je učenik uvezen (UCENICI.IDCIKLUS).
   * Učenik smije birati teme samo dok je BAŠ taj ciklus otvoren - ako je
   * trenutno otvoren neki drugi (noviji) ciklus, učenik iz starog ciklusa
   * ne smije vidjeti/birati njegove teme.
   */
  public static function dostupneTeme(int $razredId, int $ucenikId, int $ucenikCiklusId): array
  {
    $ciklus = self::openCiklus();
    if (!$ciklus || (int)$ciklus['ID'] !== $ucenikCiklusId) {
      return [];
    }

    $stmt = Db::connect()->prepare(
      "SELECT t.ID, t.NAZIV,
              p.ID AS PREDMET_ID, p.NAZIV AS PREDMET_NAZIV, p.\"LIMIT\" AS PREDMET_LIMIT,
              pr.ID AS PROFESOR_ID, pr.PREZIME || ' ' || pr.IME AS PROFESOR_NAZIV,
              (SELECT COUNT(*) FROM ODABIRI o JOIN TEME tx ON tx.ID = o.IDTEME
                WHERE o.IDTEME = t.ID AND o.STATUS = 'aktivan' AND o.DELETED = 0
                  AND tx.IDCIKLUSA = :ciklus) AS BR_ODABIRA,
              (SELECT COUNT(*) FROM ODABIRI o JOIN TEME t2 ON t2.ID = o.IDTEME
                WHERE t2.IDPREDMETA = p.ID AND o.STATUS = 'aktivan' AND o.DELETED = 0
                  AND t2.IDCIKLUSA = :ciklus) AS P_BR,
              (SELECT COUNT(*) FROM ODABIRI o JOIN TEME t3 ON t3.ID = o.IDTEME
                WHERE t3.IDPROFESORA = pr.ID AND o.STATUS = 'aktivan' AND o.DELETED = 0
                  AND t3.IDCIKLUSA = :ciklus) AS M_BR,
              :mentor_max AS M_MAX
         FROM TEME t
         JOIN PREDMETI p   ON p.ID  = t.IDPREDMETA AND p.DELETED = 0
         JOIN PROFESORI pr ON pr.ID = t.IDPROFESORA AND pr.DELETED = 0
        WHERE t.DELETED = 0
          AND t.IDRAZRED = :razred
          AND t.IDCIKLUSA = :ciklus
          AND NOT EXISTS (
                SELECT 1 FROM ODABIRI o2
                  JOIN TEME t4 ON t4.ID = o2.IDTEME AND t4.DELETED = 0
                 WHERE o2.IDTEME = t.ID AND o2.STATUS = 'aktivan' AND o2.DELETED = 0
                   AND o2.IDUCENIKA = :ucenik
                   AND t4.IDCIKLUSA = :ciklus)
        ORDER BY p.NAZIV, t.NAZIV"
    );
    $stmt->execute([
      'razred'      => $razredId,
      'ciklus'      => (int)$ciklus['ID'],
      'ucenik'      => $ucenikId,
      'mentor_max'  => $ciklus['MAX_UCENIKA_PO_MENTORU'],
    ]);
    return $stmt->fetchAll();
  }

  public static function mojiOdabiri(int $ucenikId): array
  {
    $stmt = Db::connect()->prepare(
      "SELECT o.ID AS OID, o.STATUS, o.OBRAZLOZENJE,
              TO_CHAR(o.CREATED, 'YYYY-MM-DD HH24:MI') AS DATUM,
              TO_CHAR(o.DATUM_PONISTENJA, 'YYYY-MM-DD HH24:MI') AS DATUM_PONISTENJA,
              t.NAZIV AS TEMA_NAZIV,
              t.IDCIKLUSA AS IDCIKLUSA,
              p.NAZIV AS PREDMET_NAZIV,
              pr.PREZIME || ' ' || pr.IME AS PROFESOR_NAZIV
         FROM ODABIRI o
         JOIN TEME t     ON t.ID = o.IDTEME AND t.DELETED = 0
         JOIN PREDMETI p ON p.ID = t.IDPREDMETA AND p.DELETED = 0
         JOIN PROFESORI pr ON pr.ID = t.IDPROFESORA AND pr.DELETED = 0
        WHERE o.IDUCENIKA = :ucenik AND o.DELETED = 0
        ORDER BY o.CREATED DESC"
    );
    $stmt->execute(['ucenik' => $ucenikId]);
    return $stmt->fetchAll();
  }

  /**
   * Učenik bira temu. Vraća null ako je uspjeh, inače poruku greške.
   */
  public static function odaberi(int $ucenikId, int $razredId, int $ucenikCiklusId, int $temaId): ?string
  {
    if ($temaId <= 0) {
      return 'Nepoznata tema.';
    }

    $db = Db::connect();

    $stmt = $db->prepare(
      'SELECT t.ID, t.IDPREDMETA, t.IDPROFESORA, t.IDRAZRED, t.IDCIKLUSA, t.NAZIV
         FROM TEME t
        WHERE t.ID = :id AND t.DELETED = 0'
    );
    $stmt->execute(['id' => $temaId]);
    $tema = $stmt->fetch();
    if (!$tema) {
      return 'Tema nije pronađena.';
    }

    if ((int)$tema['IDRAZRED'] !== $razredId) {
      return 'Tema nije namijenjena vašem razredu.';
    }

    $ciklus = self::openCiklus();
    if (!$ciklus) {
      return 'Trenutno nema otvorenog ciklusa za odabir.';
    }
    if ((int)$ciklus['ID'] !== $ucenikCiklusId) {
      return 'Vaš ciklus trenutno nije otvoren za odabir.';
    }
    if ((int)$tema['IDCIKLUSA'] !== (int)$ciklus['ID']) {
      return 'Tema nije u aktivnom ciklusu.';
    }

    // Brojanje i upis idu u jednu transakciju sa zaključanim retcima.
    // Bez beginTransaction() bi autocommit odmah otpustio FOR UPDATE.
    $db->beginTransaction();
    try {
      $greska = self::odaberiZakljucano($db, $ucenikId, $tema, $ciklus);
      if ($greska === null) {
        $db->commit();
      } else {
        $db->rollBack();
      }
      return $greska;
    } catch (\Throwable $e) {
      $db->rollBack();
      // ORA-00001 = jedinstveni indeks u bazi (korak 2) uhvatio je duplikat
      if (str_contains($e->getMessage(), 'ORA-00001')) {
        return 'Već imate aktivno odabranu temu.';
      }
      throw $e;
    }
  }

  /**
   * Izvršava se unutar transakcije iz odaberi(). Ovdje se NE smije zvati
   * Db::commit() - commit/rollback radi samo odaberi().
   * Redoslijed zaključavanja je uvijek isti (učenik -> predmet -> mentor),
   * da dva zahtjeva ne čekaju jedan drugoga zauvijek (deadlock).
   */
  private static function odaberiZakljucano(\PDO $db, int $ucenikId, array $tema, array $ciklus): ?string
  {
    $ciklusId = (int)$ciklus['ID'];

    $lock = $db->prepare('SELECT ID FROM UCENICI WHERE ID = :id FOR UPDATE');
    $lock->execute(['id' => $ucenikId]);
    $lock->fetchColumn();

    $lock = $db->prepare('SELECT "LIMIT" FROM PREDMETI WHERE ID = :id FOR UPDATE');
    $lock->execute(['id' => $tema['IDPREDMETA']]);
    $limit = $lock->fetchColumn();

    $lock = $db->prepare('SELECT ID FROM PROFESORI WHERE ID = :id FOR UPDATE');
    $lock->execute(['id' => $tema['IDPROFESORA']]);
    $lock->fetchColumn();

    // Tek sad brojimo - ako smo čekali na drugi zahtjev, vidimo i njegov upis.
    $stmt = $db->prepare(
      "SELECT COUNT(*) AS C FROM ODABIRI o
         JOIN TEME t5 ON t5.ID = o.IDTEME AND t5.DELETED = 0
        WHERE o.IDUCENIKA = :ucenik AND o.STATUS = 'aktivan' AND o.DELETED = 0
          AND t5.IDCIKLUSA = :ciklus"
    );
    $stmt->execute(['ucenik' => $ucenikId, 'ciklus' => $ciklusId]);
    if ((int)$stmt->fetchColumn() > 0) {
      return 'Već imate aktivno odabranu temu.';
    }

    if ($limit !== null && $limit !== false && (int)$limit > 0) {
      $stmt = $db->prepare(
        "SELECT COUNT(*) FROM ODABIRI o JOIN TEME t2 ON t2.ID = o.IDTEME
          WHERE t2.IDPREDMETA = :pid AND o.STATUS = 'aktivan' AND o.DELETED = 0
            AND t2.IDCIKLUSA = :ciklus"
      );
      $stmt->execute(['pid' => $tema['IDPREDMETA'], 'ciklus' => $ciklusId]);
      if ((int)$stmt->fetchColumn() >= (int)$limit) {
        return 'Predmet je popunjen (limit dosegnut).';
      }
    }

    if ($ciklus['MAX_UCENIKA_PO_MENTORU'] !== null) {
      $stmt = $db->prepare(
        "SELECT COUNT(*) FROM ODABIRI o JOIN TEME t3 ON t3.ID = o.IDTEME
          WHERE t3.IDPROFESORA = :prof AND o.STATUS = 'aktivan' AND o.DELETED = 0
            AND t3.IDCIKLUSA = :ciklus"
      );
      $stmt->execute(['prof' => $tema['IDPROFESORA'], 'ciklus' => $ciklusId]);
      if ((int)$stmt->fetchColumn() >= (int)$ciklus['MAX_UCENIKA_PO_MENTORU']) {
        return 'Mentor je popunjen (maksimalan broj učenika).';
      }
    }

    // Ako učenik već ima red za ovu temu (npr. poništeni), reaktiviraj ga
    // umjesto novog INSERT-a — ODABIRI_UK ograničava na (IDTEME, IDUCENIKA).
    $postoji = $db->prepare(
      "SELECT ID, STATUS FROM ODABIRI
        WHERE IDTEME = :tema AND IDUCENIKA = :ucenik AND DELETED = 0"
    );
    $postoji->execute(['tema' => $tema['ID'], 'ucenik' => $ucenikId]);
    $red = $postoji->fetch();

    if ($red && $red['STATUS'] === 'aktivan') {
      return 'Već ste odabrali tu temu.';
    }

    if ($red) {
      $upd = $db->prepare(
        "UPDATE ODABIRI
            SET STATUS = 'aktivan',
                OBRAZLOZENJE = NULL,
                PONISTIO_ID = NULL,
                DATUM_PONISTENJA = NULL,
                CREATED = SYSTIMESTAMP,
                UPDATED = SYSTIMESTAMP
          WHERE ID = :id"
      );
      $upd->execute(['id' => $red['ID']]);
      return null;
    }

    $stmt = $db->prepare(
      "INSERT INTO ODABIRI (IDTEME, IDUCENIKA, DELETED, STATUS)
       VALUES (:tema, :ucenik, 0, 'aktivan')"
    );
    $stmt->execute(['tema' => $tema['ID'], 'ucenik' => $ucenikId]);
    return null;
  }


  public static function ponisti(int $ucenikId, int $ucenikCiklusId, int $odabirId, string $razlog): ?string
  {
    if ($odabirId <= 0) {
      return 'Nepoznat odabir.';
    }
    $razlog = trim($razlog);
    if ($razlog === '') {
      return 'Razlog poništenja je obavezan.';
    }

    $ciklus = self::openCiklus();
    if (!$ciklus || (int)$ciklus['ID'] !== $ucenikCiklusId) {
      return 'Ciklus je zaključan - odabir se više ne može poništiti. Obratite se administratoru.';
    }

    $stmt = Db::connect()->prepare(
      "UPDATE ODABIRI
          SET STATUS = 'ponisten',
              OBRAZLOZENJE = :razlog,
              PONISTIO_ID = NULL,
              DATUM_PONISTENJA = SYSTIMESTAMP
        WHERE ID = :id AND IDUCENIKA = :ucenik AND STATUS = 'aktivan' AND DELETED = 0
          AND EXISTS (SELECT 1
                        FROM TEME t
                        JOIN CIKLUSI c ON c.ID = t.IDCIKLUSA
                       WHERE t.ID = ODABIRI.IDTEME
                         AND c.ID = :ciklus
                         AND c.STATUS = 'otvoreno'
                         AND c.DELETED = 0)"
    );
    $stmt->execute([
      'razlog' => $razlog,
      'id'     => $odabirId,
      'ucenik' => $ucenikId,
      'ciklus' => $ucenikCiklusId,
    ]);
    Db::commit();

    if ($stmt->rowCount() === 0) {
      return 'Aktivni odabir nije pronađen.';
    }
    return null;
  }
}