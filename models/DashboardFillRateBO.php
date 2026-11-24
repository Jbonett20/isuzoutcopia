<?php
/*
|--------------------------------------------------------------------------
| DASHBOARD DE FILL RATE & B/O - admin/fillRateBO.php
|--------------------------------------------------------------------------
| Modelo auto-contenido del tablero de nivel de servicio en la entrega de
| repuestos.
|
| Calcula, para el período seleccionado:
|   - Fill Rate          = promedio de kpi_parts.firstfillrate normalizado a %
|   - Target             = constante de negocio (97%)
|   - Gap                = Fill Rate - Target (puntos porcentuales)
|   - Back Orders        = SUM(kpi_parts.backordersdc) (valor en dólares)
|   - Back Order Lines   = SUM(kpi_parts.backorderline) (líneas pendientes)
|   - Ventas en Riesgo   = estimación (valor acumulado de Back Orders)
|
| Además expone la serie mensual de Fill Rate y de Back Orders del año y el
| desglose por dealer con estado (sobre/atencion/bajo/sin_datos).
|
| Fuentes: kpi_parts, kpi_calendarios, ots_dealers/ots_paises.
|--------------------------------------------------------------------------
*/

@session_start();

if (!class_exists('DashboardFillRateBO')) {
    class DashboardFillRateBO
    {
        /** Target de nivel de servicio (%). */
        const TARGET_FILL_RATE = 97;

        /** Umbral en puntos bajo target para el estado "Atención". */
        const GAP_THRESHOLD = 5;

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
         * Normaliza un valor de firstfillrate a porcentaje (0–100).
         * @param float $v
         * @return float
         */
        private static function frPct($v)
        {
            $v = (float) $v;
            if ($v <= 1) {
                return round($v * 100, 2);
            }
            return round($v, 2);
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
                // Mes de referencia: el último del año con datos de fill rate
                // o con ventas de repuestos > 0 (regla del resto de dashboards de partes).
                $referencia = DB::queryFirstRow(
                    "SELECT c.calendarioid, c.ano, c.mes, c.mesnum
                     FROM kpi_calendarios c
                     INNER JOIN (
                         SELECT calendarioid, SUM(counter + external + workshop + fleets + insurance) AS tot,
                                SUM(firstfillrate) AS fr
                         FROM kpi_parts GROUP BY calendarioid
                     ) p ON p.calendarioid = c.calendarioid
                     WHERE c.ano = %i AND c.mesnum <> 0 AND (p.tot > 0 OR p.fr > 0)
                     ORDER BY c.mesnum DESC LIMIT 1",
                    $anio
                );
                if (empty($referencia)) {
                    $referencia = DB::queryFirstRow(
                        "SELECT c.calendarioid, c.ano, c.mes, c.mesnum
                         FROM kpi_calendarios c
                         WHERE c.ano = %i AND c.mesnum <> 0
                           AND EXISTS (SELECT 1 FROM kpi_parts k WHERE k.calendarioid = c.calendarioid)
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
         * Nombres de meses (mesnum 1–12) en inglés (los usa el calendario).
         * @return string[]
         */
        private static function nombresMeses()
        {
            return [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ];
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
         * Métricas del dashboard Fill Rate & B/O.
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

                $nombrePorId = [];
                foreach ($dealers as $d) {
                    $nombrePorId[(int) $d['dealerid']] = $d['dealer'];
                }

                $inD = implode(',', array_fill(0, count($dealerIds), '%i'));
                $inR = implode(',', array_fill(0, count($rango), '%i'));

                // ── Datos del período (agregados y por dealer) ─────────────
                $rows = DB::query(
                    "SELECT k.dealerid, k.firstfillrate, k.backordersdc, k.backorderline
                     FROM kpi_parts k
                     INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                     WHERE k.dealerid IN ($inD) AND c.calendarioid IN ($inR) AND c.mesnum <> 0",
                    ...array_merge($dealerIds, $rango)
                );

                $acumFr  = 0.0;
                $nFr     = 0;
                $boTotal = 0.0;
                $boLines = 0.0;
                $porDealer = [];
                $dealerConDatos = [];
                foreach ($rows as $r) {
                    $did = (int) $r['dealerid'];
                    if (!isset($porDealer[$did])) {
                        $porDealer[$did] = ['fr' => [], 'bo' => 0.0, 'lines' => 0.0];
                    }
                    $porDealer[$did]['bo']     += (float) $r['backordersdc'];
                    $porDealer[$did]['lines']  += (float) $r['backorderline'];
                    $boTotal += (float) $r['backordersdc'];
                    $boLines += (float) $r['backorderline'];
                    if ((float) $r['firstfillrate'] > 0) {
                        $pct = self::frPct($r['firstfillrate']);
                        $acumFr += $pct;
                        $nFr++;
                        $porDealer[$did]['fr'][] = $pct;
                        $dealerConDatos[$did] = true;
                    }
                }

                $fillRate = $nFr > 0 ? round($acumFr / $nFr, 2) : null;
                $target   = self::TARGET_FILL_RATE;
                $gap      = $fillRate !== null ? round($fillRate - $target, 2) : null;

                // ── Series mensuales del año seleccionado ──────────────────
                $mesesAnio = DB::query(
                    "SELECT c.calendarioid, c.mesnum, c.mes
                     FROM kpi_calendarios c
                     WHERE c.ano = %i AND c.mesnum <> 0 ORDER BY c.mesnum ASC",
                    $anio
                );
                $idsMes = [];
                foreach ($mesesAnio as $mc) {
                    $idsMes[(int) $mc['mesnum']] = (int) $mc['calendarioid'];
                }
                $inM = implode(',', array_fill(0, count($idsMes), '%i'));

                $serieFr = [];
                $serieBo = [];
                $nombres = self::nombresMeses();
                if (!empty($idsMes)) {
                    $frRows = DB::query(
                        "SELECT k.calendarioid, k.firstfillrate, k.backordersdc
                         FROM kpi_parts k
                         INNER JOIN kpi_calendarios c ON c.calendarioid = k.calendarioid
                         WHERE k.dealerid IN ($inD) AND c.calendarioid IN ($inM) AND c.mesnum <> 0",
                        ...array_merge($dealerIds, array_values($idsMes))
                    );
                    $frPorMes = [];
                    $nPorMes  = [];
                    $boPorMes = [];
                    foreach ($frRows as $r) {
                        $mn = 0;
                        foreach ($idsMes as $k => $cid) {
                            if ($cid === (int) $r['calendarioid']) {
                                $mn = $k;
                                break;
                            }
                        }
                        if ($mn === 0) {
                            continue;
                        }
                        $boPorMes[$mn] = ($boPorMes[$mn] ?? 0.0) + (float) $r['backordersdc'];
                        if ((float) $r['firstfillrate'] > 0) {
                            $frPorMes[$mn] = ($frPorMes[$mn] ?? 0.0) + self::frPct($r['firstfillrate']);
                            $nPorMes[$mn]  = ($nPorMes[$mn] ?? 0) + 1;
                        }
                    }
                    for ($mn = 1; $mn <= 12; $mn++) {
                        if (isset($nPorMes[$mn]) && $nPorMes[$mn] > 0) {
                            $serieFr[] = [
                                'mesnum' => $mn,
                                'mes'    => $nombres[$mn],
                                'valor'  => round($frPorMes[$mn] / $nPorMes[$mn], 2),
                            ];
                        }
                        if (isset($boPorMes[$mn]) && $boPorMes[$mn] > 0) {
                            $serieBo[] = [
                                'mesnum' => $mn,
                                'mes'    => $nombres[$mn],
                                'valor'  => round($boPorMes[$mn], 2),
                            ];
                        }
                    }
                }

                // ── Desglose por dealer ────────────────────────────────────
                $dealersOut = [];
                foreach ($dealerIds as $did) {
                    $d = $porDealer[$did] ?? ['fr' => [], 'bo' => 0.0, 'lines' => 0.0];
                    $dFr = !empty($d['fr']) ? round(array_sum($d['fr']) / count($d['fr']), 2) : null;
                    $dGap = $dFr !== null ? round($dFr - $target, 2) : null;

                    if (isset($dealerConDatos[$did]) && $dFr !== null) {
                        if ($dFr >= $target) {
                            $estado = 'sobre';
                        } elseif ($dFr >= $target - self::GAP_THRESHOLD) {
                            $estado = 'atencion';
                        } else {
                            $estado = 'bajo';
                        }
                    } else {
                        $estado = 'sin_datos';
                    }

                    $dealersOut[] = [
                        'dealerid'        => $did,
                        'name'            => $nombrePorId[$did],
                        'fill_rate'       => $dFr,
                        'target'          => $target,
                        'gap'             => $dGap,
                        'back_orders'     => round($d['bo'], 2),
                        'ventas_en_riesgo'=> round($d['bo'], 2),
                        'estado'          => $estado,
                    ];
                }

                // Orden: con datos primero, luego por fill rate desc.
                usort($dealersOut, function ($a, $b) {
                    $va = $a['fill_rate'] !== null ? $a['fill_rate'] : -1;
                    $vb = $b['fill_rate'] !== null ? $b['fill_rate'] : -1;
                    if ($vb === $va) {
                        return strcmp($a['name'], $b['name']);
                    }
                    return $vb <=> $va;
                });

                return [
                    'anio'            => $anio,
                    'etiqueta_periodo'=> $periodo['etiqueta'],
                    'periodo_actual'  => [
                        'mes'          => $periodo['referencia']['mes'],
                        'mesnum'       => $mesFin,
                        'calendarioid' => $refCal,
                        'ano'          => $anio,
                    ],
                    'kpis' => [
                        'fill_rate'        => $fillRate,
                        'target'           => $target,
                        'gap'              => $gap,
                        'back_orders'      => round($boTotal, 2),
                        'back_order_lines' => round($boLines, 2),
                        'ventas_en_riesgo' => round($boTotal, 2),
                        'dealers_con_datos'=> count($dealerConDatos),
                    ],
                    'serie_fillrate'  => $serieFr,
                    'serie_backorders'=> $serieBo,
                    'dealers'         => $dealersOut,
                ];
            } catch (Exception $e) {
                error_log('Error en DashboardFillRateBO::getResumen: ' . $e->getMessage());
                throw $e;
            }
        }
    }
}
