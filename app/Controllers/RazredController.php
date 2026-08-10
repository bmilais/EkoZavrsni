<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\RazredService;

final class RazredController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $razredi = RazredService::list();

    $html = '<h1>Razredi</h1>';
    $html .= '<p><a href="' . $res->url('/admin/razredi/novi') . '">+ Novi razred</a></p>';

    if (!$razredi) {
      $html .= '<p>Nema razreda.</p>';
    } else {
      $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
      $html .= '<tr><th>ID</th><th>Naziv</th><th>Akcije</th></tr>';
      foreach ($razredi as $r) {
        $html .= '<tr>';
        $html .= '<td>' . $r['ID'] . '</td>';
        $html .= '<td>' . htmlspecialchars($r['NAZIV']) . '</td>';
        $html .= '<td>';
        $html .= '<a href="' . $res->url('/admin/razredi/uredi?id=' . $r['ID']) . '">Uredi</a>';
        $html .= ' | <form method="post" action="' . $res->url('/admin/razredi/obrisi?id=' . $r['ID']) . '" style="display:inline" onsubmit="return confirm(\'Obrisati razred?\')">';
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
    if ($id <= 0) $res->redirect('/admin/razredi');
    $razred = RazredService::get($id);
    if (!$razred) $res->redirect('/admin/razredi');
    $this->prikaziFormu($res, $razred, $req->query('error', ''));
  }

  public function spremi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id   = (int)$req->input('id', 0);
    $data = ['naziv' => (string)$req->input('naziv', '')];

    if (trim($data['naziv']) === '') {
      $dest = $id > 0 ? '/admin/razredi/uredi?id=' . $id : '/admin/razredi/novi';
      $res->redirect($dest . '&error=' . urlencode('Naziv je obavezan.'));
    }

    if ($id > 0) {
      RazredService::update($id, $data);
    } else {
      RazredService::create($data);
    }
    $res->redirect('/admin/razredi');
  }

  public function obrisi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id = (int)$req->query('id', 0);
    if ($id > 0) RazredService::delete($id);
    $res->redirect('/admin/razredi');
  }

  private function prikaziFormu(Response $res, ?array $razred, string $error = ''): void
  {
    $naslov = $razred ? 'Uredi razred' : 'Novi razred';
    $action = $res->url('/admin/razredi/spremi');
    $naziv  = htmlspecialchars($razred['NAZIV'] ?? '', ENT_QUOTES);
    $id     = $razred ? $razred['ID'] : 0;
    $err    = $error ? '<p style="color:red">' . htmlspecialchars($error) . '</p>' : '';

    $this->view($res, <<<HTML
      <h1>{$naslov}</h1>
      {$err}
      <form method="post" action="{$action}">
        <input type="hidden" name="id" value="{$id}">
        <label>Naziv razreda <input type="text" name="naziv" value="{$naziv}" size="30" required></label><br><br>
        <button type="submit">Spremi</button>
        <a href="{$res->url('/admin/razredi')}">Odustani</a>
      </form>
    HTML);
  }
}
