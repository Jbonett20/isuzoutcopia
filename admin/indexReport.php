<?php
    $loc = "indexreport";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>" />
    <link rel="stylesheet" href="dashboard/css/indexReport.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/indexReport.css'); ?>" />
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
            <section class="view_block_ir">

                <!-- ── PAGE TITLE ──────────────────────────────── -->
                <h1 class="ir_page_title">Index Report</h1>

                <!-- ── TOOLBAR DE FILTROS GLOBALES ─────────────── -->
                <article class="ir_toolbar">
                    <div class="ir_toolbar_title">
                        <h2 class="ir_toolbar_heading">$/UIO — Ventas de Repuestos por Unidad en Operación</h2>
                        <span class="ir_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="ir_toolbar_filters">
                        <div class="ir_toolbar_item">
                            <span class="ir_toolbar_lbl">Dealer:</span>
                            <select id="ir_filter_dealer" class="ir_toolbar_select ir_select_multi" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="ir_toolbar_item">
                            <span class="ir_toolbar_lbl">Country:</span>
                            <select id="ir_filter_country" class="ir_toolbar_select ir_select_multi" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="ir_toolbar_item">
                            <span class="ir_toolbar_lbl">Period:</span>
                            <select id="ir_filter_periodo" class="ir_toolbar_select">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="ir_toolbar_item">
                            <span class="ir_toolbar_lbl">Year:</span>
                            <select id="ir_filter_anio" class="ir_toolbar_select"></select>
                        </div>
                        <div class="ir_toolbar_item">
                            <span class="ir_toolbar_lbl">Month:</span>
                            <select id="ir_filter_mes" class="ir_toolbar_select"></select>
                        </div>
                        <button id="ir_btn_reset" class="ir_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /ir_toolbar -->

                <!-- ── KPI CARDS ───────────────────────────────── -->
                <div class="ir_kpi_row" id="ir_kpi_row">

                    <!-- Índice $/UIO Actual -->
                    <div class="ir_kpi_card">
                        <p class="ir_kpi_label">Índice $/UIO</p>
                        <p class="ir_kpi_value" id="ir_kpi_indice">—</p>
                        <p class="ir_kpi_sub" id="ir_kpi_indice_sub">—</p>
                    </div>

                    <!-- Var 3yr -->
                    <div class="ir_kpi_card">
                        <p class="ir_kpi_label">Var 3yr</p>
                        <p class="ir_kpi_value ir_red" id="ir_kpi_var3">—<span class="ir_kpi_arrow"></span></p>
                    </div>

                    <!-- Var 5yr -->
                    <div class="ir_kpi_card">
                        <p class="ir_kpi_label">Var 5yr</p>
                        <p class="ir_kpi_value ir_green" id="ir_kpi_var5">—<span class="ir_kpi_arrow"></span></p>
                    </div>

                    <!-- UIO Growth double card -->
                    <div class="ir_kpi_card_double">
                        <div class="ir_growth_row">
                            <span class="ir_growth_label">UIO 3yr Growth</span>
                            <span class="ir_growth_value" id="ir_kpi_uio3">—</span>
                        </div>
                        <hr class="ir_growth_divider">
                        <div class="ir_growth_row">
                            <span class="ir_growth_label">UIO 5yr Growth</span>
                            <span class="ir_growth_value" id="ir_kpi_uio5">—</span>
                        </div>
                    </div>

                </div><!-- /ir_kpi_row -->

                <!-- ── INFO MESSAGE BOX ─────────────────────────── -->
                <div class="ir_info_box" id="ir_info_box" style="display:none">
                    <div class="ir_info_dot"></div>
                    <p id="ir_info_text" style="margin:0;"></p>
                </div>

                <!-- ── MAIN 2-COL SECTION ───────────────────────── -->
                <div class="ir_main_row">

                    <!-- Chart card -->
                    <div class="ir_chart_card">
                        <div class="ir_chart_card_title">
                            <div class="ir_chart_accent_bar"></div>
                            $ / UIO y UIO (3/5 Yr Avg)
                            <span class="ir_chart_periodo" id="ir_chart_periodo"></span>
                        </div>

                        <div class="ir_chart_shell">
                            <!-- Y axis left ($/UIO) -->
                            <div class="ir_y_wrap">
                                <div class="ir_y_label">$/UIO</div>
                                <div class="ir_y_ticks" id="ir_y_left"></div>
                            </div>

                            <!-- Chart area -->
                            <div class="ir_chart_area">
                                <!-- Grid lines -->
                                <div class="ir_chart_grid" id="ir_chart_grid"></div>

                                <!-- SVG lines -->
                                <svg class="ir_lines_svg" id="ir_lines_svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                                    <!-- drawn by JS -->
                                </svg>

                                <!-- Bars -->
                                <div class="ir_bars" id="ir_bars"></div>

                                <!-- Tooltip -->
                                <div class="ir_tooltip" id="ir_tooltip">
                                    <span class="ir_tooltip_main"></span>
                                    <span class="ir_tooltip_sub"></span>
                                </div>
                            </div>

                            <!-- Y axis right (UIO) -->
                            <div class="ir_y_wrap_right">
                                <div class="ir_y_ticks_right" id="ir_y_right"></div>
                                <div class="ir_y_label_right">UIO</div>
                            </div>
                        </div><!-- /ir_chart_shell -->

                        <!-- Legend -->
                        <div class="ir_legend">
                            <div class="ir_legend_item">
                                <div class="ir_legend_dot" style="background:#1a4870;"></div>
                                UIO
                            </div>
                            <div class="ir_legend_item">
                                <div class="ir_legend_dashed" style="color:#d1142a;"></div>
                                $/UIO 3yr Avg
                            </div>
                            <div class="ir_legend_item">
                                <div class="ir_legend_dashed" style="color:#2563eb;"></div>
                                $/UIO 5yr Avg
                            </div>
                        </div>

                        <!-- Health scale strip -->
                        <div class="ir_health_strip">
                            <div class="ir_health_bar">
                                <div class="ir_health_green"></div>
                                <div class="ir_health_yellow"></div>
                                <div class="ir_health_red"></div>
                            </div>
                            <div class="ir_health_labels">
                                <span class="ir_hl_green">Saludable</span>
                                <span class="ir_hl_yellow">Estable</span>
                                <span class="ir_hl_red">En Descenso Sostenido</span>
                            </div>
                        </div>
                    </div><!-- /ir_chart_card -->

                    <!-- Top Dealers -->
                    <div class="ir_dealers_card">
                        <div class="ir_dealers_head">Top Dealers — Índice $/UIO</div>
                        <div class="ir_dealers_body">
                            <table class="ir_dealers_table">
                                <thead>
                                    <tr>
                                        <th>Dealer</th>
                                        <th class="ir_td_right">Ventas</th>
                                        <th class="ir_td_right">UIO</th>
                                        <th class="ir_td_right">$/UIO</th>
                                    </tr>
                                </thead>
                                <tbody id="ir_dealers_tbody">
                                    <tr><td colspan="4" class="ir_estado_vacio">No data loaded</td></tr>
                                </tbody>
                            </table>
                        </div><!-- /ir_dealers_body -->
                    </div><!-- /ir_dealers_card -->

                </div><!-- /ir_main_row -->

            </section><!-- /view_block_ir -->
        </main>
    </div><!-- /content-wrap -->

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
