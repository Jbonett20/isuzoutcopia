<?php
@session_start();
class Holiday
{
    public static function listar()
    {
        include_once('../config/init_db.php');
        
        $sql = "SELECT of.*, op.pais, ou.nombres usuario, oe.estado FROM ots_festivos of 
                INNER JOIN ots_paises op ON op.paisid = of.paisid
                INNER JOIN ots_usuarios ou ON ou.usuarioid = of.usuarioid
                INNER JOIN ots_estados oe ON oe.estadoid = of.estadoid
                ORDER BY fecha DESC";


        $lista = DB::query($sql);
        return $lista;
    }

    public static function crear($data)
    {
        include_once('../config/init_db.php');
        $usuarioid = $_SESSION["UsrData"]["usuarioid"];
        $existeReg = DB::queryFirstRow(
            "SELECT * FROM ots_festivos WHERE paisid = %i AND fecha = %s",
            $data['country'],
            $data['fecha']
        );

        if ($existeReg) {
            return [
                "success"    => false,
                "message"    => "Holiday already exists",
                "usuarioid"  => null
            ];
        }

        $insertData = [
            'nombre'    => $data['holiday'],
            'fecha'      => $data['fecha'],
            'paisid'      => $data['country'],
            'estadoid'   => 1,
            'usuarioid'   => $usuarioid
        ];

        $result = DB::insert('ots_festivos', $insertData);

        if ($result) {
            return [
                "success"    => true,
                "message"    => "The holiday has been created.!",
                "usuarioid"  => DB::insertId()
            ];
        } else {
            return [
                "success"    => false,
                "message"    => "Failed to create holiday",
                "usuarioid"  => null
            ];
        }
    }

    public static function editar($data)
    {
        include_once('../config/init_db.php');
        $usuarioid = $_SESSION["UsrData"]["usuarioid"];

        $existe = DB::queryFirstRow(
            "SELECT * FROM ots_festivos WHERE festivoid = %i",
            $data['registroid']
        );

        if (!$existe) {
            return [
                "success"    => false,
                "message"    => "Holiday not found",
                "usuarioid"  => null
            ];
        }

        // Verificar si la nueva combinación de paisid y fecha ya existe en otro registro
        // Solo si se están cambiando el país o la fecha
        
        if ($existe['paisid'] != $data['country'] || $existe['fecha'] != $data['fecha']) {
            $existeDuplicado = DB::queryFirstRow(
                "SELECT * FROM ots_festivos WHERE paisid = %i AND fecha = %s AND festivoid != %i",
                $data['country'],
                $data['fecha'],
                $data['registroid']
            );

            if ($existeDuplicado) {
                return [
                    "success"    => false,
                    "message"    => "A holiday already exists for this country and date",
                    "usuarioid"  => null
                ];
            }
        }

        $updateData = [
            'nombre'    => $data['holiday'],
            'fecha'     => $data['fecha'],
            'paisid'    => $data['country'],
            'estadoid'  => $data['estado']

        ];

        $result = DB::update('ots_festivos', $updateData, "festivoid = %i", $data['registroid']);

        if ($result !== false) {
            return [
                "success"    => true,
                "message"    => "The holiday has been updated.!",
                "usuarioid"  => $data['registroid']
            ];
        } else {
            return [
                "success"    => false,
                "message"    => "Failed to update holiday",
                "usuarioid"  => null
            ];
        }
    }
}