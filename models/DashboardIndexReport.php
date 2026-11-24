<?php
/*
|--------------------------------------------------------------------------
| DASHBOARD DE INDEX REPORT ($/UIO) - admin/indexReport.php
|--------------------------------------------------------------------------
| Modelo auto-contenido del tablero de Index Report.
|
| Mide la captura de oportunidad del mercado de repuestos:
|   - Índice $/UIO     = Ventas de repuestos (período) / UIO 5 años
|   - $/UIO 3yr Avg    = Ventas de repuestos (ventana 3 años) / UIO 3 años
|   - $/UIO 5yr Avg    = Ventas de repuestos (ventana 5 años) / UIO 5 años
|   - Var 3yr          = (Índice − $/UIO 3yr Avg) / $/UIO 3yr Avg × 100
|   - Var 5yr          = (Índice − $/UIO 5yr Avg) / $/UIO 5yr Avg × 100
|   - UIO 3yr Growth   = (UIO 3yr − UIO 3yr hace 3 años) / UIO 3yr hace 3 años × 100
|   - UIO 5yr Growth   = (UIO 5yr − UIO 5yr hace 5 años) / UIO 5yr hace 5 años × 100
|
| Fuentes: kpi_parts (ventas de repuestos por canales), kpi_sales (parque UIO),
|          kpi_calendarios, ots_dealers/ots_paises.
|--------------------------------------------------------------------------
*/

@session_start();

