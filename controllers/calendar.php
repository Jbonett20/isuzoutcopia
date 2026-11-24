<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once("../admin/src/seguridad.php");
include_once("../models/Calendar.php");
include_once("../models/State.php");
$listadoEstados = State::listar();
$listadoItems = Calendar::listar();

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'POST':
        switch ($_POST["opc"]) {
            case 'crear':
                $resultado = Calendar::crear($_POST);
                echo json_encode($resultado);
                break;

            case 'editar':
                $resultado = Calendar::editar($_POST);
                echo json_encode($resultado);
                break;

            case 'listCalendar':
                $resultado = Calendar::listCalendar();
                echo json_encode($resultado);
                break;
        }
        break;
}