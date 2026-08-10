<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\ProfesorService;

final class ProfesorController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $profesori = ProfesorService::list();

    $html = '<h1>Profesori</h1>';
    $html .= '<p><a href="' . $res->url('/admin/profesori/novi') . '">+ Novi profesor</a></p>';

    if (!$profesori) {
      $html .= '<p>Nema profesora.</p>';
    } else {
      $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
      $html .= '<tr><th>ID</th><th>Ime i prezime</th><th>Email</th><th>Uloga</th><th>Akcije</th></tr>';
      foreach ($profesori as $p) {
        $email = $p['EMAIL'] ? htmlspecialchars($p['EMAIL']) : '<em>nije postavljen</em>';
        $uloga = ProfesorService::ulogaLabel((int)$p['OVLASTI']);
        $html .= '<tr>';
        $html .= '<td>' . $p['ID'] . '</td>';
        $html .= '<td>' . htmlspecialchars($p['PREZIME'] . ' ' . $p['IME']) . '</td>';
        $html .= '<td>' . $email . '</td>';
        $html .= '<td>' . $uloga . '</td>';
        $html .= '<td>';
        $html .= '<a href="' . $res->url('/admin/profesori/uredi?id=' . $p['ID']) . '">Uredi</a>';
        $html .= ' | <form method="post" action="' . $res->url('/admin/profesori/obrisi?id=' . $p['ID']) . '" style="display:inline" onsubmit="return confirm(\'Obrisati profesora?\')">';
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
    if ($id <= 0) $res->redirect('/admin/profesori');
    $profesor = ProfesorService::get($id);
    if (!$profesor) $res->redirect('/admin/profesori');
    $this->prikaziFormu($res, $profesor, $req->query('error', ''));
  }

  public function spremi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id   = (int)$req->input('id', 0);
    $data = [
      'ime'     => (string)$req->input('ime', ''),
      'prezime' => (string)$req->input('prezime', ''),
      'email'   => (string)$req->input('email', ''),
      'lozinka' => (string)$req->input('lozinka', ''),
      'ovlasti' => (string)$req->input('ovlasti', '1'),
    ];

    if (trim($data['ime']) === '' || trim($data['prezime']) === '') {
      $dest = $id > 0 ? '/admin/profesori/uredi?id=' . $id : '/admin/profesori/novi';
      $res->redirect($dest . '&error=' . urlencode('Ime i prezime su obavezni.'));
    }

    if ($id > 0) {
      ProfesorService::update($id, $data);
    } else {
      ProfesorService::create($data);
    }
    $res->redirect('/admin/profesori');
  }

  public function obrisi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id = (int)$req->query('id', 0);
    if ($id > 0) ProfesorService::delete($id);
    $res->redirect('/admin/profesori');
  }

  private function prikaziFormu(Response $res, ?array $profesor, string $error = ''): void
  {
    $naslov  = $profesor ? 'Uredi profesora' : 'Novi profesor';
    $action  = $res->url('/admin/profesori/spremi');
    $ime     = htmlspecialchars($profesor['IME'] ?? '', ENT_QUOTES);
    $prezime = htmlspecialchars($profesor['PREZIME'] ?? '', ENT_QUOTES);
    $email   = htmlspecialchars($profesor['EMAIL'] ?? '', ENT_QUOTES);
    $lozinka = '';
    $ovlasti = (int)($profesor['OVLASTI'] ?? 1);
    $id      = $profesor ? $profesor['ID'] : 0;
    $err     = $error ? '<p style="color:red">' . htmlspecialchars($error) . '</p>' : '';
    $selUl0  = $ovlasti === 0 ? ' selected' : '';
    $selUl1  = $ovlasti === 1 ? ' selected' : '';

    $this->view($res, <<<HTML
      <h1>{$naslov}</h1>
      {$err}
      <form method="post" action="{$action}">
        <input type="hidden" name="id" value="{$id}">
        <label>Ime <input type="text" name="ime" value="{$ime}" size="30" required></label><br><br>
        <label>Prezime <input type="text" name="prezime" value="{$prezime}" size="30" required></label><br><br>
        <label>Email <input type="email" name="email" value="{$email}" size="40"></label><br><br>
        <label>Lozinka <input type="text" name="lozinka" value="{$lozinka}" size="20"> (ostavi prazno za postojeće)</label><br><br>
        <label>Uloga
          <select name="ovlasti">
            <option value="0"{$selUl0}>Admin</option>
            <option value="1"{$selUl1}>Nastavnik</option>
          </select>
        </label><br><br>
        <button type="submit">Spremi</button>
        <a href="{$res->url('/admin/profesori')}">Odustani</a>
      </form>
    HTML);
  }
}
