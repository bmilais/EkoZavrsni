<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\TemaService;
use App\Services\ProfesorService;
use App\Services\PredmetService;
use App\Services\RazredService;
use App\Services\CiklusService;
use App\Services\UcenikService;

final class UvozController extends BaseController
{
  public function uvezi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $file = $req->file('excel');
    if (!$file) {
      $this->fail($res, 'Niste odabrali datoteku ili je došlo do greške pri uploadu.');
      return;
    }

    try {
      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      if (!$reader->canRead($file['tmp_name'])) {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        if (!$reader->canRead($file['tmp_name'])) {
          $this->fail($res, 'Datoteka nije prepoznata kao Excel (.xlsx/.xls).');
          return;
        }
      }

      $spreadsheet = $reader->load($file['tmp_name']);
      $worksheet   = $spreadsheet->getActiveSheet();
      $rows        = $worksheet->toArray(null, true, true, false);

      if (count($rows) < 2) {
        $this->fail($res, 'Excel datoteka nema podataka (potreban header + barem jedan red).');
        return;
      }

      array_shift($rows);

      $predmeti  = PredmetService::list();
      $profesori = ProfesorService::list();
      $razredi   = RazredService::list();
      $ciklusi   = CiklusService::list();

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
        $line  = $i + 2;
        $naziv = trim((string)($row[0] ?? ''));
        if ($naziv === '') continue;

        $predmet  = $lookup((string)($row[1] ?? ''), $predmetMap);
        $profesor = $lookup((string)($row[2] ?? ''), $profMap);
        $razred   = $lookup((string)($row[3] ?? ''), $razredMap);
        $ciklus   = $lookup((string)($row[4] ?? ''), $ciklusMap);

        $errs = [];
        if (!$predmet)  $errs[] = 'nepoznat predmet';
        if (!$profesor) $errs[] = 'nepoznat profesor';
        if (!$razred)   $errs[] = 'nepoznat razred';

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

      $this->ok($res, [
        'ubaceno' => $ubaceno,
        'greske'  => $greske,
      ]);
    } catch (\Throwable $e) {
      $this->fail($res, 'Greška pri obradi: ' . $e->getMessage(), 500);
    }
  }

  public function uveziUcenike(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $file = $req->file('excel');
    if (!$file) {
      $this->fail($res, 'Niste odabrali datoteku ili je došlo do greške pri uploadu.');
      return;
    }

    try {
      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      if (!$reader->canRead($file['tmp_name'])) {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        if (!$reader->canRead($file['tmp_name'])) {
          $this->fail($res, 'Datoteka nije prepoznata kao Excel (.xlsx/.xls).');
          return;
        }
      }

      $spreadsheet = $reader->load($file['tmp_name']);
      $worksheet   = $spreadsheet->getActiveSheet();
      $rows        = $worksheet->toArray(null, true, true, false);

      if (count($rows) < 2) {
        $this->fail($res, 'Excel datoteka nema podataka (potreban header + barem jedan red).');
        return;
      }

      array_shift($rows);

      $razredi = RazredService::list();

      $razredMap = [];
      foreach ($razredi as $r) {
        $razredMap[(int)$r['ID']] = $r;
        $razredMap[strtolower(trim($r['NAZIV']))] = $r;
      }

      $lookup = function (string $val, array $map): ?array {
        $key = strtolower(trim($val));
        if ($key === '' || $key === '0') return null;
        if (is_numeric($key) && isset($map[(int)$key])) {
          return $map[(int)$key];
        }
        return $map[$key] ?? null;
      };

      $postojeci = [];
      foreach (UcenikService::list() as $u) {
        $postojeci[strtolower(trim($u['EMAIL']))] = true;
      }

      $ubaceno   = 0;
      $greske    = [];
      $obradjeno = [];

      foreach ($rows as $i => $row) {
        $line    = $i + 2;
        $ime     = trim((string)($row[0] ?? ''));
        $prezime = trim((string)($row[1] ?? ''));
        $email   = trim((string)($row[2] ?? ''));

        if ($ime === '' && $prezime === '' && $email === '') continue;

        if ($ime === '' || $prezime === '') {
          $greske[] = "Red {$line}: nedostaje ime ili prezime — preskočeno";
          continue;
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $greske[] = "Red {$line}: neispravan email '{$email}' — preskočeno";
          continue;
        }

        $razred = $lookup((string)($row[3] ?? ''), $razredMap);
        if (!$razred) {
          $greske[] = "Red {$line}: nepoznat razred — preskočeno";
          continue;
        }

        $key = strtolower($email);
        if (isset($postojeci[$key]) || isset($obradjeno[$key])) {
          $greske[] = "Red {$line}: email '{$email}' već postoji — preskočeno";
          continue;
        }

        UcenikService::create([
          'idrazred' => (string)$razred['ID'],
          'ime'      => $ime,
          'prezime'  => $prezime,
          'email'    => $email,
          'lozinka'  => self::DEFAULT_LOZINKA,
          'smjer'    => (string)self::smjerIzUnosa((string)($row[4] ?? '')),
        ]);

        $obradjeno[$key] = true;
        $ubaceno++;
      }

      $this->ok($res, [
        'ubaceno' => $ubaceno,
        'greske'  => $greske,
      ]);
    } catch (\Throwable $e) {
      $this->fail($res, 'Greška pri obradi: ' . $e->getMessage(), 500);
    }
  }

  private const DEFAULT_LOZINKA = '1234';

  private static function smjerIzUnosa(string $val): int
  {
    $v = strtolower(trim($val));
    if ($v === 'trgovac' || $v === '2') {
      return 2;
    }
    return 1;
  }
}
