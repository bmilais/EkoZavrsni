<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\UcenikService;
use App\Services\RazredService;

final class UcenikController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $ucenici = UcenikService::list();

    $html = '<h1>Učenici</h1>';
    $html .= '<p><a href="' . $res->url('/admin/ucenici/novi') . '">+ Novi učenik</a></p>';

    if (!$ucenici) {
      $html .= '<p>Nema učenika.</p>';
    } else {
      $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
      $html .= '<tr><th>ID</th><th>Ime i prezime</th><th>Email</th><th>Smjer</th><th>Razred</th><th>Akcije</th></tr>';
      foreach ($ucenici as $u) {
        $smjer = UcenikService::smjerLabel((int)$u['SMJER']);
        $html .= '<tr>';
        $html .= '<td>' . $u['ID'] . '</td>';
        $html .= '<td>' . htmlspecialchars($u['PREZIME'] . ' ' . $u['IME']) . '</td>';
        $html .= '<td>' . htmlspecialchars($u['EMAIL']) . '</td>';
        $html .= '<td>' . $smjer . '</td>';
        $html .= '<td>' . htmlspecialchars($u['RAZRED_NAZIV']) . '</td>';
        $html .= '<td>';
        $html .= '<a href="' . $res->url('/admin/ucenici/uredi?id=' . $u['ID']) . '">Uredi</a>';
        $html .= ' | <form method="post" action="' . $res->url('/admin/ucenici/obrisi?id=' . $u['ID']) . '" style="display:inline" onsubmit="return confirm(\'Obrisati učenika?\')">';
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
    if ($id <= 0) $res->redirect('/admin/ucenici');
    $ucenik = UcenikService::get($id);
    if (!$ucenik) $res->redirect('/admin/ucenici');
    $this->prikaziFormu($res, $ucenik, $req->query('error', ''));
  }

  public function spremi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id   = (int)$req->input('id', 0);
    $data = [
      'idrazred' => (string)$req->input('idrazred', ''),
      'ime'      => (string)$req->input('ime', ''),
      'prezime'  => (string)$req->input('prezime', ''),
      'email'    => (string)$req->input('email', ''),
      'lozinka'  => (string)$req->input('lozinka', ''),
      'smjer'    => (string)$req->input('smjer', '1'),
    ];

    $error = '';
    if (trim($data['ime']) === '' || trim($data['prezime']) === '') {
      $error = 'Ime i prezime su obavezni.';
    } elseif (trim($data['email']) === '') {
      $error = 'Email je obavezan.';
    } elseif ((int)$data['idrazred'] <= 0) {
      $error = 'Razred je obavezan.';
    }

    if ($error !== '') {
      $dest = $id > 0 ? '/admin/ucenici/uredi?id=' . $id : '/admin/ucenici/novi';
      $res->redirect($dest . '&error=' . urlencode($error));
    }

    if ($id > 0) {
      UcenikService::update($id, $data);
    } else {
      UcenikService::create($data);
    }
    $res->redirect('/admin/ucenici');
  }

  public function obrisi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id = (int)$req->query('id', 0);
    if ($id > 0) UcenikService::delete($id);
    $res->redirect('/admin/ucenici');
  }

  private function prikaziFormu(Response $res, ?array $ucenik, string $error = ''): void
  {
    $naslov  = $ucenik ? 'Uredi učenika' : 'Novi učenik';
    $action  = $res->url('/admin/ucenici/spremi');
    $ime     = htmlspecialchars($ucenik['IME'] ?? '', ENT_QUOTES);
    $prezime = htmlspecialchars($ucenik['PREZIME'] ?? '', ENT_QUOTES);
    $email   = htmlspecialchars($ucenik['EMAIL'] ?? '', ENT_QUOTES);
    $smjer   = (int)($ucenik['SMJER'] ?? 1);
    $idRazred = (int)($ucenik['IDRAZRED'] ?? 0);
    $id      = $ucenik ? $ucenik['ID'] : 0;
    $err     = $error ? '<p style="color:red">' . htmlspecialchars($error) . '</p>' : '';

    $razredi = RazredService::list();
    $razredOpcije = '<option value="">-- Odaberi razred --</option>';
    foreach ($razredi as $r) {
      $sel = (int)$r['ID'] === $idRazred ? ' selected' : '';
      $razredOpcije .= '<option value="' . $r['ID'] . '"' . $sel . '>' . htmlspecialchars($r['NAZIV']) . '</option>';
    }

    $selSmjer1 = $smjer === 1 ? ' selected' : '';
    $selSmjer2 = $smjer === 2 ? ' selected' : '';
    $lozHelp   = $id > 0 ? '(ostavi prazno da ostane ista)' : '';

    $this->view($res, <<<HTML
      <h1>{$naslov}</h1>
      {$err}
      <form method="post" action="{$action}">
        <input type="hidden" name="id" value="{$id}">
        <label>Ime <input type="text" name="ime" value="{$ime}" size="30" required></label><br><br>
        <label>Prezime <input type="text" name="prezime" value="{$prezime}" size="30" required></label><br><br>
        <label>Email <input type="email" name="email" value="{$email}" size="40" required></label><br><br>
        <label>Lozinka <input type="text" name="lozinka" value="" size="20"> {$lozHelp}</label><br><br>
        <label>Smjer
          <select name="smjer">
            <option value="1"{$selSmjer1}>Ekonomist</option>
            <option value="2"{$selSmjer2}>Trgovac</option>
          </select>
        </label><br><br>
        <label>Razred
          <select name="idrazred" required>
            {$razredOpcije}
          </select>
        </label><br><br>
        <button type="submit">Spremi</button>
        <a href="{$res->url('/admin/ucenici')}">Odustani</a>
      </form>
    HTML);
  }
}
