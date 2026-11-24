<?php
@session_start();
class Kpidata
{
   public static function listarEmpresas()
{
    include_once('../config/init_db.php');

    try {

        $roleIds = array_map(
            'intval',
            $_SESSION['UsrData']['rolids'] ?? []
        );

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad si aún existe rolid viejo
        |--------------------------------------------------------------------------
        */

        if (empty($roleIds) && !empty($_SESSION['UsrData']['rolid'])) {
            $roleIds = [(int) $_SESSION['UsrData']['rolid']];
        }

        $dealerLogin = (int) ($_SESSION['UsrData']['dealerid'] ?? 0);

        $isAdmin = in_array(1, $roleIds, true);

        $sql = "SELECT 
                    otd.dealerid,
                    otd.dealer,
                    otp.pais,
                    otp.bandera
                FROM
                    ots_dealers otd
                INNER JOIN ots_paises otp 
                    ON otp.paisid = otd.paisid
                INNER JOIN ots_estados ote 
                    ON ote.estadoid = otd.estadoid
                WHERE 
                    otd.estadoid = 1";

        /*
        |--------------------------------------------------------------------------
        | Usuarios normales solo ven su dealer
        |--------------------------------------------------------------------------
        */

        if (!$isAdmin) {

            $sql .= " AND otd.dealerid = %i
                      ORDER BY otd.dealer ASC";

            return DB::query($sql, $dealerLogin);
        }

        /*
        |--------------------------------------------------------------------------
        | Admin ve todos
        |--------------------------------------------------------------------------
        */

        $sql .= " ORDER BY otd.dealer ASC";

        return DB::query($sql);

    } catch (Exception $e) {

        error_log(
            "Error en listarEmpresas: " . $e->getMessage()
        );

        return [];
    }
}


                    public static function getYears()
    {
        include_once('../config/init_db.php');

        try {
            $sql = "SELECT
                        ano,
                        (ano = YEAR(CURDATE())) AS is_current
                    FROM
                        kpi_calendarios
                    GROUP BY
                        ano
                    ORDER BY
                        ano DESC";

            $lista = DB::query($sql);
            return $lista;
        } catch (Exception $e) {
            error_log("Error en getYears: " . $e->getMessage());
            return [];
        }
    }

    public static function getYearsMonth()
    {
        include_once('../config/init_db.php');

        try {
            $sql = "SELECT
                        calendarioid,
                        CONCAT(ano, ' - ', mes) AS yearmonth,
                        (ano = YEAR(CURDATE()) AND mes = MONTHNAME(CURDATE())) AS is_current
                    FROM
                        kpi_calendarios
                    ORDER BY
                        ano,
                        mes";

            $lista = DB::query($sql);
            return $lista;
        } catch (Exception $e) {
            // Puedes registrar el error o retornarlo
            error_log("Error en getYearsMonth: " . $e->getMessage());
            return []; // o return false; según cómo manejes errores en tu app
        }
    }

