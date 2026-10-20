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
   * Lozinke se spremaju kao password_hash(). Stare plaintext lozinke još
   * prolaze provjeru i automatski se prebacuju na hash pri prvoj prijavi.
   * Preduvjet: ALTER TABLE PROFESORI MODIFY (LOZINKA VARCHAR2(255));
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

    $spremljena = (string)$row['LOZINKA'];
    if (password_get_info($spremljena)['algoName'] === 'unknown') {
      // Stara plaintext lozinka: provjeri pa odmah zamijeni hashom.
      if (!hash_equals($spremljena, $password)) {
        return false;
      }
      self::prebaciNaHash((int)$row['ID'], $password);
    } elseif (!password_verify($password, $spremljena)) {
      return false;
    } elseif (password_needs_rehash($spremljena, PASSWORD_DEFAULT)) {
      self::prebaciNaHash((int)$row['ID'], $password);
    }

    // Novi ID sesije nakon prijave - sprječava session fixation.
    Session::regenerate();
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
   * Prijava ne smije pasti ako hash ne stane u kolonu (ALTER na
   * VARCHAR2(255) još nije pokrenut) - greška se samo zapiše u log,
   * a lozinka ostaje plaintext do sljedeće prijave.
   */
  private static function prebaciNaHash(int $id, string $password): void
  {
    try {
      ProfesorService::postaviLozinku($id, $password);
    } catch (\Throwable $e) {
      error_log('Auth: lozinka nije prebačena na hash (pokrenuti ALTER?): ' . $e->getMessage());
    }
  }

  /**
   * "Prijava" ucenika preko magic-link tokena (UCENICI.HASH).
   */
  public static function attemptToken(string $token): bool
  {
    $stmt = Db::connect()->prepare(
      'SELECT ID, IDRAZRED, IME, PREZIME, EMAIL, IDCIKLUS
         FROM UCENICI
        WHERE HASH = :hash AND DELETED = 0'
    );
    $stmt->execute(['hash' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
      return false;
    }

    // Novi ID sesije nakon prijave - sprječava session fixation.
    Session::regenerate();
    Session::set(self::SESSION_KEY, [
      'id'        => (int)$row['ID'],
      'ime'       => $row['IME'],
      'prezime'   => $row['PREZIME'],
      'email'     => $row['EMAIL'],
      'razred_id' => (int)$row['IDRAZRED'],
      'idciklus'  => (int)$row['IDCIKLUS'],
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
    Session::regenerate();
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