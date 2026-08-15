<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\CiklusController;
use App\Controllers\Api\RazredController;
use App\Controllers\Api\PredmetController;
use App\Controllers\Api\ProfesorController;
use App\Controllers\Api\UcenikController;
use App\Controllers\Api\TemaController;
use App\Controllers\Api\OdabirController;
use App\Controllers\Api\StanjeController;
use App\Controllers\Api\UvozController;
use App\Controllers\Api\ExportController;
use App\Controllers\Api\UcenikPanelController;
use App\Controllers\Api\NastavnikPanelController;

return function (Router $router): void {
  $auth = new AuthController();
  $router->post('/api/auth/login',   [$auth, 'login']);
  $router->post('/api/auth/prijava', [$auth, 'prijava']);
  $router->get('/api/auth/user',     [$auth, 'user']);
  $router->post('/api/auth/logout',  [$auth, 'logout']);

  $ciklus = new CiklusController();
  $router->get('/api/ciklusi',         [$ciklus, 'index']);
  $router->post('/api/ciklusi/spremi', [$ciklus, 'spremi']);
  $router->post('/api/ciklusi/status', [$ciklus, 'status']);
  $router->post('/api/ciklusi/obrisi', [$ciklus, 'obrisi']);

  $razred = new RazredController();
  $router->get('/api/razredi',         [$razred, 'index']);
  $router->post('/api/razredi/spremi', [$razred, 'spremi']);
  $router->post('/api/razredi/obrisi', [$razred, 'obrisi']);

  $predmet = new PredmetController();
  $router->get('/api/predmeti',         [$predmet, 'index']);
  $router->post('/api/predmeti/spremi', [$predmet, 'spremi']);
  $router->post('/api/predmeti/obrisi', [$predmet, 'obrisi']);

  $profesor = new ProfesorController();
  $router->get('/api/profesori',         [$profesor, 'index']);
  $router->post('/api/profesori/spremi', [$profesor, 'spremi']);
  $router->post('/api/profesori/obrisi', [$profesor, 'obrisi']);

  $ucenik = new UcenikController();
  $router->get('/api/ucenici',             [$ucenik, 'index']);
  $router->post('/api/ucenici/spremi',     [$ucenik, 'spremi']);
  $router->post('/api/ucenici/obrisi',     [$ucenik, 'obrisi']);
  $router->post('/api/ucenici/posalji-link', [$ucenik, 'posaljiLink']);

  $uvoz = new UvozController();
  $router->post('/api/teme/uvoz',    [$uvoz, 'uvezi']);
  $router->post('/api/ucenici/uvoz', [$uvoz, 'uveziUcenike']);

  $tema = new TemaController();
  $router->get('/api/teme',         [$tema, 'index']);
  $router->get('/api/teme/opcije',  [$tema, 'opcije']);
  $router->post('/api/teme/spremi', [$tema, 'spremi']);
  $router->post('/api/teme/obrisi', [$tema, 'obrisi']);

  $odabir = new OdabirController();
  $router->get('/api/odabiri',          [$odabir, 'index']);
  $router->post('/api/odabiri/ponisti', [$odabir, 'ponisti']);

  $stanje = new StanjeController();
  $router->get('/api/stanje', [$stanje, 'index']);

  $export = new ExportController();
  $router->get('/api/export/odabiri-excel', [$export, 'odabiriExcel']);
  $router->get('/api/export/odabiri-pdf',   [$export, 'odabiriPdf']);

  $panel = new UcenikPanelController();
  $router->get('/api/ucenik/teme',    [$panel, 'dostupne']);
  $router->get('/api/ucenik/moji',    [$panel, 'moji']);
  $router->post('/api/ucenik/odaberi', [$panel, 'odaberi']);
  $router->post('/api/ucenik/ponisti', [$panel, 'ponisti']);

  $nastavnik = new NastavnikPanelController();
  $router->get('/api/nastavnik/teme',    [$nastavnik, 'teme']);
  $router->get('/api/nastavnik/odabiri', [$nastavnik, 'odabiri']);
};
