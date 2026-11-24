<?php
/*
|--------------------------------------------------------------------------
| CONTROLADOR DASHBOARD DE INVENTORY REPORT
|--------------------------------------------------------------------------
| Endpoint para alimentar el tablero de reporte de inventario
| (admin/inventoryReport.php).
|
| Acciones disponibles (POST):
|   action = getResumen -> inventario total, meses de inventario, turnover,
|                          target, capital en riesgo, serie y tabla por dealer
|     params: year, calendarId, dealerIds[], countryIds[], periodType
|   action = getFiltros  -> opciones para la barra de filtros globales
|--------------------------------------------------------------------------
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("../models/DashboardInventoryReport.php");

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

            $dealerIds  = $inputData['dealerIds'] ?? $inputData['dealerId'] ?? [];
            $countryIds = $inputData['countryIds'] ?? [];

            $resultado = DashboardInventoryReport::getResumen(
                $year,
                $calendarId,
                $dealerIds,
                $countryIds,
                $periodType
            );

            echo json_encode(['success' => true] + $resultado);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al calcular el resumen de Inventory Report',
                'error'   => $e->getMessage(),
            ]);
        }
        break;

    case 'getFiltros':
        try {
            $filtros = DashboardInventoryReport::getFiltrosDisponibles();
            echo json_encode(['success' => true, 'filtros' => $filtros]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener los filtros',
                'error'   => $e->getMessage(),
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
