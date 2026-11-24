<?php
/*
|--------------------------------------------------------------------------
| MODELO DASHBOARD DE INFORMATION STATUS (Cumplimiento de Carga)
|--------------------------------------------------------------------------
| Mide qué tan completo está cada dealer cargando sus KPIs en las
| categorías Parts / Sales / Service / Inventory, con resumen general,
| tarjetas por categoría (con serie mensual), barchart por dealer,
| estados por dealer × categoría, panel de KPIs Críticos (Top 5) y
| comparación entre períodos (Δpp).
|
| Sigue el patrón de los dashboards previos (Dashboard.php, DashboardPartSales...)
| y consume las tablas: kpi_parts, kpi_sales, kpi_serviceorders,
| kpi_calendarios, ots_dealers y ots_paises.
|--------------------------------------------------------------------------
*/

@session_start();

class InformationStatus
{
    /**
     * Categorías del reporte. La categoría "inventory" no tiene tabla propia:
     * se lee de kpi_parts y cuenta como "cargado" solo si inventorycost > 0.
     *
     * @return array
     */
    private static function categorias()
    {
        return [
            ['clave' => 'parts',     'nombre' => 'Parts',     'tabla' => 'kpi_parts',         'color' => 'parts'],
            ['clave' => 'sales',     'nombre' => 'Sales',     'tabla' => 'kpi_sales',         'color' => 'sales'],
            ['clave' => 'service',   'nombre' => 'Service',   'tabla' => 'kpi_serviceorders', 'color' => 'service'],
            ['clave' => 'inventory', 'nombre' => 'Inventory', 'tabla' => 'kpi_parts',         'color' => 'inventory', 'condicion' => 'k.inventorycost > 0'],
        ];
    }

    /**
     * Rol de la sesión actual.
     *
     * @return array ['roleIds'=>int[], 'dealerLogin'=>int, 'isAdmin'=>bool]
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
     * Normaliza uno o varios ids a un array plano de enteros únicos.
     *
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
     * Dealers visibles según la sesión (admin ve todos, dealer normal solo el suyo).
     * Enforcement de rol: no-admin forzado a su dealer; admin sanitiza dealerIds.
     *
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
     * Opciones disponibles para la barra de filtros globales:
     * dealers (con país), países, años, meses por año (sin mesnum=0),
     * periodos y comparaciones. Respeta el rol de sesión.
     *
     * @return array
     */
    public static function getFiltrosDisponibles()
    {
        include_once('../config/init_db.php');

        $dealers = self::dealersPermitidos();

        // Países con dealers visibles
        $paises = [];
        foreach ($dealers as $d) {
            $paises[$d['paisid']] = [
                'paisid'  => (int) $d['paisid'],
                'pais'    => $d['pais'],
                'bandera' => $d['bandera'],
            ];
        }
        $paises = array_values($paises);
        usort($paises, function ($a, $b) {
            return strcmp($a['pais'], $b['pais']);
        });

        // Años disponibles con sus meses (excluyendo el dummy mesnum = 0)
        $meses = DB::query(
            "SELECT calendarioid, ano, mes, mesnum
             FROM kpi_calendarios
             WHERE mesnum <> 0
             ORDER BY ano ASC, mesnum ASC"
        );
        $anioActual = (int) date('Y');
        $anios = [];
        $tmp   = [];
        foreach ($meses as $m) {
            $tmp[(int) $m['ano']][] = [
                'calendarioid' => (int) $m['calendarioid'],
                'ano'          => (int) $m['ano'],
                'mes'          => $m['mes'],
                'mesnum'       => (int) $m['mesnum'],
            ];
        }
        foreach ($tmp as $ano => $mesesAnio) {
            $anios[] = [
                'ano'        => $ano,
                'is_current' => $ano === $anioActual ? 1 : 0,
                'meses'      => $mesesAnio,
            ];
        }
        usort($anios, function ($a, $b) {
            return $b['ano'] - $a['ano'];
        });

        return [
            'dealers'       => array_map(function ($d) {
                return [
                    'dealerid' => (int) $d['dealerid'],
                    'dealer'   => $d['dealer'],
                    'paisid'   => (int) $d['paisid'],
                    'pais'     => $d['pais'],
                ];
            }, $dealers),
            'paises'        => $paises,
            'anios'         => $anios,
            'periodos'      => [
                ['valor' => 'month',      'nombre' => 'Month'],
                ['valor' => 'ytd',        'nombre' => 'YTD'],
                ['valor' => 'fytd',       'nombre' => 'Fiscal YTD'],
                ['valor' => 'full_year',  'nombre' => 'Full Year'],
                ['valor' => 'l12m',       'nombre' => 'Last 12 Months'],
                ['valor' => 'prior_year', 'nombre' => 'Prior Year'],
            ],
            'comparaciones' => [
                ['valor' => 'none',         'nombre' => 'None'],
                ['valor' => 'cy_vs_py',     'nombre' => 'CY vs PY'],
                ['valor' => 'ytd_vs_py_ytd','nombre' => 'YTD vs PY YTD'],
                ['valor' => 'month_vs_py',  'nombre' => 'Month vs PY'],
            ],
        ];
    }

