<?php
include_once("../admin/src/seguridad.php");
include_once("../models/home.php");
$listadoMeses = Home::consultarMeses();
$listadoDealers = Home::consultarDealers();
$listadoTiposOrdenes = Home::consultarTiposOrdenes(0);
$listadoPuertos = Home::consultarPuertos(0);
$listadoEstados = Home::consultarEstadosAll();

if(isset($_GET['orderid']) && $_GET['orderid']>0){
    $DatosOrden = Home::consultarOrden($_GET['orderid']);
    $DetalleOrden = Home::consultaDetalle($_GET['orderid']);
}