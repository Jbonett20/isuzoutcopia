<?php
/*
|--------------------------------------------------------------------------
| MODELO DASHBOARD
|--------------------------------------------------------------------------
| Encapsula el cálculo del "cumplimiento de carga de información" (KPIs)
| para el dashboard de inicio (admin/dashboard.php).
|
| Reglas:
|   - Todas las categorías de KPI son OBLIGATORIAS por dealer y período.
|   - Un dealer "cargó" una categoría si existe un registro en su tabla
|     para el período (mes) seleccionado.
|   - Cumplimiento por categoría = dealers con dato cargado / total dealers.
|   - Cumplimiento general       = registros cargados (cat×dealer) / (cat×dealers).
|   - Estado por dealer según su % de categorías cargadas:
|         On track  >= 80 | Pending >= 50 | Critical > 0 | Inactive = 0
|--------------------------------------------------------------------------
*/

@session_start();

if (!class_exists('Dashboard')) {
    class Dashboard
    {
        /**
         * Catálogo de categorías de KPI (todas obligatorias).
         * @return array
         */
        private static function categorias()
        {
            return [
                ['clave' => 'parts',     'nombre' => 'Parts',     'tabla' => 'kpi_parts',             'icono' => 'fa-cog',            'color' => 'parts'],
                ['clave' => 'sales',     'nombre' => 'Sales',     'tabla' => 'kpi_sales',             'icono' => 'fa-chart-line',     'color' => 'sales'],
                ['clave' => 'workshop',  'nombre' => 'Workshop',  'tabla' => 'kpi_workshopsales',     'icono' => 'fa-tools',          'color' => 'workshop'],
                ['clave' => 'service',   'nombre' => 'Service',   'tabla' => 'kpi_serviceorders',     'icono' => 'fa-clipboard-list', 'color' => 'service'],
                ['clave' => 'vehicles',  'nombre' => 'Vehicles',  'tabla' => 'kpi_vechiclesserviced', 'icono' => 'fa-car',            'color' => 'vehicles'],
                ['clave' => 'labor',     'nombre' => 'Labor',     'tabla' => 'kpi_laborrates',        'icono' => 'fa-dollar-sign',    'color' => 'labor'],
                ['clave' => 'personnel', 'nombre' => 'Personnel', 'tabla' => 'kpi_personnel',         'icono' => 'fa-users',          'color' => 'personnel'],
            ];
        }

        /**
         * Columnas CV/LCV por categoría (solo donde existen).
         * parts, labor y personnel no se segmentan por CV/LCV en el esquema.
         * @return array
         */
        private static function columnasVehiculo()
        {
            return [
                'sales'    => ['cv' => 'unitsalescv',              'lcv' => 'unitsaleslcv'],
                'workshop' => ['cv' => 'cvworkshoplaborsales',     'lcv' => 'lcvworkshoplaborsales'],
                'service'  => ['cv' => 'cvservicejob',             'lcv' => 'lcvservicejob'],
                'vehicles' => ['cv' => 'cvannualvehiclesserviced', 'lcv' => 'lcvannualvehiclesserviced'],
            ];
        }

        /**
         * Objetivos por dealer y año (Actual vs Target).
         * Lectura dinámica: se consulta kpi_dealertarget en cada petición,
         * de modo que los objetivos cargados en cualquier momento se reflejen.
         *
         * @param int|null   $year      Año de referencia
         * @param int[]      $dealerIds Dealers visibles (ya sanitizados)
         *
         * @return array dealerid => objetivo (int)
         */
        private static function objetivosDealers($year, $dealerIds)
        {
            if (empty($year) || empty($dealerIds)) {
                return [];
            }

            include_once('../config/init_db.php');

            $in = implode(',', array_fill(0, count($dealerIds), '%i'));
            $rows = DB::query(
                "SELECT dealerid, objetivo
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
         * Contexto de rol/sesión para filtrar por permisos.
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
         * Normaliza uno o varios ids a un array plano de enteros únicos.
         * Acepta int, string CSV o array.
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
         * Lista de dealers visibles según la sesión (admin ve todos,
         * dealer normal solo el suyo). Acepta filtro por uno o varios dealerIds.
         *
         * Enforcement de rol:
         *   - No-admin: siempre forzado a su propio dealer (ignora dealerIds).
         *   - Admin: los dealerIds se sanearizan con normalizarIds() antes del IN.
         *
         * @param int|int[]|null $dealerIds
         * @return array
         */
        private static function dealersPermitidos($dealerIds = [])
        {
            include_once('../config/init_db.php');

            $rol = self::rolActual();

            // No-admin: siempre limitado a su propio dealer
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
         * años, países (con dealers visibles), dealers (con su país) y tipos de vehículo.
         * Respeta el rol de sesión: un no-admin solo ve su dealer y su país.
         *
         * @return array
         */
        public static function getFiltrosDisponibles()
        {
            include_once('../config/init_db.php');

            try {
                $rol = self::rolActual();

                // Años disponibles en el calendario KPI
                $anios = DB::query(
                    "SELECT ano,
                            (ano = YEAR(CURDATE())) AS is_current
                     FROM kpi_calendarios
                     GROUP BY ano
                     ORDER BY ano DESC"
                );
                $anios = array_map(function ($a) {
                    // Meses del año (para el selector de mes de referencia);
                    // se excluye la fila dummy mesnum=0 que existe en la tabla.
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

                // Dealers visibles con su país (para agrupar y etiquetar la barra)
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

                // Países con al menos un dealer visible (ordenados por nombre)
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
                error_log("Error en Dashboard::getFiltrosDisponibles: " . $e->getMessage());
                throw $e;
            }
        }

        /**
         * Resuelve el tipo de período a un rango de calendarioids y el mes de referencia.
         *
         * Tipos: month | ytd | fytd | full_year | l12m | prior_year.
         * El inicio del año fiscal se lee de FISCAL_START_MONTH (default 1 = enero).
         *
         * @param int|null $year       Año de referencia
         * @param int|null $calendarId Mes de referencia dentro del año
         * @param string   $periodType Tipo de período (ya normalizado)
         *
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
                 WHERE ano = %i ORDER BY mesnum ASC",
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
                     WHERE c.ano = %i
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

            // --- Construcción del rango según el tipo de período ---
            $rango        = [];  // calendarioids
            $rangoMesnums = [];  // mesnum de cada calendario en el rango

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
                     FROM kpi_calendarios ORDER BY ano ASC, mesnum ASC"
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
                     FROM kpi_calendarios WHERE ano = %i ORDER BY mesnum ASC",
                    $anioPrev
                );
                foreach ($calsPrev as $c) {
                    $rango[]        = (int) $c['calendarioid'];
                    $rangoMesnums[] = (int) $c['mesnum'];
                }
                if (!empty($calsPrev)) {
                    $ult            = $calsPrev[count($calsPrev) - 1];
                    $referencia     = $ult;
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
         * Etiqueta legible para el tipo de período seleccionado.
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
         * Devuelve el resumen del dashboard de cumplimiento de carga.
         *
         * @param int|null        $year        Año (usa el más reciente con datos si null)
         * @param int|null        $calendarId  Período/mes dentro del año
         * @param int|int[]|null  $dealerIds   Filtro por uno o varios dealers (solo admin)
         * @param int|int[]|null  $countryIds  Filtro por uno o varios países (se aplica en el desglose)
         * @param string          $vehicleType 'all' | 'cv' | 'lcv' (global; no altera el % de carga)
         * @param string          $periodType  'month' | 'ytd' | 'fytd' | 'full_year' | 'l12m' | 'prior_year'
         * @param string          $comparison  'none' | 'cy_vs_py' | 'ytd_vs_py_ytd' | 'month_vs_py'
         *
         * @return array
         */
        public static function getResumen(
            $year = null,
            $calendarId = null,
            $dealerIds = [],
            $countryIds = [],
            $vehicleType = 'all',
            $periodType = 'month',
            $comparison = 'none'
        ) {
            include_once('../config/init_db.php');

            try {
                // Normalización y validación de parámetros globales
                $dealerIds   = self::normalizarIds($dealerIds);
                $countryIds  = self::normalizarIds($countryIds);
                $vehicleType = in_array($vehicleType, ['cv', 'lcv'], true) ? $vehicleType : 'all';
                $periodType  = in_array($periodType, ['ytd', 'fytd', 'full_year', 'l12m', 'prior_year'], true) ? $periodType : 'month';
                $comparison  = in_array($comparison, ['cy_vs_py', 'ytd_vs_py_ytd', 'month_vs_py'], true) ? $comparison : 'none';

                $categorias = self::categorias();
                $dealers    = self::dealersPermitidos($dealerIds);

                // Filtro por país: no-admin ya está limitado a su propio dealer, se ignora el filtro
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

                $dealerIds    = array_column($dealers, 'dealerid');
                $totalDealers = count($dealerIds);
                $totalCats    = count($categorias);

                if ($totalDealers === 0) {
                    return [];
                }

                // Período de referencia y rango según el tipo de período
                $periodo = self::resolverPeriodo($year, $calendarId, $periodType);

                if (!empty($periodo['sin_periodo'])) {
                    return ['sin_periodo' => true, 'anio' => $year];
                }

                $year             = $periodo['anio'];
                $periodoActual    = $periodo['periodo_actual'];
                $calendariosYear  = $periodo['periodos_disponibles']; // ya mapeado para la respuesta
                $rangoCalendarios = $periodo['rango_calendarioids'];
                $etiquetaPeriodo  = $periodo['etiqueta_periodo'];

                $refCalendarioid = (int) $periodoActual['calendarioid'];
                $refMesnum       = (int) $periodoActual['mesnum'];
                $refAnio         = (int) $periodoActual['ano'];

                /*
                |--------------------------------------------------------------------------
                | Conteo de cargas por categoría / dealer dentro del rango del período
                |--------------------------------------------------------------------------
                | - cargadoPorCategoria: dealer/cat con registro dentro del rango (cumplimiento)
                | - ultimaCargaAnio:     último mes cargado en el año de referencia (antigüedad)
                |--------------------------------------------------------------------------
                */
                $inClause            = implode(',', array_fill(0, count($dealerIds), '%i'));
                $inRango             = implode(',', array_fill(0, count($rangoCalendarios), '%i'));
                $cargadoPorCategoria = [];  // cat -> [dealerid => true] (cargado en el rango)
                $cargadoMesDealer    = [];  // cat -> [calendarioid => [dealerid => true]] (para la tendencia)
                $dealerCategorias    = [];  // dealerid -> [cat => max mesnum en el año de referencia]
                $dealerCategoriasAnio = (int) $refAnio;

                foreach ($categorias as $cat) {
                    // Registros dentro del rango del período (puede cruzar años: l12m/prior_year)
                    if (!empty($rangoCalendarios)) {
                        $rowsRango = DB::query(
                            "SELECT k.dealerid, k.calendarioid
                             FROM %b k
                             INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                             WHERE k.dealerid IN ($inClause) AND c.calendarioid IN ($inRango)",
                            $cat['tabla'],
                            ...$dealerIds,
                            ...$rangoCalendarios
                        );
                        foreach ($rowsRango as $row) {
                            $did = (int) $row['dealerid'];
                            $cal = (int) $row['calendarioid'];
                            $cargadoPorCategoria[$cat['clave']][$did] = true;
                            $cargadoMesDealer[$cat['clave']][$cal][$did] = true;
                        }
                    }

                    // Último mes cargado en el año de referencia (para antigüedad / estado)
                    $rowsAnio = DB::query(
                        "SELECT k.dealerid, MAX(c.mesnum) AS max_mes
                         FROM %b k
                         INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                         WHERE c.ano = %i AND k.dealerid IN ($inClause)
                         GROUP BY k.dealerid",
                        $cat['tabla'],
                        $dealerCategoriasAnio,
                        ...$dealerIds
                    );
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
                | Tendencia mensual: cumplimiento general y por categoría por mes del rango
                |--------------------------------------------------------------------------
                */
                $tendencia = [];
                if (!empty($rangoCalendarios)) {
                    $rangoInfo = DB::query(
                        "SELECT calendarioid, ano, mes, mesnum
                         FROM kpi_calendarios
                         WHERE calendarioid IN ($inRango)
                         ORDER BY ano ASC, mesnum ASC",
                        ...$rangoCalendarios
                    );

                    $requeridosMes = $totalDealers * $totalCats;
                    foreach ($rangoInfo as $rc) {
                        $cal = (int) $rc['calendarioid'];
                        $loadedMes    = 0;
                        $porCategoria = [];
                        foreach ($categorias as $cat) {
                            $n = isset($cargadoMesDealer[$cat['clave']][$cal])
                                ? count($cargadoMesDealer[$cat['clave']][$cal])
                                : 0;
                            $porCategoria[$cat['clave']] = $totalDealers > 0
                                ? (int) round(($n / $totalDealers) * 100)
                                : 0;
                            $loadedMes += $n;
                        }
                        $tendencia[] = [
                            'calendarioid' => $cal,
                            'ano'          => (int) $rc['ano'],
                            'mes'          => $rc['mes'],
                            'mesnum'       => (int) $rc['mesnum'],
                            'cargados'     => $loadedMes,
                            'requeridos'   => $requeridosMes,
                            'cumplimiento' => $requeridosMes > 0 ? (int) round(($loadedMes / $requeridosMes) * 100) : 0,
                            'categorias'   => $porCategoria,
                        ];
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Desglose CV/LCV: dealers con dato CV/LCV en el rango, por categoría.
                | Solo para categorías con columnas CV/LCV. NO altera el % de carga binario.
                |--------------------------------------------------------------------------
                */
                $cvLcvResumen = [];
                $colVehiculo  = self::columnasVehiculo();
                if (!empty($rangoCalendarios)) {
                    foreach ($categorias as $cat) {
                        $cols = $colVehiculo[$cat['clave']] ?? null;
                        if ($cols === null) {
                            $cvLcvResumen[] = [
                                'clave'        => $cat['clave'],
                                'nombre'       => $cat['nombre'],
                                'cv'           => null,
                                'lcv'          => null,
                                'total_dealers'=> $totalDealers,
                            ];
                            continue;
                        }
                        $row = DB::queryFirstRow(
                            "SELECT
                                COUNT(DISTINCT CASE WHEN k.%b > 0 THEN k.dealerid END) AS cnt_cv,
                                COUNT(DISTINCT CASE WHEN k.%b > 0 THEN k.dealerid END) AS cnt_lcv
                             FROM %b k
                             INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                             WHERE k.dealerid IN ($inClause) AND c.calendarioid IN ($inRango)",
                            $cols['cv'],
                            $cols['lcv'],
                            $cat['tabla'],
                            ...$dealerIds,
                            ...$rangoCalendarios
                        );
                        $cvLcvResumen[] = [
                            'clave'        => $cat['clave'],
                            'nombre'       => $cat['nombre'],
                            'cv'           => (int) $row['cnt_cv'],
                            'lcv'          => (int) $row['cnt_lcv'],
                            'total_dealers'=> $totalDealers,
                        ];
                    }
                } else {
                    foreach ($categorias as $cat) {
                        $cvLcvResumen[] = [
                            'clave'        => $cat['clave'],
                            'nombre'       => $cat['nombre'],
                            'cv'           => null,
                            'lcv'          => null,
                            'total_dealers'=> $totalDealers,
                        ];
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Tarjetas: cumplimiento por categoría
                |--------------------------------------------------------------------------
                */
                $categoriasResumen = [];
                foreach ($categorias as $cat) {
                    $cargados   = isset($cargadoPorCategoria[$cat['clave']]) ? count($cargadoPorCategoria[$cat['clave']]) : 0;
                    $porcentaje = $totalDealers > 0 ? (int) round(($cargados / $totalDealers) * 100) : 0;
                    $categoriasResumen[] = $cat + [
                        'cargados'   => $cargados,
                        'requeridos' => $totalDealers,
                        'porcentaje' => $porcentaje,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Banner y barras: estado por dealer
                |--------------------------------------------------------------------------
                */
                $generalCargados   = 0;
                $generalRequeridos = $totalDealers * $totalCats;
                $dealersResumen    = [];
                $conteoEstados     = ['on_track' => 0, 'pending' => 0, 'critical' => 0, 'inactive' => 0];

                foreach ($dealers as $dealer) {
                    $did = (int) $dealer['dealerid'];

                    $cargadas = 0;
                    foreach ($categorias as $cat) {
                        if (!empty($cargadoPorCategoria[$cat['clave']][$did])) {
                            $cargadas++;
                        }
                    }

                    $generalCargados += $cargadas;
                    $porc = $totalCats > 0 ? (int) round(($cargadas / $totalCats) * 100) : 0;

                    if ($porc === 0) {
                        $estado = 'inactive';
                    } elseif ($porc >= 80) {
                        $estado = 'on_track';
                    } elseif ($porc >= 50) {
                        $estado = 'pending';
                    } else {
                        $estado = 'critical';
                    }

                    $conteoEstados[$estado]++;
                    $dealersResumen[] = [
                        'dealerid'   => $did,
                        'nombre'     => $dealer['dealer'],
                        'cargados'   => $cargadas,
                        'requeridos' => $totalCats,
                        'porcentaje' => $porc,
                        'estado'     => $estado,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Desglose por país: agrega el cumplimiento de sus dealers
                |--------------------------------------------------------------------------
                */
                $dealerResumenPorId = [];
                foreach ($dealersResumen as $dr) {
                    $dealerResumenPorId[$dr['dealerid']] = $dr;
                }

                $paisesResumen = [];
                foreach ($dealers as $dealer) {
                    $did = (int) $dealer['dealerid'];
                    $pid = (int) $dealer['paisid'];
                    $dr  = $dealerResumenPorId[$did] ?? null;
                    if ($dr === null) {
                        continue;
                    }
                    if (!isset($paisesResumen[$pid])) {
                        $paisesResumen[$pid] = [
                            'paisid'     => $pid,
                            'pais'       => $dealer['pais'],
                            'bandera'    => $dealer['bandera'],
                            'dealers'    => 0,
                            'cargados'   => 0,
                            'requeridos' => 0,
                            'porcentaje' => 0,
                        ];
                    }
                    $paisesResumen[$pid]['dealers']++;
                    $paisesResumen[$pid]['cargados']   += $dr['cargados'];
                    $paisesResumen[$pid]['requeridos'] += $dr['requeridos'];
                }
                foreach ($paisesResumen as &$p) {
                    $p['porcentaje'] = $p['requeridos'] > 0 ? (int) round(($p['cargados'] / $p['requeridos']) * 100) : 0;
                }
                unset($p);
                usort($paisesResumen, function ($a, $b) {
                    return strcmp($a['pais'], $b['pais']);
                });
                $paisesResumen = array_values($paisesResumen);

                $cumplimientoGeneral = $generalRequeridos > 0 ? (int) round(($generalCargados / $generalRequeridos) * 100) : 0;

                /*
                |--------------------------------------------------------------------------
                | Tabla: KPIs críticos / pendientes por dealer
                |--------------------------------------------------------------------------
                */
                $nombreLabels = [
                    'parts'     => 'Inventario / Ventas / Fill Rate',
                    'sales'     => 'Ventas CV / LCV / Total',
                    'workshop'  => 'Labores / Garantías / Mantenimiento',
                    'service'   => 'Order Jobs / PDI / Garantías',
                    'vehicles'  => 'Vehículos atendidos',
                    'labor'     => 'Tarifas laborales',
                    'personnel' => 'Personal técnico',
                ];

                $kpisCriticos = [];
                foreach ($dealers as $dealer) {
                    $did        = (int) $dealer['dealerid'];
                    $catsDealer = $dealerCategorias[$did] ?? [];

                    foreach ($categorias as $cat) {
                        $clave = $cat['clave'];

                        // Al día si tiene registro dentro del rango del período
                        if (!empty($cargadoPorCategoria[$clave][$did])) {
                            continue;
                        }

                        $ultimoMesNum = $catsDealer[$clave] ?? null;
                        if ($ultimoMesNum === null) {
                            $kpisCriticos[] = [
                                'dealer'       => $dealer['dealer'],
                                'dealerid'     => $did,
                                'categoria'    => $cat['nombre'],
                                'kpi'          => $nombreLabels[$clave] ?? $cat['nombre'],
                                'estado'       => 'pendiente',
                                'estado_label' => 'Pendiente',
                                'periodo'      => $periodoActual['mes'] . ' ' . $periodoActual['ano'],
                                'edad'         => null,
                            ];
                        } else {
                            $dif = $refMesnum - $ultimoMesNum;
                            if ($dif >= 2) {
                                $estado = 'vencido';
                                $label  = 'Vencido';
                            } else {
                                $estado = 'por_vencer';
                                $label  = 'Por vencer';
                            }
                            $kpisCriticos[] = [
                                'dealer'       => $dealer['dealer'],
                                'dealerid'     => $did,
                                'categoria'    => $cat['nombre'],
                                'kpi'          => $nombreLabels[$clave] ?? $cat['nombre'],
                                'estado'       => $estado,
                                'estado_label' => $label,
                                'periodo'      => $periodoActual['mes'] . ' ' . $periodoActual['ano'],
                                'edad'         => $dif . 'm',
                            ];
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Comparación entre períodos (baseline + deltas)
                |--------------------------------------------------------------------------
                | none            -> sin baseline
                | cy_vs_py        -> baseline = año completo anterior
                | ytd_vs_py_ytd   -> baseline = YTD del año anterior (mismo mes de referencia)
                | month_vs_py     -> baseline = mismo mes del año anterior
                |--------------------------------------------------------------------------
                */
                $comparacion = null;
                if ($comparison !== 'none') {
                    $baseYear      = $year - 1;
                    $basePeriod    = 'full_year';
                    $baseCalendarId = null;

                    if ($comparison === 'month_vs_py') {
                        $basePeriod = 'month';
                        $bc = DB::queryFirstRow(
                            "SELECT calendarioid FROM kpi_calendarios
                             WHERE ano = %i AND mesnum = %i ORDER BY calendarioid ASC LIMIT 1",
                            $baseYear,
                            $refMesnum
                        );
                        $baseCalendarId = $bc ? (int) $bc['calendarioid'] : null;
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

                    $base = self::getResumen(
                        $baseYear,
                        $baseCalendarId,
                        $dealerIds,
                        $countryIds,
                        $vehicleType,
                        $basePeriod,
                        'none'
                    );

                    if (!empty($base) && empty($base['sin_periodo'])) {
                        $comparacion = [
                            'comparison'     => $comparison,
                            'anio_base'      => $baseYear,
                            'etiqueta_base'  => $base['etiqueta_periodo'] ?? '',
                            'periodo_actual' => $base['periodo_actual'] ?? null,
                            'totales'        => $base['totales'],
                            'categorias'     => $base['categorias'],
                            'paises'         => $base['paises'],
                            'tendencia'      => $base['tendencia'],
                        ];

                        $baseTot = $base['totales'];
                        $comparacion['deltas'] = [
                            'cumplimiento'  => $cumplimientoGeneral,
                            'base'          => $baseTot['cumplimiento'],
                            'delta_pp'      => $cumplimientoGeneral - $baseTot['cumplimiento'],
                            'cargados'      => $generalCargados,
                            'base_cargados' => $baseTot['cargados'],
                        ];

                        $deltasCategorias = [];
                        foreach ($categoriasResumen as $cat) {
                            $baseCat = null;
                            foreach ($base['categorias'] as $bc2) {
                                if ($bc2['clave'] === $cat['clave']) {
                                    $baseCat = $bc2;
                                    break;
                                }
                            }
                            $deltasCategorias[] = [
                                'clave'         => $cat['clave'],
                                'porcentaje'    => $cat['porcentaje'],
                                'base'          => $baseCat ? $baseCat['porcentaje'] : null,
                                'delta_pp'      => $baseCat ? $cat['porcentaje'] - $baseCat['porcentaje'] : null,
                                'cargados'      => $cat['cargados'],
                                'base_cargados' => $baseCat ? $baseCat['cargados'] : null,
                            ];
                        }
                        $comparacion['deltas']['categorias'] = $deltasCategorias;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Objetivos (Actual vs Target): lectura dinámica de kpi_dealertarget
                |--------------------------------------------------------------------------
                */
                $mapaObjetivos  = self::objetivosDealers($year, $dealerIds);
                $objetivosItems = [];
                $conObjetivo    = 0;
                $cumplen        = 0;
                $sumaObjetivo   = 0;
                $sumaActualObj  = 0;

                foreach ($dealersResumen as $dr) {
                    $did    = (int) $dr['dealerid'];
                    $actual = (int) $dr['porcentaje'];
                    $obj    = isset($mapaObjetivos[$did]) ? $mapaObjetivos[$did] : null;

                    $objetivosItems[] = [
                        'dealerid' => $did,
                        'nombre'   => $dr['nombre'],
                        'actual'   => $actual,
                        'objetivo' => $obj,
                        'delta_pp' => $obj !== null ? $actual - $obj : null,
                        'cumple'   => $obj !== null ? ($actual >= $obj) : null,
                    ];

                    if ($obj !== null) {
                        $conObjetivo++;
                        $sumaObjetivo  += $obj;
                        $sumaActualObj += $actual;
                        if ($actual >= $obj) {
                            $cumplen++;
                        }
                    }
                }

                $objetivos = [
                    'items' => $objetivosItems,
                    'resumen' => [
                        'con_objetivo'      => $conObjetivo,
                        'cumplen'           => $cumplen,
                        'no_cumplen'        => $conObjetivo - $cumplen,
                        'promedio_objetivo' => $conObjetivo > 0 ? (int) round($sumaObjetivo / $conObjetivo) : null,
                        'promedio_actual'   => $conObjetivo > 0 ? (int) round($sumaActualObj / $conObjetivo) : null,
                    ],
                ];

                return [
                    'anio'                 => (int) $year,
                    'periodo_actual'       => $periodoActual,
                    'periodos_disponibles' => $calendariosYear,
                    'rango_periodo'        => [
                        'calendarioids' => $rangoCalendarios,
                        'mesnums'       => $periodo['rango_mesnums'],
                    ],
                    'etiqueta_periodo'     => $etiquetaPeriodo,
                    'totales'              => [
                        'dealers'      => $totalDealers,
                        'categorias'   => $totalCats,
                        'cargados'     => $generalCargados,
                        'requeridos'   => $generalRequeridos,
                        'cumplimiento' => $cumplimientoGeneral,
                        'on_track'     => $conteoEstados['on_track'],
                        'pending'      => $conteoEstados['pending'],
                        'critical'     => $conteoEstados['critical'],
                        'inactive'     => $conteoEstados['inactive'],
                    ],
                    'categorias'           => $categoriasResumen,
                    'dealers'              => $dealersResumen,
                    'paises'               => $paisesResumen,
                    'tendencia'            => $tendencia,
                    'cv_lcv'               => $cvLcvResumen,
                    'kpis_criticos'        => $kpisCriticos,
                    'comparacion'          => $comparacion,
                    'objetivos'            => $objetivos,
                    'filtros_aplicados'    => [
                        'anio'        => (int) $year,
                        'calendarId'  => $refCalendarioid,
                        'dealerIds'   => $dealerIds,
                        'countryIds'  => $countryIds,
                        'vehicleType' => $vehicleType,
                        'periodType'  => $periodType,
                        'comparison'  => $comparison,
                    ],
                ];
            } catch (Exception $e) {
                error_log("Error en Dashboard::getResumen: " . $e->getMessage());
                throw $e;
            }
        }
    }
}
