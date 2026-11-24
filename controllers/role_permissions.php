<?php
include_once "../admin/src/seguridad.php";
include_once "../models/Rol.php";
include_once "../models/Permission.php";

$allRoles = Rol::listar();
$roles    = array_values(array_filter($allRoles, function ($rol) {
    return intval($rol['rolid']) !== 1;
}));
$selectedRoleId = isset($_GET['rolid']) ? intval($_GET['rolid']) : 0;
if ($selectedRoleId === 1 || $selectedRoleId === 0) {
    $selectedRoleId = count($roles) > 0 ? intval($roles[0]) : 0;
}

$message     = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opc']) && $_POST['opc'] === 'guardarPermisos') {
    $selectedRoleId = isset($_POST['rolid']) ? intval($_POST['rolid']) : 0;
    $permisos       = isset($_POST['permisos']) ? $_POST['permisos'] : [];

    if ($selectedRoleId < 1) {
        $message     = 'Rol inválido. Seleccione un rol válido.';
        $messageType = 'danger';
    } else {
        $result = Permission::guardarPermisos(
            $selectedRoleId,
            $permisos
        );

        $message = $result['message'];

        $messageType = $result['success']
            ? 'success'
            : 'danger';
    }

    $redirectUrl = 'role_permissions.php?rolid=' . $selectedRoleId . '&msg=' . urlencode($message) . '&msgtype=' . urlencode($messageType);
    header('Location: ' . $redirectUrl);
    exit;
}

if (isset($_GET['msg'])) {
    $message     = $_GET['msg'];
    $messageType = isset($_GET['msgtype']) ? $_GET['msgtype'] : 'success';
}

$permissions          = Permission::listar();
$selectedCodes        = $selectedRoleId ? Permission::listarPermisosPorRol($selectedRoleId) : [];
$permissionsBySection = [];
foreach ($permissions as $permission) {
    $section = $permission['seccion'] ?: 'general';
    if (! isset($permissionsBySection[$section])) {
        $permissionsBySection[$section] = [];
    }
    $permissionsBySection[$section][] = $permission;
}
