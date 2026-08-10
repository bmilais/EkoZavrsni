<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use App\Core\Session;

final class Auth
{
  private const SESSION_KEY = 'auth_user';

  /**
   * Prijava admina/nastavnika email+lozinkom (tablica PROFESORI).
   * NAPOMENA: lozinke su trenutno plaintext (dogovoreno za lokalni razvoj).
   * Prije produkcije treba prijeci na password_hash()/password_verify()
   * i prosiriti kolonu LOZINKA (VARCHAR2(20) je premalo za bcrypt hash).
   */
  public static function attempt(string $email, string $password): bool
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, IME, PREZIME, EMAIL, LOZINKA, OVLASTI
         FROM PROFESORI
        WHERE EMAIL = :email AND DELETED = 0'
    );
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    if (!$row || $row['LOZINKA'] === null) {
      return false;
    }
    if (!hash_equals((string)$row['LOZINKA'], $password)) {
      return false;
    }

    Session::set(self::SESSION_KEY, [
      'id'      => (int)$row['ID'],
      'ime'     => $row['IME'],
      'prezime' => $row['PREZIME'],
      'email'   => $row['EMAIL'],
      'uloga'   => ((int)$row['OVLASTI'] === 0) ? 'admin' : 'nastavnik',
    ]);

    return true;
  }

  /**
   * "Prijava" ucenika preko magic-link tokena (UCENICI.HASH).
   */
  public static function attemptToken(string $token): bool
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, IDRAZRED, IME, PREZIME, EMAIL
         FROM UCENICI
        WHERE HASH = :hash AND DELETED = 0'
    );
    $stmt->execute(['hash' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
      return false;
    }

    Session::set(self::SESSION_KEY, [
      'id'        => (int)$row['ID'],
      'ime'       => $row['IME'],
      'prezime'   => $row['PREZIME'],
      'email'     => $row['EMAIL'],
      'razred_id' => (int)$row['IDRAZRED'],
      'uloga'     => 'ucenik',
    ]);

    return true;
  }

  public static function user(): ?array
  {
    return Session::get(self::SESSION_KEY);
  }

  public static function check(): bool
  {
    return self::user() !== null;
  }

  public static function role(): ?string
  {
    return self::user()['uloga'] ?? null;
  }

  public static function logout(): void
  {
    Session::remove(self::SESSION_KEY);
  }

  /**
   * Poziva se na pocetku metoda kontrolera koje treba zastititi.
   * Ako korisnik nije prijavljen -> redirect na /login.
   * Ako je prijavljen ali nema dozvoljenu ulogu -> 403 i prekid.
   *
   * Primjer: Auth::requireRole($res, ['admin']);
   */
  public static function requireRole(Response $res, array $allowedRoles): void
  {
    if (!self::check()) {
      $res->redirect('/login');
    }
    if (!in_array(self::role(), $allowedRoles, true)) {
      $res->html('<h1>403</h1><p>Nemate pristup ovoj stranici.</p>', 403);
      exit;
    }
  }
}