<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\CiklusService;

final class CiklusController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $ciklusi = CiklusService::list();

    $html = '<h1>Ciklusi</h1>';
    $html .= '<p><a href="' . $res->url('/admin/ciklusi/novi') . '">+ Novi ciklus</a></p>';

    if (!$ciklusi) {
      $html .= '<p>Nema ciklusa.</p>';
    } else {
      $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
      $html .= '<tr><th>ID</th><th>Naziv</th><th>Školska godina</th><th>Status</th><th>Otvaranje</th><th>Zatvaranje</th><th>Max učenika/mentoru</th><th>Max tema/predmetu</th><th>Akcije</th></tr>';

      foreach ($ciklusi as $c) {
        $otvaranje = $c['DATUM_OTVARANJA'] ? date('d.m.Y H:i', strtotime($c['DATUM_OTVARANJA'])) : '-';
        $zatvaranje = $c['DATUM_ZATVARANJA'] ? date('d.m.Y H:i', strtotime($c['DATUM_ZATVARANJA'])) : '-';

        $html .= '<tr>';
        $html .= '<td>' . $c['ID'] . '</td>';
        $html .= '<td>' . htmlspecialchars($c['NAZIV']) . '</td>';
        $html .= '<td>' . htmlspecialchars($c['SKOLSKA_GODINA']) . '</td>';
        $html .= '<td>' . CiklusService::statusLabel($c['STATUS']) . '</td>';
        $html .= '<td>' . $otvaranje . '</td>';
        $html .= '<td>' . $zatvaranje . '</td>';
        $html .= '<td>' . ($c['MAX_UCENIKA_PO_MENTORU'] ?? '-') . '</td>';
        $html .= '<td>' . ($c['MAX_TEMA_PO_PREDMETU'] ?? '-') . '</td>';

        $html .= '<td>';
        $html .= '<a href="' . $res->url('/admin/ciklusi/uredi?id=' . $c['ID']) . '">Uredi</a>';

        $moguci = CiklusService::moguciStatusi($c['STATUS']);
        foreach ($moguci as $s) {
          $html .= ' | <form method="post" action="' . $res->url('/admin/ciklusi/status?id=' . $c['ID'] . '&status=' . $s) . '" style="display:inline">';
          $html .= '<button type="submit">' . CiklusService::statusLabel($s) . '</button>';
          $html .= '</form>';
        }

        $html .= ' | <form method="post" action="' . $res->url('/admin/ciklusi/obrisi?id=' . $c['ID']) . '" style="display:inline" onsubmit="return confirm(\'Obrisati ciklus?\')">';
        $html .= '<button type="submit">Obriši</button>';
        $html .= '</form>';

        $html .= '</td>';
        $html .= '</tr>';
      }

      $html .= '</table>';
    }

    $html .= '<p><a href="' . $res->url('/admin') . '">← Povratak na admin panel</a></p>';

    $this->view($res, $html);
  }

  public function novi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $this->prikaziFormu($res, null, $req->query('error', ''));
  }

  public function uredi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id = (int)$req->query('id', 0);
    if ($id <= 0) {
      $res->redirect('/admin/ciklusi');
    }

    $ciklus = CiklusService::get($id);
    if (!$ciklus) {
      $res->redirect('/admin/ciklusi');
    }

    $this->prikaziFormu($res, $ciklus, $req->query('error', ''));
  }

  public function spremi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);

    $id    = (int)$req->input('id', 0);
    $data  = [
      'skolska_godina'         => (string)$req->input('skolska_godina', ''),
      'naziv'                  => (string)$req->input('naziv', ''),
      'max_ucenika_po_mentoru' => (string)$req->input('max_ucenika_po_mentoru', ''),
      'max_tema_po_predmetu'   => (string)$req->input('max_tema_po_predmetu', ''),
      'upute_pdf_url'          => (string)$req->input('upute_pdf_url', ''),
    ];

    $error = '';

    if (trim($data['skolska_godina']) === '' || trim($data['naziv']) === '') {
      $error = 'Naziv i školska godina su obavezni.';
    }

    if ($error !== '') {
      if ($id > 0) {
        $res->redirect('/admin/ciklusi/uredi?id=' . $id . '&error=' . urlencode($error));
      } else {
        $res->redirect('/admin/ciklusi/novi?error=' . urlencode($error));
      }
    }

    if ($id > 0) {
      CiklusService::update($id, $data);
    } else {
      CiklusService::create($data);
    }

    $res->redirect('/admin/ciklusi');
  }

  public function status(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);

    $id     = (int)$req->query('id', 0);
    $status = (string)$req->query('status', '');

    if ($id <= 0 || $status === '') {
      $res->redirect('/admin/ciklusi');
    }

    $error = CiklusService::promijeniStatus($id, $status);

    if ($error !== '') {
      $res->redirect('/admin/ciklusi/uredi?id=' . $id . '&error=' . urlencode($error));
    }

    $res->redirect('/admin/ciklusi');
  }

  public function obrisi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);

    $id = (int)$req->query('id', 0);
    if ($id > 0) {
      CiklusService::delete($id);
    }

    $res->redirect('/admin/ciklusi');
  }

  private function prikaziFormu(Response $res, ?array $ciklus, string $error = ''): void
  {
    $naslov  = $ciklus ? 'Uredi ciklus' : 'Novi ciklus';
    $action  = $res->url('/admin/ciklusi/spremi');
    $naziv   = htmlspecialchars($ciklus['NAZIV'] ?? '', ENT_QUOTES);
    $godina  = htmlspecialchars($ciklus['SKOLSKA_GODINA'] ?? '', ENT_QUOTES);
    $maxU    = htmlspecialchars((string)($ciklus['MAX_UCENIKA_PO_MENTORU'] ?? ''), ENT_QUOTES);
    $maxT    = htmlspecialchars((string)($ciklus['MAX_TEMA_PO_PREDMETU'] ?? ''), ENT_QUOTES);
    $upute   = htmlspecialchars($ciklus['UPUTE_PDF_URL'] ?? '', ENT_QUOTES);
    $id      = $ciklus ? $ciklus['ID'] : 0;
    $status  = $ciklus ? CiklusService::statusLabel($ciklus['STATUS']) : '';

    $errorHtml = $error ? '<p style="color:red">' . htmlspecialchars($error) . '</p>' : '';

    $this->view($res, <<<HTML
      <h1>{$naslov}</h1>
      {$errorHtml}
      <form method="post" action="{$action}">
        <input type="hidden" name="id" value="{$id}">
        <label>Naziv ciklusa <input type="text" name="naziv" value="{$naziv}" size="60" required></label><br><br>
        <label>Školska godina <input type="text" name="skolska_godina" value="{$godina}" placeholder="npr. 2025/2026" required></label><br><br>
        <label>Max učenika po mentoru <input type="number" name="max_ucenika_po_mentoru" value="{$maxU}" min="1" max="999"></label><br><br>
        <label>Max tema po predmetu <input type="number" name="max_tema_po_predmetu" value="{$maxT}" min="1" max="999"></label><br><br>
        <label>URL PDF uputa <input type="url" name="upute_pdf_url" value="{$upute}" size="80"></label><br><br>
        <p>Trenutni status: <strong>{$status}</strong></p>
        <button type="submit">Spremi</button>
        <a href="{$res->url('/admin/ciklusi')}">Odustani</a>
      </form>
    HTML);
  }
}