    public static function saveKpiData($kpiType, $dealerId, $yearMonth, $data)
    {
        include_once('../config/init_db.php');

        try {
            // Mapeo de tipos KPI a nombres de tabla
            $tableMapping = [
                'sales' => 'kpi_sales',
                'workshop' => 'kpi_workshopsales',
                'service' => 'kpi_serviceorders',
                'vehicles' => 'kpi_vechiclesserviced',
                'labor' => 'kpi_laborrates',
                'personnel' => 'kpi_personnel',
                'parts' => 'kpi_parts'
            ];

            if (!isset($tableMapping[$kpiType])) {
                throw new Exception("Invalid KPI type: $kpiType");
            }

            $tableName = $tableMapping[$kpiType];

            // Verificar si ya existe un registro
            $existingRecord = DB::queryFirstRow(
                "SELECT * FROM $tableName WHERE dealerid = %i AND calendarioid = %i",
                $dealerId,
                $yearMonth
            );

            if ($existingRecord) {
                // Actualizar registro existente
                $updateFields = [];
                $updateValues = [];

                foreach ($data as $field => $value) {
                    $updateFields[] = "$field = %i";
                    $updateValues[] = $value;
                }

                // Agregar campos de auditoría para UPDATE
                $updateFields[] = "usuarioid = %i";
                $updateValues[] = $_SESSION['UsrData']['usuarioid'] ?? 0;

                $updateFields[] = "fecha = NOW()";

                if (!empty($updateFields)) {
                    $sql = "UPDATE $tableName SET " . implode(', ', $updateFields) .
                        " WHERE dealerid = %i AND calendarioid = %i";

                    $updateValues[] = $dealerId;
                    $updateValues[] = $yearMonth;

                    DB::query($sql, ...$updateValues);
                }
            } else {
                // Insertar nuevo registro
                $fields = ['dealerid', 'calendarioid', 'usuarioid', 'fecha'];
                $values = [$dealerId, $yearMonth, $_SESSION['UsrData']['usuarioid'] ?? 0];
                $placeholders = ['%i', '%i', '%i', 'NOW()'];

                foreach ($data as $field => $value) {
                    $fields[] = $field;
                    $values[] = $value;
                    $placeholders[] = '%i';
                }

                $sql = "INSERT INTO $tableName (" . implode(', ', $fields) .
                    ") VALUES (" . implode(', ', $placeholders) . ")";

                DB::query($sql, ...$values);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error en saveKpiData: " . $e->getMessage());
            throw $e; // Re-throw para que el controlador pueda manejarlo
        }
    }

              public static function getKpiData($kpiType, $dealerId = null, $year = null)
{
    include_once('../config/init_db.php');

    // Meses del año en orden (MAYÚSCULAS para coincidir con kpi_calendarios)
    $monthNames = [
        'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
        'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
    ];

    try {

        $roleIds = array_map(
            'intval',
            $_SESSION['UsrData']['rolids'] ?? []
        );

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad con sistema viejo
        |--------------------------------------------------------------------------
        */

        if (empty($roleIds) && !empty($_SESSION['UsrData']['rolid'])) {
            $roleIds = [(int) $_SESSION['UsrData']['rolid']];
        }

        $dealerLogin = (int) ($_SESSION['UsrData']['dealerid'] ?? 0);

        $isAdmin = in_array(1, $roleIds, true);

        $tableMapping = [
            'sales'      => 'kpi_sales',
            'workshop'   => 'kpi_workshopsales',
            'service'    => 'kpi_serviceorders',
            'vehicles'   => 'kpi_vechiclesserviced',
            'labor'      => 'kpi_laborrates',
            'personnel'  => 'kpi_personnel',
            'parts'      => 'kpi_parts'
        ];

        if (!isset($tableMapping[$kpiType])) {
            throw new Exception("Invalid KPI type: $kpiType");
        }

        $tableName = $tableMapping[$kpiType];

        /*
        |--------------------------------------------------------------------------
        | Determinar dealers a consultar (según seguridad y filtro)
        |--------------------------------------------------------------------------
        */

        if (!$isAdmin) {
            // Usuario normal: solo su dealer
            $dealers = DB::query(
                "SELECT dealerid, dealer FROM ots_dealers WHERE dealerid = %i",
                $dealerLogin
            );
        } else {
            // Admin: puede filtrar un dealer o traer todos
            if (!empty($dealerId)) {
                $dealers = DB::query(
                    "SELECT dealerid, dealer FROM ots_dealers WHERE dealerid = %i",
                    (int) $dealerId
                );
            } else {
                $dealers = DB::query(
                    "SELECT dealerid, dealer FROM ots_dealers WHERE estadoid = 1 ORDER BY dealer ASC"
                );
            }
        }

                                        if (empty($dealers)) {
            return [];
        }

        // Determinar año a consultar (default: año actual)
        if (empty($year)) {
            $year = date('Y');
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener los calendarioids de los 12 meses del año seleccionado
        |--------------------------------------------------------------------------
        */

        $calendars = DB::query(
            "SELECT calendarioid, ano, mes FROM kpi_calendarios WHERE ano = %i",
            (int) $year
        );

        // Mapear calendarioid por mes (normalizado a mayúsculas)
        $calendarByMonth = [];
        foreach ($calendars as $cal) {
            $calendarByMonth[strtoupper($cal['mes'])] = $cal['calendarioid'];
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener datos existentes del KPI para ese año y dealers
        |--------------------------------------------------------------------------
        */

        $existing = [];
        if ($year) {
            $params = [(int) $year];
            $dealerList = array_column($dealers, 'dealerid');
            $inClause = implode(',', array_fill(0, count($dealerList), '%i'));
            $params = array_merge($params, $dealerList);

            $sqlData = "SELECT k.*, c.ano, c.mes, u.nombres
                        FROM $tableName k
                        INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                        LEFT JOIN ots_usuarios u ON u.usuarioid = k.usuarioid
                        WHERE c.ano = %i
                        AND k.dealerid IN ($inClause)";

            $existing = DB::query($sqlData, ...$params);
        }

                // Indexar por "dealerid_mes" (mes normalizado a mayúsculas)
        $dataByKey = [];
        foreach ($existing as $row) {
            $dataByKey[$row['dealerid'] . '_' . strtoupper($row['mes'])] = $row;
        }

                /*
        |--------------------------------------------------------------------------
        | Construir resultado SOLO con registros existentes en la base de datos.
        | No se generan filas vacías (en cero) para meses sin datos.
        |--------------------------------------------------------------------------
        */
        $result = [];
        $counter = 0;

        foreach ($existing as $row) {
            $counter++;
            $month = strtoupper($row['mes']);
            $calId = $row['calendarioid'];
            $dealerName = $row['dealer'] ?? null;

            // Si no tenemos el nombre del dealer, buscarlo en la lista de dealers
            if (empty($dealerName)) {
                foreach ($dealers as $d) {
                    if ($d['dealerid'] == $row['dealerid']) {
                        $dealerName = $d['dealer'];
                        break;
                    }
                }
            }

            $base = [
                'dealerid'      => $row['dealerid'],
                'dealer'        => $dealerName,
                'calendarioid'  => $calId,
                'ano'           => $year,
                'mes'           => $month,
                'yearmonth'     => "$year - $month",
                'nombres'       => $row['nombres'] ?? null,
                'fecha'         => $row['fecha'] ?? null,
            ];

            // Los campos KPI dinámicos se obtienen del registro existente
            foreach ($row as $k => $v) {
                if (!array_key_exists($k, $base) && $k !== 'usuarioid') {
                    $base[$k] = $v;
                }
            }

            $result[] = $base;
        }

        return $result;

    } catch (Exception $e) {

        error_log(
            "Error en getKpiData: " . $e->getMessage()
        );

        throw $e;
    }
}

    public static function getCalendarIdByText($calendarText)
    {
        include_once('../config/init_db.php');

        try {
            // Limpiar el texto
            $cleanText = trim($calendarText);

            // Intentar búsqueda exacta: "2024 - January"
            $calendar = DB::queryFirstRow(
                "SELECT calendarioid 
                 FROM kpi_calendarios 
                 WHERE CONCAT(ano, ' - ', mes) = %s 
                 LIMIT 1",
                $cleanText
            );

            if ($calendar) {
                return $calendar['calendarioid'];
            }

            // Intentar sin espacios: "2024-January"
            $calendar = DB::queryFirstRow(
                "SELECT calendarioid 
                 FROM kpi_calendarios 
                 WHERE CONCAT(ano, '-', mes) = %s 
                 LIMIT 1",
                $cleanText
            );

            if ($calendar) {
                return $calendar['calendarioid'];
            }

            // Intentar formato inverso: "January - 2024" o "January-2024"
            $parts = preg_split('/\s*-\s*/', $cleanText);
            if (count($parts) == 2) {
                $calendar = DB::queryFirstRow(
                    "SELECT calendarioid 
                     FROM kpi_calendarios 
                     WHERE ano = %s AND mes = %s 
                     LIMIT 1",
                    $parts[1],
                    $parts[0]
                );

                if ($calendar) {
                    return $calendar['calendarioid'];
                }

                // Probar al revés
                $calendar = DB::queryFirstRow(
                    "SELECT calendarioid 
                     FROM kpi_calendarios 
                     WHERE ano = %s AND mes = %s 
                     LIMIT 1",
                    $parts[0],
                    $parts[1]
                );

                if ($calendar) {
                    return $calendar['calendarioid'];
                }

                // No se encontró - Intentar crear nuevo registro
                return self::createCalendarFromText($cleanText);
            }

            // No se pudo parsear el formato
            error_log("Formato de calendario inválido: $cleanText. Use formato: YEAR - MONTH (e.g., 2024 - January)");
            return null;
        } catch (Exception $e) {
            error_log("Error en getCalendarIdByText: " . $e->getMessage());
            return null;
        }
    }

    public static function createCalendarFromText($calendarText)
    {
        include_once('../config/init_db.php');

        try {
            $cleanText = trim($calendarText);

            // Dividir por guión
            $parts = preg_split('/\s*-\s*/', $cleanText);

            if (count($parts) != 2) {
                error_log("Formato de calendario inválido: $cleanText. Use formato: YEAR - MONTH");
                return null;
            }

            // Determinar cuál parte es año y cuál es mes
            $ano = null;
            $mes = null;

            // Verificar si el primer elemento es un año (4 dígitos numéricos)
            if (is_numeric($parts[0]) && strlen($parts[0]) == 4) {
                $ano = $parts[0];
                $mes = trim($parts[1]);
            } elseif (is_numeric($parts[1]) && strlen($parts[1]) == 4) {
                $ano = $parts[1];
                $mes = trim($parts[0]);
            } else {
                error_log("No se pudo identificar el año en: $cleanText");
                return null;
            }

            // Validar que el mes no esté vacío
            if (empty($mes)) {
                error_log("Mes vacío en: $cleanText");
                return null;
            }

            // Crear el nuevo calendario
            DB::insert('kpi_calendarios', [
                'ano' => $ano,
                'mes' => $mes
            ]);

            $newCalendarId = DB::insertId();

            error_log("Calendario creado: $ano - $mes (ID: $newCalendarId)");

            return $newCalendarId;
        } catch (Exception $e) {
            error_log("Error en createCalendarFromText: " . $e->getMessage());
            return null;
        }
    }

    public static function deleteKpiData($kpiType, $dealerId, $yearMonth)
    {
        include_once('../config/init_db.php');

        try {
            // Mapeo de tipos KPI a nombres de tabla
            $tableMapping = [
                'sales' => 'kpi_sales',
                'workshop' => 'kpi_workshopsales',
                'service' => 'kpi_serviceorders',
                'vehicles' => 'kpi_vechiclesserviced',
                'labor' => 'kpi_laborrates',
                'personnel' => 'kpi_personnel',
                'parts' => 'kpi_parts'
            ];

            if (!isset($tableMapping[$kpiType])) {
                throw new Exception("Invalid KPI type: $kpiType");
            }

            $tableName = $tableMapping[$kpiType];

            // Eliminar el registro
            DB::query(
                "DELETE FROM $tableName WHERE dealerid = %i AND calendarioid = %s",
                $dealerId,
                $yearMonth
            );

                        return true;
        } catch (Exception $e) {
            error_log("Error en deleteKpiData: " . $e->getMessage());
            throw $e;
        }
    }
}
