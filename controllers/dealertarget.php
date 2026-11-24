<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once("../models/Dealertarget.php");


$dealerFiltro = isset($_GET['dealerid']) ? (int)$_GET['dealerid'] : 0;
$listadoDealers = Dealertarget::listarDealersFiltro();
$listadoItems = Dealertarget::listar($dealerFiltro);
$canManage = Dealertarget::puedeGestionar();

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'POST':
        switch ($_POST['opc'] ?? '') {
            case 'crear':
                $resultado = Dealertarget::crear($_POST);
                echo json_encode($resultado);
                break;

            case 'editar':
                $resultado = Dealertarget::editar($_POST);
                echo json_encode($resultado);
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid operation.'
                ]);
                break;
        }
        break;
}
