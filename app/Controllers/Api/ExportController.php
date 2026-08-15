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
    $data = $this->dohvatiPodatke();

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
    $data = $this->dohvatiPodatke();

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

  private function dohvatiPodatke(): array
  {
    $db = Db::connect();
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
        WHERE o.DELETED = 0
        ORDER BY o.CREATED DESC"
    );
    $stmt->execute();
    return $stmt->fetchAll();
  }
}