    /**
     * Etiqueta legible para el tipo de período seleccionado.
     */
    private static function etiquetaPeriodo($periodType, $anio, $fiscalStart)
    {
        switch ($periodType) {
            case 'month':      return 'Month ' . $anio;
            case 'ytd':        return 'YTD ' . $anio;
            case 'fytd':       return 'Fiscal YTD ' . $anio;
            case 'full_year':  return 'Full Year ' . $anio;
            case 'l12m':       return 'Last 12 Months';
            case 'prior_year': return 'Prior Year';
            default:           return (string) $anio;
        }
    }

    /**
     * Resuelve el año y mes de referencia + el rango de calendarioids
     * según el tipo de período (month/ytd/fytd/full_year/l12m/prior_year).
     *
     * @param int|null    $year
     * @param int|null    $calendarId
     * @param string      $periodType
     * @return array
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
             WHERE ano = %i AND mesnum <> 0 ORDER BY mesnum ASC",
            $anio
        );

        if (empty($calendariosAnio)) {
            return ['sin_periodo' => true, 'anio' => $anio];
        }

        // Mes de referencia (dentro del año)
        if (!empty($calendarId)) {
            $referencia = DB::queryFirstRow(
                "SELECT calendarioid, ano, mes, mesnum FROM kpi_calendarios WHERE calendarioid = %i",
                (int) $calendarId
            );
        } else {
            $referencia = DB::queryFirstRow(
                "SELECT c.calendarioid, c.ano, c.mes, c.mesnum
                 FROM kpi_calendarios c
                 WHERE c.ano = %i AND c.mesnum <> 0
                   AND (EXISTS (SELECT 1 FROM kpi_sales s WHERE s.calendarioid = c.calendarioid)
                        OR EXISTS (SELECT 1 FROM kpi_parts p WHERE p.calendarioid = c.calendarioid)
                        OR EXISTS (SELECT 1 FROM kpi_serviceorders o WHERE o.calendarioid = c.calendarioid))
                 ORDER BY c.mesnum DESC LIMIT 1",
                $anio
            );
        }
        if (empty($referencia)) {
            $referencia = $calendariosAnio[count($calendariosAnio) - 1];
        }

        $refCalendarioid = (int) $referencia['calendarioid'];
        $refMesnum       = (int) $referencia['mesnum'];
        $refAnio         = (int) $referencia['ano'];

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
                 FROM kpi_calendarios WHERE mesnum <> 0 ORDER BY ano ASC, mesnum ASC"
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
                 FROM kpi_calendarios WHERE ano = %i AND mesnum <> 0 ORDER BY mesnum ASC",
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
                $refAnio         = (int) $ult['ano'];
            }
        }

        return [
            'anio'                 => $anio,
            'fiscal_start'         => $fiscalStart,
            'periodo_actual'       => [
                'calendarioid' => $refCalendarioid,
                'ano'          => $refAnio,
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
     * Estado de una categoría para un dealer según la vigencia de su
     * última carga en el año de referencia (frente al mes de referencia).
     *
     * @param int $maxMes  último mes cargado (0 = sin carga en el año)
     * @param int $refMesnum
     * @return string 'aldia'|'pendiente'|'critico'|'inactivo'
     */
    private static function estadoCategoria($maxMes, $refMesnum)
    {
        if ($maxMes <= 0) {
            return 'inactivo';
        }
        if ($maxMes === $refMesnum) {
            return 'aldia';
        }
        if ($maxMes >= $refMesnum - 2) {
            return 'pendiente';
        }
        return 'critico';
    }

    /**
     * Estado general de un dealer según su % de cumplimiento.
     */
    private static function estadoGeneral($pct)
    {
        if ($pct >= 80) return 'aldia';
        if ($pct >= 50) return 'pendiente';
        if ($pct > 0)   return 'critico';
        return 'inactivo';
    }

