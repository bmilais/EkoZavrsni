<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;
use App\Services\ProfesorService;

final class ProfesorController extends BaseController
{
  public function index(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = (int)$req->query('id', 0);
    if ($id > 0) {
      $profesor = ProfesorService::get($id);
      if (!$profesor) {
        $this->fail($res, 'Profesor nije pronađen.', 404);
        return;
      }
      // Lozinka se nikad ne šalje frontendu. Uređivanje i dalje radi:
      // prazno polje lozinke znači "zadrži postojeću".
      unset($profesor['lozinka']);
      $this->ok($res, ['data' => $profesor]);
      return;
    }

    $profesori = ProfesorService::list();

    foreach ($profesori as &$p) {
      $p['ULOGA_LABEL'] = ProfesorService::ulogaLabel((int)$p['OVLASTI']);
    }
    unset($p);

    $this->ok($res, ['data' => $profesori]);
  }

  public function spremi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id    = $this->intParam($req, 'id');
    $data  = [
      'ime'      => trim((string)$req->input('ime', '')),
      'prezime'  => trim((string)$req->input('prezime', '')),
      'email'    => trim((string)$req->input('email', '')),
      'lozinka'  => trim((string)$req->input('lozinka', '')),
      'ovlasti'  => (string)$req->input('ovlasti', ''),
    ];

    if ($data['ime'] === '' || $data['prezime'] === '') {
      $this->fail($res, 'Ime i prezime su obavezni.');
      return;
    }

    // Admin ne smije sam sebi oduzeti admin ovlasti (ostao bi bez pristupa).
    $self = (int)(Auth::user()['id'] ?? 0);
    if ($id > 0 && $id === $self && (int)$data['ovlasti'] !== 0) {
      $this->fail($res, 'Ne možete sami sebi oduzeti admin ovlasti.');
      return;
    }

    // Prazna lozinka kod uređivanja = zadrži postojeću
    // (ProfesorService::update tada ne dira kolonu LOZINKA).
    if ($id > 0) {
      ProfesorService::update($id, $data);
    } else {
      $id = ProfesorService::create($data);
    }

    $this->ok($res, ['id' => $id]);
  }

  public function obrisi(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $id = $this->intParam($req, 'id');
    if ($id > 0 && $id === (int)(Auth::user()['id'] ?? 0)) {
      $this->fail($res, 'Ne možete obrisati vlastiti račun.');
      return;
    }
    if ($id > 0) {
      ProfesorService::delete($id);
    }

    $this->ok($res);
  }

  public function obrisiVise(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);

    $ids = $this->idsParam($req);
    $self    = (int)(Auth::user()['id'] ?? 0);
    $hadSelf = in_array($self, $ids, true);
    $ids     = array_values(array_filter($ids, fn($i) => $i !== $self));
    if (!$ids) {
      $this->fail($res, $hadSelf ? 'Ne možete obrisati vlastiti račun.' : 'Nije odabran nijedan zapis.');
      return;
    }

    $deleted = ProfesorService::deleteMany($ids);
    $this->ok($res, ['deleted' => $deleted, 'skippedSelf' => $hadSelf]);
  }
}
