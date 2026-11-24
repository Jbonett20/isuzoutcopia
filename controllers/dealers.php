<?php
include_once("../admin/src/seguridad.php");
include_once("../models/Dealer.php");
include_once("../models/home.php");
include_once("../models/State.php");
include_once("../models/Country.php");
include_once("../models/Intermediario.php");
$listadoDealers         = Home::consultarDealers();
$listadoEstados         = State::listar();
$listadoPaises          = Country::listar();
$listadoItems           = Dealer::listar();
$listadoIntermediario   = Intermediario::listar();

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'POST':
        switch ($_POST["opc"]) {
            case 'crear':
                $resultado = Dealer::crear($_POST);
                echo json_encode($resultado);
                break;

            case 'editar':
                $resultado = Dealer::editar($_POST);
                echo json_encode($resultado);
                break;
        }
        break;
}