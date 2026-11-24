<?php
    $loc = "inventoryreport";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Report</title>
    <?php include_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/inventoryReport.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/inventoryReport.css'); ?>">
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

            <div class="view_block_ir">

                <!-- ── Title ─────────────────────────────────────── -->
                <h1 class="ir_title">Inventory Report</h1>

                <!-- ── TOOLBAR DE FILTROS GLOBALES ──────────────── -->
                <article class="inv_toolbar">
                    <div class="inv_toolbar_title">
                        <h2 class="inv_toolbar_heading">Reporte de Inventario</h2>
                        <span class="inv_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="inv_toolbar_filters">
                        <div class="inv_toolbar_item">
                            <span class="inv_toolbar_lbl">Dealer:</span>
                            <select id="inv_filter_dealer" class="inv_toolbar_select inv_select_multi" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="inv_toolbar_item">
                            <span class="inv_toolbar_lbl">Country:</span>
                            <select id="inv_filter_country" class="inv_toolbar_select inv_select_multi" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="inv_toolbar_item">
                            <span class="inv_toolbar_lbl">Period:</span>
                            <select id="inv_filter_periodo" class="inv_toolbar_select">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="inv_toolbar_item">
                            <span class="inv_toolbar_lbl">Year:</span>
                            <select id="inv_filter_anio" class="inv_toolbar_select"></select>
                        </div>
                        <div class="inv_toolbar_item">
                            <span class="inv_toolbar_lbl">Month:</span>
                            <select id="inv_filter_mes" class="inv_toolbar_select"></select>
                        </div>
                        <button id="inv_btn_reset" class="inv_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /inv_toolbar -->

                <!-- ── KPI cards ─────────────────────────────────── -->
                <div class="ir_kpi_row">
                    <div class="ir_kpi_card">
                        <div class="ir_kpi_label">Inventory Total</div>
                        <div class="ir_kpi_value" id="inv_kpi_inv">—</div>
                        <div class="inv_kpi_sub" id="inv_kpi_inv_sub">—</div>
                    </div>
                    <div class="ir_kpi_card">
                        <div class="ir_kpi_label">Months of Inventory</div>
                        <div class="ir_kpi_value red" id="inv_kpi_months">—</div>
                    </div>
                    <div class="ir_kpi_card">
                        <div class="ir_kpi_label">Turnover</div>
                        <div class="ir_kpi_value" id="inv_kpi_turnover">—</div>
                    </div>
                    <div class="ir_kpi_card">
                        <div class="ir_kpi_label">Target</div>
                        <div class="ir_kpi_value" id="inv_kpi_target">—</div>
                    </div>
                    <div class="ir_kpi_card">
                        <div class="ir_kpi_label">Capital en Riesgo</div>
                        <div class="ir_kpi_value red" id="inv_kpi_capital">—</div>
                    </div>
                </div>

                <!-- ── Info box ──────────────────────────────────── -->
                <div class="ir_info_box" id="inv_info_box" style="display:none">
                    <div class="ir_info_accent"></div>
                    <span id="inv_info_text"></span>
                </div>

                <!-- ── Main row ──────────────────────────────────── -->
                <div class="ir_main_row">

                    <!-- LEFT: combo chart -->
                    <div class="ir_chart_card">
                        <div class="ir_chart_title">
                            Inventory Cost, Months y Turnover
                            <span class="inv_chart_periodo" id="inv_chart_periodo"></span>
                        </div>

                        <div class="ir_chart_shell">

                            <!-- Y-axis left -->
                            <div class="ir_y_wrap">
                                <div class="ir_y_label">Inventory Cost</div>
                                <div class="ir_y_ticks" id="ir_yticks_left"></div>
                            </div>

                            <!-- Chart area -->
                            <div class="ir_chart_area" id="ir_chart_area">
                                <div class="ir_grid" id="ir_grid"></div>
                                <div class="ir_bars" id="ir_bars"></div>
                                <svg class="ir_svg" id="ir_svg" viewBox="0 0 100 100" preserveAspectRatio="none"></svg>
                                <div class="ir_xaxis" id="ir_xaxis"></div>
                                <div class="ir_tooltip" id="ir_tooltip"></div>
                            </div>

                            <!-- Y-axis right -->
                            <div class="ir_y_wrap_right">
                                <div class="ir_y_ticks_right" id="ir_yticks_right"></div>
                                <div class="ir_y_label_right">Turnover</div>
                            </div>

                        </div><!-- .ir_chart_shell -->

                        <!-- Legend -->
                        <div class="ir_legend">
                            <div class="ir_legend_item">
                                <div class="ir_legend_sq" style="background:#4b5563;"></div>
                                Inventory Cost
                            </div>
                            <div class="ir_legend_item">
                                <div class="ir_legend_line" style="background:#f97316;"></div>
                                Months of Inventory
                            </div>
                            <div class="ir_legend_item">
                                <div class="ir_legend_dashed"></div>
                                Turnover
                            </div>
                            <div class="ir_legend_item">
                                <div class="ir_legend_line" style="background:#64748b;"></div>
                                Target
                            </div>
                        </div>

                        <!-- Health strip -->
                        <div class="ir_health_wrap">
                            <div class="ir_health_track">
                                <div class="ir_health_strip"></div>
                                <div class="ir_health_dot" id="inv_health_dot" style="display:none"></div>
                            </div>
                            <div class="ir_health_labels">
                                <span class="hl_green">Saludable 8–10</span>
                                <span class="hl_amber">Exceso 10–15</span>
                                <span class="hl_red">&gt;15 Sobre Stock</span>
                            </div>
                        </div>

                    </div><!-- .ir_chart_card -->

                    <!-- RIGHT column -->
                    <div class="ir_right_col">
                        <div class="ir_side_card">
                            <div class="ir_side_title">Inventory por Dealer</div>

                            <table class="ir_dealer_table">
                                <thead>
                                    <tr>
                                        <th>Dealer</th>
                                        <th>Inventory</th>
                                        <th>Months</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="inv_dealer_tbody">
                                    <tr><td colspan="4" class="inv_estado_vacio">No data loaded</td></tr>
                                </tbody>
                            </table>

                            <!-- Status legend -->
                            <div class="ir_status_legend" id="inv_status_legend">
                                <div class="ir_status_legend_item"><div class="ir_sl_dot" style="background:#16a34a;"></div>Saludable</div>
                                <div class="ir_status_legend_item"><div class="ir_sl_dot" style="background:#d97706;"></div>Exceso</div>
                                <div class="ir_status_legend_item"><div class="ir_sl_tri"></div>Sobre Stock</div>
                                <div class="ir_status_legend_item"><div class="ir_sl_dot" style="background:#6b7280;"></div>Bajo</div>
                            </div>
                        </div>
                    </div><!-- .ir_right_col -->

                </div><!-- .ir_main_row -->

            </div><!-- .view_block_ir -->

        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
