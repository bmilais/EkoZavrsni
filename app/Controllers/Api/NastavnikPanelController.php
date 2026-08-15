<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\NastavnikService;

final class NastavnikPanelController extends BaseController
{
  public function teme(Request $req, Response $res): void
  {
    $this->requireRole($res, ['nastavnik']);
    $u = Auth::user();

    $this->ok($res, ['data' => NastavnikService::mojeTeme((int)$u['id'])]);
  }

  public function odabiri(Request $req, Response $res): void
  {
    $this->requireRole($res, ['nastavnik']);
    $u = Auth::user();

    $temaId = (int)($req->query('tema_id', 0));
    $filter = (string)($req->query('filter', 'aktivni') ?? 'aktivni');

    $this->ok($res, [
      'data' => NastavnikService::odabiri((int)$u['id'], $temaId > 0 ? $temaId : null, $filter),
    ]);
  }
}
