<?php
    $loc = "workshopreport";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workshop Report</title>
    <?php include_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/workshopReport.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/workshopReport.css'); ?>">
</head>

<body class="">
    <?php include_once("src/navbar.php"); ?>

    <nav id="sidebar" class="sidebar">
        <div class="js-sidebar-content">
            <?php include_once("src/logo.php"); ?>
            <?php include_once("src/sidebar.php"); ?>
        </div>
    </nav>

    <div class="content-wrap">
        <main id="content" class="content" role="main">

            <div class="view_block_wr">

                <!-- ── Title ─────────────────────────────────────── -->
                <h1 class="wr_title">Workshop Report</h1>

                <!-- ── TOOLBAR DE FILTROS GLOBALES ──────────────── -->
                <article class="wr_toolbar">
                    <div class="wr_toolbar_title">
                        <h2 class="wr_toolbar_heading">Reporte Taller (Servicio)</h2>
                        <span class="wr_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="wr_toolbar_filters">
                        <div class="wr_toolbar_item">
                            <span class="wr_toolbar_lbl">Dealer:</span>
                            <select id="wr_filter_dealer" class="wr_toolbar_select wr_select_multi" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="wr_toolbar_item">
                            <span class="wr_toolbar_lbl">Country:</span>
                            <select id="wr_filter_country" class="wr_toolbar_select wr_select_multi" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="wr_toolbar_item">
                            <span class="wr_toolbar_lbl">Period:</span>
                            <select id="wr_filter_periodo" class="wr_toolbar_select">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="wr_toolbar_item">
                            <span class="wr_toolbar_lbl">Year:</span>
                            <select id="wr_filter_anio" class="wr_toolbar_select"></select>
                        </div>
                        <div class="wr_toolbar_item">
                            <span class="wr_toolbar_lbl">Month:</span>
                            <select id="wr_filter_mes" class="wr_toolbar_select"></select>
                        </div>
                        <button id="wr_btn_reset" class="wr_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /wr_toolbar -->

                <!-- ── KPI cards ─────────────────────────────────── -->
                <div class="wr_kpi_row">
                    <div class="wr_kpi_card">
                        <div class="wr_kpi_label">Service Orders <span>(Mes Actual)</span></div>
                        <div class="wr_kpi_value" id="wr_kpi_orders">—</div>
                        <div class="wr_kpi_sub" id="wr_kpi_orders_sub">—</div>
                    </div>
                    <div class="wr_kpi_card">
                        <div class="wr_kpi_label">Var % vs LY</div>
                        <div class="wr_kpi_value green" id="wr_kpi_var">—</div>
                    </div>
                    <div class="wr_kpi_card">
                        <div class="wr_kpi_label">Retención</div>
                        <div class="wr_kpi_value" id="wr_kpi_retention">—</div>
                    </div>
                    <div class="wr_kpi_card">
                        <div class="wr_kpi_label">Open Orders</div>
                        <div class="wr_kpi_value amber" id="wr_kpi_open">—</div>
                    </div>
                </div>

                <!-- ── Info box ──────────────────────────────────── -->
                <div class="wr_info_box" id="wr_info_box" style="display:none">
                    <div class="wr_info_accent"></div>
                    <span id="wr_info_text"></span>
                </div>

                <!-- ── Main row ──────────────────────────────────── -->
                <div class="wr_main_row">

                    <!-- LEFT: combo chart -->
                    <div class="wr_chart_card">
                        <div class="wr_chart_title">
                            Service Orders &amp; Retention
                            <span class="wr_chart_periodo" id="wr_chart_periodo"></span>
                        </div>

                        <div class="wr_chart_shell">

                            <!-- Y-axis left -->
                            <div class="wr_y_wrap">
                                <div class="wr_y_label">Service Ord.</div>
                                <div class="wr_y_ticks" id="wr_yticks_left"></div>
                            </div>

                            <!-- Chart area -->
                            <div class="wr_chart_area" id="wr_chart_area">
                                <!-- Grid lines -->
                                <div class="wr_grid" id="wr_grid"></div>
                                <!-- Bars -->
                                <div class="wr_bars" id="wr_bars"></div>
                                <!-- SVG overlay for retention target -->
                                <svg class="wr_svg" id="wr_svg" viewBox="0 0 100 100" preserveAspectRatio="none"></svg>
                                <!-- X-axis -->
                                <div class="wr_xaxis" id="wr_xaxis"></div>
                                <!-- Tooltip -->
                                <div class="wr_tooltip" id="wr_tooltip"></div>
                            </div>

                            <!-- Y-axis right -->
                            <div class="wr_y_wrap_right">
                                <div class="wr_y_ticks_right" id="wr_yticks_right"></div>
                                <div class="wr_y_label_right">Retención %</div>
                            </div>

                        </div><!-- .wr_chart_shell -->

                        <!-- Legend -->
                        <div class="wr_legend">
                            <div class="wr_legend_item">
                                <div class="wr_legend_sq" style="background:#1e3a5f;"></div>
                                Service Orders
                            </div>
                            <div class="wr_legend_item">
                                <div class="wr_legend_line" style="background:#dc2626;"></div>
                                Target Retention
                            </div>
                        </div>

                        <!-- Health strip (referencia de retención) -->
                        <div class="wr_health_wrap">
                            <div class="wr_health_track">
                                <div class="wr_health_strip"></div>
                                <div class="wr_health_dot" id="wr_health_dot" style="display:none"></div>
                            </div>
                            <div class="wr_health_labels">
                                <span class="wr_hl_verde">Verde &gt;12%</span>
                                <span class="wr_hl_amber">9% – 12%</span>
                                <span class="wr_hl_red">Rojo &lt;9%</span>
                            </div>
                        </div>

                    </div><!-- .wr_chart_card -->

                    <!-- RIGHT column -->
                    <div class="wr_right_col">

                        <!-- Card: Estado de Retención -->
                        <div class="wr_side_card">
                            <div class="wr_side_title">Estado de Retención</div>
                            <div class="wr_status_list" id="wr_status_list">
                                <div class="wr_estado_vacio">No data loaded</div>
                            </div>
                        </div>

                    </div><!-- .wr_right_col -->

                </div><!-- .wr_main_row -->

            </div><!-- .view_block_wr -->

        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
