<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\PredmetService;
use App\Services\RazredService;

final class PredmetController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = (int)$req->query('id', 0);
    if ($id > 0) {
      $predmet = PredmetService::get($id);
      if (!$predmet) {
        $this->fail($res, 'Predmet nije pronađen.', 404);
        return;
      }
      $predmet['SMJER_LABEL'] = PredmetService::smjerLabel((int)$predmet['SMJER']);
      $this->ok($res, ['data' => $predmet]);
      return;
    }

    $predmeti = PredmetService::list();

    foreach ($predmeti as &$p) {
      $p['SMJER_LABEL'] = PredmetService::smjerLabel((int)$p['SMJER']);
    }
    unset($p);

    $this->ok($res, ['data' => $predmeti]);
  }

  public function spremi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id    = $this->intParam($req, 'id');
    $data  = [
      'naziv'  => trim((string)$req->input('naziv', '')),
      'smjer'  => (string)$req->input('smjer', ''),
      'razred' => (string)$req->input('razred', ''),
      'limit'  => (string)$req->input('limit', ''),
    ];

    if ($data['naziv'] === '') {
      $this->fail($res, 'Naziv predmeta je obavezan.');
      return;
    }

    if ($id > 0) {
      PredmetService::update($id, $data);
    } else {
      $id = PredmetService::create($data);
    }

    $this->ok($res, ['id' => $id]);
  }

  public function obrisi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = $this->intParam($req, 'id');
    if ($id > 0) {
      PredmetService::delete($id);
    }

    $this->ok($res);
  }
}
