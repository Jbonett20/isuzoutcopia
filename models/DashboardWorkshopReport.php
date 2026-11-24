<?php
/*
|--------------------------------------------------------------------------
| DASHBOARD DE WORKSHOP REPORT (SERVICIO) - admin/workshopReport.php
|--------------------------------------------------------------------------
| Modelo auto-contenido del tablero de reporte de taller.
|
| Mide el desempeño del negocio de servicio del dealer:
|   - Service Orders   = Σ trabajos de servicio de kpi_serviceorders (fleet +
|                        internal + maintenance + warranty + pdi) del período
|   - Var % vs LY      = (órdenes − órdenes LY) / órdenes LY × 100
|   - Retención        = null (sin fuente en tablas KPI; Open Question)
|   - Open Orders      = null (sin fuente; Open Question)
|   - Referencia       = rangos de retención (Verde >12 / Amarillo 9-12 / Rojo <9)
|
| Fuentes: kpi_serviceorders, kpi_calendarios, ots_dealers/ots_paises.
|--------------------------------------------------------------------------
*/

@session_start();

if (!class_exists('DashboardWorkshopReport')) {
    class DashboardWorkshopReport
    {
        /**
         * Contexto de rol/sesión.
         * @return array
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
         * Resuelve el rango de calendarioids del período seleccionado.
         * @param int|null $year
         * @param int|null $calendarId
         * @param string   $periodType month|ytd|fytd|full_year|l12m|prior_year
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

            if (!empty($calendarId)) {
                $referencia = DB::queryFirstRow(
                    "SELECT calendarioid, ano, mes, mesnum FROM kpi_calendarios WHERE calendarioid = %i",
                    (int) $calendarId
                );
            } else {
                // Mes de referencia: el último con órdenes de servicio > 0 del año.
                $referencia = DB::queryFirstRow(
                    "SELECT c.calendarioid, c.ano, c.mes, c.mesnum
                     FROM kpi_calendarios c
                     INNER JOIN (
                         SELECT calendarioid, SUM(totalnumberfleetjob + totalnumberinternaljob
                              + totalnumbermaintenance + totalwarrantyjob + totalpdi) AS tot
                         FROM kpi_serviceorders GROUP BY calendarioid
                     ) o ON o.calendarioid = c.calendarioid
                     WHERE c.ano = %i AND c.mesnum <> 0 AND o.tot > 0
                     ORDER BY c.mesnum DESC LIMIT 1",
                    $anio
                );
                if (empty($referencia)) {
                    $referencia = DB::queryFirstRow(
                        "SELECT c.calendarioid, c.ano, c.mes, c.mesnum
                         FROM kpi_calendarios c
                         WHERE c.ano = %i AND c.mesnum <> 0
                           AND EXISTS (SELECT 1 FROM kpi_sales s WHERE s.calendarioid = c.calendarioid)
                         ORDER BY c.mesnum DESC LIMIT 1",
                        $anio
                    );
                }
            }
            if (empty($referencia)) {
                $referencia = $calendariosAnio[count($calendariosAnio) - 1];
            }

            $refCalendarioid = (int) $referencia['calendarioid'];
            $refMesnum       = (int) $referencia['mesnum'];

            $rango = [];
            if ($periodType === 'month') {
                $rango = [$refCalendarioid];
            } elseif ($periodType === 'ytd' || $periodType === 'fytd') {
                foreach ($calendariosAnio as $c) {
                    $mn = (int) $c['mesnum'];
                    if ($mn >= $fiscalStart && $mn <= $refMesnum) {
                        $rango[] = (int) $c['calendarioid'];
                    }
                }
            } elseif ($periodType === 'full_year') {
                foreach ($calendariosAnio as $c) {
                    $rango[] = (int) $c['calendarioid'];
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
                    $rango[] = (int) $cals[$i]['calendarioid'];
                }
            } elseif ($periodType === 'prior_year') {
                $calsPrev = DB::query(
                    "SELECT calendarioid, ano, mesnum
                     FROM kpi_calendarios WHERE ano = %i AND mesnum <> 0 ORDER BY mesnum ASC",
                    $anio - 1
                );
                foreach ($calsPrev as $c) {
                    $rango[] = (int) $c['calendarioid'];
                }
            }

            return [
                'anio'       => $anio,
                'rango'      => $rango,
                'referencia' => [
                    'calendarioid' => $refCalendarioid,
                    'mes'          => $referencia['mes'] ?? '',
                    'mesnum'       => $refMesnum,
                    'ano'          => (int) $referencia['ano'],
                ],
                'etiqueta'   => self::etiquetaPeriodo($periodType, $anio, $fiscalStart),
            ];
        }

        /**
         * Etiqueta legible para el tipo de período.
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
         * Suma de órdenes de servicio sobre un rango de calendarioids.
         * @param int[] $dealerIds
         * @param int[] $rango
         * @return float
         */
        private static function ordenesRango($dealerIds, $rango)
        {
            if (empty($dealerIds) || empty($rango)) {
                return 0.0;
            }
            include_once('../config/init_db.php');

            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
            $inR = implode(',', array_fill(0, count($rango), '%i'));
            $args = array_merge($dealerIds, $rango);
            $v = DB::queryFirstField(
                "SELECT COALESCE(SUM(k.totalnumberfleetjob + k.totalnumberinternaljob
                        + k.totalnumbermaintenance + k.totalwarrantyjob + k.totalpdi), 0)
                 FROM kpi_serviceorders k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD) AND c.calendarioid IN ($inR)",
                ...$args
            );
            return (float) $v;
        }

        /**
         * Rango LY: mismo período desplazado un año (mismo mesnum, año − 1).
         * Para l12m usa los 12 meses anteriores al rango actual.
         * @param int[]  $rangoActual
         * @param string $periodType
         * @return int[]
         */
        private static function rangoLY($rangoActual, $periodType)
        {
            if (empty($rangoActual)) {
                return [];
            }
            include_once('../config/init_db.php');

            if ($periodType === 'l12m') {
                $inR = implode(',', array_fill(0, count($rangoActual), '%i'));
                $cals = DB::query(
                    "SELECT calendarioid, ano, mesnum
                     FROM kpi_calendarios
                     WHERE calendarioid IN ($inR) AND mesnum <> 0
                     ORDER BY ano ASC, mesnum ASC",
                    ...$rangoActual
                );
                if (empty($cals)) {
                    return [];
                }
                $ref = $cals[0];
                $calsAll = DB::query(
                    "SELECT calendarioid, ano, mesnum
                     FROM kpi_calendarios WHERE mesnum <> 0 ORDER BY ano ASC, mesnum ASC"
                );
                $idx = -1;
                foreach ($calsAll as $i => $c) {
                    if ((int) $c['calendarioid'] === (int) $ref['calendarioid']) {
                        $idx = $i;
                        break;
                    }
                }
                if ($idx < 0) {
                    return [];
                }
                $ly = [];
                $desde = max(0, $idx - 12);
                for ($i = $desde; $i < $idx; $i++) {
                    $ly[] = (int) $calsAll[$i]['calendarioid'];
                }
                return $ly;
            }

            $mapa = [];
            $all = DB::query("SELECT calendarioid, ano, mesnum FROM kpi_calendarios WHERE mesnum <> 0");
            foreach ($all as $c) {
                $mapa[(int) $c['ano']][(int) $c['mesnum']] = (int) $c['calendarioid'];
            }

            $inR = implode(',', array_fill(0, count($rangoActual), '%i'));
            $rows = DB::query(
                "SELECT calendarioid, ano, mesnum FROM kpi_calendarios
                 WHERE calendarioid IN ($inR) AND mesnum <> 0",
                ...$rangoActual
            );
            $ly = [];
            foreach ($rows as $r) {
                $anoPrev = (int) $r['ano'] - 1;
                $mesnum  = (int) $r['mesnum'];
                if (isset($mapa[$anoPrev][$mesnum])) {
                    $ly[] = $mapa[$anoPrev][$mesnum];
                }
            }
            return array_values(array_unique($ly));
        }

        /**
         * Serie mensual de órdenes (últimos 12 meses hasta el mes de referencia).
         * @param int[] $dealerIds
         * @param int   $anioFin
         * @param int   $mesFin
         * @return array
         */
        private static function serieMensual($dealerIds, $anioFin, $mesFin)
        {
            $serie = [];
            if (empty($dealerIds)) {
                return $serie;
            }
            include_once('../config/init_db.php');

            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));

            $cals = DB::query(
                "SELECT calendarioid, ano, mes, mesnum
                 FROM kpi_calendarios
                 WHERE (ano < %i OR (ano = %i AND mesnum <= %i))
                   AND mesnum >= 1
                 ORDER BY ano DESC, mesnum DESC
                 LIMIT 12",
                $anioFin,
                $anioFin,
                $mesFin
            );
            $cals = array_reverse($cals);
            if (empty($cals)) {
                return $serie;
            }

            $rows = DB::query(
                "SELECT c.calendarioid, c.ano, c.mesnum,
                        COALESCE(SUM(k.totalnumberfleetjob + k.totalnumberinternaljob
                              + k.totalnumbermaintenance + k.totalwarrantyjob + k.totalpdi), 0) AS orders
                 FROM kpi_serviceorders k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD) AND c.mesnum >= 1
                 GROUP BY c.calendarioid, c.ano, c.mesnum",
                ...$dealerIds
            );
            $porCal = [];
            foreach ($rows as $r) {
                $porCal[(int) $r['calendarioid']] = (float) $r['orders'];
            }

            foreach ($cals as $rc) {
                $cal = (int) $rc['calendarioid'];
                $serie[] = [
                    'calendarioid' => $cal,
                    'mes'          => $rc['mes'],
                    'mesnum'       => (int) $rc['mesnum'],
                    'orders'       => $porCal[$cal] ?? 0.0,
                ];
            }
            return $serie;
        }

        /**
         * Opciones para la barra de filtros (dealers, países, años con meses, períodos).
         * Respeta el rol de sesión.
         * @return array
         */
        public static function getFiltrosDisponibles()
        {
            include_once('../config/init_db.php');

            $dealers = self::dealersPermitidos();

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

            $meses = DB::query(
                "SELECT calendarioid, ano, mes, mesnum
                 FROM kpi_calendarios
                 WHERE mesnum <> 0
                 ORDER BY ano ASC, mesnum ASC"
            );
            $anioActual = (int) date('Y');
            $anios = [];
            $tmp = [];
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
                'dealers' => array_map(function ($d) {
                    return [
                        'dealerid' => (int) $d['dealerid'],
                        'dealer'   => $d['dealer'],
                        'paisid'   => (int) $d['paisid'],
                        'pais'     => $d['pais'],
                    ];
                }, $dealers),
                'paises'  => $paises,
                'anios'   => $anios,
                'periodos' => [
                    ['valor' => 'month',      'nombre' => 'Month'],
                    ['valor' => 'ytd',        'nombre' => 'YTD'],
                    ['valor' => 'fytd',       'nombre' => 'Fiscal YTD'],
                    ['valor' => 'full_year',  'nombre' => 'Full Year'],
                    ['valor' => 'l12m',       'nombre' => 'Last 12 Months'],
                    ['valor' => 'prior_year', 'nombre' => 'Prior Year'],
                ],
            ];
        }

        /**
         * Métricas del dashboard Workshop Report.
         *
         * @param int|null      $year
         * @param int|null      $calendarId
         * @param int|int[]|null $dealerIds
         * @param int|int[]|null $countryIds
         * @param string        $periodType
         * @return array
         */
        public static function getResumen($year = null, $calendarId = null, $dealerIds = [], $countryIds = [], $periodType = 'month')
        {
            include_once('../config/init_db.php');

            try {
                $dealerIds   = self::normalizarIds($dealerIds);
                $countryIds  = self::normalizarIds($countryIds);
                $periodType  = in_array($periodType, ['month', 'ytd', 'fytd', 'full_year', 'l12m', 'prior_year'], true) ? $periodType : 'month';

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

                $periodo = self::resolverPeriodo($year, $calendarId, $periodType);
                if (!empty($periodo['sin_periodo'])) {
                    return ['sin_periodo' => true, 'anio' => $year];
                }

                $anio   = (int) $periodo['referencia']['ano'];
                $mesFin = (int) $periodo['referencia']['mesnum'];
                $rango  = $periodo['rango'];
                $rangoLY = self::rangoLY($rango, $periodType);

                $orders   = self::ordenesRango($dealerIds, $rango);
                $ordersLY = self::ordenesRango($dealerIds, $rangoLY);

                $varLY = $ordersLY > 0 ? round((($orders - $ordersLY) / $ordersLY) * 100, 2) : null;

                $serie = self::serieMensual($dealerIds, $anio, $mesFin);

                return [
                    'anio'            => $anio,
                    'etiqueta_periodo'=> $periodo['etiqueta'],
                    'periodo_actual'  => [
                        'mes'          => $periodo['referencia']['mes'],
                        'mesnum'       => $mesFin,
                        'calendarioid' => (int) $periodo['referencia']['calendarioid'],
                        'ano'          => $anio,
                    ],
                    'resumen'         => [
                        'orders'     => round($orders, 0),
                        'orders_ly'  => round($ordersLY, 0),
                        'var_ly'     => $varLY,
                        'retention'  => null, // Open Question: sin fuente en tablas KPI
                        'open_orders'=> null, // Open Question: sin fuente en tablas KPI
                    ],
                    'serie'           => $serie,
                    'performance'     => [
                        'target' => 12,
                        'rangos' => [
                            ['label' => 'Verde',    'min' => 12.01, 'max' => 100,   'color' => '#16a34a'],
                            ['label' => 'Amarillo', 'min' => 9.0,   'max' => 12.0,  'color' => '#d97706'],
                            ['label' => 'Rojo',     'min' => 0,     'max' => 8.99,  'color' => '#dc2626'],
                        ],
                    ],
                ];
            } catch (Exception $e) {
                error_log('Error en DashboardWorkshopReport::getResumen: ' . $e->getMessage());
                throw $e;
            }
        }
    }
}
