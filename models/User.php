<?php
@session_start();
class User
{
    public static function listar()
{
    include_once('../config/init_db.php');

    $sessionRoleIds = array_map(
        'intval',
        $_SESSION['UsrData']['rolids'] ?? []
    );

    $esAdmin = in_array(1, $sessionRoleIds, true);

    $dealerid = (int) ($_SESSION["UsrData"]["dealerid"] ?? 0);

    $condicion = $esAdmin
        ? "1"
        : "otu.dealerid = $dealerid";

    $sql = "SELECT DISTINCT
                otu.usuarioid,
                otu.dealerid,
                otu.nombres,
                otu.email,
                otu.creacion,
                aur.rolid,
                otu.estadoid,
                otu.ultimaconexion,
                otr.rol,
                ote.estado,
                COALESCE(otu.cargodealer, '') AS cargodealer,
                CASE
                    WHEN otp.bandera IS NOT NULL THEN 
                       CONCAT(
                            '<div style=\"display: flex; align-items: center;\">',
                            '<img src=\"../admin/img/country/', otp.bandera, '\" ',
                            'style=\"width:20px; margin-right:5px; cursor: pointer;\" ',
                            'title=\"', otp.pais, '\" ',
                            'alt=\"', otp.pais, '\"/>',
                            ' - ',
                            COALESCE(otd.dealer, 'GENERAL ADMIN'),
                            ' (',
                            otp.pais,
                            ')',
                            '</div>'
                        )
                    ELSE 
                        CONCAT(
                            '<div style=\"display: flex; align-items: center;\">',
                                '<img src=\"../admin/img/country/worldsa.svg\" ',
                                'style=\"width:20px; margin-right:5px; cursor: pointer;\" ',
                                'alt=\"All World\"/>',
                                ' - ',
                                COALESCE(otd.dealer, 'GENERAL ADMIN'),
                            '</div>'
                        )
                END AS dealer
            FROM
                ots_usuarios otu
            
            INNER JOIN ots_estados ote ON
                ote.estadoid = otu.estadoid

            LEFT JOIN ots_dealers otd ON
                otd.dealerid = otu.dealerid

            LEFT JOIN ots_paises otp ON
                otp.paisid = otd.paisid

            INNER JOIN adm_usuarioroles aur ON
                aur.usuarioid = otu.usuarioid   

            INNER JOIN ots_roles otr ON
                otr.rolid = aur.rolid 

            WHERE $condicion
            GROUP by otu.usuarioid
            ORDER BY
                otu.usuarioid DESC;
            ";
    $lista = DB::query($sql);

    foreach ($lista as &$user) {

        $roles = DB::query(
            "SELECT
                otr.rolid,
                otr.rol
             FROM adm_usuarioroles aur
             INNER JOIN ots_roles otr
                ON otr.rolid = aur.rolid
             WHERE aur.usuarioid = %i",
            $user['usuarioid']
        );

        $user['roles'] = $roles;
    }

    return $lista;
}

    public static function crear($data)
    {
        include_once('../config/init_db.php');

        $existeEmail = DB::queryFirstRow(
            "SELECT * FROM ots_usuarios WHERE email = %s",
            $data['email']
        );

        if ($existeEmail) {
            return [
                "success"    => false,
                "message"    => "Email already exists",
                "usuarioid"  => null
            ];
        }

        // Get the first role to store in the main table (for backward compatibility)
        $rolesArray = is_array($data['roles']) ? $data['roles'] : [$data['roles']];
        $primerRol = !empty($rolesArray) ? $rolesArray[0] : 1;

        $dealerid = isset($data['dealer']) && $data['dealer'] !== '' ? (int) $data['dealer'] : 0;

        $insertData = [
            'dealerid' => ($primerRol == 1 || $primerRol == 4) ? 0 : $dealerid,
            'nombres'    => $data['names'],
            'email'      => $data['email'],
            'contrasena'   => password_hash($data['password'], PASSWORD_DEFAULT),
            'cargodealer' => $data['cargodealer'],
            'estadoid'   => userHasRole(1) ? 1 : 2,
            'creacion'   => date('Y-m-d H:i:s')
        ];

        $result = DB::insert('ots_usuarios', $insertData);

        if ($result) {
            $usuarioid = DB::insertId();
            
            // Insert roles in the adm_usuarioroles table
            foreach ($rolesArray as $rolid) {
                DB::insert('adm_usuarioroles', [
                    'usuarioid' => $usuarioid,
                    'rolid' => $rolid
                ]);
            }
            
            return [
                "success"    => true,
                "message"    => "The user has been created.!",
                "usuarioid"  => $usuarioid
            ];
        } else {
            return [
                "success"    => false,
                "message"    => "Failed to create user",
                "usuarioid"  => null
            ];
        }
    }

    public static function editar($data)
    {
        include_once('../config/init_db.php');

        $usuario = DB::queryFirstRow(
            "SELECT * FROM ots_usuarios WHERE usuarioid = %i",
            $data['registroid']
        );

        if (!$usuario) {
            return [
                "success"    => false,
                "message"    => "User not found",
                "usuarioid"  => null
            ];
        }

        if ($usuario['email'] !== $data['email']) {
            $existeEmail = DB::queryFirstRow(
                "SELECT * FROM ots_usuarios WHERE email = %s AND usuarioid != %i",
                $data['email'],
                $data['registroid']
            );

            if ($existeEmail) {
                return [
                    "success"    => false,
                    "message"    => "Email already exists",
                    "usuarioid"  => null
                ];
            }
        }

        // Get the first role to store in the main table (for backward compatibility)
        $rolesArray = is_array($data['roles']) ? $data['roles'] : [$data['roles']];
        $primerRol = !empty($rolesArray) ? $rolesArray[0] : 1;

        $dealerid = isset($data['dealer']) && $data['dealer'] !== '' ? (int) $data['dealer'] : 0;

        $updateData = [
            'dealerid' => $primerRol == 1 ? 0 : $dealerid,
            'nombres'  => $data['names'],
            'email'    => $data['email'],
            'cargodealer' => $data['cargodealer']
        ];

        if (userHasRole('1')) {
            $updateData['estadoid'] = $data['estado'];
        }

        if (!empty($data['password'])) {
            $updateData['contrasena'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $result = DB::update('ots_usuarios', $updateData, "usuarioid = %i", $data['registroid']);

        if ($result !== false) {
            // Delete existing roles for this user
            DB::delete('adm_usuarioroles', "usuarioid = %i", $data['registroid']);
            
            // Insert new roles
            foreach ($rolesArray as $rolid) {
                DB::insert('adm_usuarioroles', [
                    'usuarioid' => $data['registroid'],
                    'rolid' => $rolid
                ]);
            }
            
            return [
                "success"    => true,
                "message"    => "The user has been updated.!",
                "usuarioid"  => $data['registroid']
            ];
        } else {
            return [
                "success"    => false,
                "message"    => "Failed to update user",
                "usuarioid"  => null
            ];
        }
    }

    public static function obtenerRoles($usuarioid)
    {
        include_once('../config/init_db.php');
        
        $roles = DB::query(
            "SELECT rolid FROM adm_usuarioroles WHERE usuarioid = %i",
            $usuarioid
        );
        
        return array_map(function($rol) {
            return $rol['rolid'];
        }, $roles);
    }
}