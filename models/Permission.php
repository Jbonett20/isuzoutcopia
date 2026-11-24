<?php
class Permission
{
    public static function listar()
    {
        include_once('../config/init_db.php');
        return DB::query(
            "SELECT permisoid, codigo, nombre, descripcion, seccion, icono, url
             FROM adm_permisos
             WHERE activo = 1
             ORDER BY seccion, nombre"
        );
    }

    public static function listarCodigosActivos()
    {
        include_once('../config/init_db.php');
        return array_column(DB::query("SELECT codigo FROM adm_permisos WHERE activo = 1"), 'codigo');
    }

    public static function obtenerCodigosPermitidos(array $roleIds)
    {
        if (in_array(1, $roleIds, true)) {
            return self::listarCodigosActivos();
        }

        return self::listarPermisosPorRoles($roleIds);
    }

    public static function listarPermisosPorRol(int $rolid)
    {
        include_once('../config/init_db.php');

        $rows = DB::query(
            "SELECT p.codigo
             FROM adm_permisos p
             INNER JOIN adm_rolpermisos rp ON rp.permisoid = p.permisoid
             WHERE rp.rolid = %i
               AND p.activo = 1
             ORDER BY p.seccion, p.nombre",
            $rolid
        );

        return array_column($rows, 'codigo');
    }

    public static function listarPermisosPorRoles(array $roleIds)
    {
        include_once('../config/init_db.php');

        if (empty($roleIds)) {
            return [];
        }

        $rows = DB::query(
            "SELECT DISTINCT p.codigo
             FROM adm_permisos p
             INNER JOIN adm_rolpermisos rp ON rp.permisoid = p.permisoid
             WHERE rp.rolid IN %li
               AND p.activo = 1",
            $roleIds
        );

        return array_column($rows, 'codigo');
    }

    public static function guardarPermisos(int $rolid, array $permissionCodes)
{
    include_once('../config/init_db.php');

    try {

        $permissionCodes = array_values(array_filter(array_map(function ($code) {

            if (is_scalar($code)) {
                return trim((string) $code);
            }

            return null;

        }, $permissionCodes)));

        /*
        |--------------------------------------------------------------------------
        | Eliminar permisos actuales
        |--------------------------------------------------------------------------
        */

        $deleteResult = DB::delete(
            'adm_rolpermisos',
            'rolid = %i',
            $rolid
        );

        if ($deleteResult === false) {

            return [
                "success" => false,
                "message" => "Failed to remove previous permissions."
            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Si no enviaron permisos, terminar correctamente
        |--------------------------------------------------------------------------
        */

        if (empty($permissionCodes)) {

            return [
                "success" => true,
                "message" => "Permissions updated successfully."
            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Buscar permisos válidos
        |--------------------------------------------------------------------------
        */

        $placeholderCount = count($permissionCodes);

        $placeholders = implode(
            ', ',
            array_fill(0, $placeholderCount, '%s')
        );

        $queryArgs = array_merge([
            "SELECT permisoid, codigo
             FROM adm_permisos
             WHERE codigo IN ($placeholders)
               AND activo = 1"
        ], $permissionCodes);

        $permisos = call_user_func_array(
            ['DB', 'query'],
            $queryArgs
        );

        $validMap = [];

        foreach ($permisos as $permiso) {

            $validMap[$permiso['codigo']] = $permiso['permisoid'];

        }

        /*
        |--------------------------------------------------------------------------
        | Insertar permisos
        |--------------------------------------------------------------------------
        */

        foreach ($permissionCodes as $code) {

            if (!isset($validMap[$code])) {
                continue;
            }

            $insertResult = DB::insert('adm_rolpermisos', [
                'rolid' => $rolid,
                'permisoid' => $validMap[$code]
            ]);

            if ($insertResult === false) {

                return [
                    "success" => false,
                    "message" => "Failed to save permissions."
                ];

            }

        }

        return [
            "success" => true,
            "message" => "Permissions saved successfully."
        ];

    } catch (Exception $e) {

        return [
            "success" => false,
            "message" => $e->getMessage()
        ];

    }
}
}
