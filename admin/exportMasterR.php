<?php
@session_start();
require_once 'src/PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

include_once('../config/init_db.php');

// Tomar fechas del formulario
$startDate = $_POST['txtstartdate'] ?? null;
$endDate   = $_POST['txtenddate'] ?? null;
if (empty($startDate) || empty($endDate)) {
    http_response_code(400); // Bad Request
    die("Error: You must select both Start Date and End Date.");
}

if (!empty($_SESSION['UsrData']['rolids'])) {

// Generar nombre del archivo
/*$startDate = str_replace('-','',$startDate);
$endDate = str_replace('-','',$endDate);*/
$startDateFmt   = (new DateTime($startDate))->format('mdY');
$endDateFmt     = (new DateTime($endDate))->format('mdY');
$today          = date('mdY'); // fecha generación
$download       = date('Y-m-d');
$filename       = "MasterReport_{$startDateFmt}_{$endDateFmt}_{$today}.xlsx";

    $sqlDet = "SELECT o.codigo, p.pais, s.dealer, o.creacion, a.tipoorden, eo.estadoorden, os.puerto, s.customercode, c.mesano, d.item, d.noorden, d.modelo, d.anomodelo, d.ezspeccode, d.cantidad, i.poe, i.tire, i.unitpricecif, r.color,
                i.colorcode1,i.colorcode2,i.colorcode3,i.colorcode4,i.colorcode5,i.colorcode6,i.colorcode7,i.colorcode8,i.colorcode9,i.colorcode10,i.colorcode11,i.colorcode12,i.colorcode13,i.colorcode14,i.colorcode15,i.colorcode16,i.colorcode17,i.colorcode18,i.colorcode19,
                i.optioncode1,i.optioncode2,i.optioncode3,i.optioncode4,i.optioncode5,i.optioncode6,i.optioncode7,i.optioncode8,i.optioncode9,i.optioncode10,i.optioncode11,i.optioncode12,i.optioncode13,i.optioncode14,i.optioncode15,i.optioncode16,i.optioncode17,i.optioncode18,i.optioncode19,i.optioncode20,i.optioncode21,i.optioncode22,i.optioncode23,i.optioncode24,i.optioncode25,i.optioncode26,i.optioncode27,i.optioncode28,i.optioncode29,i.optioncode30,
                i.bodybuildcode1,i.bodybuildname1,i.bodybuildcompany1,i.bodybuilddraw1,i.bodybuildout1,
                i.bodybuildcode2,i.bodybuildname2,i.bodybuildcompany2,i.bodybuilddraw2,i.bodybuildout2,
                i.bodybuildcode3,i.bodybuildname3,i.bodybuildcompany3,i.bodybuilddraw3,i.bodybuildout3,
                i.remarks, d.preciodolar, d.preciocif,
                i.vlr1,i.vlr2,i.vlr3,i.vlr4,i.vlr5,i.vlr6
                FROM ots_ordenes o, ots_ordendetalles d, ots_calendarios c, ots_colores r, ots_dealers s, ots_items i, ots_paises p, ots_tipoordenes a, ots_estadosorden eo, ots_puertos os
                WHERE o.creacion BETWEEN '".$startDate." 00:00:00' AND '".$endDate." 23:59:59' AND o.ordenid = d.ordenid AND o.calendarioid = c.calendarioid AND d.color = r.colorid AND o.dealerid = s.dealerid AND d.itemid = i.itemid AND s.paisid = p.paisid AND o.tipoordenid = a.tipoordenid AND o.estadoordenid = eo.estadoordenid AND eo.estadoordenid < 8 AND o.puertoid = os.puertoid ";
        if (userHasRole('2')) {
            $sqlDet .= " AND o.dealerid = ".$_SESSION['UsrData']['dealerid'];
        }
        $sqlDet .= " ORDER BY o.codigo, d.noorden, d.item ";

    $listadoItemsDet = DB::query($sqlDet);

    $QSeries = 0;
    $NSeries = 0;
    $FSeries = 0;
    $PSeries = 0;

    foreach ($listadoItemsDet as $itemCant) {
        if (str_starts_with(strtoupper($itemCant['modelo']), 'Q')) {
            $QSeries = $QSeries+$itemCant['cantidad'];
        }
        if (str_starts_with(strtoupper($itemCant['modelo']), 'N')) {
            $NSeries = $NSeries+$itemCant['cantidad'];
        }
        if (str_starts_with(strtoupper($itemCant['modelo']), 'F')) {
            $FSeries = $FSeries+$itemCant['cantidad'];
        }
        if (str_starts_with(strtoupper($itemCant['modelo']), 'P')) {
            $PSeries = $PSeries+$itemCant['cantidad'];
        }
    }

    $spreadsheet = new Spreadsheet();
    $newSheet = $spreadsheet->getActiveSheet();
    $newSheet->setTitle('Time Period Sheet');
    
    /* Tamaño columnas asociadas */
        $newSheet->getColumnDimension('A')->setWidth(15);
        $newSheet->getColumnDimension('B')->setWidth(20);
        $newSheet->getColumnDimension('C')->setWidth(30);
        $newSheet->getColumnDimension('D')->setWidth(15);
        $newSheet->getColumnDimension('E')->setWidth(25);
        $newSheet->getColumnDimension('F')->setWidth(10);
        $newSheet->getColumnDimension('G')->setWidth(25);
        $newSheet->getColumnDimension('H')->setWidth(25);
        $newSheet->getColumnDimension('I')->setWidth(25);
        $newSheet->getColumnDimension('J')->setWidth(25);
        $newSheet->getColumnDimension('K')->setWidth(25);
        $newSheet->getColumnDimension('L')->setWidth(25);
        $newSheet->getColumnDimension('M')->setWidth(25);
        $newSheet->getColumnDimension('N')->setWidth(25);
        $newSheet->getColumnDimension('O')->setWidth(25);
        $newSheet->getColumnDimension('P')->setWidth(25);
        $newSheet->getColumnDimension('Q')->setWidth(25);
        //R se controla
        $newSheet->getColumnDimension('S')->setWidth(20);
        $newSheet->getColumnDimension('T')->setWidth(10);
        $newSheet->getColumnDimension('U')->setWidth(10);
        $newSheet->getColumnDimension('V')->setWidth(10);
        $newSheet->getColumnDimension('W')->setWidth(10);
        $newSheet->getColumnDimension('X')->setWidth(10);
        $newSheet->getColumnDimension('Y')->setWidth(10);
        $newSheet->getColumnDimension('Z')->setWidth(10);
        $newSheet->getColumnDimension('AA')->setWidth(10);
        $newSheet->getColumnDimension('AB')->setWidth(10);
        $newSheet->getColumnDimension('AC')->setWidth(10);
        $newSheet->getColumnDimension('AD')->setWidth(10);
        $newSheet->getColumnDimension('AE')->setWidth(10);
        $newSheet->getColumnDimension('AF')->setWidth(10);
        $newSheet->getColumnDimension('AG')->setWidth(10);
        $newSheet->getColumnDimension('AH')->setWidth(10);
        $newSheet->getColumnDimension('AI')->setWidth(10);
        $newSheet->getColumnDimension('AJ')->setWidth(10);
        $newSheet->getColumnDimension('AK')->setWidth(10);
        $newSheet->getColumnDimension('AL')->setWidth(10);
        $newSheet->getColumnDimension('AM')->setWidth(10);
        $newSheet->getColumnDimension('AN')->setWidth(10);
        $newSheet->getColumnDimension('AO')->setWidth(10);
        $newSheet->getColumnDimension('AP')->setWidth(10);
        $newSheet->getColumnDimension('AQ')->setWidth(10);
        $newSheet->getColumnDimension('AR')->setWidth(10);
        $newSheet->getColumnDimension('AS')->setWidth(10);
        $newSheet->getColumnDimension('AT')->setWidth(10);
        $newSheet->getColumnDimension('AU')->setWidth(10);
        $newSheet->getColumnDimension('AV')->setWidth(10);
        $newSheet->getColumnDimension('AW')->setWidth(10);
        $newSheet->getColumnDimension('AX')->setWidth(10);
        $newSheet->getColumnDimension('AY')->setWidth(10);
        $newSheet->getColumnDimension('AZ')->setWidth(10);
        $newSheet->getColumnDimension('BA')->setWidth(10);
        $newSheet->getColumnDimension('BB')->setWidth(10);
        $newSheet->getColumnDimension('BC')->setWidth(10);
        $newSheet->getColumnDimension('BD')->setWidth(10);
        $newSheet->getColumnDimension('BE')->setWidth(10);
        $newSheet->getColumnDimension('BF')->setWidth(10);
        $newSheet->getColumnDimension('BG')->setWidth(10);
        $newSheet->getColumnDimension('BH')->setWidth(10);
        $newSheet->getColumnDimension('BI')->setWidth(10);
        $newSheet->getColumnDimension('BJ')->setWidth(10);
        $newSheet->getColumnDimension('BK')->setWidth(10);
        $newSheet->getColumnDimension('BL')->setWidth(10);
        $newSheet->getColumnDimension('BM')->setWidth(10);
        $newSheet->getColumnDimension('BN')->setWidth(10);
        $newSheet->getColumnDimension('BO')->setWidth(10);
        $newSheet->getColumnDimension('BP')->setWidth(10);
        $newSheet->getColumnDimension('BQ')->setWidth(15);
        $newSheet->getColumnDimension('BR')->setWidth(15);
        $newSheet->getColumnDimension('BS')->setWidth(15);
        $newSheet->getColumnDimension('BT')->setWidth(15);
        $newSheet->getColumnDimension('BU')->setWidth(15);
        $newSheet->getColumnDimension('BV')->setWidth(15);
        $newSheet->getColumnDimension('BW')->setWidth(15);
        $newSheet->getColumnDimension('BX')->setWidth(15);
        $newSheet->getColumnDimension('BY')->setWidth(15);
        $newSheet->getColumnDimension('BZ')->setWidth(15);
        $newSheet->getColumnDimension('CA')->setWidth(15);
        $newSheet->getColumnDimension('CB')->setWidth(15);
        $newSheet->getColumnDimension('CC')->setWidth(15);
        $newSheet->getColumnDimension('CD')->setWidth(15);
        $newSheet->getColumnDimension('CE')->setWidth(15);
        $newSheet->getColumnDimension('CF')->setWidth(50);
        //CG se controla
        $newSheet->getColumnDimension('CH')->setWidth(30);
        
        
    /* Tamaño columnas asociadas */
    if (userHasRole('2')) {
        $newSheet->getColumnDimension('R')->setWidth(0);
    $newSheet->getColumnDimension('CG')->setWidth(0);
    $newSheet->getColumnDimension('CI')->setWidth(0);
    $newSheet->getColumnDimension('CJ')->setWidth(0);
    $newSheet->getColumnDimension('CK')->setWidth(0);
    $newSheet->getColumnDimension('CL')->setWidth(0);
    $newSheet->getColumnDimension('CM')->setWidth(0);
    $newSheet->getColumnDimension('CN')->setWidth(0);
    $headersRow1 = [
        ['A4:A5', 'ORDER #'],
        ['B4:B5', 'COUNTRY'],
        ['C4:C5', 'DEALER'],
        ['D4:D5', 'ORDER PLACE DATE'],
        ['E4:E5', 'TYPE OF ORDER'],
        ['F4:F5', 'ORDER STATUS'],
        ['G4:G5', 'PORT OF DISCHARGE'],
        ['H4:H5', 'CUSTOMER CODE'],
        ['I4:I5', 'PROD.MONTH (YYMM)'],
        ['J4:J5', 'ITEM#'],
        ['K4:K5', 'ORDER NO.'],
        ['L4:L5', 'MODEL NAME'],
        ['M4:M5', 'SALES YEAR'],
        ['N4:N5', 'EZSPEC CODE'],
        ['O4:O5', 'TOTAL UNITS'],
        ['P4:P5', 'POE'],
        ['Q4:Q5', 'TIRE'],
        

        ['S4:V4','COLOR CODE(1)'],
        ['W4:Z4','COLOR CODE(2)'],
        ['AA4:AD4','COLOR CODE(3)'],
        ['AE4:AH4','COLOR CODE(4)'],
        ['AI4:AL4','COLOR CODE(5)'],
        ['AM4:BP4','OPTION CODE'],
        ['BQ4:BU4','BD.BUILD(1)'],
        ['BV4:BZ4','BD.BUILD(2)'],
        ['CA4:CE4','BD.BUILD(3)'],

        ['CF4:CF5', "REMARKS　(ILAC→ Selling Price)"],
        
        ['CH4:CH5', "UNIT PRICE ILAC⇒Selling Price (USD)"],
        ['CI4:CI5', " "],
        ['CJ4:CJ5', " "],
        ['CK4:CK5', " "],
        ['CL4:CL5', " "],
        ['CM4:CM5', " "],
        ['CN4:CN5', " "],
    ];
}else{
    $newSheet->getColumnDimension('R')->setWidth(25);
    $newSheet->getColumnDimension('CG')->setWidth(30);
    $newSheet->getColumnDimension('CI')->setWidth(30);
    $newSheet->getColumnDimension('CJ')->setWidth(30);
    $newSheet->getColumnDimension('CK')->setWidth(30);
    $newSheet->getColumnDimension('CL')->setWidth(30);
    $newSheet->getColumnDimension('CM')->setWidth(30);
    $newSheet->getColumnDimension('CN')->setWidth(30);
$headersRow1 = [
        ['A4:A5', 'ORDER #'],
        ['B4:B5', 'COUNTRY'],
        ['C4:C5', 'DEALER'],
        ['D4:D5', 'ORDER PLACE DATE'],
        ['E4:E5', 'TYPE OF ORDER'],
        ['F4:F5', 'ORDER STATUS'],
        ['G4:G5', 'PORT OF DISCHARGE'],
        ['H4:H5', 'CUSTOMER CODE'],
        ['I4:I5', 'PROD.MONTH (YYMM)'],
        ['J4:J5', 'ITEM#'],
        ['K4:K5', 'ORDER NO.'],
        ['L4:L5', 'MODEL NAME'],
        ['M4:M5', 'SALES YEAR'],
        ['N4:N5', 'EZSPEC CODE'],
        ['O4:O5', 'TOTAL UNITS'],
        ['P4:P5', 'POE'],
        ['Q4:Q5', 'TIRE'],
        ['R4:R5', "UNIT PRICE\nISZ→ILAC\nSelling Price\n(USD)"],

        ['S4:V4','COLOR CODE(1)'],
        ['W4:Z4','COLOR CODE(2)'],
        ['AA4:AD4','COLOR CODE(3)'],
        ['AE4:AH4','COLOR CODE(4)'],
        ['AI4:AL4','COLOR CODE(5)'],
        ['AM4:BP4','OPTION CODE'],
        ['BQ4:BU4','BD.BUILD(1)'],
        ['BV4:BZ4','BD.BUILD(2)'],
        ['CA4:CE4','BD.BUILD(3)'],

        ['CF4:CF5', "REMARKS　(ILAC→ Selling Price)"],
        ['CG4:CG5', "UNIT PRICE ISZJ⇒ILAC Selling Price (USD)"],
        ['CH4:CH5', "UNIT PRICE ILAC⇒Selling Price (USD)"],
        ['CI4:CI5', " "],
        ['CJ4:CJ5', " "],
        ['CK4:CK5', " "],
        ['CL4:CL5', " "],
        ['CM4:CM5', " "],
        ['CN4:CN5', " "],
    ];
}

    $headersRow1 = [
        ['A4:A5', 'ORDER #'],
        ['B4:B5', 'COUNTRY'],
        ['C4:C5', 'DEALER'],
        ['D4:D5', 'ORDER PLACE DATE'],
        ['E4:E5', 'TYPE OF ORDER'],
        ['F4:F5', 'ORDER STATUS'],
        ['G4:G5', 'PORT OF DISCHARGE'],
        ['H4:H5', 'CUSTOMER CODE'],
        ['I4:I5', 'PROD.MONTH (YYMM)'],
        ['J4:J5', 'ITEM#'],
        ['K4:K5', 'ORDER NO.'],
        ['L4:L5', 'MODEL NAME'],
        ['M4:M5', 'SALES YEAR'],
        ['N4:N5', 'EZSPEC CODE'],
        ['O4:O5', 'TOTAL UNITS'],
        ['P4:P5', 'POE'],
        ['Q4:Q5', 'TIRE'],
        ['R4:R5', "UNIT PRICE\nISZ→ILAC\nSelling Price\n(USD)"],

        ['S4:V4','COLOR CODE(1)'],
        ['W4:Z4','COLOR CODE(2)'],
        ['AA4:AD4','COLOR CODE(3)'],
        ['AE4:AH4','COLOR CODE(4)'],
        ['AI4:AL4','COLOR CODE(5)'],
        ['AM4:BP4','OPTION CODE'],
        ['BQ4:BU4','BD.BUILD(1)'],
        ['BV4:BZ4','BD.BUILD(2)'],
        ['CA4:CE4','BD.BUILD(3)'],

        ['CF4:CF5', "REMARKS　(ILAC→ Selling Price)"],
        ['CG4:CG5', "UNIT PRICE ISZJ⇒ILAC Selling Price (USD)"],
        ['CH4:CH5', "UNIT PRICE ILAC⇒Selling Price (USD)"],
        ['CI4:CI5', " "],
        ['CJ4:CJ5', " "],
        ['CK4:CK5', " "],
        ['CL4:CL5', " "],
        ['CM4:CM5', " "],
        ['CN4:CN5', " "],
    ];
    foreach ($headersRow1 as [$range, $text]) {
        $newSheet->mergeCells($range);
        $newSheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_DOUBLE);
        $newSheet->getStyle($range)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $newSheet->setCellValue(explode(':', $range)[0], $text);
        $newSheet->getStyle($range)->getFont()->setBold(true);
        $newSheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFBFBFBF');
        $newSheet->getStyle($range)->getFont()->getColor()->setARGB('FF404040');
    }
    $Sencillas = [
        ['A2','Starting Date:'],
        ['B2',$startDate],
        ['C2','Ending Date:'],
        ['D2',$endDate],
        ['E2','Date of Download:'],
        ['F2',$download],
        
        ['K1','Q-SERIES'],
        ['L1','N-SERIES'],
        ['M1','F-SERIES'],
        ['N1','P-SERIES'],

        ['K2',$QSeries],
        ['L2',$NSeries],
        ['M2',$FSeries],
        ['N2',$PSeries],

        ['S5','BODY'],
        ['T5','INT'],
        ['U5','EXT'],
        ['V5','UNIT'],
        ['W5','BODY'],
        ['X5','INT'],
        ['Y5','EXT'],
        ['Z5','UNIT'],
        ['AA5','BODY'],
        ['AB5','INT'],
        ['AC5','EXT'],
        ['AD5','UNIT'],
        ['AE5','BODY'],
        ['AF5','INT'],
        ['AG5','EXT'],
        ['AH5','UNIT'],
        ['AI5','BODY'],
        ['AJ5','INT'],
        ['AK5','EXT'],
        ['AL5','UNIT'],

        ['AM5','(1)'],
        ['AN5','(2)'],
        ['AO5','(3)'],
        ['AP5','(4)'],
        ['AQ5','(5)'],
        ['AR5','(6)'],
        ['AS5','(7)'],
        ['AT5','(8)'],
        ['AU5','(9)'],
        ['AV5','(10)'],
        ['AW5','(11)'],
        ['AX5','(12)'],
        ['AY5','(13)'],
        ['AZ5','(14)'],
        ['BA5','(15)'],
        ['BB5','(16)'],
        ['BC5','(17)'],
        ['BD5','(18)'],
        ['BE5','(19)'],
        ['BF5','(20)'],
        ['BG5','(21)'],
        ['BH5','(22)'],
        ['BI5','(23)'],
        ['BJ5','(24)'],
        ['BK5','(25)'],
        ['BL5','(26)'],
        ['BM5','(27)'],
        ['BN5','(28)'],
        ['BO5','(29)'],
        ['BP5','(30)'],

        ['BQ5','BD.BUILD CODE'],
        ['BR5','BD.BUILD NAME'],
        ['BS5','PR COMPANY'],
        ['BT5','DRAWING NO.'],
        ['BU5','OUTLINE OF BD.BUILD'],

        ['BV5','BD.BUILD CODE'],
        ['BW5','BD.BUILD NAME'],
        ['BX5','PR COMPANY'],
        ['BY5','DRAWING NO.'],
        ['BZ5','OUTLINE OF BD.BUILD'],

        ['CA5','BD.BUILD CODE'],
        ['CB5','BD.BUILD NAME'],
        ['CC5','PR COMPANY'],
        ['CD5','DRAWING NO.'],
        ['CE5','OUTLINE OF BD.BUILD']
    ];
    foreach ($Sencillas as [$range, $text]) {
        $newSheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        $newSheet->setCellValue(explode(':', $range)[0], $text);
        $newSheet->getStyle($range)->getFont()->setBold(true);
        if (in_array($range, ['A2','B2','C2','D2','E2','F2'])) {
            $newSheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $newSheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF008000');
            $newSheet->getStyle($range)->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
            if (in_array($range, ['B2','D2','F2'])) {
                $newSheet->getStyle($range)->getAlignment()->setHorizontal('right')->setVertical('center')->setWrapText(true);
            }else{
                $newSheet->getStyle($range)->getAlignment()->setHorizontal('left')->setVertical('center')->setWrapText(true);
            }
        }
        if (in_array($range, ['K1','L1','M1','N1'])) {
            $newSheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $newSheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');
            //$newSheet->getStyle($range)->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
            $newSheet->getStyle($range)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        }else if (in_array($range, ['K2','L2','M2','N2'])) {
            $newSheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $newSheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCFFCC');
            $newSheet->getStyle($range)->getFont()->getColor()->setARGB('FF008000');
            $newSheet->getStyle($range)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        }else{
            $newSheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_DOUBLE);
            $newSheet->getStyle($range)->getFont()->setBold(true);
            $newSheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFBFBFBF');
            $newSheet->getStyle($range)->getFont()->getColor()->setARGB('FF404040');
        }
    }
    $rowNum = 6;
    foreach ($listadoItemsDet as $item) {
        $newSheet->setCellValue('A'.$rowNum, $item['codigo']);
        $newSheet->setCellValue('B'.$rowNum, $item['pais']);
        $newSheet->setCellValue('C'.$rowNum, $item['dealer']);
        $newSheet->setCellValue('D'.$rowNum, substr($item['creacion'],0,10));
        $newSheet->setCellValue('E'.$rowNum, $item['tipoorden']);
        $newSheet->setCellValue('F'.$rowNum, $item['estadoorden']);
        $newSheet->setCellValue('G'.$rowNum, $item['puerto']);

        $newSheet->setCellValue('H'.$rowNum, $item['customercode']);
        $newSheet->setCellValue('I'.$rowNum, $item['mesano']);
        $newSheet->setCellValue('J'.$rowNum, $item['item']);
        $newSheet->setCellValue('K'.$rowNum, $item['noorden']);
        $newSheet->setCellValue('L'.$rowNum, $item['modelo']);
        $newSheet->setCellValue('M'.$rowNum, $item['anomodelo']);
        $newSheet->setCellValue('N'.$rowNum, $item['ezspeccode']);
        $newSheet->setCellValue('O'.$rowNum, $item['cantidad']);
        $newSheet->setCellValue('P'.$rowNum, $item['poe']);
        $newSheet->setCellValue('Q'.$rowNum, $item['tire']);
        if (userHasAnyRole(['4', '1'])) {
            $newSheet->setCellValue('R'.$rowNum, $item['preciodolar']);
            $newSheet->getStyle('R'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        }

        $newSheet->setCellValue('S'.$rowNum, $item['color']);
        $newSheet->setCellValue('T'.$rowNum, $item['colorcode1']);
        $newSheet->setCellValue('U'.$rowNum, $item['colorcode2']);
        $newSheet->setCellValue('V'.$rowNum, $item['colorcode3']);
        $newSheet->setCellValue('W'.$rowNum, $item['colorcode4']);
        $newSheet->setCellValue('X'.$rowNum, $item['colorcode5']);
        $newSheet->setCellValue('Y'.$rowNum, $item['colorcode6']);
        $newSheet->setCellValue('Z'.$rowNum, $item['colorcode7']);
        $newSheet->setCellValue('AA'.$rowNum, $item['colorcode8']);
        $newSheet->setCellValue('AB'.$rowNum, $item['colorcode9']);
        $newSheet->setCellValue('AC'.$rowNum, $item['colorcode10']);
        $newSheet->setCellValue('AD'.$rowNum, $item['colorcode11']);
        $newSheet->setCellValue('AE'.$rowNum, $item['colorcode12']);
        $newSheet->setCellValue('AF'.$rowNum, $item['colorcode13']);
        $newSheet->setCellValue('AG'.$rowNum, $item['colorcode14']);
        $newSheet->setCellValue('AH'.$rowNum, $item['colorcode15']);
        $newSheet->setCellValue('AI'.$rowNum, $item['colorcode16']);
        $newSheet->setCellValue('AJ'.$rowNum, $item['colorcode17']);
        $newSheet->setCellValue('AK'.$rowNum, $item['colorcode18']);
        $newSheet->setCellValue('AL'.$rowNum, $item['colorcode19']);

        $newSheet->setCellValue('AM'.$rowNum, $item['optioncode1']);
        $newSheet->setCellValue('AN'.$rowNum, $item['optioncode2']);
        $newSheet->setCellValue('AO'.$rowNum, $item['optioncode3']);
        $newSheet->setCellValue('AP'.$rowNum, $item['optioncode4']);
        $newSheet->setCellValue('AQ'.$rowNum, $item['optioncode5']);
        $newSheet->setCellValue('AR'.$rowNum, $item['optioncode6']);
        $newSheet->setCellValue('AS'.$rowNum, $item['optioncode7']);
        $newSheet->setCellValue('AT'.$rowNum, $item['optioncode8']);
        $newSheet->setCellValue('AU'.$rowNum, $item['optioncode9']);
        $newSheet->setCellValue('AV'.$rowNum, $item['optioncode10']);
        $newSheet->setCellValue('AW'.$rowNum, $item['optioncode11']);
        $newSheet->setCellValue('AX'.$rowNum, $item['optioncode12']);
        $newSheet->setCellValue('AY'.$rowNum, $item['optioncode13']);
        $newSheet->setCellValue('AZ'.$rowNum, $item['optioncode14']);
        $newSheet->setCellValue('BA'.$rowNum, $item['optioncode15']);
        $newSheet->setCellValue('BB'.$rowNum, $item['optioncode16']);
        $newSheet->setCellValue('BC'.$rowNum, $item['optioncode17']);
        $newSheet->setCellValue('BD'.$rowNum, $item['optioncode18']);
        $newSheet->setCellValue('BE'.$rowNum, $item['optioncode19']);
        $newSheet->setCellValue('BF'.$rowNum, $item['optioncode20']);
        $newSheet->setCellValue('BG'.$rowNum, $item['optioncode21']);
        $newSheet->setCellValue('BH'.$rowNum, $item['optioncode22']);
        $newSheet->setCellValue('BI'.$rowNum, $item['optioncode23']);
        $newSheet->setCellValue('BJ'.$rowNum, $item['optioncode24']);
        $newSheet->setCellValue('BK'.$rowNum, $item['optioncode25']);
        $newSheet->setCellValue('BL'.$rowNum, $item['optioncode26']);
        $newSheet->setCellValue('BM'.$rowNum, $item['optioncode27']);
        $newSheet->setCellValue('BN'.$rowNum, $item['optioncode28']);
        $newSheet->setCellValue('BO'.$rowNum, $item['optioncode29']);
        $newSheet->setCellValue('BP'.$rowNum, $item['optioncode30']);

        $newSheet->setCellValue('BQ'.$rowNum, $item['bodybuildcode1']);
        $newSheet->setCellValue('BR'.$rowNum, $item['bodybuildname1']);
        $newSheet->setCellValue('BS'.$rowNum, $item['bodybuildcompany1']);
        $newSheet->setCellValue('BT'.$rowNum, $item['bodybuilddraw1']);
        $newSheet->setCellValue('BU'.$rowNum, $item['bodybuildout1']);

        $newSheet->setCellValue('BV'.$rowNum, $item['bodybuildcode2']);
        $newSheet->setCellValue('BW'.$rowNum, $item['bodybuildname2']);
        $newSheet->setCellValue('BX'.$rowNum, $item['bodybuildcompany2']);
        $newSheet->setCellValue('BY'.$rowNum, $item['bodybuilddraw2']);
        $newSheet->setCellValue('BZ'.$rowNum, $item['bodybuildout2']);

        $newSheet->setCellValue('CA'.$rowNum, $item['bodybuildcode3']);
        $newSheet->setCellValue('CB'.$rowNum, $item['bodybuildname3']);
        $newSheet->setCellValue('CC'.$rowNum, $item['bodybuildcompany3']);
        $newSheet->setCellValue('CD'.$rowNum, $item['bodybuilddraw3']);
        $newSheet->setCellValue('CE'.$rowNum, $item['bodybuildout3']);

        $newSheet->setCellValue('CF'.$rowNum, $item['remarks']);
        
        if (userHasAnyRole(['4', '1'])) {
            $newSheet->setCellValue('CG'.$rowNum, $item['preciodolar']);
            $newSheet->getStyle('CG'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');

            if($item['vlr1']!=""){
                $newSheet->setCellValue('CI'.$rowNum, $item['vlr1']);
                $newSheet->getStyle('CI'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            }
            if($item['vlr2']!=""){
                $newSheet->setCellValue('CJ'.$rowNum, $item['vlr2']);
                $newSheet->getStyle('CJ'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            }
            if($item['vlr3']!=""){
                $newSheet->setCellValue('CK'.$rowNum, $item['vlr3']);
                $newSheet->getStyle('CK'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            }
            if($item['vlr4']!=""){
                $newSheet->setCellValue('CL'.$rowNum, $item['vlr4']);
                $newSheet->getStyle('CL'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            }
            if($item['vlr5']!=""){
                $newSheet->setCellValue('CM'.$rowNum, $item['vlr5']);
                $newSheet->getStyle('CM'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            }
            if($item['vlr6']!=""){
                $newSheet->setCellValue('CN'.$rowNum, $item['vlr6']);
                $newSheet->getStyle('CN'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            }
        }

        $newSheet->setCellValue('CH'.$rowNum, $item['preciocif']);
        $newSheet->getStyle('CH'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        $rowNum++;
    }

    $newSheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF008000');
    $newSheet->getStyle('A2')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
    $newSheet->getStyle('A2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $newSheet->getStyle('B2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF008000');
    $newSheet->getStyle('B2')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
    $newSheet->getStyle('B2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $newSheet->getStyle('C2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF008000');
    $newSheet->getStyle('C2')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
    $newSheet->getStyle('C2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $newSheet->getStyle('D2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF008000');
    $newSheet->getStyle('D2')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
    $newSheet->getStyle('D2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $newSheet->getStyle('E2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF008000');
    $newSheet->getStyle('E2')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
    $newSheet->getStyle('E2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $newSheet->getStyle('F2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF008000');
    $newSheet->getStyle('F2')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
    $newSheet->getStyle('F2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Salida
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
}else{
    http_response_code(401); // Unauthorized
    die("Error: You are not authorized to enter here.");
}