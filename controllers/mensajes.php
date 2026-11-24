<?php
include_once("../admin/src/seguridad.php");
if(isset($_GET['orderid'])){
    include_once("../models/orders.php");
    $detalleMensajes = Order::traerMensajes($_GET['orderid']);
}
