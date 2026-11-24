<?php
// Incluye el autoload de PhpSpreadsheet (asegúrate de que la ruta sea la correcta)
@session_start();
require_once 'src/PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


include_once('../config/init_db.php');
$sessionRoleIds = array_map(
    'intval',
    $_SESSION['UsrData']['rolids'] ?? []
);
if (!empty($sessionRoleIds)) {

    $account = DB::queryFirstRow("SELECT o.ordenid, p.pais, CONCAT(c.ano,'-',c.mes) as prodmonth, o.creacion, 
    d.fromdata, d.dealer, d.paymentterm, d.todata, d.personincharge, d.phone, t.tipoorden, d.siglaorden, c.mesano , o.codigo
    FROM ots_ordenes o, ots_dealers d, ots_paises p, ots_tipoordenes t, ots_puertos s, ots_calendarios c 
    WHERE o.ordenid = ".$_GET['orderid']." AND o.dealerid = d.dealerid AND d.paisid = p.paisid AND o.tipoordenid = t.tipoordenid AND o.puertoid = s.puertoid 
    AND o.calendarioid = c.calendarioid");


    $sql = "SELECT c.mesano, d.item, d.noorden, d.modelo, d.anomodelo, d.ezspeccode, d.cantidad, d.preciodolar, d.preciocif, r.color, d.opcionbody, d.asignacion, d.respuesta, d.precio
            FROM ots_ordenes o, ots_ordendetalles d, ots_calendarios c, ots_colores r
            WHERE o.ordenid=".$_GET['orderid']." AND o.ordenid = d.ordenid AND o.calendarioid = c.calendarioid AND d.color = r.colorid";
    $listadoItems = DB::query($sql);

    if( isset($account['ordenid']) && $account['ordenid']>0 ){
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Meiryo UI');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Order Sheet');

        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(18);
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

        // ========== 1. TITULARES ==========
        $sheet->mergeCells('B1:C1');
        $sheet->setCellValue('B1', $account['pais']);
        $sheet->getStyle('B1:C1')->getFont()->setSize(16)->setBold(true)->setName('Meiryo UI');
        $sheet->getStyle('B1:C1')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('B1:C1')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle('B1:C1')->getFont()->setSize(16)->setBold(true);

        // "ORDER SHEET" en E1
        $sheet->setCellValue('E1', '*** ORDER SHEET ***');
        $sheet->getStyle('E1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('E1')->getAlignment()->setHorizontal('center');

        // ========== 2. INFORMACIÓN ENTRE FILAS 2-10 ==========
        $info = [
            ['D3', 'Production Month :', 'right', 'E3', $account['prodmonth'], 'center'],
            ['D4', 'Order Placement date :', 'right', 'E4', $account['creacion'], 'center'],
            ['D6', 'FROM :','right', 'E6', $account['fromdata'], 'left'],
            ['D7', 'Country name :', 'right', 'E7', $account['pais'], 'left'],
            ['D8', 'Dealer name :', 'right', 'E8', $account['dealer'], 'left'],
            ['D9', 'Payment term :', 'right', 'E9', $account['paymentterm'], 'left'],
            ['J3', 'Type of Vessel :', 'right', 'K3', $account['tipoorden'], 'left'],
            ['I6', 'To :', 'right', 'J6', $account['todata'], 'left'],
            ['I7', 'Person in charge :', 'right', 'J7', $account['personincharge'], 'left'],
            ['I8', 'Phone :', 'right', 'J8', $account['phone'], 'left'],
        ];

        foreach ($info as $row) {
            [$labelCell, $label, $alignlabel, $valueCell, $value, $alignvalue] = $row;
            $sheet->setCellValue($labelCell, $label);
            $sheet->setCellValue($valueCell, $value);
            $sheet->getStyle($labelCell)->getAlignment()->setHorizontal($alignlabel);
            $sheet->getStyle($valueCell)->getAlignment()->setHorizontal($alignvalue);
            if (in_array($labelCell, ['D3', 'D4']) || in_array($valueCell, ['E3', 'E4'])) {
                $sheet->getStyle("$labelCell:$labelCell")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("$valueCell:$valueCell")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        }

        // ========== 3. ENCABEZADO DE TABLA ==========
        $headers = [
            " ", "Prod.Month (YYMM)", "Item #", "Order No.", "MODEL NAME", "Model Year",
            "EZSPEC CODE", "Qty", "UNIT PRICE", "Color", "Body/OPT", "PRICE (USD)",
            "Production Allocation Req. (Week)", "Background of request"
        ];

        foreach ($headers as $i => $title) {
            $col = chr(ord('A') + $i);
            $sheet->mergeCells("$col"."13:$col"."14");
            $sheet->setCellValue("$col"."13", $title);
            $style = $sheet->getStyle("$col"."13:$col"."14");
            $style->getFont()->setName('Meiryo UI')->setSize(9);
            $style->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
            // Encabezado en azul para columnas M y N, en gris para el resto
            //$bgColor = in_array($col, ['M', 'N']) ? 'FF9CC2E5' : 'FFE2EEDA';
            //$style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
            //$style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            if ($col === 'A') {
                // Sin bordes y fondo blanco para columna A
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFFFF');
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
            } else {
                $bgColor = in_array($col, ['M', 'N']) ? 'FF9CC2E5' : 'FFE2EEDA';
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        }

        // ========== 4. DATOS DE LA TABLA ==========
        foreach ($listadoItems as $item) {
            $data[] = [
                $item['mesano'], $item['item'], $item['noorden'], $item['modelo'], $item['anomodelo'], $item['ezspeccode'], $item['cantidad'], $item['preciocif'], $item['color'], $item['opcionbody'], $item['precio'], $item['asignacion'], $item['respuesta']
            ];
        }
        /*$data = [
            ["2509", "#NL18#A", "IT-V332-25B", "NPR71L-HUSAYN", 26, "NPR71H22V", 4, 13327.00, "999", "A/C", 53308.00, "", ""],
            ["2509", "#NL23FD#A", "IT-V332-25C", "NPR71L-FLSIADIN", 26, "NPR71F2BV", 2, 16914.00, "999", "A/C", 33828.00, "", ""],
            ["2509", "#NL29#A", "IT-V332-25D", "NQR71L-KNSYAYN", 26, "NQR71K2VV", 6, 15354.00, "999", "A/C", 92124.00, "", ""],
            ["2509", "#NL29#A+A", "IT-V332-25D", "NQR71L-KNSYAYN", 26, "NQR71K2VA", 3, 25400.00, "999", "A/C", 76200.00, "", ""]
        ];*/

        $rowNum = 15;
        $unitTotal = 0;
        $priceTotal = 0;

        foreach ($data as $index => $rowData) {
            $sheet->setCellValue("A$rowNum", $index + 1); // Conteo
            $sheet->getStyle("A$rowNum")->getFont()->setName('Meiryo UI')->setSize(11);
            $sheet->getStyle("A$rowNum")->getAlignment()->setHorizontal('center')->setVertical('center');
            //$sheet->getStyle("A$rowNum")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
            /*$sheet->getStyle("A$rowNum")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_NONE
                    ]
                ]
            ]);*/
            for ($i = 0; $i < count($rowData); $i++) {
                $col = chr(ord('B') + $i);
                $cell = $col . $rowNum;
                $sheet->setCellValue($cell, $rowData[$i]);
                if ($col === 'H') $unitTotal += (int)$rowData[$i]; // Qty
                if ($col === 'L') $priceTotal += (float)$rowData[$i]; // PRICE (USD)
                if ($col === 'H') {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0'); // entero con miles
                }
                if ($col === 'L') {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('"$"#,##0.00'); // precio en USD
                }
                if ($col === 'I') {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('"$"#,##0.00'); // precio en USD
                }
                $style = $sheet->getStyle($cell);
                $style->getFont()->setName('Meiryo UI')->setSize(11);
                $style->getAlignment()->setHorizontal('center')->setVertical('center');
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFFFF'); // blanco
            }
            $rowNum++;
        }

        // ========== 5. TOTALES DINÁMICOS ==========
        $totalRow = $rowNum;

        // Texto "Total (Units)" en G1 → combinado desde B hasta G
        $sheet->mergeCells("B$totalRow:G$totalRow");
        $sheet->setCellValue("B$totalRow", 'Total (Units)');
        $sheet->getStyle("B$totalRow")->getFont()->setName('Meiryo UI')->setSize(11);
        $sheet->getStyle("B$totalRow")->getAlignment()->setHorizontal('right')->setVertical('center');
        $sheet->getStyle("B$totalRow:G$totalRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

        // Total unidades en H
        //$sheet->setCellValue("H$totalRow", $unitTotal);
        $sheet->setCellValue("H$totalRow", "=SUM(H15:H$totalRow)");
        $sheet->getStyle("H$totalRow")->getFont()->setName('Meiryo UI')->setSize(11);
        $sheet->getStyle("H$totalRow")->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle("H$totalRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
        $style = $sheet->getStyle("H$totalRow");
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0'); // gris
        $sheet->getStyle("H$totalRow")->getNumberFormat()->setFormatCode('#,##0');


        // Total precios en L
        //$sheet->setCellValue("L$totalRow", $priceTotal);
        $sheet->setCellValue("L$totalRow", "=SUM(L15:L$totalRow)");
        $sheet->getStyle("L$totalRow")->getFont()->setName('Meiryo UI')->setSize(11);
        $sheet->getStyle("L$totalRow")->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle("L$totalRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
        $style = $sheet->getStyle("L$totalRow");
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0'); // gris
        $sheet->getStyle("L$totalRow")->getNumberFormat()->setFormatCode('"$"#,##0.00');

        //Otras concatenadas
        $sheet->mergeCells("M$totalRow:N$totalRow");
        $sheet->getStyle("M$totalRow:N$totalRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        $sheet->mergeCells("I$totalRow:K$totalRow");
        $sheet->getStyle("I$totalRow:K$totalRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

        /* Llenado de la segunda hoja del archivo en excel exclusiva para administradores */
        if( userHasAnyRole(['1', '4']) ){
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('OS ILAC-ISZJ');
            $sheet2->getColumnDimension('A')->setWidth(4);
            $sheet2->getColumnDimension('B')->setWidth(12);
            $sheet2->getColumnDimension('C')->setWidth(18);
            $sheet2->getColumnDimension('D')->setWidth(20);
            $sheet2->getColumnDimension('E')->setWidth(30);
            $sheet2->getColumnDimension('F')->setWidth(10);
            $sheet2->getColumnDimension('G')->setWidth(10);
            $sheet2->getColumnDimension('H')->setWidth(4);
            $sheet2->getColumnDimension('I')->setWidth(13);
            $sheet2->getColumnDimension('J')->setWidth(10);
            $sheet2->getColumnDimension('K')->setWidth(13);
            $sheet2->getColumnDimension('L')->setWidth(13);
            $sheet2->getColumnDimension('M')->setWidth(20);
            $sheet2->getColumnDimension('N')->setWidth(20);

            // ========== 1. TITULARES ==========
            $sheet2->mergeCells('B1:C1');
            $sheet2->setCellValue('B1', $account['pais']);
            $sheet2->getStyle('B1:C1')->getFont()->setSize(16)->setBold(true)->setName('Meiryo UI');
            $sheet2->getStyle('B1:C1')->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet2->getStyle('B1:C1')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $sheet2->getStyle('B1:C1')->getFont()->setSize(16)->setBold(true);

            // "ORDER SHEET" en E1
            $sheet2->setCellValue('E1', '*** ORDER SHEET ***');
            $sheet2->getStyle('E1')->getFont()->setSize(16)->setBold(true);
            $sheet2->getStyle('E1')->getAlignment()->setHorizontal('center');

            // ========== 2. INFORMACIÓN ENTRE FILAS 2-10 ==========
            $info = [
                ['D3', 'Production Month :', 'right', 'E3', $account['prodmonth'], 'center'],
                ['D4', 'Order Placement date :', 'right', 'E4', $account['creacion'], 'center'],
                ['D6', 'FROM :','right', 'E6', 'Isuzu Motors America, LLC Latin America & Caribbean Team', 'left'],
                ['D7', 'Country name :', 'right', 'E7', $account['pais'], 'left'],
                ['D8', 'Dealer name :', 'right', 'E8', $account['dealer'], 'left'],
                ['D9', 'Payment term :', 'right', 'E9', 'On 15th in the following month', 'left'],
                ['J3', 'Type of Vessel :', 'right', 'K3', $account['tipoorden'], 'left'],
                ['I6', 'To :', 'right', 'J6', 'Isuzu Motors Limited', 'left'],
                ['I7', 'Person in charge :', 'right', 'J7', 'Regional Management Office No. 1', 'left'],
                ['I8', 'Phone :', 'right', 'J8', '+(81) 045-299-9111', 'left'],
            ];

            foreach ($info as $row) {
                [$labelCell, $label, $alignlabel, $valueCell, $value, $alignvalue] = $row;
                $sheet2->setCellValue($labelCell, $label);
                $sheet2->setCellValue($valueCell, $value);
                $sheet2->getStyle($labelCell)->getAlignment()->setHorizontal($alignlabel);
                $sheet2->getStyle($valueCell)->getAlignment()->setHorizontal($alignvalue);
                if (in_array($labelCell, ['D3', 'D4']) || in_array($valueCell, ['E3', 'E4'])) {
                    $sheet2->getStyle("$labelCell:$labelCell")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet2->getStyle("$valueCell:$valueCell")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
            }

            // ========== 3. ENCABEZADO DE TABLA ==========
            $headers = [
                " ", "Prod.Month (YYMM)", "Item #", "Order No.", "MODEL NAME", "Model Year",
                "EZSPEC CODE", "Qty", "UNIT PRICE", "Color", "Body/OPT", "PRICE (USD)",
                "Production Allocation Req. (Week)", "Background of request"
            ];

            foreach ($headers as $i => $title) {
                $col = chr(ord('A') + $i);
                $sheet2->mergeCells("$col"."13:$col"."14");
                $sheet2->setCellValue("$col"."13", $title);
                $style =  $sheet2->getStyle("$col"."13:$col"."14");
                $style->getFont()->setName('Meiryo UI')->setSize(9);
                $style->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
                // Encabezado en azul para columnas M y N, en gris para el resto
                //$bgColor = in_array($col, ['M', 'N']) ? 'FF9CC2E5' : 'FFE2EEDA';
                //$style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
                //$style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                if ($col === 'A') {
                    // Sin bordes y fondo blanco para columna A
                    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFFFF');
                    $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
                } else {
                    $bgColor = in_array($col, ['M', 'N']) ? 'FF9CC2E5' : 'FFE2EEDA';
                    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
                    $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
            }

            // ========== 4. DATOS DE LA TABLA ==========
            foreach ($listadoItems as $item) {
                $data2[] = [
                    $item['mesano'], $item['item'], $item['noorden'], $item['modelo'], $item['anomodelo'], $item['ezspeccode'], $item['cantidad'], $item['preciodolar'], $item['color'], $item['opcionbody'], $item['cantidad']*$item['preciodolar'], $item['asignacion'], $item['respuesta']
                ];
            }
            /*$data = [
                ["2509", "#NL18#A", "IT-V332-25B", "NPR71L-HUSAYN", 26, "NPR71H22V", 4, 13327.00, "999", "A/C", 53308.00, "", ""],
                ["2509", "#NL23FD#A", "IT-V332-25C", "NPR71L-FLSIADIN", 26, "NPR71F2BV", 2, 16914.00, "999", "A/C", 33828.00, "", ""],
                ["2509", "#NL29#A", "IT-V332-25D", "NQR71L-KNSYAYN", 26, "NQR71K2VV", 6, 15354.00, "999", "A/C", 92124.00, "", ""],
                ["2509", "#NL29#A+A", "IT-V332-25D", "NQR71L-KNSYAYN", 26, "NQR71K2VA", 3, 25400.00, "999", "A/C", 76200.00, "", ""]
            ];*/

            $rowNum = 15;
            $unitTotal = 0;
            $priceTotal = 0;

            foreach ($data2 as $index => $rowData) {
                $sheet2->setCellValue("A$rowNum", $index + 1); // Conteo
                $sheet2->getStyle("A$rowNum")->getFont()->setName('Meiryo UI')->setSize(11);
                $sheet2->getStyle("A$rowNum")->getAlignment()->setHorizontal('center')->setVertical('center');
                // $sheet2->getStyle("A$rowNum")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
                /* $sheet2->getStyle("A$rowNum")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_NONE
                        ]
                    ]
                ]);*/
                for ($i = 0; $i < count($rowData); $i++) {
                    $col = chr(ord('B') + $i);
                    $cell = $col . $rowNum;
                    $sheet2->setCellValue($cell, $rowData[$i]);
                    if ($col === 'H') $unitTotal += (int)$rowData[$i]; // Qty
                    if ($col === 'L') $priceTotal += (float)$rowData[$i]; // PRICE (USD)
                    if ($col === 'H') {
                        $sheet2->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0'); // entero con miles
                    }
                    if ($col === 'L') {
                        $sheet2->getStyle($cell)->getNumberFormat()->setFormatCode('"$"#,##0.00'); // precio en USD
                    }
                    if ($col === 'I') {
                        $sheet2->getStyle($cell)->getNumberFormat()->setFormatCode('"$"#,##0.00'); // precio en USD
                    }
                    $style =  $sheet2->getStyle($cell);
                    $style->getFont()->setName('Meiryo UI')->setSize(11);
                    $style->getAlignment()->setHorizontal('center')->setVertical('center');
                    $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFFFF'); // blanco
                }
                $rowNum++;
            }

            // ========== 5. TOTALES DINÁMICOS ==========
            $totalRow = $rowNum;

            // Texto "Total (Units)" en G1 → combinado desde B hasta G
            $sheet2->mergeCells("B$totalRow:G$totalRow");
            $sheet2->setCellValue("B$totalRow", 'Total (Units)');
            $sheet2->getStyle("B$totalRow")->getFont()->setName('Meiryo UI')->setSize(11);
            $sheet2->getStyle("B$totalRow")->getAlignment()->setHorizontal('right')->setVertical('center');
            $sheet2->getStyle("B$totalRow:G$totalRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

            // Total unidades en H
            // $sheet2->setCellValue("H$totalRow", $unitTotal);
            $sheet2->setCellValue("H$totalRow", "=SUM(H15:H$totalRow)");
            $sheet2->getStyle("H$totalRow")->getFont()->setName('Meiryo UI')->setSize(11);
            $sheet2->getStyle("H$totalRow")->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet2->getStyle("H$totalRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $style =  $sheet2->getStyle("H$totalRow");
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0'); // gris
            $sheet2->getStyle("H$totalRow")->getNumberFormat()->setFormatCode('#,##0');


            // Total precios en L
            // $sheet2->setCellValue("L$totalRow", $priceTotal);
            $sheet2->setCellValue("L$totalRow", "=SUM(L15:L$totalRow)");
            $sheet2->getStyle("L$totalRow")->getFont()->setName('Meiryo UI')->setSize(11);
            $sheet2->getStyle("L$totalRow")->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet2->getStyle("L$totalRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $style =  $sheet2->getStyle("L$totalRow");
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('C0C0C0'); // gris
            $sheet2->getStyle("L$totalRow")->getNumberFormat()->setFormatCode('"$"#,##0.00');

            //Otras concatenadas
            $sheet2->mergeCells("M$totalRow:N$totalRow");
            $sheet2->getStyle("M$totalRow:N$totalRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $sheet2->mergeCells("I$totalRow:K$totalRow");
            $sheet2->getStyle("I$totalRow:K$totalRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        }
        /* Llenado de la segunda hoja del archivo en excel exclusiva para administradores */

        /* Llenado de la tercera hoja del archivo en excel exclusiva para administradores*/
        if( userHasAnyRole(['1', '4']) ){
            $sqlDet = "SELECT 
                s.customercode, c.mesano, d.item, d.noorden, d.modelo, d.anomodelo, d.ezspeccode, d.cantidad, i.poe, i.tire, i.unitpricecif, r.color,
                i.colorcode1,i.colorcode2,i.colorcode3,i.colorcode4,i.colorcode5,i.colorcode6,i.colorcode7,i.colorcode8,i.colorcode9,i.colorcode10,i.colorcode11,i.colorcode12,i.colorcode13,i.colorcode14,i.colorcode15,i.colorcode16,i.colorcode17,i.colorcode18,i.colorcode19,
                i.optioncode1,i.optioncode2,i.optioncode3,i.optioncode4,i.optioncode5,i.optioncode6,i.optioncode7,i.optioncode8,i.optioncode9,i.optioncode10,i.optioncode11,i.optioncode12,i.optioncode13,i.optioncode14,i.optioncode15,i.optioncode16,i.optioncode17,i.optioncode18,i.optioncode19,i.optioncode20,i.optioncode21,i.optioncode22,i.optioncode23,i.optioncode24,i.optioncode25,i.optioncode26,i.optioncode27,i.optioncode28,i.optioncode29,i.optioncode30,
                i.bodybuildcode1,i.bodybuildname1,i.bodybuildcompany1,i.bodybuilddraw1,i.bodybuildout1,
                i.bodybuildcode2,i.bodybuildname2,i.bodybuildcompany2,i.bodybuilddraw2,i.bodybuildout2,
                i.bodybuildcode3,i.bodybuildname3,i.bodybuildcompany3,i.bodybuilddraw3,i.bodybuildout3,
                i.remarks, d.preciodolar, d.preciocif,
                i.vlr1,i.vlr2,i.vlr3,i.vlr4,i.vlr5,i.vlr6
                FROM ots_ordenes o, ots_ordendetalles d, ots_calendarios c, ots_colores r, ots_dealers s, ots_items i
                WHERE o.ordenid=".$_GET['orderid']." AND o.ordenid = d.ordenid AND o.calendarioid = c.calendarioid AND d.color = r.colorid AND o.dealerid = s.dealerid AND d.itemid = i.itemid";
            $listadoItemsDet = DB::query($sqlDet);
            $newSheet = $spreadsheet->createSheet();
            $newSheet->setTitle('ECO ILAC-ISZJ');
            /* Tamaño columnas asociadas */
                $newSheet->getColumnDimension('A')->setWidth(15);
                $newSheet->getColumnDimension('B')->setWidth(15);
                $newSheet->getColumnDimension('C')->setWidth(25);
                $newSheet->getColumnDimension('D')->setWidth(15);
                $newSheet->getColumnDimension('E')->setWidth(25);
                $newSheet->getColumnDimension('F')->setWidth(10);
                $newSheet->getColumnDimension('G')->setWidth(25);
                $newSheet->getColumnDimension('H')->setWidth(10);
                $newSheet->getColumnDimension('I')->setWidth(10);
                $newSheet->getColumnDimension('J')->setWidth(10);
                $newSheet->getColumnDimension('K')->setWidth(10);
                $newSheet->getColumnDimension('L')->setWidth(10);
                $newSheet->getColumnDimension('M')->setWidth(10);
                $newSheet->getColumnDimension('N')->setWidth(10);
                $newSheet->getColumnDimension('O')->setWidth(10);
                $newSheet->getColumnDimension('P')->setWidth(10);
                $newSheet->getColumnDimension('Q')->setWidth(10);
                $newSheet->getColumnDimension('R')->setWidth(10);
                $newSheet->getColumnDimension('S')->setWidth(10);
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
                $newSheet->getColumnDimension('BJ')->setWidth(15);
                $newSheet->getColumnDimension('BK')->setWidth(15);
                $newSheet->getColumnDimension('BL')->setWidth(15);
                $newSheet->getColumnDimension('BM')->setWidth(15);
                $newSheet->getColumnDimension('BN')->setWidth(15);
                $newSheet->getColumnDimension('BO')->setWidth(15);
                $newSheet->getColumnDimension('BP')->setWidth(15);
                $newSheet->getColumnDimension('BQ')->setWidth(15);
                $newSheet->getColumnDimension('BR')->setWidth(15);
                $newSheet->getColumnDimension('BS')->setWidth(15);
                $newSheet->getColumnDimension('BT')->setWidth(15);
                $newSheet->getColumnDimension('BU')->setWidth(15);
                $newSheet->getColumnDimension('BV')->setWidth(15);
                $newSheet->getColumnDimension('BW')->setWidth(15);
                $newSheet->getColumnDimension('BX')->setWidth(15);
                $newSheet->getColumnDimension('BY')->setWidth(50);
                $newSheet->getColumnDimension('BZ')->setWidth(25);
                $newSheet->getColumnDimension('CA')->setWidth(25);
                $newSheet->getColumnDimension('CB')->setWidth(25);
                $newSheet->getColumnDimension('CC')->setWidth(25);
                $newSheet->getColumnDimension('CD')->setWidth(25);
                $newSheet->getColumnDimension('CE')->setWidth(25);
                $newSheet->getColumnDimension('CF')->setWidth(25);
                $newSheet->getColumnDimension('CG')->setWidth(25);
                
            /* Tamaño columnas asociadas */

            $headersRow1 = [
                ['A1:A2', 'CUSTOMER CODE'],
                ['B1:B2', 'PROD.MONTH (YYMM)'],
                ['C1:C2', 'ITEM#'],
                ['D1:D2', 'ORDER NO.'],
                ['E1:E2', 'MODEL NAME'],
                ['F1:F2', 'Sales Year'],
                ['G1:G2', 'EZSPEC CODE'],
                ['H1:H2', 'TOTAL UNITS'],
                ['I1:I2', 'POE'],
                ['J1:J2', 'TIRE'],
                ['K1:K2', "UNIT PRICE\nISZ→ILAC\nSelling Price\n(USD)"],
                ['L1:O1','COLOR CODE(1)'],
                ['P1:S1','COLOR CODE(2)'],
                ['T1:W1','COLOR CODE(3)'],
                ['X1:AA1','COLOR CODE(4)'],
                ['AB1:AE1','COLOR CODE(5)'],
                ['AF1:BI1','OPTION CODE'],
                ['BJ1:BN1','BD.BUILD(1)'],
                ['BO1:BS1','BD.BUILD(2)'],
                ['BT1:BX1','BD.BUILD(3)'],
                ['BY1:BY2', "REMARKS　(ILAC→ Selling Price)"],
                ['BZ1:BZ2', "UNIT PRICE ISZJ⇒ILAC Selling Price (USD)"],
                ['CA1:CA2', "UNIT PRICE ILAC⇒Selling Price (USD)"],
                ['CB1:CD1', "ISZJ-ILAC"],
                ['CE1:CG1', "ILAC-Dealer"]
            ];
            foreach ($headersRow1 as [$range, $text]) {
                $newSheet->mergeCells($range);
                $newSheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                $newSheet->getStyle($range)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
                $newSheet->setCellValue(explode(':', $range)[0], $text);
            }
            $Sencillas = [
                ['L2','BODY'],
                ['M2','INT'],
                ['N2','EXT'],
                ['O2','UNIT'],
                ['P2','BODY'],
                ['Q2','INT'],
                ['R2','EXT'],
                ['S2','UNIT'],
                ['T2','BODY'],
                ['U2','INT'],
                ['V2','EXT'],
                ['W2','UNIT'],
                ['X2','BODY'],
                ['Y2','INT'],
                ['Z2','EXT'],
                ['AA2','UNIT'],
                ['AB2','BODY'],
                ['AC2','INT'],
                ['AD2','EXT'],
                ['AE2','UNIT'],

                ['AF2','(1)'],
                ['AG2','(2)'],
                ['AH2','(3)'],
                ['AI2','(4)'],
                ['AJ2','(5)'],
                ['AK2','(6)'],
                ['AL2','(7)'],
                ['AM2','(8)'],
                ['AN2','(9)'],
                ['AO2','(10)'],
                ['AP2','(11)'],
                ['AQ2','(12)'],
                ['AR2','(13)'],
                ['AS2','(14)'],
                ['AT2','(15)'],
                ['AU2','(16)'],
                ['AV2','(17)'],
                ['AW2','(18)'],
                ['AX2','(19)'],
                ['AY2','(20)'],
                ['AZ2','(21)'],
                ['BA2','(22)'],
                ['BB2','(23)'],
                ['BC2','(24)'],
                ['BD2','(25)'],
                ['BE2','(26)'],
                ['BF2','(27)'],
                ['BG2','(28)'],
                ['BH2','(29)'],
                ['BI2','(30)'],

                ['BJ2','BD.BUILD CODE'],
                ['BK2','BD.BUILD NAME'],
                ['BL2','PR COMPANY'],
                ['BM2','DRAWING NO.'],
                ['BN2','OUTLINE OF BD.BUILD'],

                ['BO2','BD.BUILD CODE'],
                ['BP2','BD.BUILD NAME'],
                ['BQ2','PR COMPANY'],
                ['BR2','DRAWING NO.'],
                ['BS2','OUTLINE OF BD.BUILD'],

                ['BT2','BD.BUILD CODE'],
                ['BU2','BD.BUILD NAME'],
                ['BV2','PR COMPANY'],
                ['BW2','DRAWING NO.'],
                ['BX2','OUTLINE OF BD.BUILD'],

                ['CB2','(USD)'],
                ['CC2','O/F'],
                ['CD2','INS 0.2%'],
                ['CE2','(USD)'],
                ['CF2','O/F'],
                ['CG2','INS 0.2%']
            ];
            foreach ($Sencillas as [$range, $text]) {
                $newSheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                $newSheet->getStyle($range)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
                $newSheet->setCellValue(explode(':', $range)[0], $text);
            }
            $rowNum = 3;
            foreach ($listadoItemsDet as $item) {
                $newSheet->setCellValue('A'.$rowNum, $item['customercode']);
                $newSheet->setCellValue('B'.$rowNum, $item['mesano']);
                $newSheet->setCellValue('C'.$rowNum, $item['item']);
                $newSheet->setCellValue('D'.$rowNum, $item['noorden']);
                $newSheet->setCellValue('E'.$rowNum, $item['modelo']);
                $newSheet->setCellValue('F'.$rowNum, $item['anomodelo']);
                $newSheet->setCellValue('G'.$rowNum, $item['ezspeccode']);
                $newSheet->setCellValue('H'.$rowNum, $item['cantidad']);
                $newSheet->setCellValue('I'.$rowNum, $item['poe']);
                $newSheet->setCellValue('J'.$rowNum, $item['tire']);

                $newSheet->setCellValue('K'.$rowNum, $item['preciodolar']);
                //$newSheet->getStyle('K'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
		$newSheet->getStyle('K'.$rowNum)->getNumberFormat()->setFormatCode('""#,##0.00');
                $newSheet->setCellValue('L'.$rowNum, $item['color']);
                $newSheet->setCellValue('M'.$rowNum, $item['colorcode1']);
                $newSheet->setCellValue('N'.$rowNum, $item['colorcode2']);
                $newSheet->setCellValue('O'.$rowNum, $item['cantidad']);
                $newSheet->setCellValue('P'.$rowNum, $item['colorcode4']);
                $newSheet->setCellValue('Q'.$rowNum, $item['colorcode5']);
                $newSheet->setCellValue('R'.$rowNum, $item['colorcode6']);
                $newSheet->setCellValue('S'.$rowNum, $item['colorcode7']);
                $newSheet->setCellValue('T'.$rowNum, $item['colorcode8']);
                $newSheet->setCellValue('U'.$rowNum, $item['colorcode9']);
                $newSheet->setCellValue('V'.$rowNum, $item['colorcode10']);
                $newSheet->setCellValue('W'.$rowNum, $item['colorcode11']);
                $newSheet->setCellValue('X'.$rowNum, $item['colorcode12']);
                $newSheet->setCellValue('Y'.$rowNum, $item['colorcode13']);
                $newSheet->setCellValue('Z'.$rowNum, $item['colorcode14']);
                $newSheet->setCellValue('AA'.$rowNum, $item['colorcode15']);
                $newSheet->setCellValue('AB'.$rowNum, $item['colorcode16']);
                $newSheet->setCellValue('AC'.$rowNum, $item['colorcode17']);
                $newSheet->setCellValue('AD'.$rowNum, $item['colorcode18']);
                $newSheet->setCellValue('AE'.$rowNum, $item['colorcode19']);

                $newSheet->setCellValue('AF'.$rowNum, $item['optioncode1']);
                $newSheet->setCellValue('AG'.$rowNum, $item['optioncode2']);
                $newSheet->setCellValue('AH'.$rowNum, $item['optioncode3']);
                $newSheet->setCellValue('AI'.$rowNum, $item['optioncode4']);
                $newSheet->setCellValue('AJ'.$rowNum, $item['optioncode5']);
                $newSheet->setCellValue('AK'.$rowNum, $item['optioncode6']);
                $newSheet->setCellValue('AL'.$rowNum, $item['optioncode7']);
                $newSheet->setCellValue('AM'.$rowNum, $item['optioncode8']);
                $newSheet->setCellValue('AN'.$rowNum, $item['optioncode9']);
                $newSheet->setCellValue('AO'.$rowNum, $item['optioncode10']);
                $newSheet->setCellValue('AP'.$rowNum, $item['optioncode11']);
                $newSheet->setCellValue('AQ'.$rowNum, $item['optioncode12']);
                $newSheet->setCellValue('AR'.$rowNum, $item['optioncode13']);
                $newSheet->setCellValue('AS'.$rowNum, $item['optioncode14']);
                $newSheet->setCellValue('AT'.$rowNum, $item['optioncode15']);
                $newSheet->setCellValue('AU'.$rowNum, $item['optioncode16']);
                $newSheet->setCellValue('AV'.$rowNum, $item['optioncode17']);
                $newSheet->setCellValue('AW'.$rowNum, $item['optioncode18']);
                $newSheet->setCellValue('AX'.$rowNum, $item['optioncode19']);
                $newSheet->setCellValue('AY'.$rowNum, $item['optioncode20']);
                $newSheet->setCellValue('AZ'.$rowNum, $item['optioncode21']);
                $newSheet->setCellValue('BA'.$rowNum, $item['optioncode22']);
                $newSheet->setCellValue('BB'.$rowNum, $item['optioncode23']);
                $newSheet->setCellValue('BC'.$rowNum, $item['optioncode24']);
                $newSheet->setCellValue('BD'.$rowNum, $item['optioncode25']);
                $newSheet->setCellValue('BE'.$rowNum, $item['optioncode26']);
                $newSheet->setCellValue('BF'.$rowNum, $item['optioncode27']);
                $newSheet->setCellValue('BG'.$rowNum, $item['optioncode28']);
                $newSheet->setCellValue('BH'.$rowNum, $item['optioncode29']);
                $newSheet->setCellValue('BI'.$rowNum, $item['optioncode30']);


                $newSheet->setCellValue('BJ'.$rowNum, $item['bodybuildcode1']);
                $newSheet->setCellValue('BK'.$rowNum, $item['bodybuildname1']);
                $newSheet->setCellValue('BL'.$rowNum, $item['bodybuildcompany1']);
                $newSheet->setCellValue('BM'.$rowNum, $item['bodybuilddraw1']);
                $newSheet->setCellValue('BN'.$rowNum, $item['bodybuildout1']);
                $newSheet->setCellValue('BO'.$rowNum, $item['bodybuildcode2']);
                $newSheet->setCellValue('BP'.$rowNum, $item['bodybuildname2']);
                $newSheet->setCellValue('BQ'.$rowNum, $item['bodybuildcompany2']);
                $newSheet->setCellValue('BR'.$rowNum, $item['bodybuilddraw2']);
                $newSheet->setCellValue('BS'.$rowNum, $item['bodybuildout2']);
                $newSheet->setCellValue('BT'.$rowNum, $item['bodybuildcode3']);
                $newSheet->setCellValue('BU'.$rowNum, $item['bodybuildname3']);
                $newSheet->setCellValue('BV'.$rowNum, $item['bodybuildcompany3']);
                $newSheet->setCellValue('BW'.$rowNum, $item['bodybuilddraw3']);
                $newSheet->setCellValue('BX'.$rowNum, $item['bodybuildout3']);
                $newSheet->setCellValue('BY'.$rowNum, $item['remarks']);

                $newSheet->setCellValue('BZ'.$rowNum, $item['preciodolar']);
                $newSheet->getStyle('BZ'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');

                $newSheet->setCellValue('CA'.$rowNum, $item['preciocif']);
                $newSheet->getStyle('CA'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');

                //Colores
                $newSheet->getStyle('CB1:CD1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDEFE3');
                $newSheet->getStyle('CB2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDEFE3');
                $newSheet->getStyle('CC2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDEFE3');
                $newSheet->getStyle('CD2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDEFE3');

                $newSheet->getStyle('CE1:CG1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DEE8F2');
                $newSheet->getStyle('CE2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DEE8F2');
                $newSheet->getStyle('CF2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DEE8F2');
                $newSheet->getStyle('CG2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DEE8F2');


                if($item['vlr1']!=""){
                    $newSheet->setCellValue('CB'.$rowNum, $item['vlr1']);
                    $newSheet->getStyle('CB'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                }
                
                if($item['vlr2']!=""){
                    $newSheet->setCellValue('CC'.$rowNum, $item['vlr2']);
                    $newSheet->getStyle('CC'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                }
                if($item['vlr3']!=""){
                    $newSheet->setCellValue('CD'.$rowNum, $item['vlr3']);
                    $newSheet->getStyle('CD'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                }
                if($item['vlr4']!=""){
                    $newSheet->setCellValue('CE'.$rowNum, $item['vlr4']);
                    $newSheet->getStyle('CE'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                }
                if($item['vlr5']!=""){
                    $newSheet->setCellValue('CF'.$rowNum, $item['vlr5']);
                    $newSheet->getStyle('CF'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                }
                if($item['vlr6']!=""){
                    $newSheet->setCellValue('CG'.$rowNum, $item['vlr6']);
                    $newSheet->getStyle('CG'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                }


                $rowNum++;
            }

        }
        /* Llenado de la tercera hoja del archivo en excel exclusiva para administradores*/


        // ========== 6. DESCARGA ==========
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$account['codigo'].'-'.$account['tipoorden'].'-'.date('mdY').'.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