if (!class_exists('DashboardIndexReport')) {
    class DashboardIndexReport
    {
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
         * Resuelve el rango de calendarioids del período seleccionado
         * (para las ventas de repuestos).
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
                // Mes de referencia: el último con ventas de repuestos > 0 del año (el índice
                // es de repuestos); si no hay, el último con ventas de vehículos.
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
                'anio'          => $anio,
                'rango'         => $rango,
                'referencia'    => [
                    'calendarioid' => $refCalendarioid,
                    'mes'          => $referencia['mes'] ?? '',
                    'mesnum'       => $refMesnum,
                    'ano'          => (int) $referencia['ano'],
                ],
                'etiqueta'      => self::etiquetaPeriodo($periodType, $anio, $fiscalStart),
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
         * Suma de unidades (parque UIO) en la ventana de H años que termina en (anioFin, mesFin).
         * @param int[]  $dealerIds
         * @param int    $anioFin
         * @param int    $mesFin
         * @param int    $anios
         * @return int
         */
        private static function sumaVentana($dealerIds, $anioFin, $mesFin, $anios)
        {
            if (empty($dealerIds)) {
                return 0;
            }
            include_once('../config/init_db.php');

            $anioInicio = $anioFin - $anios + 1;
            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
            $args = array_merge($dealerIds, [$anioInicio, $anioFin, $anioFin, $mesFin]);
            $v = DB::queryFirstField(
                "SELECT COALESCE(SUM(k.allunitsales), 0)
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
         * Ventas de repuestos (suma de canales) en un rango de calendarioids.
         * @param int[] $dealerIds
         * @param int[] $rango
         * @return float
         */
        private static function sumaPartsRango($dealerIds, $rango)
        {
            if (empty($dealerIds) || empty($rango)) {
                return 0.0;
            }
            include_once('../config/init_db.php');

            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
            $inR = implode(',', array_fill(0, count($rango), '%i'));
            $args = array_merge($dealerIds, $rango);
            $v = DB::queryFirstField(
                "SELECT COALESCE(SUM(k.counter + k.external + k.workshop + k.fleets + k.insurance), 0)
                 FROM kpi_parts k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD) AND c.calendarioid IN ($inR)",
                ...$args
            );
            return (float) $v;
        }

        /**
         * Ventas de repuestos en la ventana de H años que termina en (anioFin, mesFin).
         * @param int[] $dealerIds
         * @param int   $anioFin
         * @param int   $mesFin
         * @param int   $anios
         * @return float
         */
        private static function sumaPartsVentana($dealerIds, $anioFin, $mesFin, $anios)
        {
            if (empty($dealerIds)) {
                return 0.0;
            }
            include_once('../config/init_db.php');

            $anioInicio = $anioFin - $anios + 1;
            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
            $args = array_merge($dealerIds, [$anioInicio, $anioFin, $anioFin, $mesFin]);
            $v = DB::queryFirstField(
                "SELECT COALESCE(SUM(k.counter + k.external + k.workshop + k.fleets + k.insurance), 0)
                 FROM kpi_parts k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD)
                   AND c.ano >= %i
                   AND (c.ano < %i OR (c.ano = %i AND c.mesnum <= %i))
                   AND c.mesnum >= 1",
                ...$args
            );
            return (float) $v;
        }

        /**
         * Serie de tendencia: un punto por mes (últimos 12 meses hasta el de referencia)
         * con UIO 5 años y los promedios $/UIO de 3 y 5 años.
         * @param int[] $dealerIds
         * @param int   $anioFin
         * @param int   $mesFin
         * @return array
         */
        private static function serieTendencia($dealerIds, $anioFin, $mesFin)
        {
            $serie = [];
            if (empty($dealerIds)) {
                return $serie;
            }
            include_once('../config/init_db.php');

            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));

            // Meses a mostrar: últimos 12 meses hasta el mes de referencia
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

            // Unidades mensuales de TODA la historia (para ventanas 3/5 años por punto)
            $rows = DB::query(
                "SELECT c.calendarioid, c.ano, c.mesnum, COALESCE(SUM(k.allunitsales), 0) AS allu
                 FROM kpi_sales k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD) AND c.mesnum >= 1
                 GROUP BY c.calendarioid, c.ano, c.mesnum",
                ...$dealerIds
            );
            $uioPorIndex = [];
            foreach ($rows as $r) {
                $idx = ((int) $r['ano'] - 1) * 12 + (int) $r['mesnum'];
                $uioPorIndex[$idx] = (int) round((float) $r['allu']);
            }

            // Ventas de repuestos mensuales de TODA la historia
            $rowsP = DB::query(
                "SELECT c.calendarioid, c.ano, c.mesnum,
                        COALESCE(SUM(k.counter + k.external + k.workshop + k.fleets + k.insurance), 0) AS parts
                 FROM kpi_parts k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD) AND c.mesnum >= 1
                 GROUP BY c.calendarioid, c.ano, c.mesnum",
                ...$dealerIds
            );
            $partsPorIndex = [];
            foreach ($rowsP as $r) {
                $idx = ((int) $r['ano'] - 1) * 12 + (int) $r['mesnum'];
                $partsPorIndex[$idx] = (float) $r['parts'];
            }

            $sumaVentanaIndex = function ($idxFin, $anios, &$cache) use ($uioPorIndex, $partsPorIndex) {
                if (isset($cache[$idxFin][$anios])) {
                    return $cache[$idxFin][$anios];
                }
                $meses = $anios * 12;
                $uio = 0;
                $parts = 0.0;
                for ($i = 0; $i < $meses; $i++) {
                    $idx = $idxFin - $i;
                    $uio += (int) ($uioPorIndex[$idx] ?? 0);
                    $parts += (float) ($partsPorIndex[$idx] ?? 0);
                }
                $cache[$idxFin][$anios] = ['uio' => $uio, 'parts' => $parts];
                return $cache[$idxFin][$anios];
            };
            $cache = [];

            foreach ($cals as $rc) {
                $idx = ((int) $rc['ano'] - 1) * 12 + (int) $rc['mesnum'];

                // UIO 5 años (parque) del punto
                $w5 = $sumaVentanaIndex($idx, 5, $cache);
                // Promedios $/UIO de 3 y 5 años del punto (anualizados)
                $w3 = $sumaVentanaIndex($idx, 3, $cache);
                // Ventas de repuestos de los últimos 12 meses (índice actual anualizado)
                $w12 = $sumaVentanaIndex($idx, 1, $cache);

                $serie[] = [
                    'calendarioid' => (int) $rc['calendarioid'],
                    'mes'          => $rc['mes'],
                    'mesnum'       => (int) $rc['mesnum'],
                    'uio'          => $w5['uio'],
                    'index3'       => $w3['uio'] > 0 ? round(($w3['parts'] / 3) / $w3['uio'], 0) : null,
                    'index5'       => $w5['uio'] > 0 ? round(($w5['parts'] / 5) / $w5['uio'], 0) : null,
                    'index12'      => $w5['uio'] > 0 ? round($w12['parts'] / $w5['uio'], 0) : null,
                ];
            }

            return $serie;
        }

        /**
         * Ranking de dealers por índice $/UIO (ventas del período / UIO 5 años).
         * @param array  $dealers
         * @param int[]  $rangoPeriodo
         * @param int    $anioFin
         * @param int    $mesFin
         * @return array
         */
        private static function rankingDealers($dealers, $rangoPeriodo, $anioFin, $mesFin)
        {
            $ranking = [];
            if (empty($dealers)) {
                return $ranking;
            }
            include_once('../config/init_db.php');

            $dealerIds = array_column($dealers, 'dealerid');
            $inD = implode(',', array_fill(0, count($dealerIds), '%i'));

            // Ventas de repuestos por dealer en el rango del período
            $partsPorDealer = [];
            if (!empty($rangoPeriodo)) {
                $inR = implode(',', array_fill(0, count($rangoPeriodo), '%i'));
                $rowsP = DB::query(
                    "SELECT k.dealerid, COALESCE(SUM(k.counter + k.external + k.workshop + k.fleets + k.insurance), 0) AS parts
                     FROM kpi_parts k
                     INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                     WHERE k.dealerid IN ($inD) AND c.calendarioid IN ($inR)
                     GROUP BY k.dealerid",
                    ...array_merge($dealerIds, $rangoPeriodo)
                );
                foreach ($rowsP as $r) {
                    $partsPorDealer[(int) $r['dealerid']] = (float) $r['parts'];
                }
            }

            // UIO 5 años por dealer
            $anioInicio = $anioFin - 5 + 1;
            $rowsU = DB::query(
                "SELECT k.dealerid, COALESCE(SUM(k.allunitsales), 0) AS uio
                 FROM kpi_sales k
                 INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                 WHERE k.dealerid IN ($inD)
                   AND c.ano >= %i
                   AND (c.ano < %i OR (c.ano = %i AND c.mesnum <= %i))
                   AND c.mesnum >= 1
                 GROUP BY k.dealerid",
                ...array_merge($dealerIds, [$anioInicio, $anioFin, $anioFin, $mesFin])
            );
            $uioPorDealer = [];
            foreach ($rowsU as $r) {
                $uioPorDealer[(int) $r['dealerid']] = (int) round((float) $r['uio']);
            }

            foreach ($dealers as $d) {
                $did = (int) $d['dealerid'];
                $ventas = (float) ($partsPorDealer[$did] ?? 0);
                $uio    = (int) ($uioPorDealer[$did] ?? 0);
                $ranking[] = [
                    'dealerid' => $did,
                    'dealer'   => $d['dealer'],
                    'ventas'   => $ventas,
                    'uio'      => $uio,
                    'indice'   => $uio > 0 ? round(($ventas / $uio), 0) : null,
                ];
            }
            usort($ranking, function ($a, $b) {
                $ia = $a['indice'] ?? -1;
                $ib = $b['indice'] ?? -1;
                if ($ia !== $ib) {
                    return $ib - $ia;
                }
                return strcmp($a['dealer'], $b['dealer']);
            });
            foreach ($ranking as $i => &$r) {
                $r['rank'] = $i + 1;
            }
            return $ranking;
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
         * Métricas del dashboard Index Report.
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

                // Ventas de repuestos del período seleccionado
                $ventasPeriodo = self::sumaPartsRango($dealerIds, $rango);

                // UIO (parque) y promedios anualizados
                $uio_3 = self::sumaVentana($dealerIds, $anio, $mesFin, 3);
                $uio_5 = self::sumaVentana($dealerIds, $anio, $mesFin, 5);

                $ventas3 = self::sumaPartsVentana($dealerIds, $anio, $mesFin, 3);
                $ventas5 = self::sumaPartsVentana($dealerIds, $anio, $mesFin, 5);

                // Índice actual ($/UIO anualizado): ventas del período anualizadas / parque.
                // Se anualiza (× 12 / meses del período) para que sea comparable con los
                // promedios de 3 y 5 años (ambos anualizados).
                $mesesPeriodo = count($rango);
                $indice   = ($uio_5 > 0 && $mesesPeriodo > 0) ? round(($ventasPeriodo * 12 / $mesesPeriodo) / $uio_5, 0) : null;
                // Promedio 3/5 años: ventas de la ventana anualizadas sobre el parque de la ventana
                $indice_3 = $uio_3 > 0 ? round(($ventas3 / 3) / $uio_3, 0) : null;
                $indice_5 = $uio_5 > 0 ? round(($ventas5 / 5) / $uio_5, 0) : null;

                $var_3 = ($indice !== null && $indice_3) ? round((($indice - $indice_3) / $indice_3) * 100, 2) : null;
                $var_5 = ($indice !== null && $indice_5) ? round((($indice - $indice_5) / $indice_5) * 100, 2) : null;

                // Crecimiento del parque
                $uio_3_prev = self::sumaVentana($dealerIds, $anio - 3, $mesFin, 3);
                $uio_5_prev = self::sumaVentana($dealerIds, $anio - 5, $mesFin, 5);

                $uio_3_growth = $uio_3_prev != 0 ? round((($uio_3 - $uio_3_prev) / $uio_3_prev) * 100, 2) : null;
                $uio_5_growth = $uio_5_prev != 0 ? round((($uio_5 - $uio_5_prev) / $uio_5_prev) * 100, 2) : null;

                $serie   = self::serieTendencia($dealerIds, $anio, $mesFin);
                $ranking = self::rankingDealers($dealers, $rango, $anio, $mesFin);

                return [
                    'anio'           => $anio,
                    'etiqueta_periodo' => $periodo['etiqueta'],
                    'periodo_actual' => [
                        'mes'          => $periodo['referencia']['mes'],
                        'mesnum'       => $mesFin,
                        'calendarioid' => (int) $periodo['referencia']['calendarioid'],
                        'ano'          => $anio,
                    ],
                    'resumen'        => [
                        'indice'        => $indice,
                        'ventas'        => round($ventasPeriodo, 2),
                        'uio_3'         => (int) $uio_3,
                        'uio_5'         => (int) $uio_5,
                        'indice_3'      => $indice_3,
                        'indice_5'      => $indice_5,
                        'var_3'         => $var_3,
                        'var_5'         => $var_5,
                        'uio_3_growth'  => $uio_3_growth,
                        'uio_5_growth'  => $uio_5_growth,
                    ],
                    'serie'          => $serie,
                    'ranking'        => $ranking,
                ];
            } catch (Exception $e) {
                error_log('Error en DashboardIndexReport::getResumen: ' . $e->getMessage());
                throw $e;
            }
        }
    }
}
