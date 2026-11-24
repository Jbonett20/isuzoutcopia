<?php
include_once("../admin/src/seguridad.php");
include_once("../models/listdetail.php");
if(isset($_GET['month']) && isset($_GET['dealer'])){
    $listadoItems = ListaItems::consultarItems($_GET['dealer'],$_GET['month']);
}
