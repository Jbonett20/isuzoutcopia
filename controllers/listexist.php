<?php
include_once("../admin/src/seguridad.php");
include_once("../models/listdetail.php");
if(isset($_GET['month']) && isset($_GET['dealerid']) && isset($_GET['typeorderid']) && isset($_GET['portid']) && isset($_GET['status'])){
    $listadoItems = ListaItems::consultarExistOrdes($_GET['dealerid'],$_GET['month'],$_GET['typeorderid'],$_GET['portid'],$_GET['status']);
    $mensajesPend = ListaItems::ConsultaMensajesPend();
}