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
   */
  public static function dostupneTeme(int $razredId, int $ucenikId): array
  {
    $ciklus = self::openCiklus();
    if (!$ciklus) {
      return [];
    }

    $stmt = Db::connect()->prepare(
      "SELECT t.ID, t.NAZIV,
              p.ID AS PREDMET_ID, p.NAZIV AS PREDMET_NAZIV, p.\"LIMIT\" AS PREDMET_LIMIT,
              pr.ID AS PROFESOR_ID, pr.PREZIME || ' ' || pr.IME AS PROFESOR_NAZIV,
              (SELECT COUNT(*) FROM ODABIRI o
                WHERE o.IDTEME = t.ID AND o.STATUS = 'aktivan' AND o.DELETED = 0) AS BR_ODABIRA,
              (SELECT COUNT(*) FROM ODABIRI o JOIN TEME t2 ON t2.ID = o.IDTEME
                WHERE t2.IDPREDMETA = p.ID AND o.STATUS = 'aktivan' AND o.DELETED = 0) AS P_BR,
              (SELECT COUNT(*) FROM ODABIRI o JOIN TEME t3 ON t3.ID = o.IDTEME
                WHERE t3.IDPROFESORA = pr.ID AND o.STATUS = 'aktivan' AND o.DELETED = 0) AS M_BR,
              :mentor_max AS M_MAX
         FROM TEME t
         JOIN PREDMETI p   ON p.ID  = t.IDPREDMETA AND p.DELETED = 0
         JOIN PROFESORI pr ON pr.ID = t.IDPROFESORA AND pr.DELETED = 0
        WHERE t.DELETED = 0
          AND t.IDRAZRED = :razred
          AND (t.IDCIKLUSA IS NULL OR t.IDCIKLUSA = :ciklus)
          AND NOT EXISTS (
                SELECT 1 FROM ODABIRI o2
                 WHERE o2.IDTEME = t.ID AND o2.STATUS = 'aktivan' AND o2.DELETED = 0
                   AND o2.IDUCENIKA = :ucenik)
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
  public static function odaberi(int $ucenikId, int $razredId, int $temaId): ?string
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
    if ($tema['IDCIKLUSA'] !== null && (int)$tema['IDCIKLUSA'] !== (int)$ciklus['ID']) {
      return 'Tema nije u aktivnom ciklusu.';
    }

    $stmt = $db->prepare(
      "SELECT COUNT(*) AS C FROM ODABIRI
        WHERE IDUCENIKA = :ucenik AND STATUS = 'aktivan' AND DELETED = 0"
    );
    $stmt->execute(['ucenik' => $ucenikId]);
    if ((int)$stmt->fetchColumn() > 0) {
      return 'Već imate aktivno odabranu temu.';
    }

    $predmet = $db->prepare('SELECT "LIMIT" FROM PREDMETI WHERE ID = :id');
    $predmet->execute(['id' => $tema['IDPREDMETA']]);
    $limit = $predmet->fetchColumn();
    if ($limit !== null && $limit !== false && (int)$limit > 0) {
      $stmt = $db->prepare(
        "SELECT COUNT(*) FROM ODABIRI o JOIN TEME t2 ON t2.ID = o.IDTEME
          WHERE t2.IDPREDMETA = :pid AND o.STATUS = 'aktivan' AND o.DELETED = 0"
      );
      $stmt->execute(['pid' => $tema['IDPREDMETA']]);
      if ((int)$stmt->fetchColumn() >= (int)$limit) {
        return 'Predmet je popunjen (limit dosegnut).';
      }
    }

    if ($ciklus['MAX_UCENIKA_PO_MENTORU'] !== null) {
      $stmt = $db->prepare(
        "SELECT COUNT(*) FROM ODABIRI o JOIN TEME t3 ON t3.ID = o.IDTEME
          WHERE t3.IDPROFESORA = :prof AND o.STATUS = 'aktivan' AND o.DELETED = 0"
      );
      $stmt->execute(['prof' => $tema['IDPROFESORA']]);
      if ((int)$stmt->fetchColumn() >= (int)$ciklus['MAX_UCENIKA_PO_MENTORU']) {
        return 'Mentor je popunjen (maksimalan broj učenika).';
      }
    }

    $stmt = $db->prepare(
      "INSERT INTO ODABIRI (IDTEME, IDUCENIKA, DELETED, STATUS)
       VALUES (:tema, :ucenik, 0, 'aktivan')"
    );
    $stmt->execute(['tema' => $temaId, 'ucenik' => $ucenikId]);

    return null;
  }

  /**
   * Učenik otkazuje svoj aktivni odabir.
   */
  public static function ponisti(int $ucenikId, int $odabirId): ?string
  {
    if ($odabirId <= 0) {
      return 'Nepoznat odabir.';
    }

    $stmt = Db::connect()->prepare(
      "UPDATE ODABIRI
          SET STATUS = 'ponisten',
              OBRAZLOZENJE = 'Otkazao učenik',
              PONISTIO_ID = NULL,
              DATUM_PONISTENJA = SYSTIMESTAMP
        WHERE ID = :id AND IDUCENIKA = :ucenik AND STATUS = 'aktivan' AND DELETED = 0"
    );
    $stmt->execute(['id' => $odabirId, 'ucenik' => $ucenikId]);

    if ($stmt->rowCount() === 0) {
      return 'Aktivni odabir nije pronađen.';
    }
    return null;
  }
}
