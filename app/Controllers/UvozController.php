<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\TemaService;
use App\Services\ProfesorService;
use App\Services\PredmetService;
use App\Services\RazredService;
use App\Services\CiklusService;

final class UvozController extends BaseController
{
  public function form(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $msg    = $req->query('msg', '');
    $error  = $req->query('error', '');
    $info   = $msg ? '<p style="color:green">' . htmlspecialchars($msg) . '</p>' : '';
    $err    = $error ? '<p style="color:red">' . htmlspecialchars($error) . '</p>' : '';
    $action = $res->url('/admin/teme/uvoz');
    $back   = $res->url('/admin/teme');

    $this->view($res, <<<HTML
      <h1>Uvoz tema iz Excela</h1>
      {$info}{$err}
      <p>Očekivani column redoslijed (prvi red = zaglavlje, preskače se):</p>
      <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse">
        <tr><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th></tr>
        <tr><td>Naziv teme</td><td>Predmet<br><small>(ID ili naziv)</small></td><td>Profesor<br><small>(ID ili "Prezime Ime")</small></td><td>Razred<br><small>(ID ili naziv)</small></td><td>Ciklus<br><small>(ID ili naziv, opcionalno)</small></td></tr>
      </table>
      <br>
      <form method="post" action="{$action}" enctype="multipart/form-data">
        <input type="file" name="excel" accept=".xlsx,.xls" required>
        <br><br>
        <button type="submit">Uvezi</button>
        <a href="{$back}">Odustani</a>
      </form>
    HTML);
  }

  public function uvezi(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);

    $file = $req->file('excel');
    if (!$file) {
      $res->redirect('/admin/teme/uvoz?error=' . urlencode('Niste odabrali datoteku ili je došlo do greške pri uploadu.'));
    }

    try {
      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      if (!$reader->canRead($file['tmp_name'])) {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        if (!$reader->canRead($file['tmp_name'])) {
          $res->redirect('/admin/teme/uvoz?error=' . urlencode('Datoteka nije prepoznata kao Excel (.xlsx/.xls).'));
        }
      }

      $spreadsheet = $reader->load($file['tmp_name']);
      $worksheet   = $spreadsheet->getActiveSheet();
      $rows        = $worksheet->toArray(null, true, true, false);

      if (count($rows) < 2) {
        $res->redirect('/admin/teme/uvoz?error=' . urlencode('Excel datoteka nema podataka (potreban header + barem jedan red).'));
      }

      // skip header row (row 0)
      array_shift($rows);

      $predmeti = PredmetService::list();
      $profesori = ProfesorService::list();
      $razredi = RazredService::list();
      $ciklusi = CiklusService::list();

      $predmetMap = [];
      foreach ($predmeti as $p) {
        $predmetMap[(int)$p['ID']] = $p;
        $predmetMap[strtolower(trim($p['NAZIV']))] = $p;
      }

      $profMap = [];
      foreach ($profesori as $p) {
        $profMap[(int)$p['ID']] = $p;
        $profMap[strtolower(trim($p['PREZIME'] . ' ' . $p['IME']))] = $p;
        $profMap[strtolower(trim($p['PREZIME'] . '  ' . $p['IME']))] = $p;
      }

      $razredMap = [];
      foreach ($razredi as $r) {
        $razredMap[(int)$r['ID']] = $r;
        $razredMap[strtolower(trim($r['NAZIV']))] = $r;
      }

      $ciklusMap = [];
      foreach ($ciklusi as $c) {
        $ciklusMap[(int)$c['ID']] = $c;
        $ciklusMap[strtolower(trim($c['NAZIV']))] = $c;
      }

      $lookup = function (string $val, array $map): ?array {
        $key = strtolower(trim($val));
        if ($key === '' || $key === '0') return null;
        if (is_numeric($key) && isset($map[(int)$key])) {
          return $map[(int)$key];
        }
        return $map[$key] ?? null;
      };

      $ubaceno = 0;
      $greske  = [];

      foreach ($rows as $i => $row) {
        $line = $i + 2;
        $naziv = trim((string)($row[0] ?? ''));
        if ($naziv === '') continue;

        $predmet  = $lookup((string)($row[1] ?? ''), $predmetMap);
        $profesor = $lookup((string)($row[2] ?? ''), $profMap);
        $razred   = $lookup((string)($row[3] ?? ''), $razredMap);
        $ciklus   = $lookup((string)($row[4] ?? ''), $ciklusMap);

        $errs = [];
        if (!$predmet) $errs[] = 'nepoznat predmet';
        if (!$profesor) $errs[] = 'nepoznat profesor';
        if (!$razred) $errs[] = 'nepoznat razred';

        if ($errs) {
          $greske[] = "Red {$line}: " . implode(', ', $errs) . " — '{$naziv}' preskočeno";
          continue;
        }

        TemaService::create([
          'idpredmeta'  => (string)$predmet['ID'],
          'idprofesora' => (string)$profesor['ID'],
          'idrazred'    => (string)$razred['ID'],
          'idciklusa'   => $ciklus ? (string)$ciklus['ID'] : '',
          'naziv'       => $naziv,
        ]);
        $ubaceno++;
      }

      $poruke = [];
      if ($ubaceno > 0) {
        $poruke[] = "Uspješno uvezeno {$ubaceno} tema.";
      }
      if ($greske) {
        $poruke[] = 'Greške:';
        $poruke = array_merge($poruke, array_slice($greske, 0, 20));
        if (count($greske) > 20) {
          $poruke[] = '... i još ' . (count($greske) - 20) . ' grešaka';
        }
      }

      $msg  = implode("\n", array_slice($poruke, 0, 1));
      $err  = implode("<br>", array_slice($poruke, 1));
      $dest = '/admin/teme/uvoz';
      $dest .= '?msg=' . urlencode($msg);
      if ($err) $dest .= '&error=' . urlencode($err);
      $res->redirect($dest);

    } catch (\Throwable $e) {
      $res->redirect('/admin/teme/uvoz?error=' . urlencode('Greška pri obradi: ' . $e->getMessage()));
    }
  }
}
