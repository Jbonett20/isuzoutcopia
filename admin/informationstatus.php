<?php
    $loc = "informationstatus";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>" />
    <link rel="stylesheet" href="dashboard/css/informationstatus.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/informationstatus.css'); ?>" />
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
            <section id="seccion_informationstatus" class="view_block_informationstatus">

                <!-- Título -->
                <h2 class="titulo_informationstatus">Information Status Report</h2>

                <!-- ── TOOLBAR DE FILTROS GLOBALES ─────────────────── -->
                <article class="is_toolbar">
                    <div class="is_toolbar_title">
                        <h2 class="is_toolbar_heading">Cumplimiento de Carga de Datos</h2>
                        <span class="is_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="is_toolbar_filters">
                        <div class="is_toolbar_item">
                            <span class="is_toolbar_lbl">Dealer:</span>
                            <select id="is_filter_dealer" class="is_toolbar_select is_select_multi" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="is_toolbar_item">
                            <span class="is_toolbar_lbl">Country:</span>
                            <select id="is_filter_country" class="is_toolbar_select is_select_multi" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="is_toolbar_item">
                            <span class="is_toolbar_lbl">Period:</span>
                            <select id="is_filter_periodo" class="is_toolbar_select">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="is_toolbar_item">
                            <span class="is_toolbar_lbl">Year:</span>
                            <select id="is_filter_anio" class="is_toolbar_select"></select>
                        </div>
                        <div class="is_toolbar_item">
                            <span class="is_toolbar_lbl">Month:</span>
                            <select id="is_filter_mes" class="is_toolbar_select"></select>
                        </div>
                        <div class="is_toolbar_item">
                            <span class="is_toolbar_lbl">Comparison:</span>
                            <select id="is_filter_comparacion" class="is_toolbar_select">
                                <option value="none" selected>None</option>
                                <option value="cy_vs_py">CY vs PY</option>
                                <option value="ytd_vs_py_ytd">YTD vs PY YTD</option>
                                <option value="month_vs_py">Month vs PY</option>
                            </select>
                        </div>
                        <button id="is_btn_reset" class="is_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /is_toolbar -->

                <!-- ── RESUMEN VERDE + BADGES ──────────────────────── -->
                <div class="resumen_section_informationstatus">
                    <div class="resumen_green_bar_informationstatus">
                        <span class="resumen_label_informationstatus">Cumplimiento General</span>
                        <span class="resumen_percent_informationstatus" id="is_resumen_pct">—</span>
                        <span class="resumen_delta_informationstatus" id="is_resumen_delta" style="display:none"></span>
                    </div>
                    <div class="resumen_badges_box_informationstatus">
                        <span class="badge_item_informationstatus aldia"><strong id="is_badge_aldia">—</strong> Al Día</span>
                        <span class="badge_separator_informationstatus">·</span>
                        <span class="badge_item_informationstatus pendiente"><strong id="is_badge_pendiente">—</strong> Pendiente</span>
                        <span class="badge_separator_informationstatus">·</span>
                        <span class="badge_item_informationstatus critico"><strong id="is_badge_critico">—</strong> Críticos</span>
                    </div>
                </div>

                <!-- ── KPI CARDS POR CATEGORÍA (dinámicas) ─────────── -->
                <div class="kpi_grid_informationstatus" id="is_kpi_grid"></div>
            </section>

            <!-- ========== SEGUNDA SECCIÓN ========== -->
            <section class="seccion2_informationstatus">

                <!-- FILA 1: Barchart + KPIs Críticos Top -->
                <div class="fila_superior_informationstatus">

                    <!-- Barchart Panel -->
                    <div class="panel_informationstatus panel_barchart_informationstatus">
                        <div class="tabla_header_informationstatus">
                            <h3 class="panel_titulo_informationstatus">Information Status by Dealer</h3>
                            <span class="is_chart_periodo" id="is_chart_periodo"></span>
                        </div>
                        <div class="barchart_body_informationstatus" id="is_barchart">
                            <div class="is_estado_vacio">No data loaded</div>
                        </div>
                    </div>

                    <!-- KPIs Críticos Top (derecha) -->
                    <div class="panel_informationstatus panel_kpicriticos_informationstatus">
                        <div class="kpicriticos_header_informationstatus">
                            <span class="kpicriticos_icon_informationstatus">&#9888;</span>
                            <h3 class="kpicriticos_titulo_informationstatus">KPIs Críticos (Top 5)</h3>
                            <span class="kpicriticos_bell_informationstatus">&#128276;</span>
                        </div>
                        <div class="kpicriticos_list_informationstatus" id="is_kpicriticos_list">
                            <div class="is_estado_vacio">No critical KPIs</div>
                        </div>
                    </div>
                </div>

                <!-- FILA 2: Tabla + KPIs Críticos Bottom -->
                <div class="fila_inferior_informationstatus">

                    <!-- Tabla -->
                    <div class="panel_informationstatus panel_tabla_informationstatus">
                        <div class="tabla_header_informationstatus">
                            <h3 class="panel_titulo_informationstatus">Information Status by Dealer</h3>
                            <div class="tabla_leyenda_informationstatus">
                                <span class="leyenda_item_informationstatus"><span class="leyenda_dot_informationstatus leyenda_aldia_informationstatus"></span> Al Día</span>
                                <span class="leyenda_item_informationstatus"><span class="leyenda_dot_informationstatus leyenda_cortona_informationstatus"></span> Pendiente</span>
                                <span class="leyenda_item_informationstatus"><span class="leyenda_dot_informationstatus leyenda_critico_informationstatus"></span> Crítico</span>
                            </div>
                        </div>
                        <div class="is_tabla_scroll">
                            <table class="tabla_informationstatus">
                                <thead>
                                    <tr>
                                        <th>Dealer</th>
                                        <th>Parts</th>
                                        <th>Sales</th>
                                        <th>Service</th>
                                        <th>Inventory</th>
                                        <th>Overall Status</th>
                                    </tr>
                                </thead>
                                <tbody id="is_tabla_body">
                                    <tr><td colspan="6" class="is_estado_vacio">No data loaded</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- KPIs Críticos Bottom -->
                    <div class="panel_informationstatus panel_kpicriticos_informationstatus">
                        <div class="kpicriticos_header_informationstatus">
                            <span class="kpicriticos_icon_informationstatus">&#9888;</span>
                            <h3 class="kpicriticos_titulo_informationstatus">KPIS Críticos (Top 5)</h3>
                        </div>
                        <div class="kpicriticos_list_informationstatus" id="is_kpicriticos_list_bottom">
                            <div class="is_estado_vacio">No critical KPIs</div>
                        </div>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
