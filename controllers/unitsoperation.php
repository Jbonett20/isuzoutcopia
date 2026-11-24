<?php
/*
|--------------------------------------------------------------------------
| CONTROLADOR DASHBOARD DE UNIDADES EN OPERACIÓN
|--------------------------------------------------------------------------
| Endpoint para alimentar el tablero de unidades en operación
| (admin/unitsoperation.php).
|
| Acciones disponibles (POST):
|   action = getResumen -> métricas UIO, serie de tendencia, mix y ranking
|     params: year, calendarId, dealerIds[], countryIds[], vehicleType
|   action = getFiltros  -> opciones para la barra de filtros globales
|--------------------------------------------------------------------------
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("../models/DashboardUnitsOperation.php");

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
            $vehicleType = $inputData['vehicleType'] ?? 'all';

            $dealerIds  = $inputData['dealerIds'] ?? $inputData['dealerId'] ?? [];
            $countryIds = $inputData['countryIds'] ?? [];

            $resultado = DashboardUnitsOperation::getResumen(
                $year,
                $calendarId,
                $dealerIds,
                $countryIds,
                $vehicleType
            );

            echo json_encode([
                'success' => true,
                'data'    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error cargando resumen del dashboard de UIO: ' . $e->getMessage()
            ]);
        }
        break;

    case 'getFiltros':
        try {
            $resultado = DashboardUnitsOperation::getFiltrosDisponibles();
            echo json_encode([
                'success' => true,
                'data'    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error cargando filtros del dashboard de UIO: ' . $e->getMessage()
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
