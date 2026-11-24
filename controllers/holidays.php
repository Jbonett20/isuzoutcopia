<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once("../admin/src/seguridad.php");
include_once("../models/Holiday.php");
include_once("../models/State.php");
include_once("../models/Country.php");
$listadoEstados = State::listar();
$listadoPaises = Country::listar();
$listadoItems = Holiday::listar();

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'POST':
        switch ($_POST["opc"]) {
            case 'crear':
                $resultado = Holiday::crear($_POST);
                echo json_encode($resultado);
                break;

                case 'editar':
                $resultado = Holiday::editar($_POST);
                echo json_encode($resultado);
                break;
        }
        break;
}
?>