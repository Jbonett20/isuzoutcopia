<?php

include_once("../admin/src/seguridad.php");
include_once("../models/home.php");
include_once("../models/Permission.php");

$inactivosUs = Home::ConsultaCantInactivos();
$mensajesPend = Home::ConsultaMensajesPend();

/*
|--------------------------------------------------------------------------
| Roles de sesión
|--------------------------------------------------------------------------
*/

$sessionRoleIds = array_map(
    'intval',
    $_SESSION['UsrData']['rolids'] ?? []
);

if (empty($sessionRoleIds) && !empty($_SESSION['UsrData']['rolid'])) {
    $sessionRoleIds = [
        (int) $_SESSION['UsrData']['rolid']
    ];
}

/*
|--------------------------------------------------------------------------
| Helper roles
|--------------------------------------------------------------------------
*/

$hasRole = function ($ids) use ($sessionRoleIds) {

    $ids = is_array($ids)
        ? $ids
        : [$ids];

    return (bool) count(
        array_intersect(
            $sessionRoleIds,
            array_map('intval', $ids)
        )
    );
};

/*
|--------------------------------------------------------------------------
| Permisos permitidos
|--------------------------------------------------------------------------
*/

$allowedPermissionCodes = Permission::obtenerCodigosPermitidos(
    $sessionRoleIds
);



$can = function ($code) use (
    $allowedPermissionCodes,
    $hasRole
) {

    /*
    |--------------------------------------------------------------------------
    | Admin acceso total
    |--------------------------------------------------------------------------
    */

    if ($hasRole(1)) {
        return true;
    }

    return in_array(
        $code,
        $allowedPermissionCodes,
        true
    );
};

/*
|--------------------------------------------------------------------------
| Menú HOME KPI
|--------------------------------------------------------------------------
*/

$permisosHomeBi = [
    'dashboard',
    'sales',
    'partssalesperformance',
    'unitsoperation',
    'informationstatus',
    'indexreport',
    'partssalesbychannel',
    'workshopreport',
    'inventoryreport',
    'rankingreport',
    'fillratebo'
];

$usaMenuBi = $hasRole(1) || (bool) count(
    array_intersect(
        $allowedPermissionCodes,
        $permisosHomeBi
    )
);
/*
|--------------------------------------------------------------------------
| Home dinámico
|--------------------------------------------------------------------------
|
| Busca el primer módulo permitido.
|
*/

$homeHref = "../admin/login";

/*
|--------------------------------------------------------------------------
| Prioridad 1
| Home OTS
|--------------------------------------------------------------------------
*/

if ($can('overview')) {
    $homeHref = "../admin/overview.php";

}


/*
|--------------------------------------------------------------------------
| Prioridad 2
| Home KPI
|--------------------------------------------------------------------------
*/

elseif ($usaMenuBi) {

    $biRoutes = [

    'dashboard'             => '../admin/dashboard.php',
    'sales'                 => '../admin/dashboardSales.php',
    'partssalesperformance' => '../admin/partSalesPerformance.php',
    'unitsoperation'        => '../admin/unitsoperation.php',
    'informationstatus'     => '../admin/informationstatus.php',
    'indexreport'           => '../admin/indexReport.php',
    'partssalesbychannel'   => '../admin/partsSalesByChannel.php',
    'workshopreport'        => '../admin/workshopReport.php',
    'inventoryreport'       => '../admin/inventoryReport.php',
    'rankingreport'         => '../admin/rankingReport.php',
    'fillratebo'            => '../admin/fillRateBO.php',
];

    foreach ($biRoutes as $permission => $route) {

        if ($can($permission)) {

            $homeHref = $route;

            break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Prioridad 3
| Primer módulo disponible
|--------------------------------------------------------------------------
*/

else {

  $fallbackRoutes = [

    'new'          => '../admin/new.php',
    'exist'        => '../admin/exist.php',
    'list'         => '../admin/listitems.php',
    'calendar'     => '../admin/calendar.php',
    'master'       => '../admin/master.php',
    'dealer'       => '../admin/dealers.php',
    'holiday'      => '../admin/holidays.php',
    'dealertarget' => '../admin/TargetxDealer.php',
    'kpidata'      => '../admin/kpidata.php',
    'user'         => '../admin/users.php',
];

    foreach ($fallbackRoutes as $permission => $route) {

        if ($can($permission)) {

            $homeHref = $route;

            break;
        }
    }
}
