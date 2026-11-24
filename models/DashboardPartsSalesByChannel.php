<?php
/*
|--------------------------------------------------------------------------
| DASHBOARD DE PARTS SALES BY CHANNEL - admin/partsSalesByChannel.php
|--------------------------------------------------------------------------
| Modelo auto-contenido del tablero de venta de partes por canal.
|
| Analiza la distribución de las ventas de repuestos entre los canales
| comerciales de kpi_parts (workshop, counter, external, fleets, insurance):
|   - Total Ventas        = Σ ventas de todos los canales (período)
|   - Participación Canal = Ventas del canal / Total × 100
|   - Var % Canal         = (Ventas canal − Ventas canal LY) / Ventas canal LY × 100
|   - Variación total vs LY = (Total − Total LY) / Total LY × 100
|
| Fuentes: kpi_parts (canales en moneda), kpi_calendarios, ots_dealers/ots_paises.
|--------------------------------------------------------------------------
*/

@session_start();

if (!class_exists('DashboardPartsSalesByChannel')) {
    class DashboardPartsSalesByChannel
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
         * Canales comerciales de kpi_parts.
         * @return array
         */
        private static function canales()
        {
            return [
                ['clave' => 'workshop',  'nombre' => 'Workshop',  'color' => '#0f766e'],
                ['clave' => 'counter',   'nombre' => 'Counter',   'color' => '#d97706'],
                ['clave' => 'external',  'nombre' => 'External',  'color' => '#1e3a5f'],
                ['clave' => 'fleets',    'nombre' => 'Fleets',    'color' => '#3b82f6'],
                ['clave' => 'insurance', 'nombre' => 'Insurance', 'color' => '#7dd3fc'],
            ];
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
                $referencia = DB::queryFirstRow(
                    "SELECT c.calendarioid, c.ano, c.mes, c.mesnum
                     FROM kpi_calendarios c
                     INNER JOIN (
                         SELECT calendarioid, SUM(counter + external + workshop + fleets + insurance) AS tot
                         FROM kpi_parts GROUP BY calendarioid
                     ) p ON p.calendarioid = c.calendarioid
                     WHERE c.ano = %i AND c.mesnum <> 0 AND p.tot > 0
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
         * Ventas por canal sobre un rango de calendarioids.
         * @param int[] $dealerIds
         * @param int[] $rango
         * @return array [clave => float]
         */
        private static function ventasPorCanalRango($dealerIds, $rango)
        {
            $out = [];
            if (empty($dealerIds) || empty($rango)) {
                return $out;
            }
            include_once('../config/init_db.php');

            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
            $inR = implode(',', array_fill(0, count($rango), '%i'));
            $args = array_merge($dealerIds, $rango);
            $row = DB::queryFirstRow(
                "SELECT COALESCE(SUM(k.counter), 0)   AS counter,
                        COALESCE(SUM(k.external), 0)  AS external,
                        COALESCE(SUM(k.workshop), 0)  AS workshop,
                        COALESCE(SUM(k.fleets), 0)    AS fleets,
                        COALESCE(SUM(k.insurance), 0) AS insurance
                 FROM kpi_parts k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD) AND c.calendarioid IN ($inR)",
                ...$args
            );
            foreach (self::canales() as $c) {
                $out[$c['clave']] = (float) ($row[$c['clave']] ?? 0);
            }
            return $out;
        }

        /**
         * Rango LY: mismo período desplazado un año (mismo mesnum, año − 1).
         * Para l12m usa los 12 meses anteriores al rango actual.
         * @param int[] $rangoActual
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

            // Mapa mesnum -> calendarioid por año
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
         * Serie mensual por canal (últimos 12 meses hasta el mes de referencia).
         * @param int[] $dealerIds
         * @param int   $anioFin
         * @param int   $mesFin
         * @return array
         */
        private static function serieMensualCanales($dealerIds, $anioFin, $mesFin)
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
                        COALESCE(SUM(k.counter), 0)   AS counter,
                        COALESCE(SUM(k.external), 0)  AS external,
                        COALESCE(SUM(k.workshop), 0)  AS workshop,
                        COALESCE(SUM(k.fleets), 0)    AS fleets,
                        COALESCE(SUM(k.insurance), 0) AS insurance
                 FROM kpi_parts k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD) AND c.mesnum >= 1
                 GROUP BY c.calendarioid, c.ano, c.mesnum",
                ...$dealerIds
            );
            $porCal = [];
            foreach ($rows as $r) {
                $porCal[(int) $r['calendarioid']] = $r;
            }

            foreach ($cals as $rc) {
                $cal = (int) $rc['calendarioid'];
                $m = $porCal[$cal] ?? null;
                $punto = [
                    'calendarioid' => $cal,
                    'mes'          => $rc['mes'],
                    'mesnum'       => (int) $rc['mesnum'],
                ];
                foreach (self::canales() as $c) {
                    $punto[$c['clave']] = $m ? (float) ($m[$c['clave']] ?? 0) : 0.0;
                }
                $serie[] = $punto;
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
         * Métricas del dashboard Parts Sales by Channel.
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

                $ventas   = self::ventasPorCanalRango($dealerIds, $rango);
                $ventasLY = self::ventasPorCanalRango($dealerIds, $rangoLY);

                $total   = array_sum($ventas);
                $totalLY = array_sum($ventasLY);

                $canales = [];
                $canalLider = null;
                foreach (self::canales() as $c) {
                    $clave  = $c['clave'];
                    $v      = (float) ($ventas[$clave] ?? 0);
                    $ly     = (float) ($ventasLY[$clave] ?? 0);
                    $share  = $total > 0 ? round(($v / $total) * 100, 1) : 0;
                    $var    = $ly > 0 ? round((($v - $ly) / $ly) * 100, 2) : null;
                    $canales[] = $c + [
                        'ventas' => round($v, 2),
                        'ly'     => round($ly, 2),
                        'share'  => $share,
                        'var'    => $var,
                    ];
                    if ($canalLider === null || $share > $canalLider['share']) {
                        $canalLider = [
                            'clave'   => $clave,
                            'nombre'  => $c['nombre'],
                            'color'   => $c['color'],
                            'ventas'  => round($v, 2),
                            'share'   => $share,
                        ];
                    }
                }

                // Canal con mayor crecimiento (var definida y ventas > 0)
                $canalCrec = null;
                foreach ($canales as $c) {
                    if ($c['var'] === null || $c['var'] === 0.0 || $c['ventas'] <= 0) {
                        continue;
                    }
                    if ($canalCrec === null || $c['var'] > $canalCrec['var']) {
                        $canalCrec = [
                            'clave'  => $c['clave'],
                            'nombre' => $c['nombre'],
                            'color'  => $c['color'],
                            'ventas' => $c['ventas'],
                            'var'    => $c['var'],
                        ];
                    }
                }

                $varTotal = $totalLY > 0 ? round((($total - $totalLY) / $totalLY) * 100, 2) : null;

                // Canales ordenados por var % vs LY (para paneles de crecimiento/caída)
                $crecimiento = [];
                foreach ($canales as $c) {
                    if ($c['var'] === null || $c['ventas'] <= 0) {
                        continue;
                    }
                    $crecimiento[] = $c;
                }
                usort($crecimiento, function ($a, $b) {
                    return $b['var'] - $a['var'];
                });

                $serie = self::serieMensualCanales($dealerIds, $anio, $mesFin);

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
                        'total'             => round($total, 2),
                        'total_ly'          => round($totalLY, 2),
                        'var_total'         => $varTotal,
                        'canal_lider'       => $canalLider,
                        'canal_crecimiento' => $canalCrec,
                    ],
                    'canales'         => $canales,
                    'crecimiento'     => $crecimiento,
                    'serie'           => $serie,
                ];
            } catch (Exception $e) {
                error_log('Error en DashboardPartsSalesByChannel::getResumen: ' . $e->getMessage());
                throw $e;
            }
        }
    }
}
