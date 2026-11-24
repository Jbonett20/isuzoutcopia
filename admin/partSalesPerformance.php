<?php
    $loc = "partssalesperformance";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>" />
    <link rel="stylesheet" href="dashboard/css/partSalesPerformance.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/partSalesPerformance.css'); ?>" />
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
            <section class="view_block_psp">

                <!-- ── PAGE TITLE ──────────────────────────────────── -->
                <h1 class="psp_page_title">Part Sales Performance</h1>

                <!-- ── TOOLBAR DE FILTROS ──────────────────────────── -->
                <article class="psp_toolbar">
                    <div class="psp_toolbar_title">
                        <h2 class="psp_toolbar_heading">Ventas de Partes</h2>
                        <span class="psp_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="psp_toolbar_filters">
                        <div class="psp_toolbar_item">
                            <span class="psp_toolbar_lbl">Dealer:</span>
                            <select id="psp_filter_dealer" class="psp_toolbar_select psp_select_multi" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="psp_toolbar_item">
                            <span class="psp_toolbar_lbl">Country:</span>
                            <select id="psp_filter_country" class="psp_toolbar_select psp_select_multi" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="psp_toolbar_item">
                            <span class="psp_toolbar_lbl">Channel:</span>
                            <select id="psp_filter_channel" class="psp_toolbar_select psp_select_multi" multiple data-placeholder="All channels"></select>
                        </div>
                        <div class="psp_toolbar_item">
                            <span class="psp_toolbar_lbl">Period:</span>
                            <select id="psp_filter_periodo" class="psp_toolbar_select">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="psp_toolbar_item">
                            <span class="psp_toolbar_lbl">Year:</span>
                            <select id="psp_filter_anio" class="psp_toolbar_select"></select>
                        </div>
                        <div class="psp_toolbar_item">
                            <span class="psp_toolbar_lbl">Month:</span>
                            <select id="psp_filter_mes" class="psp_toolbar_select"></select>
                        </div>
                        <div class="psp_toolbar_item">
                            <span class="psp_toolbar_lbl">Comparison:</span>
                            <select id="psp_filter_comparacion" class="psp_toolbar_select">
                                <option value="none" selected>None</option>
                                <option value="cy_vs_py">CY vs PY</option>
                                <option value="ytd_vs_py_ytd">YTD vs PY YTD</option>
                                <option value="month_vs_py">Month vs PY</option>
                            </select>
                        </div>
                        <button id="psp_btn_reset" class="psp_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /psp_toolbar -->

                <!-- ── KPI CARDS ───────────────────────────────────── -->
                <div class="psp_kpi_row" id="psp_kpi_row">

                    <!-- Card 1: Ventas Actual -->
                    <div class="psp_kpi_card">
                        <p class="psp_kpi_label">Ventas Actual</p>
                        <p class="psp_kpi_value"><span id="psp_kpi_actual">—</span></p>
                        <p class="psp_kpi_sub" id="psp_kpi_actual_trend">—</p>
                    </div>

                    <!-- Card 2: Cumplimiento vs Objetivo -->
                    <div class="psp_kpi_card">
                        <p class="psp_kpi_label">Cumplimiento vs Objetivo</p>
                        <p class="psp_kpi_value"><span id="psp_kpi_cumplimiento">—</span></p>
                        <p class="psp_kpi_sub" id="psp_kpi_cumplimiento_sub">—</p>
                    </div>

                    <!-- Card 3: Gap vs Objetivo -->
                    <div class="psp_kpi_card">
                        <p class="psp_kpi_label">Gap vs Objetivo</p>
                        <p class="psp_kpi_value"><span id="psp_kpi_gap">—</span></p>
                        <p class="psp_kpi_sub" id="psp_kpi_gap_sub">—</p>
                    </div>

                    <!-- Card 4: Var % vs LY -->
                    <div class="psp_kpi_card">
                        <p class="psp_kpi_label">Var % vs LY</p>
                        <p class="psp_kpi_value"><span id="psp_kpi_var">—</span></p>
                        <p class="psp_kpi_sub" id="psp_kpi_var_sub">—</p>
                    </div>

                    <!-- Sparkline card (dinámica) -->
                    <div class="psp_sparkline_card">
                        <div class="psp_spark_badges">
                            <span class="psp_spark_badge psp_up" id="psp_spark_up">—</span>
                            <span class="psp_spark_badge psp_dn" id="psp_spark_dn">—</span>
                            <span class="psp_spark_badge psp_flat" id="psp_spark_flat">—</span>
                        </div>
                        <div class="psp_spark_area">
                            <svg class="psp_spark_svg" id="psp_spark_svg" viewBox="0 0 166 52" preserveAspectRatio="none"></svg>
                        </div>
                        <div class="psp_spark_x" id="psp_spark_x"></div>
                    </div>

                </div><!-- /psp_kpi_row -->

                <!-- ── MAIN 2-COL SECTION ───────────────────────────── -->
                <div class="psp_main_row">

                    <!-- Chart card -->
                    <div class="psp_chart_card">
                        <div class="psp_chart_card_title">
                            <div class="psp_chart_accent_bar"></div>
                            <span id="psp_chart_title">Ventas de Partes vs Objetivo</span>
                            <span id="psp_delta_comparacion" class="psp_delta_comparacion" style="display:none"></span>
                        </div>

                        <div class="psp_chart_shell">
                            <!-- Y axis left -->
                            <div class="psp_y_wrap">
                                <div class="psp_y_label">Ventas</div>
                                <div class="psp_y_ticks" id="psp_y_left"></div>
                            </div>

                            <!-- Bar + lines area -->
                            <div class="psp_chart_area">
                                <div class="psp_chart_grid" id="psp_chart_grid"></div>
                                <svg class="psp_lines_svg" id="psp_lines_svg" viewBox="0 0 100 100" preserveAspectRatio="none"></svg>
                                <div class="psp_bars" id="psp_bars"></div>
                                <div class="psp_tooltip" id="psp_tooltip">
                                    <span class="psp_tooltip_main"></span>
                                    <span class="psp_tooltip_sub"></span>
                                </div>
                            </div>
                        </div><!-- /psp_chart_shell -->

                        <!-- Legend -->
                        <div class="psp_legend">
                            <div class="psp_legend_item">
                                <div class="psp_legend_dot" style="background:#d1142a;"></div>
                                Ventas Reales
                            </div>
                            <div class="psp_legend_item">
                                <div class="psp_legend_line" style="background:#1f2937;"></div>
                                Objetivo
                            </div>
                            <div class="psp_legend_item">
                                <div class="psp_legend_dashed" style="color:#2563eb;"></div>
                                Año Pasado (LY)
                            </div>
                        </div>
                    </div><!-- /psp_chart_card -->

                    <!-- Top Dealers (dinámico) -->
                    <div class="psp_dealers_card">
                        <div class="psp_dealers_head">Top Dealers</div>
                        <div class="psp_dealers_body">
                            <table class="psp_dealers_table">
                                <thead>
                                    <tr>
                                        <th>Ranking</th>
                                        <th class="psp_td_right">Ventas</th>
                                    </tr>
                                </thead>
                                <tbody id="psp_top_dealers_body"></tbody>
                            </table>
                        </div>
                    </div><!-- /psp_dealers_card -->

                </div><!-- /psp_main_row -->

                <!-- ── BOTTOM TABLE SECTION ─────────────────────────── -->
                <div class="psp_table_section">
                    <div class="psp_table_head">
                        <div class="psp_table_title">
                            <strong>Ventas de Partes</strong> por Dealer
                            <span class="psp_table_info" id="psp_table_info">0 &ndash; 0 de 0</span>
                        </div>
                    </div>
                    <div class="psp_data_table_wrap">
                        <table class="psp_data_table" id="psp_data_table">
                            <thead>
                                <tr>
                                    <th>Dealer</th>
                                    <th>Ventas</th>
                                    <th>Objetivo</th>
                                    <th>% Cumplimiento</th>
                                    <th>Var % vs LY</th>
                                    <th>Estado</th>
                                    <th class="psp_td_nav">
                                        <button id="psp_btn_prev" class="psp_pag_btn" aria-label="Previous"><i class="fa fa-chevron-left"></i></button>
                                        <button id="psp_btn_next" class="psp_pag_btn" aria-label="Next"><i class="fa fa-chevron-right"></i></button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="psp_table_body"></tbody>
                        </table>
                    </div>
                </div><!-- /psp_table_section -->

            </section><!-- /view_block_psp -->
        </main>
    </div><!-- /content-wrap -->

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
