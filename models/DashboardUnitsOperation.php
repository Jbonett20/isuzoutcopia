<?php
/*
|--------------------------------------------------------------------------
| DASHBOARD DE UNIDADES EN OPERACIÓN (admin/unitsoperation.php)
|--------------------------------------------------------------------------
| Modelo auto-contenido del tablero de UIO (parque de vehículos en operación).
|
| Métricas (ver design.md del cambio units-in-operation):
|   - UIO 3 años     = SUM(unidades) en la ventana de 3 años que termina en (añoRef, mesRef)
|   - UIO 5 años     = SUM(unidades) en la ventana de 5 años que termina en (añoRef, mesRef)
|   - UIO Total      = UIO 5 años (horizonte definido)
|   - Crecimiento 3a = (UIO 3a - UIO 3a hace 3 años) / UIO 3a hace 3 años * 100
|   - Crecimiento 5a = (UIO 5a - UIO 5a hace 5 años) / UIO 5a hace 5 años * 100
|   - Mix LCV        = lcv / (cv + lcv) * 100 dentro del horizonte
|
| Fuentes: kpi_sales (unitsalescv, unitsaleslcv, allunitsales), kpi_calendarios,
|          ots_dealers/ots_paises.
|--------------------------------------------------------------------------
*/

@session_start();

