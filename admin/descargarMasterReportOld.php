<?php
require_once 'src/PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;


include_once('../config/init_db.php');

// Crear hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Order Sheet");

// Simula los datos (reemplázalos con tus resultados reales)
$encabezado = [
    'pais' => 'Haiti',
    'prodmonth' => '2025-Sep',
    'creacion' => '15-Jul-25',
    'fromdata' => 'ITOCHU Corporation',
    'dealer' => 'Automerca',
    'paymentterm' => 'Cash within 7 working days after B/L date',
    'todata' => 'Isuzu Motors America, LLC - Latin America & Caribbean Team',
    'personincharge' => 'Jindai Denawa, Vanessa Cossio',
    'phone' => '+1-786-814-5000',
    'tipoorden' => 'CONTAINER FCA'
];
$sheet->getColumnDimension('A')->setWidth(4);
$sheet->getColumnDimension('B')->setWidth(10);
$sheet->getColumnDimension('C')->setWidth(13);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(30);
$sheet->getColumnDimension('F')->setWidth(10);
$sheet->getColumnDimension('G')->setWidth(10);
$sheet->getColumnDimension('H')->setWidth(4);
$sheet->getColumnDimension('I')->setWidth(13);
$sheet->getColumnDimension('J')->setWidth(10);
$sheet->getColumnDimension('K')->setWidth(13);
$sheet->getColumnDimension('L')->setWidth(13);
$sheet->getColumnDimension('M')->setWidth(20);
$sheet->getColumnDimension('N')->setWidth(20);


$sheet->mergeCells('B1:C1');
$sheet->setCellValue('B1', $encabezado['pais']);
$sheet->getStyle('B1:C1')->getFont()->setSize(16)->setBold(true);
$sheet->getStyle('B1:C1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
$sheet->getStyle('B1:C1')->getAlignment()->setHorizontal('center')->setVertical('center');
/*$sheet->getRowDimension(1)->setRowHeight(30);*/


$sheet->setCellValue('E1', '*** ORDER SHEET ***');
$sheet->getStyle('E1')->getFont()->setSize(16)->setBold(true);

$sheet->setCellValue('C4', 'Production Month :')->setCellValue('D4', $encabezado['prodmonth']);
$sheet->setCellValue('C5', 'Order Placement date :')->setCellValue('D5', $encabezado['creacion']);

$sheet->setCellValue('C7', 'FROM :')->setCellValue('D7', $encabezado['fromdata']);
$sheet->setCellValue('C8', 'Country name :')->setCellValue('D8', $encabezado['pais']);
$sheet->setCellValue('C9', 'Dealer name :')->setCellValue('D9', $encabezado['dealer']);
$sheet->setCellValue('C10', 'Payment term :')->setCellValue('D10', $encabezado['paymentterm']);

$sheet->setCellValue('M4', 'Type of Vessel :')->setCellValue('N4', $encabezado['tipoorden']);
$sheet->setCellValue('M6', 'To :')->setCellValue('N6', $encabezado['todata']);
$sheet->setCellValue('M7', 'Person in charge :')->setCellValue('N7', $encabezado['personincharge']);
$sheet->setCellValue('M8', 'Phone :')->setCellValue('N8', $encabezado['phone']);

// Encabezados de la tabla (fila 14)
$headers = ['Prod.Month', 'Item #', 'Order No.', 'MODEL NAME', 'Model Year', 'EZSPEC CODE', 'Qty', 'UNIT PRICE', 'Color', 'Body/OPT', 'PRICE (USD)', 'Production Allocation Req. (Week)', 'Background of request'];
$sheet->fromArray($headers, null, 'B14');

// Datos de la tabla (simulado, reemplaza con tu query real)
$rows = [
    ['2509', '#NL18#A', 'IT-V332-25B', 'NPR71L-HUSAYN', 26, 'NPR71H22V', 4, 13327.00, 999, 'A/C', 53308.00, '', ''],
    ['2509', '#NL23FD#A', 'IT-V332-25C', 'NPR71L-FLSIADIN', 26, 'NPR71F2BV', 2, 16914.00, 999, 'A/C', 33828.00, '', ''],
    ['2509', '#NL29#A', 'IT-V332-25D', 'NQR71L-KNSYAYN', 26, 'NQR71K2VV', 6, 15354.00, 999, 'A/C', 92124.00, '', ''],
    ['2509', '#QLF1', 'IT-V332-25A', 'QLR77L-EE1AYN', 26, 'QLL03F01', 2, 9023.00, 999, '', 18046.00, '', '']
];

// Escribe los datos
$rowStart = 15;
foreach ($rows as $row) {
    $sheet->fromArray($row, null, 'B' . $rowStart++);
}

// Totales
$sheet->setCellValue('H34', 'Total (Units)');
$sheet->setCellValue('I34', 14);
$sheet->setCellValue('K34', 197306.00);

// Descargar el archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="order_sheet.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;