<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\TemaService;
use App\Services\PredmetService;
use App\Services\ProfesorService;
use App\Services\RazredService;
use App\Services\CiklusService;

final class TemaController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $teme = TemaService::list();

    $html = '<h1>Teme</h1>';
    $html .= '<p><a href="' . $res->url('/admin/teme/novi') . '">+ Nova tema</a> | <a href="' . $res->url('/admin/teme/uvoz') . '">Uvoz iz Excela</a></p>';

    if (!$teme) {
      $html .= '<p>Nema tema.</p>';
    } else {
      $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
      $html .= '<tr><th>ID</th><th>Naziv</th><th>Predmet</th><th>Profesor</th><th>Razred</th><th>Ciklus</th><th>Akcije</th></tr>';
      foreach ($teme as $t) {
        $html .= '<tr>';
        $html .= '<td>' . $t['ID'] . '</td>';
        $html .= '<td>' . htmlspecialchars($t['NAZIV']) . '</td>';
        $html .= '<td>' . htmlspecialchars($t['PREDMET_NAZIV']) . '</td>';
        $html .= '<td>' . htmlspecialchars($t['PROFESOR_NAZIV']) . '</td>';
        $html .= '<td>' . htmlspecialchars($t['RAZRED_NAZIV']) . '</td>';
        $html .= '<td>' . htmlspecialchars($t['CIKLUS_NAZIV'] ?? '-') . '</td>';
        $html .= '<td>';
        $html .= '<a href="' . $res->url('/admin/teme/uredi?id=' . $t['ID']) . '">Uredi</a>';
        $html .= ' | <form method="post" action="' . $res->url('/admin/teme/obrisi?id=' . $t['ID']) . '" style="display:inline" onsubmit="return confirm(\'Obrisati temu?\')">';
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
    if ($id <= 0) $res->redirect('/admin/teme');
    $tema = TemaService::get($id);
    if (!$tema) $res->redirect('/admin/teme');
    $this->prikaziFormu($res, $tema, $req->query('error', ''));
  }

  public function spremi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id   = (int)$req->input('id', 0);
    $data = [
      'idpredmeta'  => (string)$req->input('idpredmeta', ''),
      'idprofesora' => (string)$req->input('idprofesora', ''),
      'idrazred'    => (string)$req->input('idrazred', ''),
      'idciklusa'   => (string)$req->input('idciklusa', ''),
      'naziv'       => (string)$req->input('naziv', ''),
    ];

    $error = '';
    if (trim($data['naziv']) === '') {
      $error = 'Naziv teme je obavezan.';
    } elseif ((int)$data['idpredmeta'] <= 0) {
      $error = 'Predmet je obavezan.';
    } elseif ((int)$data['idprofesora'] <= 0) {
      $error = 'Profesor je obavezan.';
    } elseif ((int)$data['idrazred'] <= 0) {
      $error = 'Razred je obavezan.';
    }

    if ($error !== '') {
      $dest = $id > 0 ? '/admin/teme/uredi?id=' . $id : '/admin/teme/novi';
      $res->redirect($dest . '&error=' . urlencode($error));
    }

    if ($id > 0) {
      TemaService::update($id, $data);
    } else {
      TemaService::create($data);
    }
    $res->redirect('/admin/teme');
  }

  public function obrisi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $id = (int)$req->query('id', 0);
    if ($id > 0) TemaService::delete($id);
    $res->redirect('/admin/teme');
  }

  private function prikaziFormu(Response $res, ?array $tema, string $error = ''): void
  {
    $naslov = $tema ? 'Uredi temu' : 'Nova tema';
    $action = $res->url('/admin/teme/spremi');
    $naziv  = htmlspecialchars($tema['NAZIV'] ?? '', ENT_QUOTES);
    $idP    = (int)($tema['IDPREDMETA'] ?? 0);
    $idPr   = (int)($tema['IDPROFESORA'] ?? 0);
    $idR    = (int)($tema['IDRAZRED'] ?? 0);
    $idC    = (int)($tema['IDCIKLUSA'] ?? 0);
    $id     = $tema ? $tema['ID'] : 0;
    $err    = $error ? '<p style="color:red">' . htmlspecialchars($error) . '</p>' : '';

    $predmeti = PredmetService::list();
    $predOpcije = '<option value="">-- Odaberi predmet --</option>';
    foreach ($predmeti as $p) {
      $sel = (int)$p['ID'] === $idP ? ' selected' : '';
      $predOpcije .= '<option value="' . $p['ID'] . '"' . $sel . '>' . htmlspecialchars($p['NAZIV']) . '</option>';
    }

    $profesori = ProfesorService::list();
    $profOpcije = '<option value="">-- Odaberi profesora --</option>';
    foreach ($profesori as $p) {
      $sel = (int)$p['ID'] === $idPr ? ' selected' : '';
      $profOpcije .= '<option value="' . $p['ID'] . '"' . $sel . '>' . htmlspecialchars($p['PREZIME'] . ' ' . $p['IME']) . '</option>';
    }

    $razredi = RazredService::list();
    $razOpcije = '<option value="">-- Odaberi razred --</option>';
    foreach ($razredi as $r) {
      $sel = (int)$r['ID'] === $idR ? ' selected' : '';
      $razOpcije .= '<option value="' . $r['ID'] . '"' . $sel . '>' . htmlspecialchars($r['NAZIV']) . '</option>';
    }

    $ciklusi = CiklusService::list();
    $cikOpcije = '<option value="">-- Nije vezano uz ciklus --</option>';
    foreach ($ciklusi as $c) {
      $sel = (int)$c['ID'] === $idC ? ' selected' : '';
      $cikOpcije .= '<option value="' . $c['ID'] . '"' . $sel . '>' . htmlspecialchars($c['NAZIV']) . '</option>';
    }

    $this->view($res, <<<HTML
      <h1>{$naslov}</h1>
      {$err}
      <form method="post" action="{$action}">
        <input type="hidden" name="id" value="{$id}">
        <label>Naziv teme <input type="text" name="naziv" value="{$naziv}" size="80" required></label><br><br>
        <label>Predmet
          <select name="idpredmeta" required>
            {$predOpcije}
          </select>
        </label><br><br>
        <label>Profesor (mentor)
          <select name="idprofesora" required>
            {$profOpcije}
          </select>
        </label><br><br>
        <label>Razred
          <select name="idrazred" required>
            {$razOpcije}
          </select>
        </label><br><br>
        <label>Ciklus
          <select name="idciklusa">
            {$cikOpcije}
          </select>
        </label><br><br>
        <button type="submit">Spremi</button>
        <a href="{$res->url('/admin/teme')}">Odustani</a>
      </form>
    HTML);
  }
}
