<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\Db;

final class OdabirController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $db = Db::connect();

    $filter = (string)($req->query('filter', 'aktivni') ?? 'aktivni');
    $where  = 'o.DELETED = 0';
    if ($filter === 'aktivni') {
      $where .= " AND o.STATUS = 'aktivan'";
    } elseif ($filter === 'ponisteni') {
      $where .= " AND o.STATUS = 'ponisten'";
    }

    $stmt = $db->prepare(
      "SELECT o.ID AS OID, o.STATUS, o.OBRAZLOZENJE,
              TO_CHAR(o.CREATED, 'YYYY-MM-DD HH24:MI') AS DATUM_ODABIRA,
              u.IME AS U_IME, u.PREZIME AS U_PREZIME, u.EMAIL AS U_EMAIL,
              t.NAZIV AS TEMA_NAZIV,
              p.NAZIV AS PREDMET_NAZIV,
              pr.PREZIME || ' ' || pr.IME AS PROFESOR_NAZIV
         FROM ODABIRI o
         JOIN UCENICI u  ON u.ID = o.IDUCENIKA AND u.DELETED = 0
         JOIN TEME t     ON t.ID = o.IDTEME AND t.DELETED = 0
         JOIN PREDMETI p ON p.ID = t.IDPREDMETA AND p.DELETED = 0
         JOIN PROFESORI pr ON pr.ID = t.IDPROFESORA AND pr.DELETED = 0
        WHERE {$where}
        ORDER BY o.CREATED DESC"
    );
    $stmt->execute();
    $this->ok($res, ['data' => $stmt->fetchAll()]);
  }

  public function ponisti(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id           = $this->intParam($req, 'id');
    $obrazlozenje = trim((string)$req->input('obrazlozenje', ''));

    if ($id <= 0 || $obrazlozenje === '') {
      $this->fail($res, 'Obrazloženje je obavezno.');
      return;
    }

    $user = Auth::user();

    $stmt = Db::connect()->prepare(
      "UPDATE ODABIRI
          SET STATUS = 'ponisten',
              OBRAZLOZENJE = :obrazlozenje,
              PONISTIO_ID  = :ponistio,
              DATUM_PONISTENJA = SYSTIMESTAMP
        WHERE ID = :id AND DELETED = 0 AND STATUS = 'aktivan'"
    );
    $stmt->execute([
      'obrazlozenje' => $obrazlozenje,
      'ponistio'     => $user['id'],
      'id'           => $id,
    ]);

    $this->ok($res);
  }
}
