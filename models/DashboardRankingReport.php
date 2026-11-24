<?php
/*
|--------------------------------------------------------------------------
| DASHBOARD DE RANKING REPORT - admin/rankingReport.php
|--------------------------------------------------------------------------
| Modelo auto-contenido del tablero de ranking de dealers por KPIs.
|
| Consolida por dealer (período seleccionado):
|   - Unit Sales          = SUM(kpi_sales.allunitsales) del período
|   - UIO 3yr / 5yr       = parque (ventana de 3/5 años)
|   - Index ($/UIO)       = ventas de repuestos / UIO 5yr
|   - Index 3yr / 5yr     = promedios anualizados de ventanas 3/5 años
|   - Inventory Turnover  = (consumo mensual × 12) / inventario
|   - Inventory Months    = inventario / consumo mensual
|   - Score general       = promedio de scores normalizados (0–100)
|
| Fuentes: kpi_sales, kpi_parts, kpi_calendarios, ots_dealers/ots_paises.
|--------------------------------------------------------------------------
*/

@session_start();

if (!class_exists('DashboardRankingReport')) {
    class DashboardRankingReport
    {
        /** KPI keys usados en el score general (pesos iguales). */
        const SCORE_KPIS = ['unit_sales', 'index', 'inv_turnover', 'uio_5'];

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
                // Mes de referencia: el último con ventas de repuestos > 0 del año
                // (el ranking mezcla ventas, partes e inventario; ese mes tiene todos los módulos).
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
         * Rango LY (mismo período del año anterior). Para l12m usa los 12 meses previos.
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
                     WHERE calendarioid IN ($inR) AND mesnum <> 0 ORDER BY ano ASC, mesnum ASC",
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
         * KPIs por dealer para un rango/referencia dados.
         * @param int[] $dealerIds
         * @param int[] $rango
         * @param int   $refCal
         * @param int   $anio
         * @param int   $mesFin
         * @return array [dealerid => array de KPIs]
         */
        private static function kpisPorDealer($dealerIds, $rango, $refCal, $anio, $mesFin)
        {
            $out = [];
            if (empty($dealerIds)) {
                return $out;
            }
            include_once('../config/init_db.php');

            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
            $meses = count($rango);

            // Suma agrupada por dealer
            $suma = function ($sql, $args) use ($inD) {
                $sql = str_replace('__IN__', $inD, $sql);
                $rows = DB::query($sql, ...$args);
                $map = [];
                foreach ($rows as $r) {
                    $map[(int) $r['dealerid']] = (float) $r['v'];
                }
                return $map;
            };

            // Unit sales del período
            $inR = implode(',', array_fill(0, count($rango), '%i'));
            $unit = $suma(
                "SELECT k.dealerid, COALESCE(SUM(k.allunitsales),0) AS v
                 FROM kpi_sales k INNER JOIN kpi_calendarios c ON c.calendarioid=k.calendarioid
                 WHERE k.dealerid IN (__IN__) AND c.calendarioid IN ($inR) GROUP BY k.dealerid",
                array_merge($dealerIds, $rango)
            );

            // Parts del período
            $parts = $suma(
                "SELECT k.dealerid, COALESCE(SUM(k.counter + k.external + k.workshop + k.fleets + k.insurance),0) AS v
                 FROM kpi_parts k INNER JOIN kpi_calendarios c ON c.calendarioid=k.calendarioid
                 WHERE k.dealerid IN (__IN__) AND c.calendarioid IN ($inR) GROUP BY k.dealerid",
                array_merge($dealerIds, $rango)
            );

            // UIO ventanas (3 y 5 años)
            $uio = [3 => [], 5 => []];
            $partsWin = [3 => [], 5 => []];
            foreach ([3, 5] as $H) {
                $anioInicio = $anio - $H + 1;
                $argsU = array_merge($dealerIds, [$anioInicio, $anio, $anio, $mesFin]);
                $uio[$H] = $suma(
                    "SELECT k.dealerid, COALESCE(SUM(k.allunitsales),0) AS v
                     FROM kpi_sales k INNER JOIN kpi_calendarios c ON c.calendarioid=k.calendarioid
                     WHERE k.dealerid IN (__IN__)
                       AND c.ano >= %i AND (c.ano < %i OR (c.ano = %i AND c.mesnum <= %i)) AND c.mesnum >= 1
                     GROUP BY k.dealerid",
                    $argsU
                );
                $argsP = array_merge($dealerIds, [$anioInicio, $anio, $anio, $mesFin]);
                $partsWin[$H] = $suma(
                    "SELECT k.dealerid, COALESCE(SUM(k.counter + k.external + k.workshop + k.fleets + k.insurance),0) AS v
                     FROM kpi_parts k INNER JOIN kpi_calendarios c ON c.calendarioid=k.calendarioid
                     WHERE k.dealerid IN (__IN__)
                       AND c.ano >= %i AND (c.ano < %i OR (c.ano = %i AND c.mesnum <= %i)) AND c.mesnum >= 1
                     GROUP BY k.dealerid",
                    $argsP
                );
            }

            // Inventario del mes de referencia
            $invMap = $suma(
                "SELECT k.dealerid, COALESCE(SUM(k.inventorycost),0) AS v
                 FROM kpi_parts k INNER JOIN kpi_calendarios c ON c.calendarioid=k.calendarioid
                 WHERE k.dealerid IN (__IN__) AND c.calendarioid = %i GROUP BY k.dealerid",
                array_merge($dealerIds, [$refCal])
            );

            foreach ($dealerIds as $did) {
                $unitS     = (float) ($unit[$did] ?? 0);
                $partsS    = (float) ($parts[$did] ?? 0);
                $uio3      = (float) ($uio[3][$did] ?? 0);
                $uio5      = (float) ($uio[5][$did] ?? 0);
                $parts3    = (float) ($partsWin[3][$did] ?? 0);
                $parts5    = (float) ($partsWin[5][$did] ?? 0);
                $inventory = (float) ($invMap[$did] ?? 0);
                $consumoM  = $meses > 0 ? $partsS / $meses : 0.0;

                $out[$did] = [
                    'unit_sales'  => $unitS,
                    'uio_3'       => $uio3,
                    'uio_5'       => $uio5,
                    'parts_sales' => $partsS,
                    'index'       => $uio5 > 0 ? ($partsS / $uio5) : null,
                    'index_3'     => $uio3 > 0 ? (($parts3 / 3) / $uio3) : null,
                    'index_5'     => $uio5 > 0 ? (($parts5 / 5) / $uio5) : null,
                    'inventory'   => $inventory,
                    'inv_turnover'=> $inventory > 0 ? (($consumoM * 12) / $inventory) : null,
                    'inv_months'  => $consumoM > 0 ? ($inventory / $consumoM) : null,
                ];
            }
            return $out;
        }

        /**
         * Score general (0–100) a partir de KPIs normalizados.
         * @param array $kpis  mapa dealerid => KPIs
         * @param int[] $dealerIds
         * @return array [dealerid => score]
         */
        private static function puntaje($kpis, $dealerIds)
        {
            $scores = [];
            foreach (self::SCORE_KPIS as $clave) {
                $max = 0;
                foreach ($dealerIds as $did) {
                    $v = $kpis[$did][$clave] ?? 0;
                    if (is_numeric($v) && $v > $max) {
                        $max = $v;
                    }
                }
                foreach ($dealerIds as $did) {
                    $v = $kpis[$did][$clave] ?? 0;
                    $scores[$did][$clave] = ($max > 0 && is_numeric($v)) ? ($v / $max) * 100 : 0;
                }
            }
            $out = [];
            foreach ($dealerIds as $did) {
                $sum = 0;
                foreach (self::SCORE_KPIS as $clave) {
                    $sum += $scores[$did][$clave] ?? 0;
                }
                $out[$did] = round($sum / count(self::SCORE_KPIS), 1);
            }
            return $out;
        }

        /**
         * Opciones para la barra de filtros.
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
                 FROM kpi_calendarios WHERE mesnum <> 0 ORDER BY ano ASC, mesnum ASC"
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
                $anios[] = ['ano' => $ano, 'is_current' => $ano === $anioActual ? 1 : 0, 'meses' => $mesesAnio];
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
         * Métricas del dashboard Ranking Report.
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
                $refCal = (int) $periodo['referencia']['calendarioid'];
                $rango  = $periodo['rango'];

                $kpis = self::kpisPorDealer($dealerIds, $rango, $refCal, $anio, $mesFin);

                // Score actual y ranking
                $puntajeActual = self::puntaje($kpis, $dealerIds);
                arsort($puntajeActual);
                $rankActual = [];
                $i = 1;
                foreach ($puntajeActual as $did => $score) {
                    $rankActual[$did] = $i++;
                }

                // Score LY (unit_sales + index) para tendencia
                $rangoLY = self::rangoLY($rango, $periodType);
                $kpisLY = [];
                if (!empty($rangoLY)) {
                    $kpisLY = self::kpisPorDealer($dealerIds, $rangoLY, $refCal, $anio, $mesFin);
                }
                $puntajeLY = self::puntaje($kpisLY, $dealerIds);
                arsort($puntajeLY);
                $rankLY = [];
                $i = 1;
                foreach ($puntajeLY as $did => $score) {
                    $rankLY[$did] = $i++;
                }

                $nombrePorId = [];
                foreach ($dealers as $d) {
                    $nombrePorId[(int) $d['dealerid']] = $d['dealer'];
                }

                $fmt = function ($v) {
                    return $v === null ? null : round($v, 2);
                };

                // --- KPIs (para el gráfico de barras con selector) ---
                $kpisChart = [
                    'parts_sales'      => ['nombre' => 'Parts Sales',   'fmt' => 'moneda'],
                    'unit_sales'       => ['nombre' => 'Unit Sales',    'fmt' => 'entero'],
                    'uio_3'            => ['nombre' => 'UIO 3yr',       'fmt' => 'entero'],
                    'uio_5'            => ['nombre' => 'UIO 5yr',       'fmt' => 'entero'],
                    'index_3'          => ['nombre' => 'Index 3yr',     'fmt' => 'moneda'],
                    'index_5'          => ['nombre' => 'Index 5yr',     'fmt' => 'moneda'],
                    'inv_turnover'     => ['nombre' => 'Inventory Turnover', 'fmt' => 'decimal'],
                ];
                $kpisOut = [];
                foreach ($kpisChart as $clave => $meta) {
                    $max = 0;
                    foreach ($dealerIds as $did) {
                        $v = $kpis[$did][$clave] ?? 0;
                        if (is_numeric($v) && $v > $max) {
                            $max = $v;
                        }
                    }
                    $lista = [];
                    foreach ($dealerIds as $did) {
                        $v = $kpis[$did][$clave] ?? 0;
                        $score = ($max > 0 && is_numeric($v)) ? round(($v / $max) * 100, 0) : 0;
                        $tend = self::tendenciaTexto($rankActual[$did] ?? null, $rankLY[$did] ?? null);
                        $lista[] = [
                            'dealerid' => $did,
                            'name'     => $nombrePorId[$did],
                            'valor'    => $fmt($v),
                            'score'    => $score,
                            'trend'    => $tend,
                        ];
                    }
                    usort($lista, function ($a, $b) {
                        return $b['score'] - $a['score'];
                    });
                    $kpisOut[$clave] = [
                        'nombre' => $meta['nombre'],
                        'fmt'    => $meta['fmt'],
                        'rows'   => $lista,
                    ];
                }

                // --- Podio ---
                $podium = [
                    'parts_sales' => [],
                    'uio_5'       => [],
                ];
                $medals = [1 => 'gold', 2 => 'silver', 3 => 'bronze'];
                foreach (['parts_sales', 'uio_5'] as $clave) {
                    $orden = [];
                    foreach ($dealerIds as $did) {
                        $orden[] = ['did' => $did, 'v' => $kpis[$did][$clave] ?? 0];
                    }
                    usort($orden, function ($a, $b) {
                        return $b['v'] - $a['v'];
                    });
                    for ($k = 0; $k < min(3, count($orden)); $k++) {
                        $podium[$clave][] = [
                            'rank'  => $k + 1,
                            'medal' => $medals[$k + 1],
                            'name'  => $nombrePorId[$orden[$k]['did']],
                            'value' => $clave === 'parts_sales'
                                ? '$' . number_format(round($orden[$k]['v']), 0)
                                : number_format(round($orden[$k]['v']), 0),
                        ];
                    }
                }

                // --- Tarjetas de líderes (metrics) ---
                $metricasDef = [
                    ['clave' => 'unit_sales', 'titulo' => 'Unit Sales',  'color' => '#16a34a', 'fmt' => 'entero'],
                    ['clave' => 'uio_3',      'titulo' => 'UIO 3yr',     'color' => '#16a34a', 'fmt' => 'entero'],
                    ['clave' => 'uio_5',      'titulo' => 'UIO 5yr',     'color' => '#16a34a', 'fmt' => 'entero'],
                    ['clave' => 'index_3',    'titulo' => 'Index 3yr',   'color' => '#f97316', 'fmt' => 'moneda'],
                    ['clave' => 'index_5',    'titulo' => 'Index 5yr',   'color' => '#f97316', 'fmt' => 'moneda'],
                ];
                $metricas = [];
                foreach ($metricasDef as $m) {
                    $maxV = null;
                    $leaderId = null;
                    foreach ($dealerIds as $did) {
                        $v = $kpis[$did][$m['clave']];
                        if (is_numeric($v) && ($maxV === null || $v > $maxV)) {
                            $maxV = $v;
                            $leaderId = $did;
                        }
                    }
                    $valTxt = $maxV === null ? '—' : ($m['fmt'] === 'entero'
                        ? number_format(round($maxV), 0)
                        : '$' . number_format(round($maxV), 0));
                    $metricas[] = [
                        'titulo' => $m['titulo'],
                        'lider'  => $leaderId !== null ? $nombrePorId[$leaderId] : '—',
                        'valor'  => $valTxt,
                        'color'  => $m['color'],
                        'cambio' => ($rankActual[$leaderId] ?? null) !== null ? '#' . $rankActual[$leaderId] : '',
                    ];
                }

                // --- Ranking general (score) ---
                $scoreRows = [];
                $tier = 1;
                $prevScore = null;
                foreach ($puntajeActual as $did => $score) {
                    $tierLabel = $tier === 1 ? 'top' : ($tier <= 3 ? 'mid' : 'bajo');
                    $scoreRows[] = [
                        'rank'     => $rankActual[$did],
                        'dealerid' => $did,
                        'name'     => $nombrePorId[$did],
                        'score'    => $score,
                        'pos'      => isset($rankLY[$did]) ? $rankLY[$did] : '—',
                        'tier'     => $tierLabel,
                        'label'    => $tier === 1 ? 'Top Performer' : ($tier <= 3 ? 'Mid Tier' : 'Por mejorar'),
                        'tend'     => self::tendenciaTexto($rankActual[$did], $rankLY[$did] ?? null),
                    ];
                    $tier++;
                }

                // --- Rankings laterales (top 3 por KPI) ---
                $rankingsDef = [
                    ['clave' => 'unit_sales', 'titulo' => 'Unit Sales', 'fmt' => 'entero'],
                    ['clave' => 'uio_5',      'titulo' => 'UIO 5yr',    'fmt' => 'entero'],
                    ['clave' => 'index_5',    'titulo' => 'Index 5yr',  'fmt' => 'moneda'],
                    ['clave' => 'inv_turnover','titulo' => 'Inventory Turnover', 'fmt' => 'decimal'],
                ];
                $rankings = [];
                foreach ($rankingsDef as $rd) {
                    $orden = [];
                    foreach ($dealerIds as $did) {
                        $orden[] = ['did' => $did, 'v' => $kpis[$did][$rd['clave']]];
                    }
                    usort($orden, function ($a, $b) {
                        $va = is_numeric($a['v']) ? $a['v'] : -1;
                        $vb = is_numeric($b['v']) ? $b['v'] : -1;
                        return $vb - $va;
                    });
                    $rows = [];
                    for ($k = 0; $k < min(3, count($orden)); $k++) {
                        $v = $orden[$k]['v'];
                        $valTxt = !is_numeric($v) ? '—' : ($rd['fmt'] === 'entero'
                            ? number_format(round($v), 0)
                            : ($rd['fmt'] === 'moneda' ? '$' . number_format(round($v), 0) : number_format($v, 2)));
                        $rows[] = ['n' => $k + 1, 'name' => $nombrePorId[$orden[$k]['did']], 'val' => $valTxt];
                    }
                    $rankings[] = ['titulo' => $rd['titulo'], 'rows' => $rows];
                }

                return [
                    'anio'            => $anio,
                    'etiqueta_periodo'=> $periodo['etiqueta'],
                    'periodo_actual'  => [
                        'mes'          => $periodo['referencia']['mes'],
                        'mesnum'       => $mesFin,
                        'calendarioid' => $refCal,
                        'ano'          => $anio,
                    ],
                    'podium'   => $podium,
                    'metricas' => $metricas,
                    'kpis'     => $kpisOut,
                    'score'    => $scoreRows,
                    'rankings' => $rankings,
                ];
            } catch (Exception $e) {
                error_log('Error en DashboardRankingReport::getResumen: ' . $e->getMessage());
                throw $e;
            }
        }

        /**
         * Texto de tendencia según el cambio de posición (vs período anterior).
         */
        private static function tendenciaTexto($rankActual, $rankLY)
        {
            if ($rankActual === null || $rankLY === null) {
                return '—';
            }
            if ($rankActual < $rankLY) {
                return '▲+' . ($rankLY - $rankActual);
            }
            if ($rankActual > $rankLY) {
                return '▼-' . ($rankActual - $rankLY);
            }
            return '—';
        }
    }
}
