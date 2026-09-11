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

    $this->ok($res);
  }

  /**
   * Šalje magic-link email svim učenicima zadanog ciklusa odjednom
   * (umjesto da admin klika "Pošalji link" za svakog posebno).
   */
  public function posaljiLinkSvima(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $ciklusId = (int)$req->input('ciklus_id', 0);
    if ($ciklusId <= 0) {
      $this->fail($res, 'Ciklus je obavezan.');
      return;
    }

    $ucenici = UcenikService::list($ciklusId);

    $poslano = 0;
    $greske  = [];

    foreach ($ucenici as $u) {
      // list() ne vraća HASH (ne šalje se u JSON prema frontendu), pa se
      // za svakog učenika posebno dohvaća puni zapis potreban za slanje.
      $puni = UcenikService::get((int)$u['ID']);
      if (!$puni) {
        $greske[] = trim($u['PREZIME'] . ' ' . $u['IME']) . ': učenik više ne postoji.';
        continue;
      }

      $greska = MailService::posaljiLinkUceniku(
        $puni['email'],
        $puni['ime'],
        $puni['prezime'],
        $puni['hash']
      );

      if ($greska !== null) {
        $greske[] = trim($puni['prezime'] . ' ' . $puni['ime']) . ' (' . $puni['email'] . '): ' . $greska;
        continue;
      }

      $poslano++;
    }

    $this->ok($res, [
      'poslano' => $poslano,
      'ukupno'  => count($ucenici),
      'greske'  => $greske,
    ]);
  }
}