    /**
     * Métricas del reporte Information Status.
     *
     * @param int|null    $year
     * @param int|null    $calendarId
     * @param int|int[]|null $dealerIds
     * @param int|int[]|null $countryIds
     * @param string      $periodType
     * @param string      $comparison
     * @return array
     */
    public static function getResumen($year, $calendarId, $dealerIds, $countryIds, $periodType = 'month', $comparison = 'none')
    {
        include_once('../config/init_db.php');

        $periodType = in_array($periodType, ['month', 'ytd', 'fytd', 'full_year', 'l12m', 'prior_year'], true) ? $periodType : 'month';
        $comparison = in_array($comparison, ['cy_vs_py', 'ytd_vs_py_ytd', 'month_vs_py'], true) ? $comparison : 'none';

        $periodo = self::resolverPeriodo($year, $calendarId, $periodType);
        if (!empty($periodo['sin_periodo'])) {
            return ['sin_periodo' => true, 'anio' => $periodo['anio']];
        }

        $refMesnum = (int) $periodo['periodo_actual']['mesnum'];
        $refAnio   = (int) $periodo['periodo_actual']['ano'];
        $rangoCalendarios = $periodo['rango_calendarioids'];

        // Dealers (con enforcement de rol y filtro de país)
        $dealers = self::dealersPermitidos($dealerIds);
        $countryIds = self::normalizarIds($countryIds);
        if (!empty($countryIds)) {
            $paisesSet = array_flip($countryIds);
            $dealers = array_values(array_filter($dealers, function ($d) use ($paisesSet) {
                return isset($paisesSet[(int) $d['paisid']]);
            }));
        }
        $totalDealers = count($dealers);
        $dealerIds2   = array_map(function ($d) {
            return (int) $d['dealerid'];
        }, $dealers);

        $categorias = self::categorias();

        $respuesta = [
            'resumen'   => [],
            'categorias'=> [],
            'dealers'   => [],
            'kpisCriticos' => [],
        ];

        if ($totalDealers === 0 || empty($rangoCalendarios)) {
            $respuesta['resumen'] = [
                'cumplimiento' => 0,
                'cargados'     => 0,
                'requeridos'   => 0,
                'conteos'      => ['aldia' => 0, 'pendiente' => 0, 'critico' => 0, 'inactivo' => 0, 'criticos_total' => 0],
                'etiqueta_periodo' => $periodo['etiqueta_periodo'],
                'periodo_actual'   => $periodo['periodo_actual'],
            ];
            return $respuesta;
        }

        $inClause = implode(',', array_fill(0, count($dealerIds2), '%i'));
        $inRango  = implode(',', array_fill(0, count($rangoCalendarios), '%i'));

        /*
        |--------------------------------------------------------------------------
        | Conteo de cargas por categoría / dealer dentro del rango
        |--------------------------------------------------------------------------
        */
        $cargadoPorCategoria = [];  // cat -> [dealerid => true]
        $cargadoMesDealer    = [];  // cat -> [calendarioid => [dealerid => true]]
        $dealerCategorias    = [];  // dealerid -> [cat => max mesnum en el año de referencia]

        foreach ($categorias as $cat) {
            $sql = "SELECT k.dealerid, k.calendarioid
                    FROM %b k
                    INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                    WHERE k.dealerid IN ($inClause) AND c.calendarioid IN ($inRango)";
            $args = array_merge([$cat['tabla']], $dealerIds2, $rangoCalendarios);
            if (!empty($cat['condicion'])) {
                $sql .= ' AND ' . $cat['condicion'];
            }
            $rowsRango = DB::query($sql, ...$args);

            foreach ($rowsRango as $row) {
                $did = (int) $row['dealerid'];
                $cal = (int) $row['calendarioid'];
                $cargadoPorCategoria[$cat['clave']][$did] = true;
                $cargadoMesDealer[$cat['clave']][$cal][$did] = true;
            }

            // Último mes cargado en el año de referencia (vigencia / estado)
            $sqlAnio = "SELECT k.dealerid, MAX(c.mesnum) AS max_mes
                        FROM %b k
                        INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                        WHERE c.ano = %i AND k.dealerid IN ($inClause)
                        GROUP BY k.dealerid";
            $argsAnio = array_merge([$cat['tabla']], [$refAnio], $dealerIds2);
            if (!empty($cat['condicion'])) {
                $sqlAnio .= ' AND ' . $cat['condicion'];
            }
            $rowsAnio = DB::query($sqlAnio, ...$argsAnio);
            foreach ($rowsAnio as $row) {
                $did = (int) $row['dealerid'];
                if (!isset($dealerCategorias[$did])) {
                    $dealerCategorias[$did] = [];
                }
                $dealerCategorias[$did][$cat['clave']] = (int) $row['max_mes'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Serie mensual por categoría (mini-barras)
        |--------------------------------------------------------------------------
        */
        $rangoInfo = DB::query(
            "SELECT calendarioid, ano, mes, mesnum
             FROM kpi_calendarios
             WHERE calendarioid IN ($inRango)
             ORDER BY ano ASC, mesnum ASC",
            ...$rangoCalendarios
        );

        $seriesPorCategoria = [];
        foreach ($categorias as $cat) {
            $serie = [];
            foreach ($rangoInfo as $rc) {
                $cal = (int) $rc['calendarioid'];
                $n   = isset($cargadoMesDealer[$cat['clave']][$cal]) ? count($cargadoMesDealer[$cat['clave']][$cal]) : 0;
                $serie[] = [
                    'calendarioid' => $cal,
                    'mes'          => $rc['mes'],
                    'mesnum'       => (int) $rc['mesnum'],
                    'porcentaje'   => $totalDealers > 0 ? (int) round(($n / $totalDealers) * 100) : 0,
                ];
            }
            $seriesPorCategoria[$cat['clave']] = $serie;
        }

        /*
        |--------------------------------------------------------------------------
        | Cumplimiento por categoría y general
        |--------------------------------------------------------------------------
        */
        $generalCargados   = 0;
        $generalRequeridos = $totalDealers * count($categorias);
        $categoriasResumen = [];

        foreach ($categorias as $cat) {
            $cargados   = isset($cargadoPorCategoria[$cat['clave']]) ? count($cargadoPorCategoria[$cat['clave']]) : 0;
            $porcentaje = $totalDealers > 0 ? (int) round(($cargados / $totalDealers) * 100) : 0;
            $generalCargados += $cargados;
            $categoriasResumen[$cat['clave']] = [
                'clave'      => $cat['clave'],
                'nombre'     => $cat['nombre'],
                'cargados'   => $cargados,
                'requeridos' => $totalDealers,
                'porcentaje' => $porcentaje,
                'serie'      => $seriesPorCategoria[$cat['clave']] ?? [],
            ];
        }

        $cumplimientoGeneral = $generalRequeridos > 0 ? (int) round(($generalCargados / $generalRequeridos) * 100) : 0;

        /*
        |--------------------------------------------------------------------------
        | Dealers: cumplimiento, estado general y estados por categoría
        |--------------------------------------------------------------------------
        */
        $conteos  = ['aldia' => 0, 'pendiente' => 0, 'critico' => 0, 'inactivo' => 0];
        $dealersResumen = [];

        foreach ($dealers as $dealer) {
            $did = (int) $dealer['dealerid'];

            $categoriasDealer = [];
            $cargadas = 0;
            foreach ($categorias as $cat) {
                $maxMes = $dealerCategorias[$did][$cat['clave']] ?? 0;
                $estado = self::estadoCategoria($maxMes, $refMesnum);

                // % de meses cargados dentro del rango (detalle de la tabla)
                $mesesCargados = 0;
                foreach ($rangoInfo as $rc) {
                    if (!empty($cargadoMesDealer[$cat['clave']][(int) $rc['calendarioid']][$did])) {
                        $mesesCargados++;
                    }
                }
                $pctCat = count($rangoInfo) > 0 ? (int) round(($mesesCargados / count($rangoInfo)) * 100) : 0;

                $categoriasDealer[$cat['clave']] = [
                    'clave'  => $cat['clave'],
                    'nombre' => $cat['nombre'],
                    'pct'    => $pctCat,
                    'estado' => $estado,
                ];

                if (!empty($cargadoPorCategoria[$cat['clave']][$did])) {
                    $cargadas++;
                }
                $conteos[$estado]++;
            }

            $pctDealer = count($categorias) > 0 ? (int) round(($cargadas / count($categorias)) * 100) : 0;
            $estadoGral = self::estadoGeneral($pctDealer);

            $dealersResumen[] = [
                'dealerid'   => $did,
                'nombre'     => $dealer['dealer'],
                'paisid'     => (int) $dealer['paisid'],
                'pais'       => $dealer['pais'],
                'bandera'    => $dealer['bandera'],
                'cargados'   => $cargadas,
                'requeridos' => count($categorias),
                'porcentaje' => $pctDealer,
                'estado'     => $estadoGral,
                'categorias' => $categoriasDealer,
            ];
        }
        $conteos['criticos_total'] = $conteos['critico'] + $conteos['inactivo'];

        /*
        |--------------------------------------------------------------------------
        | KPIs Críticos (Top 5): combinaciones dealer × categoría severas
        |--------------------------------------------------------------------------
        */
        $criticos = [];
        foreach ($dealersResumen as $dr) {
            foreach ($dr['categorias'] as $cat) {
                if ($cat['estado'] === 'critico' || $cat['estado'] === 'inactivo') {
                    $criticos[] = [
                        'dealer'    => $dr['nombre'],
                        'dealerid'  => $dr['dealerid'],
                        'categoria' => $cat['nombre'],
                        'clave'     => $cat['clave'],
                        'pct'       => $cat['pct'],
                        'estado'    => $cat['estado'],
                    ];
                }
            }
        }
        usort($criticos, function ($a, $b) {
            // inactivo (2) peor que critico (1); luego menor pct, luego alfabético
            $sevA = $a['estado'] === 'inactivo' ? 2 : 1;
            $sevB = $b['estado'] === 'inactivo' ? 2 : 1;
            if ($sevA !== $sevB) {
                return $sevB - $sevA;
            }
            if ($a['pct'] !== $b['pct']) {
                return $a['pct'] - $b['pct'];
            }
            return strcmp($a['dealer'], $b['dealer']);
        });
        $kpisCriticos = array_slice($criticos, 0, 5);

        /*
        |--------------------------------------------------------------------------
        | Comparación entre períodos (baseline + deltas)
        |--------------------------------------------------------------------------
        */
        $comparacion = null;
        if ($comparison !== 'none') {
            $baseYear      = (int) $periodo['anio'] - 1;
            $basePeriod    = 'full_year';
            $baseCalendarId = null;

            if ($comparison === 'month_vs_py' || $comparison === 'cy_vs_py') {
                $basePeriod = $comparison === 'month_vs_py' ? 'month' : 'full_year';
                if ($comparison === 'month_vs_py') {
                    $bc = DB::queryFirstRow(
                        "SELECT calendarioid FROM kpi_calendarios
                         WHERE ano = %i AND mesnum = %i ORDER BY calendarioid ASC LIMIT 1",
                        $baseYear,
                        $refMesnum
                    );
                    $baseCalendarId = $bc ? (int) $bc['calendarioid'] : null;
                }
            } elseif ($comparison === 'ytd_vs_py_ytd') {
                $basePeriod = 'ytd';
                $bc = DB::queryFirstRow(
                    "SELECT calendarioid FROM kpi_calendarios
                     WHERE ano = %i AND mesnum = %i ORDER BY calendarioid ASC LIMIT 1",
                    $baseYear,
                    $refMesnum
                );
                $baseCalendarId = $bc ? (int) $bc['calendarioid'] : null;
            }

            $base = self::getResumen($baseYear, $baseCalendarId, $dealerIds, $countryIds, $basePeriod, 'none');

            if (!empty($base) && empty($base['sin_periodo'])) {
                $baseResumen = $base['resumen'];
                $baseCats    = [];
                foreach ($base['categorias'] as $bcat) {
                    $baseCats[$bcat['clave']] = $bcat;
                }

                $deltasCategorias = [];
                foreach ($categoriasResumen as $cat) {
                    $bc2 = $baseCats[$cat['clave']] ?? null;
                    $deltasCategorias[] = [
                        'clave'      => $cat['clave'],
                        'porcentaje' => $cat['porcentaje'],
                        'base'       => $bc2 ? $bc2['porcentaje'] : null,
                        'delta_pp'   => $bc2 ? $cat['porcentaje'] - $bc2['porcentaje'] : null,
                    ];
                }

                $comparacion = [
                    'comparison'    => $comparison,
                    'anio_base'     => $baseYear,
                    'etiqueta_base' => $baseResumen['etiqueta_periodo'] ?? '',
                    'cumplimiento'  => $cumplimientoGeneral,
                    'base'          => $baseResumen['cumplimiento'] ?? 0,
                    'delta_pp'      => $cumplimientoGeneral - ($baseResumen['cumplimiento'] ?? 0),
                    'categorias'    => $deltasCategorias,
                ];
            }
        }

        $respuesta['resumen'] = [
            'cumplimiento'    => $cumplimientoGeneral,
            'cargados'        => $generalCargados,
            'requeridos'      => $generalRequeridos,
            'conteos'         => $conteos,
            'etiqueta_periodo'=> $periodo['etiqueta_periodo'],
            'periodo_actual'  => $periodo['periodo_actual'],
            'comparacion'     => $comparacion,
        ];

        $respuesta['categorias']    = array_values($categoriasResumen);
        $respuesta['dealers']       = $dealersResumen;
        $respuesta['kpisCriticos']  = $kpisCriticos;

        return $respuesta;
    }
}
