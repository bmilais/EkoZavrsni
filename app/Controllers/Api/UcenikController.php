<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\UcenikService;
use App\Services\RazredService;
use App\Services\MailService;

final class UcenikController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = (int)$req->query('id', 0);
    if ($id > 0) {
      $ucenik = UcenikService::get($id);
      if (!$ucenik) {
        $this->fail($res, 'Učenik nije pronađen.', 404);
        return;
      }
      $ucenik['SMJER_LABEL'] = UcenikService::smjerLabel((int)$ucenik['smjer']);
      // Lozinka i magic-link token (hash) nikad ne idu frontendu. Uređivanje
      // i dalje radi: spremi() kod prazne lozinke uzima postojeću iz baze.
      unset($ucenik['lozinka'], $ucenik['hash']);
      $this->ok($res, ['data' => $ucenik]);
      return;
    }

    $ciklusId = (int)$req->query('ciklus_id', 0);
    $ucenici  = UcenikService::list($ciklusId > 0 ? $ciklusId : null);

    foreach ($ucenici as &$u) {
      $u['SMJER_LABEL'] = UcenikService::smjerLabel((int)$u['SMJER']);
    }
    unset($u);

    $this->ok($res, ['data' => $ucenici]);
  }

  public function spremi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id    = $this->intParam($req, 'id');
    $data  = [
      'idrazred' => (string)$req->input('idrazred', ''),
      'ime'      => trim((string)$req->input('ime', '')),
      'prezime'  => trim((string)$req->input('prezime', '')),
      'email'    => trim((string)$req->input('email', '')),
      'lozinka'  => trim((string)$req->input('lozinka', '')),
      'smjer'    => (string)$req->input('smjer', '1'),
      'idciklus' => (string)$req->input('idciklus', ''),
    ];

    if ($data['ime'] === '' || $data['prezime'] === '') {
      $this->fail($res, 'Ime i prezime su obavezni.');
      return;
    }
    if ($data['email'] === '') {
      $this->fail($res, 'Email je obavezan.');
      return;
    }
    if ((int)$data['idrazred'] <= 0) {
      $this->fail($res, 'Razred je obavezan.');
      return;
    }
    if ($id <= 0 && (int)$data['idciklus'] <= 0) {
      $this->fail($res, 'Ciklus je obavezan.');
      return;
    }

    $postojeci = $id > 0 ? UcenikService::get($id) : null;
    if ($data['lozinka'] === '' && $postojeci) {
      $data['lozinka'] = $postojeci['lozinka'];
    }

    if ($id > 0) {
      UcenikService::update($id, $data);
    } else {
      $id = UcenikService::create($data);
    }

    $this->ok($res, ['id' => $id]);
  }

  public function obrisi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = $this->intParam($req, 'id');
    if ($id > 0) {
      UcenikService::delete($id);
    }

    $this->ok($res);
  }

  public function posaljiLink(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = $this->intParam($req, 'id');
    $ucenik = UcenikService::get($id);
    if (!$ucenik) {
      $this->fail($res, 'Učenik nije pronađen.', 404);
      return;
    }

    if ((string)($ucenik['hash'] ?? '') === '') {
      $this->fail($res, 'Učenik nema token za prijavu.');
      return;
    }

    $greska = MailService::posaljiLinkUceniku(
      $ucenik['email'],
      $ucenik['ime'],
      $ucenik['prezime'],
      $ucenik['hash']
    );

    if ($greska !== null) {
      $this->fail($res, $greska);
      return;
    }

    UcenikService::oznaciLinkPoslan($id);
    $this->ok($res);
  }

  /**
   * Šalje magic-link email učenicima zadanog ciklusa odjednom.
   *
   * Zadano samo onima kojima link još nije uspješno poslan (LINK_POSLAN IS NULL),
   * pa ponovni klik nakon prekida nastavlja gdje je stalo, umjesto da šalje
   * svima iznova. {"ponovno_svima": true} šalje svima u ciklusu.
   */
  public function posaljiLinkSvima(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $ciklusId = (int)$req->input('ciklus_id', 0);
    if ($ciklusId <= 0) {
      $this->fail($res, 'Ciklus je obavezan.');
      return;
    }
    $ponovnoSvima = filter_var($req->input('ponovno_svima', false), FILTER_VALIDATE_BOOL);

    // Jedan upit za sve (umjesto UcenikService::get() po učeniku).
    $ucenici = UcenikService::zaSlanjeLinka($ciklusId, !$ponovnoSvima);

    // Slanje se nastavlja i ako admin zatvori karticu, a vremensko ograničenje
    // obnavlja se po učeniku (umjesto 120 s za cijelu petlju).
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

        $ime  = trim($u['PREZIME'] . ' ' . $u['IME']) . ' (' . $u['EMAIL'] . ')';
        $hash = (string)($u['HASH'] ?? '');
        if ($hash === '') {
          $greske[] = $ime . ': nema token za prijavu.';
          continue;
        }

        $greska = MailService::posalji($mail, (string)$u['EMAIL'], (string)$u['IME'], (string)$u['PREZIME'], $hash);
        if ($greska !== null) {
          $greske[] = $ime . ': ' . $greska;
          continue;
        }

        UcenikService::oznaciLinkPoslan((int)$u['ID']);
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

  public function obrisiVise(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $ids = $this->idsParam($req);
    if (!$ids) {
      $this->fail($res, 'Nije odabran nijedan zapis.');
      return;
    }

    $deleted = UcenikService::deleteMany($ids);
    $this->ok($res, ['deleted' => $deleted]);
  }
}
