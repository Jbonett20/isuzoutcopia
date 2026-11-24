<?php
/*
|--------------------------------------------------------------------------
| CONTROLADOR DASHBOARD
|--------------------------------------------------------------------------
| Endpoint para alimentar el dashboard de inicio (admin/dashboard.php).
|
| Acciones disponibles (POST, formato :contentType JSON o form):
|   action = getResumen -> resumen general de cumplimiento
|     params: year, calendarId, dealerIds[]|dealerId (compat), countryIds[],
|             vehicleType, periodType, comparison
|   action = getFiltros  -> opciones para la barra de filtros globales
|--------------------------------------------------------------------------
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("../models/Dashboard.php");

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
            $periodType  = $inputData['periodType']  ?? 'month';
            $comparison  = $inputData['comparison']  ?? 'none';

            // dealerIds: array/CSV, o el viejo dealerId (backward-compat)
            $dealerIds  = $inputData['dealerIds'] ?? $inputData['dealerId'] ?? [];
            $countryIds = $inputData['countryIds'] ?? [];

            $resultado = Dashboard::getResumen(
                $year,
                $calendarId,
                $dealerIds,
                $countryIds,
                $vehicleType,
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
                'message' => 'Error cargando resumen del dashboard: ' . $e->getMessage()
            ]);
        }
        break;

    case 'getFiltros':
        try {
            $resultado = Dashboard::getFiltrosDisponibles();

            echo json_encode([
                'success' => true,
                'data'    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error cargando filtros del dashboard: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Acción no reconocida: ' . $action
        ]);
        break;
}
