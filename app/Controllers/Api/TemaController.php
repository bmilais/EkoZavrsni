<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\TemaService;
use App\Services\PredmetService;
use App\Services\ProfesorService;
use App\Services\RazredService;
use App\Services\CiklusService;

final class TemaController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = (int)$req->query('id', 0);
    if ($id > 0) {
      $tema = TemaService::get($id);
      if (!$tema) {
        $this->fail($res, 'Tema nije pronađena.', 404);
        return;
      }
      $this->ok($res, ['data' => $tema]);
      return;
    }

    $this->ok($res, ['data' => TemaService::list()]);
  }

  /**
   * Podaci za padajuce izbore na formi (predmeti, profesori, razredi, ciklusi).
   */
  public function opcije(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $this->ok($res, [
      'predmeti'  => PredmetService::list(),
      'profesori' => ProfesorService::list(),
      'razredi'   => RazredService::list(),
      'ciklusi'   => CiklusService::list(),
    ]);
  }

  public function spremi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id    = $this->intParam($req, 'id');
    $data  = [
      'idpredmeta'  => (string)$req->input('idpredmeta', ''),
      'idprofesora' => (string)$req->input('idprofesora', ''),
      'idrazred'    => (string)$req->input('idrazred', ''),
      'idciklusa'   => (string)$req->input('idciklusa', ''),
      'naziv'       => trim((string)$req->input('naziv', '')),
    ];

    if ($data['naziv'] === '') {
      $this->fail($res, 'Naziv teme je obavezan.');
      return;
    }
    if ((int)$data['idpredmeta'] <= 0) {
      $this->fail($res, 'Predmet je obavezan.');
      return;
    }
    if ((int)$data['idprofesora'] <= 0) {
      $this->fail($res, 'Profesor je obavezan.');
      return;
    }
    if ((int)$data['idrazred'] <= 0) {
      $this->fail($res, 'Razred je obavezan.');
      return;
    }

    if ($id > 0) {
      TemaService::update($id, $data);
    } else {
      $id = TemaService::create($data);
    }

    $this->ok($res, ['id' => $id]);
  }

  public function obrisi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = $this->intParam($req, 'id');
    if ($id > 0) {
      TemaService::delete($id);
    }

    $this->ok($res);
  }
}
