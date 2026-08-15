<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\OdabirService;

final class UcenikPanelController extends BaseController
{
  public function dostupne(Request $req, Response $res): void
  {
    $this->requireRole($res, ['ucenik']);
    $u = Auth::user();

    $this->ok($res, [
      'data'   => OdabirService::dostupneTeme((int)$u['razred_id'], (int)$u['id']),
      'ciklus' => OdabirService::openCiklus(),
    ]);
  }

  public function moji(Request $req, Response $res): void
  {
    $this->requireRole($res, ['ucenik']);
    $u = Auth::user();

    $this->ok($res, ['data' => OdabirService::mojiOdabiri((int)$u['id'])]);
  }

  public function odaberi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['ucenik']);
    $u = Auth::user();

    $temaId = $this->intParam($req, 'tema_id');
    $greska = OdabirService::odaberi((int)$u['id'], (int)$u['razred_id'], $temaId);

    if ($greska !== null) {
      $this->fail($res, $greska);
      return;
    }

    $this->ok($res);
  }

  public function ponisti(Request $req, Response $res): void
  {
    $this->requireRole($res, ['ucenik']);
    $u = Auth::user();

    $odabirId = $this->intParam($req, 'id');
    $greska   = OdabirService::ponisti((int)$u['id'], $odabirId);

    if ($greska !== null) {
      $this->fail($res, $greska);
      return;
    }

    $this->ok($res);
  }
}
