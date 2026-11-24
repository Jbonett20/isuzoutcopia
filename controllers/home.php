<?php
include_once("../models/home.php");
$listadoEstados = Home::consultarEstados();
$listadoMeses = Home::consultarMeses();
$listadoDealers = Home::consultarDealers();
if(isset($_GET['dealerid']) && isset($_GET['monthid'])){

    $listadoOrdenes = Home::consultarOrdenes($_GET['dealerid'],$_GET['monthid']);
    //print_r($listadoOrdenes);
}
$mensajesPend = Home::ConsultaMensajesPend();