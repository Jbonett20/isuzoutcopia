<?php
/*
|--------------------------------------------------------------------------
| DASHBOARD DE PERFORMANCE DE VENTA DE PARTES (admin/partSalesPerformance.php)
|--------------------------------------------------------------------------
| Modelo auto-contenido del tablero de performance de venta de partes.
|
| Métricas (ver design.md del cambio part-sales-performance):
|   - Ventas Actual  = SUM(ventas de los canales seleccionados) en el rango
|   - LY             = SUM(ventas) del período equivalente del año anterior
|   - Var % vs LY    = (actual - LY) / LY * 100  (null si LY = 0)
|   - Objetivo       = kpi_dealertarget.objetivovlr (USD, anual por dealer), prorrateado
|   - Cumplimiento   = actual / objetivo * 100    (null si no hay objetivo)
|   - Gap            = actual - objetivo
|
| Fuentes: kpi_parts (counter, external, workshop, fleets, insurance),
|          kpi_dealertarget, kpi_calendarios, ots_dealers/ots_paises.
|--------------------------------------------------------------------------
*/

@session_start();

if (!class_exists('DashboardPartSalesPerformance')) {
    class DashboardPartSalesPerformance
    {
    /**
     * Canales de venta de partes (columnas de kpi_parts).
     * @return string[]
     */
    private static function canales()
    {
        return ['counter', 'external', 'workshop', 'fleets', 'insurance'];
    }

    /**
     * Normaliza la selección de canales a un array plano válido.
     * Vacío o 'all' => todos los canales.
     * @param mixed $canales
     * @return string[]
     */
    private static function normalizarCanales($canales)
    {
        $todos = self::canales();
        if ($canales === null || $canales === '' || $canales === 'all') {
            return $todos;
        }
        if (!is_array($canales)) {
            $canales = explode(',', (string) $canales);
        }
        $set = array_flip($todos);
        $out = [];
        foreach ($canales as $c) {
            $c = trim((string) $c);
            if ($c === '' || $c === 'all') {
                return $todos;
            }
            if (isset($set[$c])) {
                $out[] = $c;
            }
        }
        return $out ?: $todos;
    }

    /**
     * Expresión SQL de suma para los canales seleccionados (whitelist segura).
     * @param mixed $canales
     * @return string
     */
    private static function sumaCanalesSQL($canales)
    {
        $cols = self::normalizarCanales($canales);
        $expr = array_map(function ($c) {
            return 'COALESCE(' . $c . ',0)';
        }, $cols);
        return implode(' + ', $expr);
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
     * Resuelve el tipo de período a un rango de calendarioids y el mes de referencia.
     * (Misma semántica que el dashboard de ventas; el mes de referencia usa kpi_parts.)
     */
    private static function resolverPeriodo($year, $calendarId, $periodType)
    {
        include_once('../config/init_db.php');

        $fiscalStart = (int) (getenv('FISCAL_START_MONTH') ?: 1);
        if ($fiscalStart < 1 || $fiscalStart > 12) {
            $fiscalStart = 1;
        }

        $anio = (int) ($year ?: date('Y'));

        $calendariosAnio = DB::query(
            "SELECT calendarioid, ano, mes, mesnum
             FROM kpi_calendarios
             WHERE ano = %i AND mesnum >= 1 ORDER BY mesnum ASC",
            $anio
        );

        if (empty($calendariosAnio)) {
            return ['sin_periodo' => true, 'anio' => $anio];
        }

        // Mes de referencia (dentro del año): el último con ventas de partes > 0
        if (!empty($calendarId)) {
            $referencia = DB::queryFirstRow(
                "SELECT calendarioid, ano, mes, mesnum FROM kpi_calendarios WHERE calendarioid = %i",
                (int) $calendarId
            );
        } else {
            $referencia = DB::queryFirstRow(
                "SELECT c.calendarioid, c.ano, c.mes, c.mesnum
                 FROM kpi_calendarios c
                 INNER JOIN (
                     SELECT calendarioid, SUM(counter + external + workshop + fleets + insurance) AS tot
                     FROM kpi_parts GROUP BY calendarioid
                 ) s ON s.calendarioid = c.calendarioid
                 WHERE c.ano = %i AND s.tot > 0
                 ORDER BY c.mesnum DESC LIMIT 1",
                $anio
            );
        }
        if (empty($referencia)) {
            $referencia = $calendariosAnio[count($calendariosAnio) - 1];
        }

        $refCalendarioid = (int) $referencia['calendarioid'];
        $refMesnum       = (int) $referencia['mesnum'];

        // Construcción del rango según el tipo de período
        $rango        = [];
        $rangoMesnums = [];

        if ($periodType === 'month') {
            $rango        = [$refCalendarioid];
            $rangoMesnums = [$refMesnum];

        } elseif ($periodType === 'ytd' || $periodType === 'fytd') {
            foreach ($calendariosAnio as $c) {
                $mn = (int) $c['mesnum'];
                if ($mn >= $fiscalStart && $mn <= $refMesnum) {
                    $rango[]        = (int) $c['calendarioid'];
                    $rangoMesnums[] = $mn;
                }
            }

        } elseif ($periodType === 'full_year') {
            foreach ($calendariosAnio as $c) {
                $rango[]        = (int) $c['calendarioid'];
                $rangoMesnums[] = (int) $c['mesnum'];
            }

        } elseif ($periodType === 'l12m') {
            $cals = DB::query(
                "SELECT calendarioid, ano, mesnum
                 FROM kpi_calendarios WHERE mesnum >= 1
                 ORDER BY ano ASC, mesnum ASC"
            );
            $idx = -1;
            foreach ($cals as $i => $c) {
                if ((int) $c['calendarioid'] === $refCalendarioid) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx < 0) {
                $idx = count($cals) - 1;
            }
            $desde = max(0, $idx - 11);
            for ($i = $desde; $i <= $idx; $i++) {
                $rango[]        = (int) $cals[$i]['calendarioid'];
                $rangoMesnums[] = (int) $cals[$i]['mesnum'];
            }

        } elseif ($periodType === 'prior_year') {
            $anioPrev = $anio - 1;
            $calsPrev = DB::query(
                "SELECT calendarioid, ano, mes, mesnum
                 FROM kpi_calendarios WHERE ano = %i AND mesnum >= 1 ORDER BY mesnum ASC",
                $anioPrev
            );
            foreach ($calsPrev as $c) {
                $rango[]        = (int) $c['calendarioid'];
                $rangoMesnums[] = (int) $c['mesnum'];
            }
            if (!empty($calsPrev)) {
                $ult             = $calsPrev[count($calsPrev) - 1];
                $referencia      = $ult;
                $refCalendarioid = (int) $ult['calendarioid'];
                $refMesnum       = (int) $ult['mesnum'];
            }
        }

        return [
            'anio'                 => $anio,
            'fiscal_start'         => $fiscalStart,
            'periodo_actual'       => [
                'calendarioid' => $refCalendarioid,
                'ano'          => (int) $referencia['ano'],
                'mes'          => $referencia['mes'] ?? '',
                'mesnum'       => $refMesnum,
            ],
            'periodos_disponibles' => array_map(function ($c) {
                return [
                    'calendarioid' => (int) $c['calendarioid'],
                    'ano'          => (int) $c['ano'],
                    'mes'          => $c['mes'],
                    'mesnum'       => (int) $c['mesnum'],
                    'yearmonth'    => $c['ano'] . ' - ' . $c['mes'],
                ];
            }, $calendariosAnio),
            'rango_calendarioids'  => $rango,
            'rango_mesnums'        => $rangoMesnums,
            'etiqueta_periodo'     => self::etiquetaPeriodo($periodType, $anio, $fiscalStart),
        ];
    }

    /**
     * Etiqueta legible del período.
     */
    private static function etiquetaPeriodo($periodType, $anio, $fiscalStart)
    {
        switch ($periodType) {
            case 'month':
                return 'Mes';
            case 'ytd':
                return 'YTD ' . $anio;
            case 'fytd':
                return 'Fiscal YTD ' . $anio . ' (desde mes ' . $fiscalStart . ')';
            case 'full_year':
                return 'Año completo ' . $anio;
            case 'l12m':
                return 'Últimos 12 meses';
            case 'prior_year':
                return 'Año anterior ' . ($anio - 1);
        }
        return '';
    }

    /**
     * Opciones para la barra de filtros (años con meses, países, dealers, canales).
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
                'anios'   => $anios,
                'paises'  => $paises,
                'dealers' => $dealers,
                'canales' => [
                    ['valor' => 'counter',   'nombre' => 'Counter'],
                    ['valor' => 'workshop',  'nombre' => 'Taller'],
                    ['valor' => 'external',  'nombre' => 'Externo'],
                    ['valor' => 'fleets',    'nombre' => 'Flotas'],
                    ['valor' => 'insurance', 'nombre' => 'Insurance'],
                ],
            ];
        } catch (Exception $e) {
            error_log('Error en DashboardPartSalesPerformance::getFiltrosDisponibles: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Objetivos anuales por dealer desde kpi_dealertarget (lectura dinámica).
     * Este dashboard mide repuestos en USD: usa la columna objetivovlr (objetivo en dólares).
     * @return array dealerid => objetivo (USD)
     */
    private static function objetivosDealers($year, $dealerIds)
    {
        if (empty($year) || empty($dealerIds)) {
            return [];
        }
        include_once('../config/init_db.php');

        $in = implode(',', array_fill(0, count($dealerIds), '%i'));
        $rows = DB::query(
            "SELECT dealerid, objetivovlr AS objetivo
             FROM kpi_dealertarget
             WHERE year = %i AND dealerid IN ($in)",
            $year,
            ...$dealerIds
        );
        $mapa = [];
        foreach ($rows as $r) {
            $mapa[(int) $r['dealerid']] = (int) $r['objetivo'];
        }
        return $mapa;
    }

    /**
     * Suma total de ventas (canales seleccionados) para los dealers en el rango.
     * @param int[]   $dealerIds
     * @param string  $sumaSQL    Expresión SQL de suma (whitelist de canales)
     * @param int[]   $rango      calendarioids
     * @return float
     */
    private static function ventasTotal($dealerIds, $sumaSQL, $rango)
    {
        if (empty($dealerIds) || empty($rango)) {
            return 0.0;
        }
        include_once('../config/init_db.php');

        $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
        $inR = implode(',', array_fill(0, count($rango), '%i'));
        $v = DB::queryFirstField(
            "SELECT COALESCE(SUM($sumaSQL), 0)
             FROM kpi_parts k
             WHERE k.dealerid IN ($inD) AND k.calendarioid IN ($inR)",
            ...$dealerIds,
            ...$rango
        );
        return (float) $v;
    }

    /**
     * Ventas por calendarioid en el rango.
     * @return array calendarioid => float
     */
    private static function ventasPorCalendario($dealerIds, $sumaSQL, $rango)
    {
        $mapa = [];
        if (empty($dealerIds) || empty($rango)) {
            return $mapa;
        }
        include_once('../config/init_db.php');

        $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
        $inR = implode(',', array_fill(0, count($rango), '%i'));
        $rows = DB::query(
            "SELECT k.calendarioid, COALESCE(SUM($sumaSQL), 0) AS tot
             FROM kpi_parts k
             WHERE k.dealerid IN ($inD) AND k.calendarioid IN ($inR)
             GROUP BY k.calendarioid",
            ...$dealerIds,
            ...$rango
        );
        foreach ($rows as $r) {
            $mapa[(int) $r['calendarioid']] = (float) $r['tot'];
        }
        return $mapa;
    }

    /**
     * Ventas por dealer en el rango.
     * @return array dealerid => float
     */
    private static function ventasPorDealer($dealerIds, $sumaSQL, $rango)
    {
        $mapa = [];
        if (empty($dealerIds) || empty($rango)) {
            return $mapa;
        }
        include_once('../config/init_db.php');

        $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
        $inR = implode(',', array_fill(0, count($rango), '%i'));
        $rows = DB::query(
            "SELECT k.dealerid, COALESCE(SUM($sumaSQL), 0) AS tot
             FROM kpi_parts k
             WHERE k.dealerid IN ($inD) AND k.calendarioid IN ($inR)
             GROUP BY k.dealerid",
            ...$dealerIds,
            ...$rango
        );
        foreach ($rows as $r) {
            $mapa[(int) $r['dealerid']] = (float) $r['tot'];
        }
        return $mapa;
    }

    /**
     * Desglose de ventas por canal en el rango.
     * @return array canal => float
     */
    private static function ventasPorCanal($dealerIds, $rango)
    {
        $mapa = [];
        if (empty($dealerIds) || empty($rango)) {
            return $mapa;
        }
        include_once('../config/init_db.php');

        $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
        $inR = implode(',', array_fill(0, count($rango), '%i'));
        $cols = implode(', ', array_map(function ($c) {
            return 'COALESCE(SUM(COALESCE(' . $c . ',0)),0) AS ' . $c;
        }, self::canales()));
        $row = DB::queryFirstRow(
            "SELECT $cols FROM kpi_parts k
             WHERE k.dealerid IN ($inD) AND k.calendarioid IN ($inR)",
            ...$dealerIds,
            ...$rango
        );
        foreach (self::canales() as $c) {
            $mapa[$c] = (float) ($row[$c] ?? 0);
        }
        return $mapa;
    }

    /**
     * Meses distintos del año objetivo cubiertos por el rango (para prorrateo).
     * @param int[]  $rango
     * @param int    $goalYear
     * @param string $periodType
     * @return int
     */
    private static function mesesCubiertos($rango, $goalYear, $periodType)
    {
        if (empty($rango)) {
            return 0;
        }
        if ($periodType === 'month') {
            return 1;
        }
        if ($periodType === 'full_year') {
            return 12; // objetivo anual completo
        }
        include_once('../config/init_db.php');

        $inR = implode(',', array_fill(0, count($rango), '%i'));
        $rows = DB::query(
            "SELECT DISTINCT mesnum FROM kpi_calendarios
             WHERE ano = %i AND calendarioid IN ($inR)",
            $goalYear,
            ...$rango
        );
        return count($rows);
    }

    /**
     * Período equivalente del año anterior para la comparación LY.
     * @return array (misma forma que resolverPeriodo, o rango vacío)
     */
    private static function periodoBaseLY($year, $refCal, $refMesnum, $periodType)
    {
        include_once('../config/init_db.php');

        if ($periodType === 'prior_year') {
            return self::resolverPeriodo($year - 2, null, 'full_year');
        }
        $baseYear = $year - 1;
        $lyCal = DB::queryFirstField(
            "SELECT calendarioid FROM kpi_calendarios
             WHERE ano = %i AND mesnum = %i ORDER BY calendarioid ASC LIMIT 1",
            $baseYear,
            $refMesnum
        );
        if ($lyCal) {
            return self::resolverPeriodo($baseYear, (int) $lyCal, $periodType);
        }
        return ['rango_calendarioids' => [], 'rango_mesnums' => []];
    }

    /**
     * Estado según el % de cumplimiento.
     * @param float|null $cumplimiento
     * @param bool       $tieneObjetivo
     * @return string on|near|under|no_target
     */
    private static function estadoPorCumplimiento($cumplimiento, $tieneObjetivo)
    {
        if (!$tieneObjetivo || $cumplimiento === null) {
            return 'no_target';
        }
        if ($cumplimiento >= 100) {
            return 'on';
        }
        if ($cumplimiento >= 95) {
            return 'near';
        }
        return 'under';
    }

    /**
     * Resumen del tablero de performance de venta de partes.
     *
     * @param int|null        $year
     * @param int|null        $calendarId
     * @param int|int[]|null  $dealerIds
     * @param int|int[]|null  $countryIds
     * @param mixed           $canales      all|''|counter|workshop|external|fleets|insurance|array
     * @param string          $periodType   month|ytd|fytd|full_year|l12m|prior_year
     * @param string          $comparison   none|cy_vs_py|ytd_vs_py_ytd|month_vs_py
     * @return array
     */
    public static function getResumen(
        $year = null,
        $calendarId = null,
        $dealerIds = [],
        $countryIds = [],
        $canales = 'all',
        $periodType = 'month',
        $comparison = 'none'
    ) {
        include_once('../config/init_db.php');

        try {
            $dealerIds   = self::normalizarIds($dealerIds);
            $countryIds  = self::normalizarIds($countryIds);
            $canalesSel  = self::normalizarCanales($canales);
            $sumaSQL     = self::sumaCanalesSQL($canalesSel);
            $periodType  = in_array($periodType, ['ytd', 'fytd', 'full_year', 'l12m', 'prior_year'], true) ? $periodType : 'month';
            $comparison  = in_array($comparison, ['cy_vs_py', 'ytd_vs_py_ytd', 'month_vs_py'], true) ? $comparison : 'none';

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
                return ['sin_datos' => true, 'etiqueta_periodo' => ''];
            }

            $periodo = self::resolverPeriodo($year, $calendarId, $periodType);
            if (!empty($periodo['sin_periodo'])) {
                return ['sin_periodo' => true, 'anio' => $year];
            }

            $year          = $periodo['anio'];
            $rango         = $periodo['rango_calendarioids'];
            $periodoActual = $periodo['periodo_actual'];
            $refCal        = (int) $periodoActual['calendarioid'];
            $refMesnum     = (int) $periodoActual['mesnum'];
            $etiqueta      = $periodo['etiqueta_periodo'];

            // Objetivos (anuales) del año que corresponde al período
            $goalYear = ($periodType === 'prior_year') ? ($year - 1) : $year;
            $mapaObjetivos = self::objetivosDealers($goalYear, $dealerIds);
            $goalSum = 0;
            foreach ($dealerIds as $did) {
                if (isset($mapaObjetivos[$did])) {
                    $goalSum += $mapaObjetivos[$did];
                }
            }
            $tieneObjetivo = $goalSum > 0;

            $elapsed = self::mesesCubiertos($rango, $goalYear, $periodType);
            $objetivoMensual   = $tieneObjetivo ? (int) round($goalSum / 12) : null;
            $objetivoAcumulado = $tieneObjetivo ? (int) round($goalSum * max(1, $elapsed) / 12) : null;
            $objetivoMostrar   = ($periodType === 'month') ? $objetivoMensual : $objetivoAcumulado;

            // Venta actual
            $actualTotal = self::ventasTotal($dealerIds, $sumaSQL, $rango);

            // LY (período equivalente del año anterior)
            $basePeriodo = self::periodoBaseLY($year, $refCal, $refMesnum, $periodType);
            $baseRango   = $basePeriodo['rango_calendarioids'] ?? [];
            $lyTotal     = !empty($baseRango) ? self::ventasTotal($dealerIds, $sumaSQL, $baseRango) : 0.0;

            $varPct        = ($lyTotal != 0) ? round((($actualTotal - $lyTotal) / $lyTotal) * 100, 1) : null;
            $cumplimiento  = ($tieneObjetivo && $objetivoMostrar > 0) ? round(($actualTotal / $objetivoMostrar) * 100, 1) : null;
            $gap           = $tieneObjetivo ? round($actualTotal - $objetivoMostrar, 2) : null;

            $serie  = self::serieMensual($dealerIds, $sumaSQL, $rango, $mapaObjetivos, $tieneObjetivo, $periodType, $goalYear);
            $tabla  = self::tablaComparativa($dealers, $sumaSQL, $rango, $baseRango, $mapaObjetivos, $tieneObjetivo, $periodType, $goalYear);
            $comparacion = self::calcularComparacion($year, $refCal, $refMesnum, $dealerIds, $countryIds, $canalesSel, $periodType, $comparison, $actualTotal, $lyTotal);
            $porCanal = self::ventasPorCanal($dealerIds, $rango);

            return [
                'anio'             => (int) $year,
                'periodo_actual'   => $periodoActual,
                'etiqueta_periodo' => $etiqueta,
                'canales'          => $canalesSel,
                'totales'          => [
                    'actual'             => round($actualTotal, 2),
                    'ly'                 => round($lyTotal, 2),
                    'var_pct'            => $varPct,
                    'objetivo'           => $objetivoMostrar,
                    'cumplimiento'       => $cumplimiento,
                    'gap'                => $gap,
                    'tiene_objetivo'     => $tieneObjetivo,
                    'objetivo_mensual'   => $objetivoMensual,
                    'objetivo_acumulado' => $objetivoAcumulado,
                ],
                'serie'            => $serie,
                'tabla'            => $tabla,
                'por_canal'        => array_map(function ($v) {
                    return round($v, 2);
                }, $porCanal),
                'comparacion'      => $comparacion,
                'filtros_aplicados' => [
                    'anio'        => (int) $year,
                    'calendarId'  => $refCal,
                    'dealerIds'   => $dealerIds,
                    'countryIds'  => $countryIds,
                    'canales'     => $canalesSel,
                    'periodType'  => $periodType,
                    'comparison'  => $comparison,
                ],
            ];
        } catch (Exception $e) {
            error_log('Error en DashboardPartSalesPerformance::getResumen: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Serie del gráfico: un punto por mes del rango con actual, objetivo y ly.
     */
    private static function serieMensual($dealerIds, $sumaSQL, $rango, $mapaObjetivos, $tieneObjetivo, $periodType, $goalYear)
    {
        $serie = [];
        if (empty($rango)) {
            return $serie;
        }
        include_once('../config/init_db.php');

        $inR = implode(',', array_fill(0, count($rango), '%i'));
        $rangoInfo = DB::query(
            "SELECT calendarioid, ano, mes, mesnum
             FROM kpi_calendarios
             WHERE calendarioid IN ($inR)
             ORDER BY ano ASC, mesnum ASC",
            ...$rango
        );

        $actualPorMes = self::ventasPorCalendario($dealerIds, $sumaSQL, $rango);

        // Calendarios del año anterior para cada punto (mismo mesnum)
        $lyCalByKey  = [];
        $lyCalIds    = [];
        foreach ($rangoInfo as $c) {
            $key = (int) $c['ano'] . ':' . (int) $c['mesnum'];
            if (isset($lyCalByKey[$key])) {
                continue;
            }
            $prev = DB::queryFirstField(
                "SELECT calendarioid FROM kpi_calendarios
                 WHERE ano = %i AND mesnum = %i ORDER BY calendarioid ASC LIMIT 1",
                (int) $c['ano'] - 1,
                (int) $c['mesnum']
            );
            if ($prev) {
                $lyCalByKey[$key] = (int) $prev;
                $lyCalIds[]       = (int) $prev;
            }
        }
        $lyPorCal = self::ventasPorCalendario($dealerIds, $sumaSQL, $lyCalIds);

        $goalTotal = 0;
        foreach ($mapaObjetivos as $o) {
            $goalTotal += $o;
        }

        foreach ($rangoInfo as $c) {
            $key        = (int) $c['ano'] . ':' . (int) $c['mesnum'];
            $lyCal      = $lyCalByKey[$key] ?? null;
            $objetivoPunto = null;
            if ($tieneObjetivo) {
                if ($periodType === 'month') {
                    $objetivoPunto = (int) round($goalTotal / 12);
                } elseif ((int) $c['ano'] === $goalYear) {
                    $objetivoPunto = (int) round($goalTotal * (int) $c['mesnum'] / 12);
                }
            }
            $serie[] = [
                'calendarioid' => (int) $c['calendarioid'],
                'periodo'      => $c['mes'] . ' ' . $c['ano'],
                'mesnum'       => (int) $c['mesnum'],
                'actual'       => round($actualPorMes[(int) $c['calendarioid']] ?? 0, 2),
                'ly'           => $lyCal ? round($lyPorCal[$lyCal] ?? 0, 2) : 0.0,
                'objetivo'     => $objetivoPunto,
            ];
        }
        return $serie;
    }

    /**
     * Tabla comparativa por dealer.
     */
    private static function tablaComparativa($dealers, $sumaSQL, $rango, $baseRango, $mapaObjetivos, $tieneObjetivo, $periodType, $goalYear)
    {
        $tabla  = [];
        $dealerIds = array_column($dealers, 'dealerid');
        $elapsed   = self::mesesCubiertos($rango, $goalYear, $periodType);

        $actualMap = self::ventasPorDealer($dealerIds, $sumaSQL, $rango);
        $lyMap     = self::ventasPorDealer($dealerIds, $sumaSQL, $baseRango);

        foreach ($dealers as $dealer) {
            $did    = (int) $dealer['dealerid'];
            $actual = round($actualMap[$did] ?? 0, 2);
            $ly     = round($lyMap[$did] ?? 0, 2);
            $var    = ($ly != 0) ? round((($actual - $ly) / $ly) * 100, 1) : null;

            $obj  = ($tieneObjetivo && isset($mapaObjetivos[$did])) ? $mapaObjetivos[$did] : 0;
            $objMostrar = null;
            $gap = null;
            $cumplimiento = null;
            if ($obj > 0) {
                $objMostrar = ($periodType === 'month')
                    ? (int) round($obj / 12)
                    : (int) round($obj * max(1, $elapsed) / 12);
                $cumplimiento = ($objMostrar > 0) ? round(($actual / $objMostrar) * 100, 1) : null;
                $gap = round($actual - $objMostrar, 2);
            }

            $tabla[] = [
                'dealerid'     => $did,
                'dealer'       => $dealer['dealer'],
                'pais'         => $dealer['pais'],
                'actual'       => $actual,
                'ly'           => $ly,
                'var_pct'      => $var,
                'objetivo'     => $objMostrar,
                'cumplimiento' => $cumplimiento,
                'gap'          => $gap,
                'estado'       => self::estadoPorCumplimiento($cumplimiento, $obj > 0),
            ];
        }
        return $tabla;
    }

    /**
     * Comparación entre períodos (baseline + deltas).
     */
    private static function calcularComparacion($year, $refCal, $refMesnum, $dealerIds, $countryIds, $canales, $periodType, $comparison, $actualTotal, $lyTotal)
    {
        if ($comparison === 'none') {
            return null;
        }
        include_once('../config/init_db.php');

        $baseYear   = $year - 1;
        $basePeriod = 'full_year';
        $baseCal    = null;

        if ($comparison === 'month_vs_py') {
            $basePeriod = 'month';
            $baseCal = DB::queryFirstField(
                "SELECT calendarioid FROM kpi_calendarios
                 WHERE ano = %i AND mesnum = %i ORDER BY calendarioid ASC LIMIT 1",
                $baseYear,
                $refMesnum
            );
        } elseif ($comparison === 'ytd_vs_py_ytd') {
            $basePeriod = 'ytd';
            $baseCal = DB::queryFirstField(
                "SELECT calendarioid FROM kpi_calendarios
                 WHERE ano = %i AND mesnum = %i ORDER BY calendarioid ASC LIMIT 1",
                $baseYear,
                $refMesnum
            );
        }

        $base = self::getResumen($baseYear, $baseCal, $dealerIds, $countryIds, $canales, $basePeriod, 'none');
        if (empty($base) || !empty($base['sin_periodo']) || !empty($base['sin_datos'])) {
            return null;
        }

        $baseActual = (float) ($base['totales']['actual'] ?? 0);
        return [
            'comparison'    => $comparison,
            'anio_base'     => $baseYear,
            'etiqueta_base' => $base['etiqueta_periodo'] ?? '',
            'base_actual'   => round($baseActual, 2),
            'actual'        => round((float) $actualTotal, 2),
            'delta_units'   => round((float) $actualTotal - $baseActual, 2),
            'delta_pct'     => $baseActual != 0 ? round((((float) $actualTotal - $baseActual) / $baseActual) * 100, 1) : null,
            'ly'            => round((float) $lyTotal, 2),
        ];
    }
}
}
