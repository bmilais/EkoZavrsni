<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\Db;

final class ExportController extends BaseController
{
  public function odabiriExcel(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $ciklusId = (int)$req->query('ciklus_id', 0);
    $data = $this->dohvatiPodatke($ciklusId > 0 ? $ciklusId : null);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Odabiri');

    $headers = ['ID', 'Učenik', 'Email', 'Tema', 'Predmet', 'Profesor', 'Datum odabira', 'Status', 'Obrazloženje'];
    foreach (range('A', 'I') as $i => $col) {
      $sheet->setCellValueExplicit($col . '1', $headers[$i], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
    $sheet->getStyle('A1:I1')->getFont()->setBold(true);

    $row = 2;
    foreach ($data as $d) {
      $sheet->setCellValueExplicit('A' . $row, (string)$d['OID'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('B' . $row, $d['U_IME'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('C' . $row, $d['U_EMAIL'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('D' . $row, $d['TEMA_NAZIV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('E' . $row, $d['PREDMET_NAZIV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('F' . $row, $d['PROFESOR_NAZIV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('G' . $row, $d['DATUM_ODABIRA'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('H' . $row, $d['STATUS'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('I' . $row, $d['OBRAZLOZENJE'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $row++;
    }

    foreach (range('A', 'I') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
    header('Content-Disposition: attachment; filename="odabiri-' . date('Y-m-d') . '.xlsx"');
    $writer->save('php://output');
    exit;
  }

  public function odabiriPdf(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $ciklusId = (int)$req->query('ciklus_id', 0);
    $data = $this->dohvatiPodatke($ciklusId > 0 ? $ciklusId : null);

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
      body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
      table { border-collapse: collapse; width: 100%; }
      th, td { border: 1px solid #333; padding: 4px; }
      th { background: #eee; }
    </style></head><body>';
    $html .= '<h1 style="font-size:18px">Odabiri tema</h1>';
    $html .= '<p>Datum izvoza: ' . date('d.m.Y H:i') . '</p>';
    $html .= '<table>';
    $html .= '<tr><th>ID</th><th>Učenik</th><th>Tema</th><th>Predmet</th><th>Profesor</th><th>Datum</th><th>Status</th></tr>';
    foreach ($data as $d) {
      $html .= '<tr>';
      $html .= '<td>' . htmlspecialchars((string)$d['OID'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['U_IME'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['TEMA_NAZIV'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['PREDMET_NAZIV'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['PROFESOR_NAZIV'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['DATUM_ODABIRA'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['STATUS'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '</tr>';
    }
    $html .= '</table></body></html>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('odabiri-' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
    exit;
  }

  public function uceniciExcel(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $ciklusId = (int)$req->query('ciklus_id', 0);
    $data = $this->dohvatiUcenike($ciklusId > 0 ? $ciklusId : null);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Učenici');

    // Redoslijed stupaca mora odgovarati onome što očekuje uvoz (UvozController::uveziUcenike):
    // Ime, Prezime, Email, Razred, Smjer — bez ID stupca, kako bi izvoz iz jednog ciklusa
    // mogao izravno poslužiti kao uvoz u drugi ciklus.
    $headers = ['Ime', 'Prezime', 'Email', 'Razred', 'Smjer'];
    foreach (range('A', 'E') as $i => $col) {
      $sheet->setCellValueExplicit($col . '1', $headers[$i], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);

    $row = 2;
    foreach ($data as $d) {
      $sheet->setCellValueExplicit('A' . $row, $d['IME'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('B' . $row, $d['PREZIME'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('C' . $row, $d['EMAIL'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('D' . $row, $d['RAZRED_NAZIV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('E' . $row, $d['SMJER_LABEL'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $row++;
    }

    foreach (range('A', 'E') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ucenici-' . date('Y-m-d') . '.xlsx"');
    $writer->save('php://output');
    exit;
  }

  public function uceniciPdf(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $ciklusId = (int)$req->query('ciklus_id', 0);
    $data = $this->dohvatiUcenike($ciklusId > 0 ? $ciklusId : null);

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
      body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
      table { border-collapse: collapse; width: 100%; }
      th, td { border: 1px solid #333; padding: 4px; }
      th { background: #eee; }
    </style></head><body>';
    $html .= '<h1 style="font-size:18px">Popis učenika</h1>';
    $html .= '<p>Datum izvoza: ' . date('d.m.Y H:i') . '</p>';
    $html .= '<table>';
    $html .= '<tr><th>Ime</th><th>Prezime</th><th>Email</th><th>Razred</th><th>Smjer</th></tr>';
    foreach ($data as $d) {
      $html .= '<tr>';
      $html .= '<td>' . htmlspecialchars($d['IME'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['PREZIME'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['EMAIL'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['RAZRED_NAZIV'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['SMJER_LABEL'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '</tr>';
    }
    $html .= '</table></body></html>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('ucenici-' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
    exit;
  }

  public function temeExcel(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $ciklusId = (int)$req->query('ciklus_id', 0);
    $data = \App\Services\TemaService::list($ciklusId > 0 ? $ciklusId : null);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Teme');

    // Redoslijed stupaca mora odgovarati onome što očekuje uvoz (UvozController::uvezi):
    // Naziv, Predmet, Profesor, Razred — bez ID stupca i bez stupca Ciklus (tema uvijek
    // ide u ciklus čija je stranica uvoza/izvoza trenutno otvorena, ne po vrijednosti iz retka),
    // kako bi izvoz iz jednog ciklusa mogao izravno poslužiti kao uvoz u drugi ciklus.
    $headers = ['Naziv', 'Predmet', 'Profesor', 'Razred'];
    foreach (range('A', 'D') as $i => $col) {
      $sheet->setCellValueExplicit($col . '1', $headers[$i], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
    $sheet->getStyle('A1:D1')->getFont()->setBold(true);

    $row = 2;
    foreach ($data as $d) {
      $sheet->setCellValueExplicit('A' . $row, $d['NAZIV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('B' . $row, $d['PREDMET_NAZIV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('C' . $row, $d['PROFESOR_NAZIV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('D' . $row, $d['RAZRED_NAZIV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $row++;
    }

    foreach (range('A', 'D') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
    header('Content-Disposition: attachment; filename="teme-' . date('Y-m-d') . '.xlsx"');
    $writer->save('php://output');
    exit;
  }

  public function temePdf(Request $req, Response $res): void
  {
    $this->requireRole($res, ['admin']);
    $ciklusId = (int)$req->query('ciklus_id', 0);
    $data = \App\Services\TemaService::list($ciklusId > 0 ? $ciklusId : null);

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
      body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
      table { border-collapse: collapse; width: 100%; }
      th, td { border: 1px solid #333; padding: 4px; }
      th { background: #eee; }
    </style></head><body>';
    $html .= '<h1 style="font-size:18px">Popis tema</h1>';
    $html .= '<p>Datum izvoza: ' . date('d.m.Y H:i') . '</p>';
    $html .= '<table>';
    $html .= '<tr><th>Naziv</th><th>Predmet</th><th>Profesor</th><th>Razred</th></tr>';
    foreach ($data as $d) {
      $html .= '<tr>';
      $html .= '<td>' . htmlspecialchars($d['NAZIV'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['PREDMET_NAZIV'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['PROFESOR_NAZIV'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '<td>' . htmlspecialchars($d['RAZRED_NAZIV'], ENT_QUOTES, 'UTF-8') . '</td>';
      $html .= '</tr>';
    }
    $html .= '</table></body></html>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('teme-' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
    exit;
  }

  private function dohvatiPodatke(?int $ciklusId = null): array
  {
    $db = Db::connect();
    $where = 'o.DELETED = 0';
    $params = [];
    if ($ciklusId !== null) {
      $where .= ' AND t.IDCIKLUSA = :ciklus';
      $params['ciklus'] = $ciklusId;
    }
    $stmt = $db->prepare(
      "SELECT o.ID AS OID, o.STATUS, o.OBRAZLOZENJE, TO_CHAR(o.CREATED, 'YYYY-MM-DD HH24:MI') AS DATUM_ODABIRA,
              u.PREZIME || ' ' || u.IME AS U_IME,
              u.EMAIL AS U_EMAIL,
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
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  private function dohvatiUcenike(?int $ciklusId = null): array
  {
    $ucenici = \App\Services\UcenikService::list($ciklusId);
    foreach ($ucenici as &$u) {
      $u['SMJER_LABEL'] = \App\Services\UcenikService::smjerLabel((int)$u['SMJER']);
    }
    unset($u);
    return $ucenici;
  }
}
