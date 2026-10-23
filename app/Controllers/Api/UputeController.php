<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\MailService;
use App\Services\UcenikService;
use App\Services\UputeService;

/**
 * Panel "Upute": PDF upute za učenike odabranog ciklusa
 * (upload, pregled i slanje mailom kao privitak).
 */
final class UputeController extends BaseController
{
  /** GET /api/upute?ciklus_id= - podaci za panel. */
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $ciklusId = (int)$req->query('ciklus_id', 0);
    $info = $ciklusId > 0 ? UputeService::info($ciklusId) : null;
    if (!$info) {
      $this->fail($res, 'Ciklus nije pronađen.', 404);
      return;
    }

    $this->ok($res, ['data' => $info]);
  }

  /** POST /api/upute/upload (multipart: ciklus_id, pdf) - učitava ili zamjenjuje PDF. */
  public function upload(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    // Ako je datoteka veća od post_max_size, PHP odbaci cijeli zahtjev
    // pa su i $_POST i $_FILES prazni.
    if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
      $this->fail($res, 'Datoteka je prevelika za poslužitelj (post_max_size = ' . ini_get('post_max_size') . ').');
      return;
    }

    $ciklusId = (int)$req->input('ciklus_id', 0);
    if ($ciklusId <= 0 || !UputeService::info($ciklusId)) {
      $this->fail($res, 'Ciklus nije pronađen.', 404);
      return;
    }

    $file  = $_FILES['pdf'] ?? null;
    $error = $file ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
      $poruka = match ($error) {
        UPLOAD_ERR_NO_FILE                       => 'Niste odabrali datoteku.',
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Datoteka je prevelika za poslužitelj (upload_max_filesize = ' . ini_get('upload_max_filesize') . ').',
        default                                  => 'Greška pri učitavanju datoteke (kod ' . $error . ').',
      };
      $this->fail($res, $poruka);
      return;
    }

    $greska = UputeService::spremi($ciklusId, $file);
    if ($greska !== null) {
      $this->fail($res, $greska);
      return;
    }

    $this->ok($res);
  }

  /** GET /api/upute/preuzmi?ciklus_id= - adminu prikazuje učitani PDF. */
  public function preuzmi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $ciklusId = (int)$req->query('ciklus_id', 0);
    $info = $ciklusId > 0 ? UputeService::info($ciklusId) : null;
    if (!$info || !$info['POSTOJI']) {
      $this->fail($res, 'PDF s uputama nije učitan.', 404);
      return;
    }

    $naziv = (string)$info['NAZIV'];
    $ascii = (string)preg_replace('/[^A-Za-z0-9._-]+/', '_', $naziv);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $info['VELICINA']);
    header('Content-Disposition: inline; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($naziv));
    header('X-Content-Type-Options: nosniff');
    readfile(UputeService::putanja($ciklusId));
  }

  /**
   * POST /api/upute/posalji-uceniku {"ucenik_id": 501}
   * Šalje PDF upute jednom učeniku (ciklus se uzima iz učenika), kao
   * "Pošalji link" kod maila za prijavu. Šalje se i ako ih je već dobio.
   */
  public function posaljiUceniku(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $ucenikId = $this->intParam($req, 'ucenik_id');
    $ucenik = $ucenikId > 0 ? UcenikService::get($ucenikId) : null;
    if (!$ucenik) {
      $this->fail($res, 'Učenik nije pronađen.', 404);
      return;
    }

    $info = UputeService::info((int)$ucenik['idciklus']);
    if (!$info || !$info['POSTOJI']) {
      $this->fail($res, 'Za ciklus ovog učenika nije učitan PDF s uputama.');
      return;
    }

    try {
      $mail = MailService::otvoriVezu();
    } catch (\Throwable $e) {
      error_log('Mail greška: ' . $e->getMessage());
      $this->fail($res, 'Ne mogu pripremiti slanje maila: ' . $e->getMessage(), 500);
      return;
    }

    try {
      $greska = MailService::posaljiUpute(
        $mail,
        (string)$ucenik['email'],
        (string)$ucenik['ime'],
        (string)$ucenik['prezime'],
        (string)$info['CIKLUS_NAZIV'],
        UputeService::putanja((int)$ucenik['idciklus']),
        (string)$info['NAZIV']
      );
    } finally {
      MailService::zatvoriVezu($mail);
    }

    if ($greska !== null) {
      $this->fail($res, $greska);
      return;
    }

    UcenikService::oznaciUputePoslane($ucenikId);
    $this->ok($res);
  }

  /**
   * POST /api/upute/posalji {"ciklus_id": 1, "ponovno_svima": false}
   * Šalje PDF kao privitak. Zadano samo učenicima koji upute još nisu dobili
   * (UCENICI.UPUTE_POSLANE IS NULL); ponovno_svima = svima u ciklusu.
   */
  public function posalji(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $ciklusId = (int)$req->input('ciklus_id', 0);
    $info = $ciklusId > 0 ? UputeService::info($ciklusId) : null;
    if (!$info) {
      $this->fail($res, 'Ciklus nije pronađen.', 404);
      return;
    }
    if (!$info['POSTOJI']) {
      $this->fail($res, 'Prvo učitajte PDF s uputama.');
      return;
    }
    $ponovnoSvima = filter_var($req->input('ponovno_svima', false), FILTER_VALIDATE_BOOL);

    $ucenici = UcenikService::zaSlanjeUputa($ciklusId, !$ponovnoSvima);
    $putanja = UputeService::putanja($ciklusId);

    ignore_user_abort(true);

    $poslano = 0;
    $greske  = [];

    try {
      $mail = MailService::otvoriVezu();
    } catch (\Throwable $e) {
      error_log('Mail greška: ' . $e->getMessage());
      $this->fail($res, 'Ne mogu pripremiti slanje maila: ' . $e->getMessage(), 500);
      return;
    }

    try {
      foreach ($ucenici as $u) {
        set_time_limit(60);

        $greska = MailService::posaljiUpute(
          $mail,
          (string)$u['EMAIL'],
          (string)$u['IME'],
          (string)$u['PREZIME'],
          (string)$info['CIKLUS_NAZIV'],
          $putanja,
          (string)$info['NAZIV']
        );
        if ($greska !== null) {
          $greske[] = trim($u['PREZIME'] . ' ' . $u['IME']) . ' (' . $u['EMAIL'] . '): ' . $greska;
          continue;
        }

        UcenikService::oznaciUputePoslane((int)$u['ID']);
        $poslano++;
      }
    } finally {
      MailService::zatvoriVezu($mail);
    }

    $this->ok($res, [
      'poslano' => $poslano,
      'ukupno'  => count($ucenici),
      'greske'  => $greske,
    ]);
  }
}
