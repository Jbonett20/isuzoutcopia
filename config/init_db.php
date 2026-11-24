<?php
date_default_timezone_set('America/Bogota');

//include_once('datos.php');
loadEnv('../config/.env');
$host = getenv('HOST');

include_once('db.class.php');

DB::$user 		= getenv('USSER');
DB::$password 	= getenv('PASS');
DB::$dbName 	= getenv('DB');
DB::$host 		= getenv('HOST');
DB::$encoding = 'utf8'; // defaults to latin1 if omitted


function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        die("El archivo .env no existe en la ruta especificada.");
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Dividir la línea en clave y valor
        list($key, $value) = explode('=', $line, 2);
        // Eliminar espacios en blanco y comillas
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        // Cargar la variable en el entorno
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

if (!function_exists('currentUserRoleIds')) {
    function currentUserRoleIds(): array
    {
        if (empty($_SESSION['UsrData']) || !is_array($_SESSION['UsrData'])) {
            return [];
        }

        if (!empty($_SESSION['UsrData']['roles']) && is_array($_SESSION['UsrData']['roles'])) {
            $roles = [];
            foreach ($_SESSION['UsrData']['roles'] as $role) {
                if (is_array($role) && isset($role['rolid'])) {
                    $roles[] = (string) $role['rolid'];
                } elseif (is_object($role) && isset($role->rolid)) {
                    $roles[] = (string) $role->rolid;
                } elseif (is_scalar($role)) {
                    $roles[] = (string) $role;
                }
            }
            return array_values(array_unique($roles));
        }

        if (isset($_SESSION['UsrData']['rolid'])) {
            return [(string) $_SESSION['UsrData']['rolid']];
        }

        return [];
    }
}

if (!function_exists('userHasRole')) {
    function userHasRole($roleId): bool
    {
        $roleId = (string) $roleId;
        return in_array($roleId, currentUserRoleIds(), true);
    }
}

if (!function_exists('userHasAnyRole')) {
    function userHasAnyRole($roleIds): bool
    {
        foreach ((array) $roleIds as $roleId) {
            if (userHasRole($roleId)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('userIsAuthenticated')) {
    function userIsAuthenticated(): bool
    {
        return !empty(currentUserRoleIds());
    }
}
