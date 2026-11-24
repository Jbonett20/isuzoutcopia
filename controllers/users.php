<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once("../admin/src/seguridad.php");
include_once("../models/User.php");
include_once("../models/home.php");
include_once("../models/State.php");
include_once("../models/Rol.php");
$listadoDealers = Home::consultarDealers();
$listadoEstados = State::listar();
$listadoRoles = Rol::listar();
$listadoItems = User::listar();

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'POST':
        switch ($_POST["opc"]) {
            case 'crear':
                $resultado = User::crear($_POST);
                echo json_encode($resultado);
                break;

            case 'editar':
                $resultado = User::editar($_POST);
                echo json_encode($resultado);
                break;

            case 'obtenerRoles':
                $usuarioid = isset($_POST['usuarioid']) ? $_POST['usuarioid'] : 0;
                $roles = User::obtenerRoles($usuarioid);
                echo json_encode([
                    'success' => true,
                    'roles' => $roles
                ]);
                break;
        }
        break;
}
?>