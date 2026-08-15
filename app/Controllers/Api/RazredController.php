<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\RazredService;

final class RazredController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = (int)$req->query('id', 0);
    if ($id > 0) {
      $razred = RazredService::get($id);
      if (!$razred) {
        $this->fail($res, 'Razred nije pronađen.', 404);
        return;
      }
      $this->ok($res, ['data' => $razred]);
      return;
    }

    $this->ok($res, ['data' => RazredService::list()]);
  }

  public function spremi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id    = $this->intParam($req, 'id');
    $naziv = trim((string)$req->input('naziv', ''));

    if ($naziv === '') {
      $this->fail($res, 'Naziv je obavezan.');
      return;
    }

    $data = ['naziv' => $naziv];
    if ($id > 0) {
      RazredService::update($id, $data);
    } else {
      $id = RazredService::create($data);
    }

    $this->ok($res, ['id' => $id]);
  }

  public function obrisi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = $this->intParam($req, 'id');
    if ($id > 0) {
      RazredService::delete($id);
    }

    $this->ok($res);
  }
}
