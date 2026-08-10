<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\PredmetService;

final class PredmetController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $predmeti = PredmetService::list();

    $html = '<h1>Predmeti</h1>';
    $html .= '<p><a href="' . $res->url('/admin/predmeti/novi') . '">+ Novi predmet</a></p>';

    if (!$predmeti) {
      $html .= '<p>Nema predmeta.</p>';
    } else {
      $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
      $html .= '<tr><th>ID</th><th>Naziv</th><th>Smjer</th><th>Razred</th><th>Limit</th><th>Akcije</th></tr>';
      foreach ($predmeti as $p) {
        $smjer  = PredmetService::smjerLabel((int)$p['SMJER']);
        $razred = $p['RAZRED'] ?? '-';
        $limit  = $p['LIMIT'] ?? '-';
        $html .= '<tr>';
        $html .= '<td>' . $p['ID'] . '</td>';
        $html .= '<td>' . htmlspecialchars($p['NAZIV']) . '</td>';
        $html .= '<td>' . $smjer . '</td>';
        $html .= '<td>' . $razred . '</td>';
        $html .= '<td>' . $limit . '</td>';
        $html .= '<td>';
        $html .= '<a href="' . $res->url('/admin/predmeti/uredi?id=' . $p['ID']) . '">Uredi</a>';
        $html .= ' | <form method="post" action="' . $res->url('/admin/predmeti/obrisi?id=' . $p['ID']) . '" style="display:inline" onsubmit="return confirm(\'Obrisati predmet?\')">';
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
    if ($id <= 0) $res->redirect('/admin/predmeti');
    $predmet = PredmetService::get($id);
    if (!$predmet) $res->redirect('/admin/predmeti');
    $this->prikaziFormu($res, $predmet, $req->query('error', ''));
  }

  public function spremi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id   = (int)$req->input('id', 0);
    $data = [
      'naziv'  => (string)$req->input('naziv', ''),
      'smjer'  => (string)$req->input('smjer', '1'),
      'razred' => (string)$req->input('razred', ''),
      'limit'  => (string)$req->input('limit', ''),
    ];

    if (trim($data['naziv']) === '') {
      $dest = $id > 0 ? '/admin/predmeti/uredi?id=' . $id : '/admin/predmeti/novi';
      $res->redirect($dest . '&error=' . urlencode('Naziv je obavezan.'));
    }

    if ($id > 0) {
      PredmetService::update($id, $data);
    } else {
      PredmetService::create($data);
    }
    $res->redirect('/admin/predmeti');
  }

  public function obrisi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id = (int)$req->query('id', 0);
    if ($id > 0) PredmetService::delete($id);
    $res->redirect('/admin/predmeti');
  }

  private function prikaziFormu(Response $res, ?array $predmet, string $error = ''): void
  {
    $naslov = $predmet ? 'Uredi predmet' : 'Novi predmet';
    $action = $res->url('/admin/predmeti/spremi');
    $naziv  = htmlspecialchars($predmet['NAZIV'] ?? '', ENT_QUOTES);
    $smjer  = (int)($predmet['SMJER'] ?? 1);
    $razred = (string)($predmet['RAZRED'] ?? '');
    $limit  = (string)($predmet['LIMIT'] ?? '');
    $id     = $predmet ? $predmet['ID'] : 0;
    $err    = $error ? '<p style="color:red">' . htmlspecialchars($error) . '</p>' : '';

    $razrediOpcije = '';
    for ($r = 1; $r <= 5; $r++) {
      $sel = (string)$r === $razred ? ' selected' : '';
      $razrediOpcije .= "<option value=\"{$r}\"{$sel}>{$r}. razred</option>";
    }

    $selSmjer1 = $smjer === 1 ? ' selected' : '';
    $selSmjer2 = $smjer === 2 ? ' selected' : '';

    $this->view($res, <<<HTML
      <h1>{$naslov}</h1>
      {$err}
      <form method="post" action="{$action}">
        <input type="hidden" name="id" value="{$id}">
        <label>Naziv predmeta <input type="text" name="naziv" value="{$naziv}" size="60" required></label><br><br>
        <label>Smjer
          <select name="smjer">
            <option value="1"{$selSmjer1}>Ekonomist</option>
            <option value="2"{$selSmjer2}>Trgovac</option>
          </select>
        </label><br><br>
        <label>Razred
          <select name="razred">
            <option value="">Svi razredi</option>
            {$razrediOpcije}
          </select>
        </label><br><br>
        <label>Limit prijava <input type="number" name="limit" value="{$limit}" min="1" max="99"></label><br><br>
        <button type="submit">Spremi</button>
        <a href="{$res->url('/admin/predmeti')}">Odustani</a>
      </form>
    HTML);
  }
}
