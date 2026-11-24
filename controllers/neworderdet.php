<?php
if(isset($_POST['opcn']) && $_POST['opcn']=="SaveData" ){
    include_once("../admin/src/seguridad.php");
    include_once("../models/orders.php");
    $resGuardarOrden = Order::guardarOrden($_POST['items'],$_POST['prodmonthhome'],$_POST['orderhome'],$_POST['dealerhome'],$_POST['estadoOrden'],$_POST['typeorderhome'],$_POST['porthome']);
    echo json_encode(['status' => $resGuardarOrden]);
}else if(isset($_POST['opcn']) && $_POST['opcn']=="UpdateData" ){
    include_once("../admin/src/seguridad.php");
    include_once("../models/orders.php");
    $resGuardarOrden = Order::actualizarOrden($_POST['items'],$_POST['prodmonthhome'],$_POST['orderhome'],$_POST['dealerhome'],$_POST['estadoOrden'],$_POST['typeorderhome'],$_POST['porthome'],$_POST['ordenid']);
    echo json_encode(['status' => $resGuardarOrden]);
}else if(isset($_POST['opcn']) && $_POST['opcn']=="SendChange" ){
    include_once("../admin/src/seguridad.php");
    include_once("../models/orders.php");
    $resGuardarOrden = Order::enviaMensaje($_POST['statusid'],$_POST['note'],$_POST['visible'],$_POST['ordenid'],$_POST['orderhome']);
    echo json_encode(['status' => $resGuardarOrden]);
}else if(isset($_POST['opcn']) && $_POST['opcn']=="DeleteOrder" ){
    include_once("../admin/src/seguridad.php");
    include_once("../models/orders.php");
    $resGuardarOrden = Order::borrarOrden($_POST['ordenid']);
    echo json_encode(['status' => $resGuardarOrden]);
}else if(isset($_POST['opcn']) && $_POST['opcn']=="SendReadNote" ){
    include_once("../admin/src/seguridad.php");
    include_once("../models/orders.php");
    $resGuardarOrden = Order::marcarMensaje($_POST['ordennotaid']);
    echo json_encode(['status' => $resGuardarOrden]);
}else{
    include_once("../admin/src/seguridad.php");
    include_once("models/home.php");
    $listadoMeses = Home::consultarMeses();
    $listadoDealers = Home::consultarDealers();
    $listadoTiposOrdenes = Home::consultarTiposOrdenes(0);
    $listadoPuertos = Home::consultarPuertos(0);
}