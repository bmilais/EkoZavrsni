<?php
declare(strict_types=1);

namespace App\Services;

final class NastavnikService
{
  /**
   * Teme nastavnika s popunjenošću (predmet i mentor).
   */
  public static function mojeTeme(int $profesorId): array
  {
    $stmt = Db::connect()->prepare(
      "SELECT t.ID, t.NAZIV,
              p.ID AS PREDMET_ID, p.NAZIV AS PREDMET_NAZIV, p.\"LIMIT\" AS PREDMET_LIMIT,
              r.NAZIV AS RAZRED_NAZIV,
              c.NAZIV AS CIKLUS_NAZIV,
              (SELECT COUNT(*) FROM ODABIRI o
                WHERE o.IDTEME = t.ID AND o.STATUS = 'aktivan' AND o.DELETED = 0) AS BR_ODABIRA,
              (SELECT COUNT(*) FROM ODABIRI o JOIN TEME t2 ON t2.ID = o.IDTEME
                WHERE t2.IDPREDMETA = p.ID AND o.STATUS = 'aktivan' AND o.DELETED = 0) AS P_BR,
              (SELECT COUNT(*) FROM ODABIRI o JOIN TEME t3 ON t3.ID = o.IDTEME
                WHERE t3.IDPROFESORA = :pid2 AND o.STATUS = 'aktivan' AND o.DELETED = 0) AS M_BR,
              :mentor_max AS M_MAX
         FROM TEME t
         JOIN PREDMETI p   ON p.ID  = t.IDPREDMETA AND p.DELETED = 0
         JOIN RAZREDI r    ON r.ID  = t.IDRAZRED AND r.DELETED = 0
         LEFT JOIN CIKLUSI c ON c.ID = t.IDCIKLUSA AND c.DELETED = 0
        WHERE t.DELETED = 0 AND t.IDPROFESORA = :pid1
        ORDER BY t.NAZIV"
    );
    $stmt->execute([
      'pid1'       => $profesorId,
      'pid2'       => $profesorId,
      'mentor_max' => OdabirService::openCiklus()['MAX_UCENIKA_PO_MENTORU'] ?? null,
    ]);
    return $stmt->fetchAll();
  }

  /**
   * Odabiri učenika na temama nastavnika.
   */
  public static function odabiri(int $profesorId, ?int $temaId, string $filter): array
  {
    $where = "t.IDPROFESORA = :pid AND o.DELETED = 0 AND t.DELETED = 0";
    $params = ['pid' => $profesorId];

    if ($temaId !== null && $temaId > 0) {
      $where .= ' AND o.IDTEME = :tema';
      $params['tema'] = $temaId;
    }

    if ($filter === 'aktivni') {
      $where .= " AND o.STATUS = 'aktivan'";
    } elseif ($filter === 'ponisteni') {
      $where .= " AND o.STATUS = 'ponisten'";
    }

    $stmt = Db::connect()->prepare(
      "SELECT o.ID AS OID, o.STATUS, o.OBRAZLOZENJE,
              TO_CHAR(o.CREATED, 'YYYY-MM-DD HH24:MI') AS DATUM_ODABIRA,
              TO_CHAR(o.DATUM_PONISTENJA, 'YYYY-MM-DD HH24:MI') AS DATUM_PONISTENJA,
              u.IME AS U_IME, u.PREZIME AS U_PREZIME, u.EMAIL AS U_EMAIL,
              r.NAZIV AS RAZRED_NAZIV,
              t.NAZIV AS TEMA_NAZIV,
              p.NAZIV AS PREDMET_NAZIV
         FROM ODABIRI o
         JOIN UCENICI u  ON u.ID = o.IDUCENIKA AND u.DELETED = 0
         JOIN TEME t     ON t.ID = o.IDTEME
         JOIN PREDMETI p ON p.ID = t.IDPREDMETA AND p.DELETED = 0
         JOIN RAZREDI r  ON r.ID = u.IDRAZRED AND r.DELETED = 0
        WHERE {$where}
        ORDER BY o.CREATED DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
  }
}
