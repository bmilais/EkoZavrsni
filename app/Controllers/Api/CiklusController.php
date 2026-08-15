<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\CiklusService;

final class CiklusController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = (int)$req->query('id', 0);
    if ($id > 0) {
      $ciklus = CiklusService::get($id);
      if (!$ciklus) {
        $this->fail($res, 'Ciklus nije pronađen.', 404);
        return;
      }
      $ciklus['STATUS_LABEL'] = CiklusService::statusLabel($ciklus['STATUS']);
      $this->ok($res, ['data' => $ciklus]);
      return;
    }

    $ciklusi = CiklusService::list();

    foreach ($ciklusi as &$c) {
      $c['STATUS_LABEL'] = CiklusService::statusLabel($c['STATUS']);
      $c['MOGUCI_STATUSI'] = CiklusService::moguciStatusi($c['STATUS']);
    }
    unset($c);

    $this->ok($res, ['data' => $ciklusi]);
  }

  public function spremi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id    = $this->intParam($req, 'id');
    $data  = [
      'skolska_godina'         => (string)$req->input('skolska_godina', ''),
      'naziv'                  => (string)$req->input('naziv', ''),
      'max_ucenika_po_mentoru' => (string)$req->input('max_ucenika_po_mentoru', ''),
      'max_tema_po_predmetu'   => (string)$req->input('max_tema_po_predmetu', ''),
      'upute_pdf_url'          => (string)$req->input('upute_pdf_url', ''),
    ];

    if (trim($data['naziv']) === '' || trim($data['skolska_godina']) === '') {
      $this->fail($res, 'Naziv i školska godina su obavezni.');
      return;
    }

    if ($id > 0) {
      CiklusService::update($id, $data);
    } else {
      $id = CiklusService::create($data);
    }

    $this->ok($res, ['id' => $id]);
  }

  public function status(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id     = $this->intParam($req, 'id');
    $status = (string)$req->input('status', '');

    if ($id <= 0 || $status === '') {
      $this->fail($res, 'Nedostaju parametri.');
      return;
    }

    $error = CiklusService::promijeniStatus($id, $status);
    if ($error !== '') {
      $this->fail($res, $error, 400);
      return;
    }

    $this->ok($res);
  }

  public function obrisi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = $this->intParam($req, 'id');
    if ($id > 0) {
      CiklusService::delete($id);
    }

    $this->ok($res);
  }
}
