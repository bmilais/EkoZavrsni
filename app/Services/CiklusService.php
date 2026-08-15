<?php
declare(strict_types=1);

namespace App\Services;

final class CiklusService
{
  private const STATUS_ORDER = ['priprema' => 0, 'otvoreno' => 1, 'zakljucano' => 2, 'arhivirano' => 3];

  public static function list(): array
  {
    $stmt = Db::connect()->prepare(
      "SELECT ID, SKOLSKA_GODINA, NAZIV, TO_CHAR(DATUM_OTVARANJA, 'YYYY-MM-DD HH24:MI:SS') AS DATUM_OTVARANJA,
              TO_CHAR(DATUM_ZATVARANJA, 'YYYY-MM-DD HH24:MI:SS') AS DATUM_ZATVARANJA,
              STATUS, MAX_UCENIKA_PO_MENTORU, MAX_TEMA_PO_PREDMETU, UPUTE_PDF_URL, CREATED, UPDATED
         FROM CIKLUSI
        WHERE DELETED = 0
        ORDER BY ID DESC"
    );
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public static function get(int $id): ?array
  {
    $stmt = Db::connect()->prepare(
      "SELECT ID, SKOLSKA_GODINA, NAZIV, TO_CHAR(DATUM_OTVARANJA, 'YYYY-MM-DD HH24:MI:SS') AS DATUM_OTVARANJA,
              TO_CHAR(DATUM_ZATVARANJA, 'YYYY-MM-DD HH24:MI:SS') AS DATUM_ZATVARANJA,
              STATUS, MAX_UCENIKA_PO_MENTORU, MAX_TEMA_PO_PREDMETU, UPUTE_PDF_URL, CREATED, UPDATED
         FROM CIKLUSI
        WHERE ID = :id AND DELETED = 0"
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $db = Db::connect();
    $stmt = $db->prepare(
      'INSERT INTO CIKLUSI (SKOLSKA_GODINA, NAZIV, MAX_UCENIKA_PO_MENTORU, MAX_TEMA_PO_PREDMETU, UPUTE_PDF_URL)
       VALUES (:skolska_godina, :naziv, :max_ucenika, :max_tema, :upute_pdf_url)'
    );
    $stmt->execute([
      'skolska_godina' => $data['skolska_godina'],
      'naziv'          => $data['naziv'],
      'max_ucenika'    => !empty($data['max_ucenika_po_mentoru']) ? (int)$data['max_ucenika_po_mentoru'] : null,
      'max_tema'       => !empty($data['max_tema_po_predmetu']) ? (int)$data['max_tema_po_predmetu'] : null,
      'upute_pdf_url'  => $data['upute_pdf_url'] ?? null,
    ]);
    return Db::lastId('CIKLUSI_ID_SEQ');
  }

  public static function update(int $id, array $data): void
  {
    $stmt = Db::connect()->prepare(
      'UPDATE CIKLUSI
          SET SKOLSKA_GODINA         = :skolska_godina,
              NAZIV                  = :naziv,
              MAX_UCENIKA_PO_MENTORU = :max_ucenika,
              MAX_TEMA_PO_PREDMETU   = :max_tema,
              UPUTE_PDF_URL          = :upute_pdf_url
        WHERE ID = :id AND DELETED = 0'
    );
    $stmt->execute([
      'skolska_godina' => $data['skolska_godina'],
      'naziv'          => $data['naziv'],
      'max_ucenika'    => !empty($data['max_ucenika_po_mentoru']) ? (int)$data['max_ucenika_po_mentoru'] : null,
      'max_tema'       => !empty($data['max_tema_po_predmetu']) ? (int)$data['max_tema_po_predmetu'] : null,
      'upute_pdf_url'  => $data['upute_pdf_url'] ?? null,
      'id'             => $id,
    ]);
  }

  public static function delete(int $id): void
  {
    $stmt = Db::connect()->prepare('UPDATE CIKLUSI SET DELETED = 1 WHERE ID = :id');
    $stmt->execute(['id' => $id]);
  }

  public static function mozePromijenitiStatus(string $trenutni, string $novi): bool
  {
    $trenutniR = self::STATUS_ORDER[$trenutni] ?? null;
    $noviR     = self::STATUS_ORDER[$novi] ?? null;

    if ($trenutniR === null || $noviR === null) return false;
    if ($trenutniR >= $noviR) return false;

    return true;
  }

  public static function promijeniStatus(int $id, string $noviStatus): string
  {
    $ciklus = self::get($id);
    if (!$ciklus) {
      return 'Ciklus nije pronađen.';
    }

    if (!self::mozePromijenitiStatus($ciklus['STATUS'], $noviStatus)) {
      return "Status ne može ići iz '{$ciklus['STATUS']}' u '{$noviStatus}'.";
    }

    $setovi = ['STATUS = :status'];
    $params = ['status' => $noviStatus, 'id' => $id];

    if ($noviStatus === 'otvoreno') {
      $setovi[] = 'DATUM_OTVARANJA = SYSTIMESTAMP';
    } elseif ($noviStatus === 'zakljucano') {
      $setovi[] = 'DATUM_ZATVARANJA = SYSTIMESTAMP';
    }

    $sql = 'UPDATE CIKLUSI SET ' . implode(', ', $setovi) . ' WHERE ID = :id AND DELETED = 0';

    try {
      Db::connect()->prepare($sql)->execute($params);
      return '';
    } catch (\Throwable $e) {
      if (str_contains($e->getMessage(), 'CIKLUSI_JEDAN_OTVOREN_UK')) {
        return 'Već postoji jedan otvoreni ciklus. Zatvorite ga prije otvaranja novog.';
      }
      return 'Greška pri promjeni statusa: ' . $e->getMessage();
    }
  }

  public static function statusLabel(string $status): string
  {
    return match ($status) {
      'priprema'   => 'Priprema',
      'otvoreno'   => 'Otvoreno',
      'zakljucano' => 'Zaključano',
      'arhivirano' => 'Arhivirano',
    };
  }

  public static function moguciStatusi(string $trenutni): array
  {
    $svi = ['priprema', 'otvoreno', 'zakljucano', 'arhivirano'];
    return array_values(array_filter($svi, fn($s) => self::mozePromijenitiStatus($trenutni, $s)));
  }
}
