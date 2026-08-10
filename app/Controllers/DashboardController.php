<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;

final class DashboardController extends BaseController
{
  public function admin(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['admin']);
    $u      = Auth::user();
    $logout = $res->url('/logout');
    $menu   = $this->adminNavigacija($res);
    $this->view($res, <<<HTML
      <h1>Admin panel</h1>
      <p>Pozdrav, {$u['ime']} {$u['prezime']}.</p>
      {$menu}
      <p>Odaberite opciju iz izbornika.</p>
    HTML);
  }

  private function adminNavigacija(Response $res): string
  {
    $ciklusi  = $res->url('/admin/ciklusi');
    $razredi  = $res->url('/admin/razredi');
    $predmeti  = $res->url('/admin/predmeti');
    $profesori = $res->url('/admin/profesori');
    $ucenici   = $res->url('/admin/ucenici');
    $teme      = $res->url('/admin/teme');
    $odabiri   = $res->url('/admin/odabiri');
    $stanje    = $res->url('/admin/stanje');
    $logout    = $res->url('/logout');
    return <<<HTML
      <hr>
      <nav>
        <a href="{$ciklusi}">Ciklusi</a> |
        <a href="{$razredi}">Razredi</a> |
        <a href="{$predmeti}">Predmeti</a> |
        <a href="{$profesori}">Profesori</a> |
        <a href="{$ucenici}">Učenici</a> |
        <a href="{$teme}">Teme</a> |
        <a href="{$odabiri}">Odabiri</a> |
        <a href="{$stanje}">Stanje</a> |
        <a href="{$logout}">Odjava</a>
      </nav>
      <hr>
    HTML;
  }

  public function nastavnik(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['nastavnik', 'admin']);
    $u = Auth::user();
    $logout = $res->url('/logout');
    $this->view($res, "<h1>Nastavnik panel</h1><p>Pozdrav, {$u['ime']} {$u['prezime']}.</p><a href='{$logout}'>Odjava</a>");
  }

  public function ucenik(Request $req, Response $res): void
  {
    Auth::requireRole($res, ['ucenik']);
    $u = Auth::user();
    $logout = $res->url('/logout');
    $this->view($res, "<h1>Ucenicki panel</h1><p>Pozdrav, {$u['ime']} {$u['prezime']}.</p><a href='{$logout}'>Odjava</a>");
  }
}