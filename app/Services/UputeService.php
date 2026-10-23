<?php
declare(strict_types=1);

namespace App\Services;

/**
 * PDF upute za učenike - jedan PDF po ciklusu.
 *
 * Datoteka se sprema na disk: storage/uploads/upute/ciklus-{ID}.pdf
 * (storage/ je zaštićen s .htaccess, pa PDF nije dostupan izravno preko weba).
 * U tablici CIKLUSI pamti se izvorni naziv datoteke (UPUTE_NAZIV) i vrijeme
 * učitavanja (UPUTE_DATUM). Novi upload zamjenjuje stari PDF.
 */
final class UputeService
{
  /** Najveća dopuštena veličina PDF-a (ide kao privitak u svakom mailu). */
  public const MAX_VELICINA = 10 * 1024 * 1024; // 10 MB

  public static function putanja(int $ciklusId): string
  {
    return dirname(__DIR__, 2) . '/storage/uploads/upute/ciklus-' . $ciklusId . '.pdf';
  }

  /**
   * Podaci za panel: ciklus, učitani PDF i stanje slanja po učenicima.
   * Vraća null ako ciklus ne postoji.
   */
  public static function info(int $ciklusId): ?array
  {
    $db = Db::connect();

    $stmt = $db->prepare(
      "SELECT ID, NAZIV, UPUTE_NAZIV,
              TO_CHAR(UPUTE_DATUM, 'YYYY-MM-DD HH24:MI') AS UPUTE_DATUM
         FROM CIKLUSI
        WHERE ID = :id AND DELETED = 0"
    );
    $stmt->execute(['id' => $ciklusId]);
    $ciklus = $stmt->fetch();
    if (!$ciklus) {
      return null;
    }

    $stmt = $db->prepare(
      "SELECT u.ID, u.IME, u.PREZIME, u.EMAIL,
              TO_CHAR(u.UPUTE_POSLANE, 'YYYY-MM-DD HH24:MI') AS UPUTE_POSLANE,
              CASE WHEN u.UPUTE_POSLANE < c.UPUTE_DATUM THEN 1 ELSE 0 END AS STARA_VERZIJA
         FROM UCENICI u
         JOIN CIKLUSI c ON c.ID = u.IDCIKLUS
        WHERE u.IDCIKLUS = :id AND u.DELETED = 0
        ORDER BY u.PREZIME, u.IME"
    );
    $stmt->execute(['id' => $ciklusId]);
    $ucenici = $stmt->fetchAll();

    $putanja = self::putanja($ciklusId);
    $postoji = $ciklus['UPUTE_NAZIV'] !== null && is_file($putanja);

    return [
      'CIKLUS_ID'     => (int)$ciklus['ID'],
      'CIKLUS_NAZIV'  => $ciklus['NAZIV'],
      'POSTOJI'       => $postoji,
      'NAZIV'         => $postoji ? $ciklus['UPUTE_NAZIV'] : null,
      'DATUM'         => $postoji ? $ciklus['UPUTE_DATUM'] : null,
      'VELICINA'      => $postoji ? (int)filesize($putanja) : 0,
      'MAX_VELICINA'  => self::MAX_VELICINA,
      'UKUPNO'        => count($ucenici),
      'POSLANO'       => count(array_filter($ucenici, fn($u) => $u['UPUTE_POSLANE'] !== null)),
      'STARA_VERZIJA' => count(array_filter($ucenici, fn($u) => (int)$u['STARA_VERZIJA'] === 1)),
      'UCENICI'       => $ucenici,
    ];
  }

  /**
   * Sprema uploadani PDF za ciklus (zamjenjuje postojeći).
   * $file je element iz $_FILES. Vraća null ako je uspjelo, inače poruku greške.
   */
  public static function spremi(int $ciklusId, array $file): ?string
  {
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
      return 'Datoteka nije ispravno učitana.';
    }

    $velicina = (int)($file['size'] ?? 0);
    if ($velicina <= 0) {
      return 'Datoteka je prazna.';
    }
    if ($velicina > self::MAX_VELICINA) {
      return 'Datoteka je prevelika (najviše ' . (self::MAX_VELICINA / 1024 / 1024) . ' MB).';
    }

    // Provjera sadržaja, ne samo nastavka: svaki PDF počinje s "%PDF-".
    $fh = fopen($tmp, 'rb');
    $pocetak = $fh ? (string)fread($fh, 5) : '';
    if ($fh) {
      fclose($fh);
    }
    if ($pocetak !== '%PDF-') {
      return 'Datoteka nije PDF.';
    }

    $putanja = self::putanja($ciklusId);
    $dir = dirname($putanja);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
      error_log('[EkoZavrsni] upute: ne mogu stvoriti mapu ' . $dir);
      return 'Ne mogu spremiti datoteku na poslužitelj.';
    }
    if (!move_uploaded_file($tmp, $putanja)) {
      error_log('[EkoZavrsni] upute: move_uploaded_file nije uspio za ' . $putanja);
      return 'Ne mogu spremiti datoteku na poslužitelj.';
    }

    $stmt = Db::connect()->prepare(
      'UPDATE CIKLUSI SET UPUTE_NAZIV = :naziv, UPUTE_DATUM = SYSTIMESTAMP WHERE ID = :id'
    );
    $stmt->execute(['naziv' => self::cistiNaziv((string)($file['name'] ?? '')), 'id' => $ciklusId]);
    Db::commit();

    return null;
  }

  /**
   * Izvorni naziv datoteke, očišćen za spremanje i za naziv privitka:
   * bez putanje i znakova koje Windows/mail klijenti ne vole, najviše 200 znakova.
   */
  public static function cistiNaziv(string $naziv): string
  {
    $naziv = basename(str_replace('\\', '/', $naziv));
    $naziv = trim((string)preg_replace('/[\x00-\x1F\x7F"\/\\\\:*?<>|]+/u', '_', $naziv));
    if (!preg_match('/\.pdf$/i', $naziv)) {
      $naziv .= '.pdf';
    }
    if ($naziv === '.pdf') {
      $naziv = 'upute.pdf';
    }
    if (mb_strlen($naziv) > 200) {
      $naziv = mb_substr($naziv, 0, 196) . '.pdf';
    }
    return $naziv;
  }
}
