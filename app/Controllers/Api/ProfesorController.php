<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\ProfesorService;

final class ProfesorController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = (int)$req->query('id', 0);
    if ($id > 0) {
      $profesor = ProfesorService::get($id);
      if (!$profesor) {
        $this->fail($res, 'Profesor nije pronađen.', 404);
        return;
      }
      $this->ok($res, ['data' => $profesor]);
      return;
    }

    $profesori = ProfesorService::list();

    foreach ($profesori as &$p) {
      $p['ULOGA_LABEL'] = ProfesorService::ulogaLabel((int)$p['OVLASTI']);
    }
    unset($p);

    $this->ok($res, ['data' => $profesori]);
  }

  public function spremi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id    = $this->intParam($req, 'id');
    $data  = [
      'ime'      => trim((string)$req->input('ime', '')),
      'prezime'  => trim((string)$req->input('prezime', '')),
      'email'    => trim((string)$req->input('email', '')),
      'lozinka'  => trim((string)$req->input('lozinka', '')),
      'ovlasti'  => (string)$req->input('ovlasti', ''),
    ];

    if ($data['ime'] === '' || $data['prezime'] === '') {
      $this->fail($res, 'Ime i prezime su obavezni.');
      return;
    }

    $postojeci = $id > 0 ? ProfesorService::get($id) : null;
    if ($data['lozinka'] === '' && $postojeci) {
      $data['lozinka'] = $postojeci['LOZINKA'];
    }

    if ($id > 0) {
      ProfesorService::update($id, $data);
    } else {
      $id = ProfesorService::create($data);
    }

    $this->ok($res, ['id' => $id]);
  }

  public function obrisi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = $this->intParam($req, 'id');
    if ($id > 0) {
      ProfesorService::delete($id);
    }

    $this->ok($res);
  }
}
