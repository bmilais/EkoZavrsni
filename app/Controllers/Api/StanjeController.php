<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Db;

final class StanjeController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $db = Db::connect();

    $ciklusId = (int)$req->query('ciklus_id', 0);
    if ($ciklusId <= 0) {
      $this->fail($res, 'Ciklus je obavezan.');
      return;
    }

    // Sve brojke su ovdje strogo vezane uz IZABRANI ciklus (t.IDCIKLUSA = :cik) -
    // teme i odabiri iz drugih ciklusa se ne ubrajaju.
    $stmt = $db->prepare(
      "SELECT p.ID, p.PREZIME, p.IME,
              COUNT(DISTINCT t.ID) AS br_tema,
              COUNT(DISTINCT o.ID) AS br_odabira,
              COUNT(DISTINCT CASE WHEN o.STATUS = 'aktivan' THEN o.ID END) AS br_aktivnih
         FROM PROFESORI p
         LEFT JOIN TEME t  ON t.IDPROFESORA = p.ID AND t.DELETED = 0 AND t.IDCIKLUSA = :cik1
         LEFT JOIN ODABIRI o ON o.IDTEME = t.ID AND o.DELETED = 0
        WHERE p.DELETED = 0
        GROUP BY p.ID, p.PREZIME, p.IME
        ORDER BY br_tema DESC"
    );
    $stmt->execute(['cik1' => $ciklusId]);
    $profesori = $stmt->fetchAll();

    $stmt = $db->prepare(
      "SELECT p.ID, p.NAZIV, p.\"LIMIT\" AS LIMIT,
              COUNT(DISTINCT t.ID) AS br_tema,
              COUNT(DISTINCT o.ID) AS br_odabira
         FROM PREDMETI p
         LEFT JOIN TEME t   ON t.IDPREDMETA = p.ID AND t.DELETED = 0 AND t.IDCIKLUSA = :cik2
         LEFT JOIN ODABIRI o ON o.IDTEME = t.ID AND o.DELETED = 0 AND o.STATUS = 'aktivan'
        WHERE p.DELETED = 0
        GROUP BY p.ID, p.NAZIV, p.\"LIMIT\"
        ORDER BY br_tema DESC"
    );
    $stmt->execute(['cik2' => $ciklusId]);
    $predmeti = $stmt->fetchAll();
    foreach ($predmeti as &$p) {
      $p['LIMIT']      = $p['LIMIT'] !== null ? (int)$p['LIMIT'] : null;
      $p['POPUNJENOST'] = $p['LIMIT'] > 0 ? round(((int)$p['BR_ODABIRA'] / $p['LIMIT']) * 100) : null;
    }
    unset($p);

    $this->ok($res, [
      'profesori' => $profesori,
      'predmeti'  => $predmeti,
    ]);
  }
}
