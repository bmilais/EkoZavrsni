<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CiklusController;
use App\Controllers\RazredController;
use App\Controllers\PredmetController;
use App\Controllers\ProfesorController;
use App\Controllers\UcenikController;
use App\Controllers\TemaController;
use App\Controllers\UvozController;
use App\Controllers\StanjeController;
use App\Controllers\OdabirController;
use App\Controllers\ExportController;

return function (Router $router): void {
  $c = new HomeController();

  $router->get('/',         [$c, 'index']);
  $router->get('/health',   [$c, 'health']);
  $router->get('/db-check', [$c, 'dbCheck']);

  $auth = new AuthController();
  $router->get('/login',   [$auth, 'loginForm']);
  $router->post('/login',  [$auth, 'login']);
  $router->get('/logout',  [$auth, 'logout']);
  $router->get('/prijava', [$auth, 'ucenikToken']);

  $dash = new DashboardController();
  $router->get('/admin',     [$dash, 'admin']);
  $router->get('/nastavnik', [$dash, 'nastavnik']);
  $router->get('/ucenik',    [$dash, 'ucenik']);

  $ciklus = new CiklusController();
  $router->get('/admin/ciklusi',          [$ciklus, 'index']);
  $router->get('/admin/ciklusi/novi',     [$ciklus, 'novi']);
  $router->get('/admin/ciklusi/uredi',    [$ciklus, 'uredi']);
  $router->post('/admin/ciklusi/spremi',  [$ciklus, 'spremi']);
  $router->post('/admin/ciklusi/status',  [$ciklus, 'status']);
  $router->post('/admin/ciklusi/obrisi',  [$ciklus, 'obrisi']);

  $razred = new RazredController();
  $router->get('/admin/razredi',         [$razred, 'index']);
  $router->get('/admin/razredi/novi',    [$razred, 'novi']);
  $router->get('/admin/razredi/uredi',   [$razred, 'uredi']);
  $router->post('/admin/razredi/spremi', [$razred, 'spremi']);
  $router->post('/admin/razredi/obrisi', [$razred, 'obrisi']);

  $predmet = new PredmetController();
  $router->get('/admin/predmeti',          [$predmet, 'index']);
  $router->get('/admin/predmeti/novi',     [$predmet, 'novi']);
  $router->get('/admin/predmeti/uredi',    [$predmet, 'uredi']);
  $router->post('/admin/predmeti/spremi',  [$predmet, 'spremi']);
  $router->post('/admin/predmeti/obrisi',  [$predmet, 'obrisi']);

  $profesor = new ProfesorController();
  $router->get('/admin/profesori',           [$profesor, 'index']);
  $router->get('/admin/profesori/novi',      [$profesor, 'novi']);
  $router->get('/admin/profesori/uredi',     [$profesor, 'uredi']);
  $router->post('/admin/profesori/spremi',   [$profesor, 'spremi']);
  $router->post('/admin/profesori/obrisi',   [$profesor, 'obrisi']);

  $ucenik = new UcenikController();
  $router->get('/admin/ucenici',          [$ucenik, 'index']);
  $router->get('/admin/ucenici/novi',     [$ucenik, 'novi']);
  $router->get('/admin/ucenici/uredi',    [$ucenik, 'uredi']);
  $router->post('/admin/ucenici/spremi',  [$ucenik, 'spremi']);
  $router->post('/admin/ucenici/obrisi',  [$ucenik, 'obrisi']);

  $tema = new TemaController();
  $router->get('/admin/teme',          [$tema, 'index']);
  $router->get('/admin/teme/novi',     [$tema, 'novi']);
  $router->get('/admin/teme/uredi',    [$tema, 'uredi']);
  $router->post('/admin/teme/spremi',  [$tema, 'spremi']);
  $router->post('/admin/teme/obrisi',  [$tema, 'obrisi']);

  $uvoz = new UvozController();
  $router->get('/admin/teme/uvoz',  [$uvoz, 'form']);
  $router->post('/admin/teme/uvoz', [$uvoz, 'uvezi']);

  $stanje = new StanjeController();
  $router->get('/admin/stanje', [$stanje, 'index']);

  $odabir = new OdabirController();
  $router->get('/admin/odabiri',              [$odabir, 'index']);
  $router->get('/admin/odabiri/ponisti',       [$odabir, 'ponistiForma']);
  $router->post('/admin/odabiri/ponisti',      [$odabir, 'ponisti']);

  $export = new ExportController();
  $router->get('/admin/export/odabiri-excel',  [$export, 'odabiriExcel']);
  $router->get('/admin/export/odabiri-pdf',    [$export, 'odabiriPdf']);
};