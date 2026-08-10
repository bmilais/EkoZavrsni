<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;

final class AuthController extends BaseController
{
  public function loginForm(Request $req, Response $res): void
  {
    if (Auth::check()) {
      $res->redirect($this->pocetnaZaUlogu(Auth::role()));
    }

    $greska = $req->query('greska') ? '<p style="color:red">Pogresan email ili lozinka.</p>' : '';

    $action = $res->url('/login');
    $this->view($res, <<<HTML
      <h1>Prijava</h1>
      {$greska}
      <form method="post" action="{$action}">
        <label>Email <input type="email" name="email" required></label><br>
        <label>Lozinka <input type="password" name="lozinka" required></label><br>
        <button type="submit">Prijavi se</button>
      </form>
    HTML);
  }

  public function login(Request $req, Response $res): void
  {
    $email   = (string)$req->input('email', '');
    $lozinka = (string)$req->input('lozinka', '');

    if (!Auth::attempt($email, $lozinka)) {
      $res->redirect('/login?greska=1');
    }

    $res->redirect($this->pocetnaZaUlogu(Auth::role()));
  }

  /**
   * Ulaz za ucenika preko magic-linka: /prijava?token=...
   */
  public function ucenikToken(Request $req, Response $res): void
  {
    $token = (string)$req->query('token', '');

    if ($token === '' || !Auth::attemptToken($token)) {
      $this->view($res, '<h1>Link nije ispravan</h1><p>Provjerite jeste li kopirali cijeli link iz e-maila.</p>', 403);
      return;
    }

    $res->redirect('/ucenik');
  }

  public function logout(Request $req, Response $res): void
  {
    Auth::logout();
    $res->redirect('/login');
  }

  private function pocetnaZaUlogu(?string $uloga): string
  {
    return match ($uloga) {
      'admin'     => '/admin',
      'nastavnik' => '/nastavnik',
      'ucenik'    => '/ucenik',
      default     => '/login',
    };
  }
}