if (!class_exists('DashboardUnitsOperation')) {
    class DashboardUnitsOperation
    {
    /**
     * Columna de unidades según el tipo de vehículo seleccionado.
     * @return array
     */
    private static function columnasVehiculo()
    {
        return [
            'all' => 'allunitsales',
            'cv'  => 'unitsalescv',
            'lcv' => 'unitsaleslcv',
        ];
    }

    /**
     * Contexto de rol/sesión.
     * @return array ['roleIds' => int[], 'dealerLogin' => int, 'isAdmin' => bool]
     */
    private static function rolActual()
    {
        $roleIds = array_map('intval', $_SESSION['UsrData']['rolids'] ?? []);
        if (empty($roleIds) && !empty($_SESSION['UsrData']['rolid'])) {
            $roleIds = [(int) $_SESSION['UsrData']['rolid']];
        }
        return [
            'roleIds'     => $roleIds,
            'dealerLogin' => (int) ($_SESSION['UsrData']['dealerid'] ?? 0),
            'isAdmin'     => in_array(1, $roleIds, true),
        ];
    }

    /**
     * Normaliza ids a un array plano de enteros únicos.
     * @param mixed $ids
     * @return int[]
     */
    private static function normalizarIds($ids)
    {
        if ($ids === null || $ids === '') {
            return [];
        }
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /**
     * Dealers visibles según la sesión y el filtro (enforcement de rol).
     * @param int|int[]|null $dealerIds
     * @return array
     */
    private static function dealersPermitidos($dealerIds = [])
    {
        include_once('../config/init_db.php');

        $rol = self::rolActual();

        if (!$rol['isAdmin']) {
            return DB::query(
                "SELECT d.dealerid, d.dealer, d.paisid, p.pais, p.bandera
                 FROM ots_dealers d
                 INNER JOIN ots_paises p ON p.paisid = d.paisid
                 WHERE d.dealerid = %i",
                $rol['dealerLogin']
            );
        }

        $dealerIds = self::normalizarIds($dealerIds);
        if (!empty($dealerIds)) {
            $in = implode(',', array_fill(0, count($dealerIds), '%i'));
            return DB::query(
                "SELECT d.dealerid, d.dealer, d.paisid, p.pais, p.bandera
                 FROM ots_dealers d
                 INNER JOIN ots_paises p ON p.paisid = d.paisid
                 WHERE d.estadoid = 1 AND d.dealerid IN ($in) ORDER BY d.dealer ASC",
                ...$dealerIds
            );
        }
        return DB::query(
            "SELECT d.dealerid, d.dealer, d.paisid, p.pais, p.bandera
             FROM ots_dealers d
             INNER JOIN ots_paises p ON p.paisid = d.paisid
             WHERE d.estadoid = 1 ORDER BY d.dealer ASC"
        );
    }

    /**
     * Resuelve la referencia (año + mes) usada para las ventanas de UIO.
     * El mes de referencia es el elegido por el usuario o el último con ventas del año.
     * @param int|null $year
     * @param int|null $calendarId
     * @return array
     */
    private static function resolverReferencia($year, $calendarId)
    {
        include_once('../config/init_db.php');

        $anio = (int) ($year ?: date('Y'));

        if (!empty($calendarId)) {
            $ref = DB::queryFirstRow(
                "SELECT calendarioid, ano, mes, mesnum FROM kpi_calendarios WHERE calendarioid = %i",
                (int) $calendarId
            );
        } else {
            $ref = DB::queryFirstRow(
                "SELECT c.calendarioid, c.ano, c.mes, c.mesnum
                 FROM kpi_calendarios c
                 INNER JOIN (
                     SELECT calendarioid, SUM(allunitsales) AS tot
                     FROM kpi_sales GROUP BY calendarioid
                 ) s ON s.calendarioid = c.calendarioid
                 WHERE c.ano = %i AND s.tot > 0
                 ORDER BY c.mesnum DESC LIMIT 1",
                $anio
            );
        }
        if (empty($ref)) {
            $ref = DB::queryFirstRow(
                "SELECT calendarioid, ano, mes, mesnum
                 FROM kpi_calendarios
                 WHERE ano = %i AND mesnum >= 1
                 ORDER BY mesnum DESC LIMIT 1",
                $anio
            );
        }
        if (empty($ref)) {
            return ['sin_periodo' => true, 'anio' => $anio];
        }

        return [
            'anio'         => (int) $ref['ano'],
            'mes'          => $ref['mes'] ?? '',
            'mesnum'       => (int) $ref['mesnum'],
            'calendarioid' => (int) $ref['calendarioid'],
        ];
    }

    /**
     * Suma de unidades en la ventana de H años que termina en (anioFin, mesFin).
     * Incluye meses desde (anioFin - H + 1, enero) hasta (anioFin, mesFin).
     * @param int[]  $dealerIds
     * @param string $col       allunitsales | unitsalescv | unitsaleslcv
     * @param int    $anioFin
     * @param int    $mesFin
     * @param int    $anios
     * @return int
     */
    private static function sumaVentana($dealerIds, $col, $anioFin, $mesFin, $anios)
    {
        if (empty($dealerIds)) {
            return 0;
        }
        include_once('../config/init_db.php');

        $anioInicio = $anioFin - $anios + 1;
        $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
        $args = array_merge([$col], $dealerIds, [$anioInicio, $anioFin, $anioFin, $mesFin]);
        $v = DB::queryFirstField(
            "SELECT COALESCE(SUM(k.%b), 0)
             FROM kpi_sales k
             INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
             WHERE k.dealerid IN ($inD)
               AND c.ano >= %i
               AND (c.ano < %i OR (c.ano = %i AND c.mesnum <= %i))
               AND c.mesnum >= 1",
            ...$args
        );
        return (int) round((float) $v);
    }

    /**
     * Sumas cv/lcv en la ventana (para el mix LCV/CV).
     * @return array ['cv' => int, 'lcv' => int]
     */
    private static function sumaCvLcv($dealerIds, $anioFin, $mesFin, $anios)
    {
        $out = ['cv' => 0, 'lcv' => 0];
        if (empty($dealerIds)) {
            return $out;
        }
        include_once('../config/init_db.php');

        $anioInicio = $anioFin - $anios + 1;
        $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
        $args = array_merge($dealerIds, [$anioInicio, $anioFin, $anioFin, $mesFin]);
        $row = DB::queryFirstRow(
            "SELECT COALESCE(SUM(k.unitsalescv), 0) AS cv,
                    COALESCE(SUM(k.unitsaleslcv), 0) AS lcv
             FROM kpi_sales k
             INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
             WHERE k.dealerid IN ($inD)
               AND c.ano >= %i
               AND (c.ano < %i OR (c.ano = %i AND c.mesnum <= %i))
               AND c.mesnum >= 1",
            ...$args
        );
        $out['cv']  = (int) round((float) ($row['cv'] ?? 0));
        $out['lcv'] = (int) round((float) ($row['lcv'] ?? 0));
        return $out;
    }

    /**
     * Serie de tendencia: un punto por mes desde el inicio de la ventana
     * hasta el mes de referencia, con ventas CV/LCV (barras apiladas),
     * ventas totales del tipo seleccionado y UIO rodante de 3 y 5 años
     * (comparación directa 3yr vs 5yr).
     * @param int[]  $dealerIds
     * @param string $vehicleType all|cv|lcv
     * @return array
     */
    private static function serieTendencia($dealerIds, $vehicleType, $anioFin, $mesFin, $anios)
    {
        $serie = [];
        if (empty($dealerIds)) {
            return $serie;
        }
        include_once('../config/init_db.php');

        $anioInicio = $anioFin - $anios + 1;
        $inD = implode(',', array_fill(0, count($dealerIds), '%i'));

        // Meses a mostrar: ventana de H años hasta el mes de referencia
        $cals = DB::query(
            "SELECT calendarioid, ano, mes, mesnum
             FROM kpi_calendarios
             WHERE ano >= %i
               AND (ano < %i OR (ano = %i AND mesnum <= %i))
               AND mesnum >= 1
             ORDER BY ano ASC, mesnum ASC",
            $anioInicio,
            $anioFin,
            $anioFin,
            $mesFin
        );
        if (empty($cals)) {
            return $serie;
        }

        // Unidades mensuales de TODA la historia (para ventanas 3/5 años por punto)
        $rows = DB::query(
            "SELECT c.calendarioid, c.ano, c.mesnum,
                    COALESCE(SUM(k.unitsalescv), 0)   AS cv,
                    COALESCE(SUM(k.unitsaleslcv), 0)  AS lcv,
                    COALESCE(SUM(k.allunitsales), 0)  AS allu
             FROM kpi_sales k
             INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
             WHERE k.dealerid IN ($inD) AND c.mesnum >= 1
             GROUP BY c.calendarioid, c.ano, c.mesnum",
            ...$dealerIds
        );
        $porIndex = [];
        foreach ($rows as $r) {
            $idx = ((int) $r['ano'] - 1) * 12 + (int) $r['mesnum'];
            $porIndex[$idx] = [
                'ano'    => (int) $r['ano'],
                'mesnum' => (int) $r['mesnum'],
                'cv'     => (int) round((float) $r['cv']),
                'lcv'    => (int) round((float) $r['lcv']),
                'allu'   => (int) round((float) $r['allu']),
            ];
        }

        // Unidades del tipo seleccionado en un mes (all -> allunitsales, igual que los totales)
        $unidadesDe = function ($m) use ($vehicleType) {
            if ($vehicleType === 'cv') {
                return (int) $m['cv'];
            }
            if ($vehicleType === 'lcv') {
                return (int) $m['lcv'];
            }
            return (int) $m['allu'];
        };

        // UIO acumulado desde el inicio de la ventana mostrada (línea creciente).
        // uio_3yr acumula solo los últimos 3 años de la ventana (coincide con el total al final).
        $uio5 = 0;
        $uio3 = 0;
        $anio3Inicio = $anioFin - 2;
        foreach ($cals as $c) {
            $cA   = (int) $c['ano'];
            $cM   = (int) $c['mesnum'];
            $m    = $porIndex[($cA - 1) * 12 + $cM] ?? ['ano' => $cA, 'mesnum' => $cM, 'cv' => 0, 'lcv' => 0, 'allu' => 0];
            $ventas = $unidadesDe($m);
            $uio5 += $ventas;
            if ($cA >= $anio3Inicio) {
                $uio3 += $ventas;
            }
            $serie[] = [
                'calendarioid' => (int) $c['calendarioid'],
                'periodo'      => $c['mes'] . ' ' . $c['ano'],
                'ano'          => $cA,
                'mes'          => $c['mes'],
                'ventas'       => $ventas,
                'ventas_cv'    => (int) $m['cv'],
                'ventas_lcv'   => (int) $m['lcv'],
                'uio'          => $uio5,
                'uio_3yr'      => $uio3,
                'uio_5yr'      => $uio5,
            ];
        }
        return $serie;
    }

    /**
     * Ranking de dealers por UIO del horizonte (descendente).
     * @param array $dealers
     * @return array
     */
    private static function rankingDealers($dealers, $anioFin, $mesFin, $anios)
    {
        $ranking = [];
        if (empty($dealers)) {
            return $ranking;
        }
        include_once('../config/init_db.php');

        $anioInicio = $anioFin - $anios + 1;
        $dealerIds = array_column($dealers, 'dealerid');
        $inD = implode(',', array_fill(0, count($dealerIds), '%i'));

        $rows = DB::query(
            "SELECT k.dealerid,
                    COALESCE(SUM(k.unitsalescv), 0)  AS cv,
                    COALESCE(SUM(k.unitsaleslcv), 0) AS lcv
             FROM kpi_sales k
             INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
             WHERE k.dealerid IN ($inD)
               AND c.ano >= %i
               AND (c.ano < %i OR (c.ano = %i AND c.mesnum <= %i))
               AND c.mesnum >= 1
             GROUP BY k.dealerid",
            ...array_merge($dealerIds, [$anioInicio, $anioFin, $anioFin, $mesFin])
        );
        $mapa = [];
        foreach ($rows as $r) {
            $cv  = (int) round((float) $r['cv']);
            $lcv = (int) round((float) $r['lcv']);
            $total = $cv + $lcv;
            $mapa[(int) $r['dealerid']] = [
                'cv'      => $cv,
                'lcv'     => $lcv,
                'total'   => $total,
                'mix_lcv' => $total > 0 ? round(($lcv / $total) * 100, 1) : null,
            ];
        }

        foreach ($dealers as $d) {
            $did = (int) $d['dealerid'];
            $m = $mapa[$did] ?? ['cv' => 0, 'lcv' => 0, 'total' => 0, 'mix_lcv' => null];
            $ranking[] = [
                'dealerid' => $did,
                'dealer'   => $d['dealer'],
                'uio'      => $m['total'],
                'cv'       => $m['cv'],
                'lcv'      => $m['lcv'],
                'mix_lcv'  => $m['mix_lcv'],
            ];
        }
        usort($ranking, function ($a, $b) {
            return $b['uio'] - $a['uio'];
        });
        return $ranking;
    }

    /**
     * Opciones para la barra de filtros (años con meses, países, dealers, tipos).
     * Respeta el rol de sesión.
     * @return array
     */
    public static function getFiltrosDisponibles()
    {
        include_once('../config/init_db.php');

        try {
            $rol = self::rolActual();

            $anios = DB::query(
                "SELECT ano,
                        (ano = YEAR(CURDATE())) AS is_current
                 FROM kpi_calendarios
                 GROUP BY ano
                 ORDER BY ano DESC"
            );
            $anios = array_map(function ($a) {
                $meses = DB::query(
                    "SELECT calendarioid, mes, mesnum
                     FROM kpi_calendarios
                     WHERE ano = %i AND mesnum >= 1
                     ORDER BY mesnum ASC",
                    (int) $a['ano']
                );
                return [
                    'ano'        => (int) $a['ano'],
                    'is_current' => (bool) $a['is_current'],
                    'meses'      => array_map(function ($m) {
                        return [
                            'calendarioid' => (int) $m['calendarioid'],
                            'mes'          => $m['mes'],
                            'mesnum'       => (int) $m['mesnum'],
                        ];
                    }, $meses),
                ];
            }, $anios);

            if ($rol['isAdmin']) {
                $dealers = DB::query(
                    "SELECT d.dealerid, d.dealer, d.paisid, p.pais, p.bandera
                     FROM ots_dealers d
                     INNER JOIN ots_paises p ON p.paisid = d.paisid
                     WHERE d.estadoid = 1
                     ORDER BY d.dealer ASC"
                );
            } else {
                $dealers = DB::query(
                    "SELECT d.dealerid, d.dealer, d.paisid, p.pais, p.bandera
                     FROM ots_dealers d
                     INNER JOIN ots_paises p ON p.paisid = d.paisid
                     WHERE d.estadoid = 1 AND d.dealerid = %i
                     ORDER BY d.dealer ASC",
                    $rol['dealerLogin']
                );
            }

            $vistos = [];
            $paises = [];
            foreach ($dealers as $d) {
                $pid = (int) $d['paisid'];
                if (!isset($vistos[$pid])) {
                    $vistos[$pid] = true;
                    $paises[] = [
                        'paisid'  => $pid,
                        'pais'    => $d['pais'],
                        'bandera' => $d['bandera'],
                    ];
                }
            }
            usort($paises, function ($a, $b) {
                return strcmp($a['pais'], $b['pais']);
            });

            return [
                'anios'          => $anios,
                'paises'         => $paises,
                'dealers'        => $dealers,
                'tipos_vehiculo' => [
                    ['valor' => 'all', 'nombre' => 'All (CV + LCV)'],
                    ['valor' => 'cv',  'nombre' => 'CV'],
                    ['valor' => 'lcv', 'nombre' => 'LCV'],
                ],
            ];
        } catch (Exception $e) {
            error_log('Error en DashboardUnitsOperation::getFiltrosDisponibles: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Resumen del tablero de UIO.
     *
     * @param int|null       $year
     * @param int|null       $calendarId
     * @param int|int[]|null $dealerIds
     * @param int|int[]|null $countryIds
     * @param string         $vehicleType all|cv|lcv
     * @return array
     */
    public static function getResumen($year = null, $calendarId = null, $dealerIds = [], $countryIds = [], $vehicleType = 'all')
    {
        include_once('../config/init_db.php');

        try {
            $dealerIds   = self::normalizarIds($dealerIds);
            $countryIds  = self::normalizarIds($countryIds);
            $vehicleType = in_array($vehicleType, ['cv', 'lcv'], true) ? $vehicleType : 'all';
            $col         = self::columnasVehiculo()[$vehicleType];

            $dealers = self::dealersPermitidos($dealerIds);
            $rol = self::rolActual();
            if (!$rol['isAdmin']) {
                $countryIds = [];
            }
            if (!empty($countryIds)) {
                $paisesSet = array_flip($countryIds);
                $dealers = array_values(array_filter($dealers, function ($d) use ($paisesSet) {
                    return isset($paisesSet[(int) $d['paisid']]);
                }));
            }
            $dealerIds = array_column($dealers, 'dealerid');
            if (empty($dealerIds)) {
                return ['sin_datos' => true, 'anio' => $year];
            }

            $ref = self::resolverReferencia($year, $calendarId);
            if (!empty($ref['sin_periodo'])) {
                return ['sin_periodo' => true, 'anio' => $year];
            }

            $anio   = $ref['anio'];
            $mesFin = $ref['mesnum'];
            $H      = 5; // horizonte definido para UIO Total / mix / ranking

            $uio_3    = self::sumaVentana($dealerIds, $col, $anio, $mesFin, 3);
            $uio_5    = self::sumaVentana($dealerIds, $col, $anio, $mesFin, 5);
            $uio_total = $uio_5;

            $uio_3_prev = self::sumaVentana($dealerIds, $col, $anio - 3, $mesFin, 3);
            $uio_5_prev = self::sumaVentana($dealerIds, $col, $anio - 5, $mesFin, 5);

            $crec_3 = ($uio_3_prev != 0) ? round((($uio_3 - $uio_3_prev) / $uio_3_prev) * 100, 1) : null;
            $crec_5 = ($uio_5_prev != 0) ? round((($uio_5 - $uio_5_prev) / $uio_5_prev) * 100, 1) : null;

            $mixCvLcv = self::sumaCvLcv($dealerIds, $anio, $mesFin, $H);
            $totalMix = $mixCvLcv['cv'] + $mixCvLcv['lcv'];
            if ($vehicleType === 'all') {
                $mix_lcv = $totalMix > 0 ? round(($mixCvLcv['lcv'] / $totalMix) * 100, 1) : null;
                $mix_cv  = $totalMix > 0 ? round(($mixCvLcv['cv'] / $totalMix) * 100, 1) : null;
            } elseif ($vehicleType === 'cv') {
                $mix_lcv = 0;
                $mix_cv  = 100;
            } else {
                $mix_lcv = 100;
                $mix_cv  = 0;
            }

            $serie   = self::serieTendencia($dealerIds, $vehicleType, $anio, $mesFin, $H);
            $ranking = self::rankingDealers($dealers, $anio, $mesFin, $H);

            return [
                'anio'           => (int) $anio,
                'mes_referencia' => [
                    'mes'          => $ref['mes'],
                    'mesnum'       => $mesFin,
                    'calendarioid' => (int) $ref['calendarioid'],
                ],
                'tipo_vehiculo'  => $vehicleType,
                'horizonte'      => $H,
                'totales'        => [
                    'uio_total'       => (int) $uio_total,
                    'uio_3yr'         => (int) $uio_3,
                    'uio_5yr'         => (int) $uio_5,
                    'uio_3yr_prev'    => (int) $uio_3_prev,
                    'uio_5yr_prev'    => (int) $uio_5_prev,
                    'crecimiento_3yr' => $crec_3,
                    'crecimiento_5yr' => $crec_5,
                    'mix_lcv'         => $mix_lcv,
                    'mix_cv'          => $mix_cv,
                    'lcv_units'       => (int) $mixCvLcv['lcv'],
                    'cv_units'        => (int) $mixCvLcv['cv'],
                ],
                'serie'            => $serie,
                'ranking'          => $ranking,
                'filtros_aplicados' => [
                    'anio'        => (int) $anio,
                    'calendarId'  => (int) $ref['calendarioid'],
                    'dealerIds'   => $dealerIds,
                    'countryIds'  => $countryIds,
                    'vehicleType' => $vehicleType,
                ],
            ];
        } catch (Exception $e) {
            error_log('Error en DashboardUnitsOperation::getResumen: ' . $e->getMessage());
            throw $e;
        }
    }
}
}
