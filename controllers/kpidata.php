<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("../models/Kpidata.php");
$metodo = $_SERVER['REQUEST_METHOD'];

// Detectar si los datos vienen como JSON
$inputData = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
  $rawInput = file_get_contents('php://input');
  $inputData = json_decode($rawInput, true);
} else {
  $inputData = $_POST;
}

switch ($metodo) {
  case 'POST':
    switch ($inputData["action"]) {
      case 'getEmpresas':
        $resultado = Kpidata::listarEmpresas();
        echo json_encode($resultado);
        break;
                  case 'getYears':
        $resultado = Kpidata::getYears();
        echo json_encode($resultado);
        break;
      case 'getYearsMonth':
        $resultado = Kpidata::getYearsMonth();
        echo json_encode($resultado);
        break;
      case 'saveKpiData':
        try {
          $kpiType = $inputData['kpiType'];
          $dealerId = $inputData['dealerId'];
          $yearMonth = $inputData['yearMonth'];
          $data = $inputData['data'];
          $resultado = Kpidata::saveKpiData($kpiType, $dealerId, $yearMonth, $data);

          if ($resultado) {
            echo json_encode([
              'success' => true,
              'message' => ucfirst($kpiType) . ' data saved successfully'
            ]);
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Error saving data'
            ]);
          }
        } catch (Exception $e) {
          echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
          ]);
        }
        break;
                  case 'getKpiData':
        try {
          $kpiType = $inputData['kpiType'];
          $dealerId = $inputData['dealerId'] ?? null;
          $year = $inputData['year'] ?? null;

          $resultado = Kpidata::getKpiData($kpiType, $dealerId, $year);

          echo json_encode([
            'success' => true,
            'data' => $resultado
          ]);
        } catch (Exception $e) {
          echo json_encode([
            'success' => false,
            'message' => 'Error loading data: ' . $e->getMessage()
          ]);
        }
        break;
      case 'deleteKpiData':
        try {
          $kpiType = $inputData['kpiType'];
          $dealerId = $inputData['dealerId'];
          $yearMonth = $inputData['yearMonth'];

          $resultado = Kpidata::deleteKpiData($kpiType, $dealerId, $yearMonth);

          if ($resultado) {
            echo json_encode([
              'success' => true,
              'message' => ucfirst($kpiType) . ' record deleted successfully'
            ]);
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Error deleting record'
            ]);
          }
        } catch (Exception $e) {
          echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
          ]);
        }
        break;
      case 'bulkUploadKpiData':
        try {
          $kpiType = $inputData['kpiType'];
          $data = $inputData['data'];

          if (!is_array($data) || empty($data)) {
            throw new Exception('No data provided for import');
          }

          $successCount = 0;
          $errorCount = 0;
          $errors = [];

          foreach ($data as $index => $row) {
            try {
              // Validar que cada fila tiene dealerId y calendar
              if (empty($row['dealerId'])) {
                throw new Exception('Dealer ID is required');
              }
              if (empty($row['calendar'])) {
                throw new Exception('Calendar is required');
              }

              // Extraer dealerId y calendar
              $dealerId = $row['dealerId'];
              $calendarText = $row['calendar'];

              // Convertir el texto del calendario a ID
              $calendarId = Kpidata::getCalendarIdByText($calendarText);

              if (!$calendarId) {
                throw new Exception("Calendar '$calendarText' not found. Use format: YEAR - MONTH (e.g., 2024 - January)");
              }

              // Remover dealerId y calendar del row para dejar solo los campos KPI
              unset($row['dealerId']);
              unset($row['calendar']);

              // Guardar el registro
              $resultado = Kpidata::saveKpiData($kpiType, $dealerId, $calendarId, $row);

              if ($resultado) {
                $successCount++;
              } else {
                $errors[] = "Row " . ($index + 1) . ": Error saving data";
                $errorCount++;
              }
            } catch (Exception $e) {
              $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
              $errorCount++;
            }
          }

          $message = "$successCount record(s) imported successfully.";
          if ($errorCount > 0) {
            $message .= " $errorCount record(s) failed.";
          }

          echo json_encode([
            'success' => $successCount > 0,
            'message' => $message,
            'details' => [
              'success' => $successCount,
              'errors' => $errorCount,
              'errorMessages' => $errors
            ]
          ]);
        } catch (Exception $e) {
          echo json_encode([
            'success' => false,
            'message' => 'Bulk upload error: ' . $e->getMessage()
          ]);
        }
        break;
    }
    break;
}
