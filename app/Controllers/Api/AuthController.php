<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;

final class AuthController extends BaseController
{
  public function login(Request $req, Response $res): void
  {
    $email   = (string)$req->input('email', '');
    $lozinka = (string)$req->input('lozinka', '');

    if ($email === '' || $lozinka === '') {
      $this->fail($res, 'Email i lozinka su obavezni.');
      return;
    }

    if (!Auth::attempt($email, $lozinka)) {
      $this->fail($res, 'Pogrešan email ili lozinka.', 401);
      return;
    }

    $this->ok($res, ['user' => Auth::user()]);
  }

  public function prijava(Request $req, Response $res): void
  {
    $token = (string)$req->input('token', '');

    if ($token === '' || !Auth::attemptToken($token)) {
      $this->fail($res, 'Link nije ispravan.', 403);
      return;
    }

    $this->ok($res, ['user' => Auth::user()]);
  }

  public function user(Request $req, Response $res): void
  {
    $this->json($res, ['user' => Auth::user()]);
  }

  public function logout(Request $req, Response $res): void
  {
    Auth::logout();
    $this->ok($res);
  }
}
