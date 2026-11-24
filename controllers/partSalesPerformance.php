<?php
/*
|--------------------------------------------------------------------------
| CONTROLADOR DASHBOARD DE PERFORMANCE DE VENTA DE PARTES
|--------------------------------------------------------------------------
| Endpoint para alimentar el tablero de performance de venta de partes
| (admin/partSalesPerformance.php).
|
| Acciones disponibles (POST):
|   action = getResumen -> métricas, serie del gráfico, tabla comparativa y
|                          desglose por canal
|     params: year, calendarId, dealerIds[], countryIds[],
|             canales[], periodType, comparison
|   action = getFiltros  -> opciones para la barra de filtros globales
|--------------------------------------------------------------------------
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("../models/DashboardPartSalesPerformance.php");

$inputData = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    $inputData = json_decode(file_get_contents('php://input'), true);
} else {
    $inputData = $_POST;
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$action = $inputData['action'] ?? '';

switch ($action) {
    case 'getResumen':
        try {
            $year        = $inputData['year']        ?? null;
            $calendarId  = $inputData['calendarId']  ?? null;
            $periodType  = $inputData['periodType']  ?? 'month';
            $comparison  = $inputData['comparison']  ?? 'none';

            $dealerIds  = $inputData['dealerIds'] ?? $inputData['dealerId'] ?? [];
            $countryIds = $inputData['countryIds'] ?? [];
            $canales    = $inputData['canales'] ?? $inputData['canal'] ?? 'all';

            $resultado = DashboardPartSalesPerformance::getResumen(
                $year,
                $calendarId,
                $dealerIds,
                $countryIds,
                $canales,
                $periodType,
                $comparison
            );

            echo json_encode([
                'success' => true,
                'data'    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error cargando resumen del dashboard de partes: ' . $e->getMessage()
            ]);
        }
        break;

    case 'getFiltros':
        try {
            $resultado = DashboardPartSalesPerformance::getFiltrosDisponibles();
            echo json_encode([
                'success' => true,
                'data'    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error cargando filtros del dashboard de partes: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida: ' . $action
        ]);
}
