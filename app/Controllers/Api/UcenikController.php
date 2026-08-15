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
      $ucenik['SMJER_LABEL'] = UcenikService::smjerLabel((int)$ucenik['SMJER']);
      $this->ok($res, ['data' => $ucenik]);
      return;
    }

    $ucenici = UcenikService::list();

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

    $postojeci = $id > 0 ? UcenikService::get($id) : null;
    if ($data['lozinka'] === '' && $postojeci) {
      $data['lozinka'] = $postojeci['LOZINKA'];
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
      $ucenik['EMAIL'],
      $ucenik['IME'],
      $ucenik['PREZIME'],
      $ucenik['HASH']
    );

    if ($greska !== null) {
      $this->fail($res, $greska);
      return;
    }

    $this->ok($res);
  }
}
