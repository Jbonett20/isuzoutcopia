<?php
    $loc = "sales";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once("../controllers/sidebar.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>" />
    <link rel="stylesheet" href="dashboard/css/dashboardSales.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/dashboardSales.css'); ?>" />
</head>

<body class="">
    <?php require_once("src/navbar.php"); ?>
    <nav id="sidebar" class="sidebar" role="navigation">
        <div class="js-sidebar-content">
            <?php require_once("src/logo.php"); ?>
            <?php require_once("src/sidebar.php"); ?>
        </div>
    </nav>

    <div class="content-wrap">
        <main id="content" class="content" role="main">

            <section id="seccion_tablero" class="view_block_tablero">

                <!-- ── TOOLBAR ─────────────────────────────────────────── -->
                <article id="toolbar_tablero" class="card_common panel_common toolbar_tablero">
                    <div class="titulo_wrap_tablero">
                        <h2 class="titulo_tablero">ISUZU KPI &ndash; <strong>Sales</strong> Dashboard</h2>
                        <span class="fecha_tablero"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="filtros_tablero">
                        <div class="filtro_item_tablero">
                            <span class="filtro_lbl_tablero">Dealer:</span>
                            <select id="filtro_dealer_tablero" class="select_common select_tablero select_multi_tablero" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="filtro_item_tablero">
                            <span class="filtro_lbl_tablero">Country:</span>
                            <select id="filtro_pais_tablero" class="select_common select_tablero select_multi_tablero" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="filtro_item_tablero">
                            <span class="filtro_lbl_tablero">Vehicle Type:</span>
                            <select id="filtro_tipo_tablero" class="select_common select_tablero">
                                <option value="all" selected>All (CV + LCV)</option>
                                <option value="cv">CV</option>
                                <option value="lcv">LCV</option>
                            </select>
                        </div>
                        <div class="filtro_item_tablero">
                            <span class="filtro_lbl_tablero">Period:</span>
                            <select id="filtro_periodo_tablero" class="select_common select_tablero">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="filtro_item_tablero">
                            <span class="filtro_lbl_tablero">Year:</span>
                            <select id="filtro_anio_tablero" class="select_common select_tablero"></select>
                        </div>
                        <div class="filtro_item_tablero">
                            <span class="filtro_lbl_tablero">Month:</span>
                            <select id="filtro_mes_tablero" class="select_common select_tablero"></select>
                        </div>
                        <div class="filtro_item_tablero">
                            <span class="filtro_lbl_tablero">Comparison:</span>
                            <select id="filtro_comparacion_tablero" class="select_common select_tablero">
                                <option value="none" selected>None</option>
                                <option value="cy_vs_py">CY vs PY</option>
                                <option value="ytd_vs_py_ytd">YTD vs PY YTD</option>
                                <option value="month_vs_py">Month vs PY</option>
                            </select>
                        </div>
                        <button id="btn_reset_tablero" class="btn_buscar_tablero" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article>

                <!-- ── KPI CARDS ────────────────────────────────────────── -->
                <div id="kpi_tablero" class="kpi_grid_tablero">

                    <!-- Card 1: Venta Actual (green) -->
                    <article class="card_common kpi_card_tablero success_tablero">
                        <h3 class="kpi_title_tablero">Current Sales</h3>
                        <p class="kpi_value_tablero"><span id="kpi_actual_tablero">0</span> <span id="kpi_unit_actual_tablero" class="kpi_unit_tablero">units</span></p>
                        <div class="kpi_progress_tablero"><span id="kpi_progress_actual_tablero" style="width:0%"></span></div>
                        <p class="kpi_trend_tablero up_tablero">
                            <i class="fa fa-arrow-up trend_icon_tablero"></i> <span id="kpi_trend_actual_tablero">—</span>
                        </p>
                    </article>

                    <!-- Card 2: Var % vs LY (amber/orange) -->
                    <article class="card_common kpi_card_tablero warning_tablero">
                        <h3 class="kpi_title_tablero">
                            <span class="kpi_chip_tablero chip_warning_tablero">
                                <i class="fa fa-refresh"></i>
                            </span>
                            Var % vs LY
                        </h3>
                        <p class="kpi_value_tablero"><span id="kpi_var_tablero">—</span><span class="kpi_unit_tablero">%</span></p>
                        <div class="kpi_progress_tablero"><span id="kpi_progress_var_tablero" style="width:0%"></span></div>
                        <p class="kpi_trend_tablero neutral_tablero" id="kpi_trend_var_tablero">
                            <i class="fa fa-arrow-up trend_icon_tablero"></i> —
                        </p>
                    </article>

                    <!-- Card 3: Objetivo (tan/beige) -->
                    <article class="card_common kpi_card_tablero info_tablero">
                        <h3 class="kpi_title_tablero">
                            <span class="kpi_chip_tablero chip_info_tablero">
                                <i class="fa fa-bars"></i>
                            </span>
                            Goal
                        </h3>
                        <p class="kpi_value_tablero"><span id="kpi_objetivo_tablero">—</span> <span class="kpi_unit_tablero">Units</span></p>
                        <div class="kpi_progress_tablero"><span id="kpi_progress_objetivo_tablero" style="width:0%"></span></div>
                        <p class="kpi_trend_tablero up_tablero">
                            <i class="fa fa-arrow-up trend_icon_tablero"></i> <span id="kpi_trend_objetivo_tablero">—</span>
                        </p>
                    </article>

                    <!-- Card 4: Gap (red/pink) -->
                    <article class="card_common kpi_card_tablero danger_tablero">
                        <h3 class="kpi_title_tablero">
                            <span class="kpi_chip_tablero chip_danger_tablero">
                                <i class="fa fa-shield"></i>
                            </span>
                            Gap
                        </h3>
                        <p class="kpi_value_tablero"><span id="kpi_gap_tablero">—</span> <span class="kpi_unit_tablero">units</span></p>
                        <div class="kpi_progress_tablero"><span id="kpi_progress_gap_tablero" style="width:0%"></span></div>
                        <p class="kpi_trend_tablero up_tablero">
                            <i class="fa fa-arrow-up trend_icon_tablero"></i> <span id="kpi_trend_gap_tablero">—</span>
                        </p>
                    </article>

                </div><!-- /kpi_grid_tablero -->

                <!-- ── CHART ─────────────────────────────────────────────── -->
                <section id="grafico_tablero" class="card_common panel_common panel_chart_tablero">
                    <div class="panel_head_common">
                        <h2 class="chart_title_tablero">
                            <span id="chart_metric_label_tablero">Sales</span> <span class="chart_subtitle_tablero">(Units Sold)</span>
                            <span id="delta_comparacion_tablero" class="delta_comparacion_tablero" style="display:none"></span>
                        </h2>
                    </div>
                    <div class="chart_shell_tablero">
                        <!-- Y-axis labels -->
                        <div class="chart_y_wrap_tablero" aria-hidden="true">
                            <div class="chart_y_label_tablero">Units Sold</div>
                            <div id="chart_y_ticks_tablero" class="chart_y_ticks_tablero"></div>
                        </div>
                        <!-- Chart area -->
                        <div class="chart_area_tablero">
                            <div class="chart_grid_tablero" aria-hidden="true"></div>
                            <div class="chart_target_line_tablero" aria-hidden="true">
                                <span class="target_badge_tablero"></span>
                            </div>
                            <div class="chart_bars_wrap_tablero" aria-label="Comparative sales chart">
                                <svg id="linea_ly_tablero" class="linea_ly_tablero" aria-hidden="true"></svg>
                                <div id="bars_tablero" class="bars_tablero"></div>
                            </div>
                            <!-- Tooltip -->
                            <div id="tooltip_tablero" class="tooltip_tablero" aria-live="polite">
                                <strong id="tt_periodo_tablero"></strong>
                                <span>Unidades Actuales <b id="tt_actual_tablero"></b></span>
                                <span>Objetivo <b id="tt_objetivo_tablero"></b></span>
                                <span>Var % vs LY <b id="tt_var_tablero"></b></span>
                            </div>
                        </div>
                    </div>
                    <!-- Legend -->
                    <div class="leyenda_chart_tablero" aria-label="Chart legend">
                        <span><i class="dot_leyenda_tablero actual_leyenda_tablero"></i>Actuales</span>
                        <span><i class="line_leyenda_tablero ly_leyenda_tablero"></i>LY %</span>
                        <span><i class="dot_leyenda_tablero objetivo_leyenda_tablero"></i>Objetivo</span>
                        <span><i class="line_leyenda_tablero target_leyenda_tablero"></i>Target</span>
                    </div>
                </section>

                <!-- ── OPTIONAL TABLE ─────────────────────────────────────── -->
                <section id="tabla_tablero" class="card_common panel_common panel_table_tablero">
                    <div class="head_table_tablero">
                        <button class="toggle_table_tablero" id="toggle_tabla_btn"
                                aria-expanded="true" aria-controls="tabla_body_wrap_tablero">
                            <i class="fa fa-chevron-down toggle_icon_tablero"></i>
                            Comparative sales table
                        </button>
                        <button class="btn_more_tablero" aria-label="Options">
                            <i class="fa fa-ellipsis-h"></i>
                        </button>
                    </div>
                    <div class="table_body_wrap_tablero" id="tabla_body_wrap_tablero">
                        <div class="table_wrap_tablero">
                            <table id="tabla_comparativa_tablero">
                                <thead>
                                    <tr>
                                        <th>Dealer</th>
                                        <th>Vehicle Type</th>
                                        <th>Actual</th>
                                        <th>LY</th>
                                        <th>Var %</th>
                                        <th>Goal</th>
                                        <th>Gap</th>
                                        <th class="th_paginado_tablero">
                                            <i class="fa fa-search th_search_icon_tablero"></i>
                                            <span id="kpi_info_tablero">0 &ndash; 0 of 0</span>
                                            <button id="kpi_btn_prev_tablero" class="pag_next_tablero" aria-label="Previous">
                                                <i class="fa fa-chevron-left"></i>
                                            </button>
                                            <button id="kpi_btn_next_tablero" class="pag_next_tablero" aria-label="Next">
                                                <i class="fa fa-chevron-right"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="tabla_comparativa_body_tablero">
                                    <tr>
                                        <td colspan="3" class="empty_tablero">No sales data loaded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

            </section><!-- /seccion_tablero -->

        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>
    <script>
        // Toggle optional table collapse
        (function () {
            var btn = document.getElementById('toggle_tabla_btn');
            var wrap = document.getElementById('tabla_body_wrap_tablero');
            if (!btn || !wrap) return;
            btn.addEventListener('click', function () {
                var isExpanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', String(!isExpanded));
                wrap.classList.toggle('is_collapsed_tablero', isExpanded);
                btn.querySelector('.toggle_icon_tablero').className =
                    'toggle_icon_tablero fa ' + (isExpanded ? 'fa-chevron-right' : 'fa-chevron-down');
            });
        })();

        // Chart de ventas (render real en dashboardSales.js) - dummy desactivado
        (function () { return;  // bloque dummy eliminado
            var datos = [
                { y: '2010', actuales: 80,  objetivo: 95,  ly: 55 },
                { y: '2011', actuales: 92,  objetivo: 105, ly: 70 },
                { y: '2012', actuales: 105, objetivo: 125, ly: 85 },
                { y: '2013', actuales: 118, objetivo: 140, ly: 98 },
                { y: '2014', actuales: 128, objetivo: 148, ly: 120 },
                { y: '2015', actuales: 145, objetivo: 182, ly: 135 },
                { y: '2015', actuales: 162, objetivo: 198, ly: 148 },
                { y: '2016', actuales: 178, objetivo: 225, ly: 198 },
                { y: '2018', actuales: 210, objetivo: 292, ly: 248 },
                { y: '2018', actuales: 258, objetivo: 355, ly: 272 },
                { y: '2017', actuales: 275, objetivo: 342, ly: 265 },
                { y: '2018', actuales: 292, objetivo: 362, ly: 285 },
                { y: '2019', actuales: 318, objetivo: 395, ly: 322 },
                { y: '2019', actuales: 332, objetivo: 432, ly: 338 },
                { y: '2019', actuales: 305, objetivo: 372, ly: 352 },
                { y: '2019', actuales: 365, objetivo: 420, ly: 375 },
                { y: '2021', actuales: 382, objetivo: 452, ly: 402 },
                { y: '2022', actuales: 395, objetivo: 440, ly: 425 },
                { y: '2024', actuales: 380, objetivo: 460, ly: 438, active: true, mes: 'Abr 2024', varTxt: '-8.2%' },
                { y: '2029', actuales: 445, objetivo: 488, ly: 455 },
                { y: '2023', actuales: 468, objetivo: 565, ly: 472 },
                { y: '2024', actuales: 520, objetivo: 580, ly: 502 },
                { y: '2024', actuales: 548, objetivo: 612, ly: 520 },
                { y: '2024', actuales: 568, objetivo: 602, ly: 548 }
            ];

            var maxY = 700;
            var target = 900;
            var bars = document.getElementById('bars_tablero');
            var lineSvg = document.getElementById('linea_ly_tablero');
            var tooltip = document.getElementById('tooltip_tablero');
            if (!bars || !lineSvg || !tooltip) return;

            var ttPeriodo = document.getElementById('tt_periodo_tablero');
            var ttActual = document.getElementById('tt_actual_tablero');
            var ttObjetivo = document.getElementById('tt_objetivo_tablero');
            var ttVar = document.getElementById('tt_var_tablero');
            var targetBadge = document.querySelector('.target_badge_tablero');
            var targetLine = document.querySelector('.chart_target_line_tablero');
            var chartYAxisTicks = document.getElementById('chart_y_ticks_tablero');
            var chartGrid = document.querySelector('.chart_grid_tablero');
            var filtroTipo = document.getElementById('filtro_tipo_tablero');
            var chartMetricLabel = document.getElementById('chart_metric_label_tablero');

            maxY = Math.max(target, datos.reduce(function (maximo, item) {
                return Math.max(maximo, item.actuales, item.objetivo, item.ly);
            }, 0));
            maxY = Math.ceil(maxY / 100) * 100;
            // Ensure the target line has at least ~10% headroom from the top
            if (target / maxY >= 0.9) {
                maxY = Math.ceil(target / 100) * 100 + 100;
            }

            function renderYAxis() {
                var pasos = [];
                for (var valor = maxY; valor >= 0; valor -= 100) {
                    pasos.push('<span>' + valor + '</span>');
                }

                if (chartYAxisTicks) {
                    chartYAxisTicks.innerHTML = pasos.join('');
                }

                if (chartGrid) {
                    chartGrid.innerHTML = pasos.map(function (_, index) {
                        var posicion = (index / (pasos.length - 1)) * 100;
                        return '<span style="bottom:' + posicion + '%"></span>';
                    }).join('');
                }
            }

            var X_AXIS_LABEL_SPACE = 24;

            function getPlotHeight() {
                return Math.max(0, bars.clientHeight - X_AXIS_LABEL_SPACE);
            }

            function valueToHeight(valor) {
                return (valor / maxY) * getPlotHeight();
            }

            function valueToTop(valor) {
                return getPlotHeight() - valueToHeight(valor);
            }

            function updateTargetLine(valor, indice) {
                if (!targetLine) return;
                var v = (valor !== undefined) ? valor : target;

                if (typeof indice === 'number' && indice >= 0) {
                    var barraObjetivo = bars.querySelector('.bar_wrap_tablero[data-idx="' + indice + '"] .bar_objetivo_tablero');
                    if (barraObjetivo) {
                        targetLine.style.top = barraObjetivo.offsetTop + 'px';
                    } else {
                        targetLine.style.top = valueToTop(v) + 'px';
                    }
                } else {
                    targetLine.style.top = valueToTop(v) + 'px';
                }

                if (targetBadge) targetBadge.textContent = String(v);
            }

            function actualizarTipoVehiculo() {
                if (!filtroTipo || !chartMetricLabel) return;

                var labels = {
                    all: 'Vehicle Sales',
                    cv: 'CV Sales',
                    lcv: 'LCV Sales'
                };

                chartMetricLabel.textContent = labels[filtroTipo.value] || 'LCV Sales';
            }

            function pct(v) {
                var p = (v / maxY) * 100;
                if (p < 2) return 2;
                if (p > 100) return 100;
                return p;
            }

            var selectedIndex = null;

            function renderBars() {
                bars.innerHTML = datos.map(function (d, idx) {
                    var isSelected = idx === selectedIndex;
                    return '<div class="bar_wrap_tablero" data-idx="' + idx + '">' +
                        '<div class="bar_objetivo_tablero" style="height:' + valueToHeight(d.objetivo) + 'px"></div>' +
                        '<div class="bar_tablero ' + (isSelected ? 'highlight_bar_tablero' : '') + '" style="--h:' + valueToHeight(d.actuales) + 'px"></div>' +
                        '<span class="' + (isSelected ? 'x_active_tablero' : '') + '">' + d.y + '</span>' +
                        '</div>';
                }).join('');
            }

            function drawLine() {
                var wraps = bars.querySelectorAll('.bar_wrap_tablero');
                var h = getPlotHeight();
                var pts = [];
                wraps.forEach(function (w, i) {
                    var x = w.offsetLeft + (w.clientWidth / 2);
                    var y = h - ((datos[i].ly / maxY) * h);
                    pts.push([x, y]);
                });

                var poly = '<polyline class="ly_poly_tablero" points="' + pts.map(function (p) { return p[0] + ',' + p[1]; }).join(' ') + '"></polyline>';
                var circles = pts.map(function (p, i) {
                    var cls = (i === selectedIndex ? 'ly_point_tablero ly_point_active_tablero' : 'ly_point_tablero');
                    return '<circle class="' + cls + '" cx="' + p[0] + '" cy="' + p[1] + '" r="5"></circle>';
                }).join('');
                lineSvg.setAttribute('viewBox', '0 0 ' + bars.clientWidth + ' ' + h);
                lineSvg.innerHTML = poly + circles;
            }

            function setTooltip(i) {
                var d = datos[i];
                var varTxt = d.varTxt ? d.varTxt : (((d.actuales - d.ly) / d.ly) * 100).toFixed(1) + '%';
                ttPeriodo.textContent = d.mes ? d.mes : d.y;
                ttActual.textContent = d.actuales;
                ttObjetivo.textContent = d.objetivo;
                ttVar.textContent = varTxt;
                ttVar.className = (String(varTxt).indexOf('-') === 0) ? 'negativo_tablero' : 'positivo_tablero';
                tooltip.classList.add('is_visible_tablero');
                updateTargetLine(d.objetivo, i);
            }

            renderYAxis();
            updateTargetLine();
            actualizarTipoVehiculo();
            renderBars();
            drawLine();

            if (filtroTipo) {
                filtroTipo.addEventListener('change', actualizarTipoVehiculo);
            }

            bars.addEventListener('click', function (e) {
                e.stopPropagation();
                var wrap = e.target.closest('.bar_wrap_tablero');
                if (!wrap) return;
                var i = parseInt(wrap.getAttribute('data-idx'), 10);
                if (isNaN(i)) return;

                selectedIndex = i;
                renderBars();
                drawLine();
                setTooltip(i);
            });

            document.addEventListener('click', function (e) {
                var clickDentroBarras = e.target.closest('#bars_tablero');
                var clickTooltip = e.target.closest('#tooltip_tablero');
                if (clickDentroBarras || clickTooltip) return;

                selectedIndex = null;
                renderBars();
                drawLine();
                tooltip.classList.remove('is_visible_tablero');
                updateTargetLine(target);
            });

            window.addEventListener('resize', function () {
                renderBars();
                drawLine();

                if (selectedIndex !== null) {
                    updateTargetLine(datos[selectedIndex].objetivo, selectedIndex);
                } else {
                    updateTargetLine(target);
                }
            });
        })();
    </script>
</body>
</html>
