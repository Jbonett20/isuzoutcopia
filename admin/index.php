<?php
@session_start();

if (!isset($_SESSION['UsrData'])) {
    header("Location: login");
    exit;
}

/*
|--------------------------------------------------------------------------
| Controlador principal
|--------------------------------------------------------------------------
*/

include_once("../controllers/sidebar.php");

/*
|--------------------------------------------------------------------------
| Redirección inteligente
|--------------------------------------------------------------------------
|
| Decide automáticamente a qué módulo enviar
| según los permisos del usuario.
|
*/


header("Location: " . $homeHref);

exit;