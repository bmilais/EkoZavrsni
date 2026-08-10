<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\Db;

final class OdabirController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $db = Db::connect();

    $filter = $req->query('filter', 'aktivni');
    $where  = 'o.DELETED = 0';
    if ($filter === 'aktivni') {
      $where .= " AND o.STATUS = 'aktivan'";
    } elseif ($filter === 'ponisteni') {
      $where .= " AND o.STATUS = 'ponisten'";
    }

    $stmt = $db->prepare(
      "SELECT o.ID AS OID, o.STATUS, o.OBRAZLOZENJE, TO_CHAR(o.CREATED, 'YYYY-MM-DD HH24:MI') AS DATUM_ODABIRA,
              u.IME AS U_IME, u.PREZIME AS U_PREZIME, u.EMAIL AS U_EMAIL,
              t.NAZIV AS TEMA_NAZIV,
              p.NAZIV AS PREDMET_NAZIV,
              pr.PREZIME || ' ' || pr.IME AS PROFESOR_NAZIV
         FROM ODABIRI o
         JOIN UCENICI u  ON u.ID = o.IDUCENIKA AND u.DELETED = 0
         JOIN TEME t     ON t.ID = o.IDTEME AND t.DELETED = 0
         JOIN PREDMETI p ON p.ID = t.IDPREDMETA AND p.DELETED = 0
         JOIN PROFESORI pr ON pr.ID = t.IDPROFESORA AND pr.DELETED = 0
        WHERE {$where}
        ORDER BY o.CREATED DESC"
    );
    $stmt->execute();
    $odabiri = $stmt->fetchAll();

    $excelUrl = $res->url('/admin/export/odabiri-excel');
    $pdfUrl   = $res->url('/admin/export/odabiri-pdf');

    $html = '<h1>Odabiri tema</h1>';
    $html .= '<p>';
    $html .= ($filter === 'svi')     ? 'Svi'     : '<a href="?filter=svi">Svi</a>';
    $html .= ' | ';
    $html .= ($filter === 'aktivni') ? 'Aktivni' : '<a href="?filter=aktivni">Aktivni</a>';
    $html .= ' | ';
    $html .= ($filter === 'ponisteni') ? 'Poništeni' : '<a href="?filter=ponisteni">Poništeni</a>';
    $html .= ' | ';
    $html .= "<a href=\"{$excelUrl}\">Export Excel</a>";
    $html .= ' | ';
    $html .= "<a href=\"{$pdfUrl}\">Export PDF</a>";
    $html .= '</p>';

    if (!$odabiri) {
      $html .= '<p>Nema odabira.</p>';
    } else {
      $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
      $html .= '<tr><th>ID</th><th>Učenik</th><th>Email</th><th>Tema</th><th>Predmet</th><th>Profesor</th><th>Datum</th><th>Status</th><th>Akcije</th></tr>';
      foreach ($odabiri as $o) {
        $statusLabel = $o['STATUS'] === 'aktivan' ? '<span style="color:green">Aktivan</span>' : '<span style="color:red">Poništen</span>';
        $html .= '<tr>';
        $html .= '<td>' . $o['OID'] . '</td>';
        $html .= '<td>' . htmlspecialchars($o['U_PREZIME'] . ' ' . $o['U_IME']) . '</td>';
        $html .= '<td>' . htmlspecialchars($o['U_EMAIL']) . '</td>';
        $html .= '<td>' . htmlspecialchars($o['TEMA_NAZIV']) . '</td>';
        $html .= '<td>' . htmlspecialchars($o['PREDMET_NAZIV']) . '</td>';
        $html .= '<td>' . htmlspecialchars($o['PROFESOR_NAZIV']) . '</td>';
        $html .= '<td>' . $o['DATUM_ODABIRA'] . '</td>';
        $html .= '<td>' . $statusLabel . '</td>';
        $html .= '<td>';
        if ($o['STATUS'] === 'aktivan') {
          $html .= '<a href="' . $res->url('/admin/odabiri/ponisti?id=' . $o['OID']) . '">Poništi</a>';
        } else {
          $html .= htmlspecialchars($o['OBRAZLOZENJE'] ?? '-');
        }
        $html .= '</td>';
        $html .= '</tr>';
      }
      $html .= '</table>';
    }

    $html .= '<p><a href="' . $res->url('/admin') . '">← Povratak na admin panel</a></p>';
    $this->view($res, $html);
  }

  public function ponistiForma(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id = (int)$req->query('id', 0);
    if ($id <= 0) $res->redirect('/admin/odabiri');

    $db  = Db::connect();
    $stmt = $db->prepare(
      "SELECT o.ID, o.STATUS, u.PREZIME || ' ' || u.IME AS UCENIK, t.NAZIV AS TEMA,
              p.NAZIV AS PREDMET, pr.PREZIME || ' ' || pr.IME AS PROFESOR
         FROM ODABIRI o
         JOIN UCENICI u  ON u.ID = o.IDUCENIKA
         JOIN TEME t     ON t.ID = o.IDTEME
         JOIN PREDMETI p ON p.ID = t.IDPREDMETA
         JOIN PROFESORI pr ON pr.ID = t.IDPROFESORA
        WHERE o.ID = :id AND o.DELETED = 0 AND o.STATUS = 'aktivan'"
    );
    $stmt->execute(['id' => $id]);
    $odabir = $stmt->fetch();
    if (!$odabir) $res->redirect('/admin/odabiri');

    $back   = $res->url('/admin/odabiri');
    $action = $res->url('/admin/odabiri/ponisti');

    $this->view($res, <<<HTML
      <h1>Poništi odabir</h1>
      <table border="0" cellpadding="4">
        <tr><td><strong>Učenik:</strong></td><td>{$odabir['UCENIK']}</td></tr>
        <tr><td><strong>Tema:</strong></td><td>{$odabir['TEMA']}</td></tr>
        <tr><td><strong>Predmet:</strong></td><td>{$odabir['PREDMET']}</td></tr>
        <tr><td><strong>Profesor:</strong></td><td>{$odabir['PROFESOR']}</td></tr>
      </table>
      <br>
      <form method="post" action="{$action}">
        <input type="hidden" name="id" value="{$id}">
        <label><strong>Obrazloženje poništenja (obavezno):</strong><br>
          <textarea name="obrazlozenje" rows="4" cols="60" required></textarea>
        </label><br><br>
        <button type="submit">Poništi odabir</button>
        <a href="{$back}">Odustani</a>
      </form>
    HTML);
  }

  public function ponisti(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id            = (int)$req->input('id', 0);
    $obrazlozenje  = trim((string)$req->input('obrazlozenje', ''));

    if ($id <= 0 || $obrazlozenje === '') {
      $res->redirect('/admin/odabiri/ponisti?id=' . $id . '&error=' . urlencode('Obrazloženje je obavezno.'));
    }

    $user = Auth::user();

    $db = Db::connect();
    $stmt = $db->prepare(
      "UPDATE ODABIRI
          SET STATUS = 'ponisten',
              OBRAZLOZENJE = :obrazlozenje,
              PONISTIO_ID  = :ponistio,
              DATUM_PONISTENJA = SYSTIMESTAMP
        WHERE ID = :id AND DELETED = 0 AND STATUS = 'aktivan'"
    );
    $stmt->execute([
      'obrazlozenje' => $obrazlozenje,
      'ponistio'     => $user['id'],
      'id'           => $id,
    ]);

    $res->redirect('/admin/odabiri');
  }
}
