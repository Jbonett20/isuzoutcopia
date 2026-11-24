<?php
/*
|--------------------------------------------------------------------------
| CONTROLADOR DASHBOARD DE INFORMATION STATUS (Cumplimiento de Carga)
|--------------------------------------------------------------------------
| Endpoint para alimentar el tablero de Information Status
| (admin/informationstatus.php).
|
| Acciones disponibles (POST):
|   action = getResumen -> cumplimiento general y por categoría, serie
|                          mensual, dealers (con estados), KPIs críticos
|     params: year, calendarId, dealerIds[], countryIds[],
|             periodType, comparison
|   action = getFiltros  -> opciones para la barra de filtros globales
|--------------------------------------------------------------------------
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("../models/InformationStatus.php");

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

            $resultado = InformationStatus::getResumen(
                $year,
                $calendarId,
                $dealerIds,
                $countryIds,
                $periodType,
                $comparison
            );

            echo json_encode(['success' => true] + $resultado);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al calcular el resumen de Information Status',
                'error'   => $e->getMessage(),
            ]);
        }
        break;

    case 'getFiltros':
        try {
            $filtros = InformationStatus::getFiltrosDisponibles();
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
