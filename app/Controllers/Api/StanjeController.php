<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Db;
use App\Services\CiklusService;

final class StanjeController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $db = Db::connect();

    $stmt = $db->prepare(
      "SELECT p.ID, p.PREZIME, p.IME,
              COUNT(DISTINCT t.ID) AS br_tema,
              COUNT(DISTINCT o.ID) AS br_odabira,
              COUNT(DISTINCT CASE WHEN o.STATUS = 'aktivan' THEN o.ID END) AS br_aktivnih
         FROM PROFESORI p
         LEFT JOIN TEME t  ON t.IDPROFESORA = p.ID AND t.DELETED = 0
         LEFT JOIN ODABIRI o ON o.IDTEME = t.ID AND o.DELETED = 0
        WHERE p.DELETED = 0
        GROUP BY p.ID, p.PREZIME, p.IME
        ORDER BY br_tema DESC"
    );
    $stmt->execute();
    $profesori = $stmt->fetchAll();

    $stmt = $db->prepare(
      "SELECT p.ID, p.NAZIV, p.\"LIMIT\" AS LIMIT,
              COUNT(DISTINCT t.ID) AS br_tema,
              COUNT(DISTINCT o.ID) AS br_odabira
         FROM PREDMETI p
         LEFT JOIN TEME t   ON t.IDPREDMETA = p.ID AND t.DELETED = 0
         LEFT JOIN ODABIRI o ON o.IDTEME = t.ID AND o.DELETED = 0
        WHERE p.DELETED = 0
        GROUP BY p.ID, p.NAZIV, p.\"LIMIT\"
        ORDER BY br_tema DESC"
    );
    $stmt->execute();
    $predmeti = $stmt->fetchAll();
    foreach ($predmeti as &$p) {
      $p['LIMIT']      = $p['LIMIT'] !== null ? (int)$p['LIMIT'] : null;
      $p['POPUNJENOST'] = $p['LIMIT'] > 0 ? round(((int)$p['BR_ODABIRA'] / $p['LIMIT']) * 100) : null;
    }
    unset($p);

    $ciklusi = CiklusService::list();
    foreach ($ciklusi as &$c) {
      $stmt = $db->prepare(
        "SELECT COUNT(DISTINCT t.ID) AS br_tema, COUNT(DISTINCT o.ID) AS br_odabira
           FROM TEME t
           LEFT JOIN ODABIRI o ON o.IDTEME = t.ID AND o.DELETED = 0
          WHERE t.IDCIKLUSA = :id AND t.DELETED = 0"
      );
      $stmt->execute(['id' => $c['ID']]);
      $stat = $stmt->fetch();
      $c['BR_TEMA']   = (int)($stat['BR_TEMA'] ?? 0);
      $c['BR_ODABIRA'] = (int)($stat['BR_ODABIRA'] ?? 0);
      $c['STATUS_LABEL'] = CiklusService::statusLabel($c['STATUS']);
    }
    unset($c);

    $this->ok($res, [
      'profesori' => $profesori,
      'predmeti'  => $predmeti,
      'ciklusi'   => $ciklusi,
    ]);
  }
}
