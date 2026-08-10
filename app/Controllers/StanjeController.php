<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\Db;
use App\Services\CiklusService;

final class StanjeController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $db = Db::connect();

    $html = '<h1>Stanje zauzetosti tema i mentora</h1>';
    $html .= '<p><a href="' . $res->url('/admin') . '">← Povratak na admin panel</a></p>';

    // -- profesor summary --
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

    $html .= '<h2>Mentori (profesori)</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse">';
    $html .= '<tr><th>Profesor</th><th>Tema</th><th>Ukupno odabira</th><th>Aktivnih odabira</th></tr>';
    foreach ($profesori as $p) {
      $html .= '<tr>';
      $html .= '<td>' . htmlspecialchars($p['PREZIME'] . ' ' . $p['IME']) . '</td>';
      $html .= '<td>' . $p['BR_TEMA'] . '</td>';
      $html .= '<td>' . $p['BR_ODABIRA'] . '</td>';
      $html .= '<td>' . $p['BR_AKTIVNIH'] . '</td>';
      $html .= '</tr>';
    }
    $html .= '</table>';

    // -- predmet summary --
    $stmt = $db->prepare(
      "SELECT p.ID, p.NAZIV, p.LIMIT,
              COUNT(DISTINCT t.ID) AS br_tema,
              COUNT(DISTINCT o.ID) AS br_odabira
         FROM PREDMETI p
         LEFT JOIN TEME t   ON t.IDPREDMETA = p.ID AND t.DELETED = 0
         LEFT JOIN ODABIRI o ON o.IDTEME = t.ID AND o.DELETED = 0
        WHERE p.DELETED = 0
        GROUP BY p.ID, p.NAZIV, p.LIMIT
        ORDER BY br_tema DESC"
    );
    $stmt->execute();
    $predmeti = $stmt->fetchAll();

    $html .= '<h2>Predmeti</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse">';
    $html .= '<tr><th>Predmet</th><th>Tema</th><th>Odabira</th><th>Limit</th><th>Popunjenost</th></tr>';
    foreach ($predmeti as $p) {
      $limit   = $p['LIMIT'] ?: '-';
      $postotak = $p['LIMIT'] > 0 ? round(($p['BR_ODABIRA'] / $p['LIMIT']) * 100) . '%' : '-';
      $html .= '<tr>';
      $html .= '<td>' . htmlspecialchars($p['NAZIV']) . '</td>';
      $html .= '<td>' . $p['BR_TEMA'] . '</td>';
      $html .= '<td>' . $p['BR_ODABIRA'] . '</td>';
      $html .= '<td>' . $limit . '</td>';
      $html .= '<td>' . $postotak . '</td>';
      $html .= '</tr>';
    }
    $html .= '</table>';

    // -- ciklus summary --
    $ciklusi = CiklusService::list();
    $html .= '<h2>Ciklusi</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse">';
    $html .= '<tr><th>Ciklus</th><th>Status</th><th>Tema</th><th>Odabira</th></tr>';
    foreach ($ciklusi as $c) {
      $stmt = $db->prepare(
        "SELECT COUNT(DISTINCT t.ID) AS br_tema, COUNT(DISTINCT o.ID) AS br_odabira
           FROM TEME t
           LEFT JOIN ODABIRI o ON o.IDTEME = t.ID AND o.DELETED = 0
          WHERE t.IDCIKLUSA = :id AND t.DELETED = 0"
      );
      $stmt->execute(['id' => $c['ID']]);
      $stat = $stmt->fetch();
      $html .= '<tr>';
      $html .= '<td>' . htmlspecialchars($c['NAZIV']) . '</td>';
      $html .= '<td>' . CiklusService::statusLabel($c['STATUS']) . '</td>';
      $html .= '<td>' . ($stat['BR_TEMA'] ?? 0) . '</td>';
      $html .= '<td>' . ($stat['BR_ODABIRA'] ?? 0) . '</td>';
      $html .= '</tr>';
    }
    $html .= '</table>';

    $this->view($res, $html);
  }
}
