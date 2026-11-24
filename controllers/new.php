<?php
include_once("../admin/src/seguridad.php");
include_once("../models/home.php");
$listadoMeses = Home::consultarMeses();
$listadoDealers = Home::consultarDealers();
$listadoTiposOrdenes = Home::consultarTiposOrdenes(0);
$listadoPuertos = Home::consultarPuertos(0);
