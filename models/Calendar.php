<?php
@session_start();
class Calendar
{
    public static function listar()
    {
        include_once('../config/init_db.php');
        $sql = "SELECT
                    otc.*,
                    ote.estado
                FROM
                    ots_calendarios otc
                INNER JOIN ots_estados ote ON
                    ote.estadoid = otc.estadoid
                ORDER BY
                    ano,
                    mesnum ASC;";

        $lista = DB::query($sql);
        return $lista;
    }

    public static function listCalendar()
    {
        include_once('../config/init_db.php');
        
        $paises = !empty($_SESSION["DatLoc"]["paisid"])
            ? $_SESSION["DatLoc"]["paisid"] == 24 ? $_SESSION["DatLoc"]["paisid"] : $_SESSION["DatLoc"]["paisid"] . ", 24"
            : "24";

        $sql = "(SELECT
                            CONCAT(otc.ano, '-', otc.mes, ' Production') COLLATE utf8mb4_unicode_ci AS title,
                            '#ff0000' as 'color',
                            DATE_FORMAT(otc.inicia, '%Y-%m-%dT%H:%i:%s') AS inicia,
                            DATE_FORMAT(otc.finaliza, '%Y-%m-%dT%H:%i:%s') AS finaliza
                        FROM ots_calendarios otc
                        INNER JOIN ots_estados ote ON ote.estadoid = otc.estadoid
                        WHERE ote.estadoid = 1
                        ORDER BY ano, mesnum ASC
                    )
                    UNION
                    (
                        SELECT
                            CONCAT(of.nombre, ' - ', op.pais) COLLATE utf8mb4_unicode_ci AS title,
                            '#c1c1c1' as 'color',
                            CONCAT(of.fecha, ' 00:00:00') AS inicia,
                            CONCAT(of.fecha, ' 23:59:59') AS finaliza
                        FROM ots_festivos of
                        INNER JOIN ots_paises op ON op.paisid = of.paisid
                        WHERE op.paisid IN($paises)
                    );";
        $lista = DB::query($sql);
        $data = ["success" => "true", "message" => "", "eventos" => $lista];
        return $data;
    }

    public static function crear($data)
    {
        include_once('../config/init_db.php');
        if (!userHasRole(1)) {
            return [
                "success" => false,
                "message" => "You are not authorized to use this role.",
                "calendarioid" => null
            ];
        }
        $year = $data['year'];
        $month = $data['month'];
        $meses = [
            "",
            "JAN",
            "FEB",
            "MAR",
            "APR",
            "MAY",
            "JUN",
            "JUL",
            "AUG",
            "SEP",
            "OCT",
            "NOV",
            "DEC"
        ];
        $yearShort = substr($year, -2);
        $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
        $mesano = $yearShort . $monthFormatted;
        $nombreMes = isset($meses[intval($month)]) ? $meses[intval($month)] : '';
        $existeCalendario = DB::queryFirstRow(
            "SELECT * FROM ots_calendarios WHERE ano = %s AND mes = %s",
            $year,
            $nombreMes,
        );

        if (!empty($existeCalendario)) {
            return [
                "success" => false,
                "message" => "A calendar entry for this month already exists.",
                "calendarioid" => null
            ];
        }

        $datos = [
            'ano'        => $year,
            'mes'        => strtoupper($nombreMes),
            'mesnum'     => $month,
            'mesano'     => $mesano,
            'inicia'     => $data['inicia'],
            'finaliza'   => $data['finaliza'],
            'estadoid'   => 1,
            'usuarioid'  => $_SESSION["UsrData"]["usuarioid"],
            'fechamod'   => date('Y-m-d H:i:s')
        ];

        $result = DB::insert('ots_calendarios', $datos);

        if ($result) {
            return [
                "success" => true,
                "message" => "The calendar entry has been created.",
                "calendarioid" => DB::insertId()
            ];
        } else {
            return [
                "success" => false,
                "message" => "Failed to create calendar entry.",
                "calendarioid" => null
            ];
        }
    }


    public static function editar($data)
    {
        include_once('../config/init_db.php');
        if (!userHasRole(1)) {
            return [
                "success" => false,
                "message" => "You are not authorized to use this role.",
                "calendarioid" => null
            ];
        }
        $year = $data['year'];
        $month = $data['month'];
        $meses = [
            "",
            "JAN",
            "FEB",
            "MAR",
            "APR",
            "MAY",
            "JUN",
            "JUL",
            "AUG",
            "SEP",
            "OCT",
            "NOV",
            "DEC"
        ];
        $yearShort = substr($year, -2);
        $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
        $mesano = $yearShort . $monthFormatted;
        $nombreMes = isset($meses[intval($month)]) ? $meses[intval($month)] : '';
        $existeCalendario = DB::queryFirstRow(
            "SELECT * FROM ots_calendarios WHERE ano = %s AND mes = %s AND calendarioid != %i",
            $year,
            $nombreMes,
            $data['registroid']
        );


        if (!empty($existeCalendario)) {
            return [
                "success" => false,
                "message" => "A calendar entry for this month already exists.",
                "calendarioid" => null
            ];
        }

        $result = DB::update('ots_calendarios', [
            'ano'        => $year,
            'mes'        => strtoupper($nombreMes),
            'mesnum'     => $month,
            'mesano'     => $mesano,
            'inicia'     => $data['inicia'],
            'finaliza'   => $data['finaliza'],
            'estadoid'   => $data['estado'],
            'usuarioid'  => $_SESSION["UsrData"]["usuarioid"],
            'fechamod'   => date('Y-m-d H:i:s')
        ], "calendarioid=%i", $data['registroid']);

        if ($result) {
            return [
                "success" => true,
                "message" => "The calendar entry has been updated.",
                "calendarioid" => $data['registroid']
            ];
        } else {
            return [
                "success" => false,
                "message" => "Failed to update calendar entry.",
                "calendarioid" => null
            ];
        }
    }
}