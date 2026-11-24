<?php
    $loc = "partssalesbychannel";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>" />
    <link rel="stylesheet" href="dashboard/css/partsSalesByChannel.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/partsSalesByChannel.css'); ?>" />
    <title>Parts Sales by Channel – KPI Platform</title>
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

            <div class="view_block_psc">

                <!-- Title -->
                <h1 class="psc_title">Parts Sales by Channel</h1>

                <!-- ── TOOLBAR DE FILTROS GLOBALES ─────────────── -->
                <article class="psc_toolbar">
                    <div class="psc_toolbar_title">
                        <h2 class="psc_toolbar_heading">Venta de Partes por Canal</h2>
                        <span class="psc_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="psc_toolbar_filters">
                        <div class="psc_toolbar_item">
                            <span class="psc_toolbar_lbl">Dealer:</span>
                            <select id="psc_filter_dealer" class="psc_toolbar_select psc_select_multi" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="psc_toolbar_item">
                            <span class="psc_toolbar_lbl">Country:</span>
                            <select id="psc_filter_country" class="psc_toolbar_select psc_select_multi" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="psc_toolbar_item">
                            <span class="psc_toolbar_lbl">Period:</span>
                            <select id="psc_filter_periodo" class="psc_toolbar_select">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="psc_toolbar_item">
                            <span class="psc_toolbar_lbl">Year:</span>
                            <select id="psc_filter_anio" class="psc_toolbar_select"></select>
                        </div>
                        <div class="psc_toolbar_item">
                            <span class="psc_toolbar_lbl">Month:</span>
                            <select id="psc_filter_mes" class="psc_toolbar_select"></select>
                        </div>
                        <button id="psc_btn_reset" class="psc_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /psc_toolbar -->

                <!-- KPI Cards -->
                <div class="psc_kpi_row">
                    <div class="psc_kpi_card">
                        <div class="psc_kpi_label">Total Ventas Parts</div>
                        <div class="psc_kpi_value" id="psc_kpi_total">—</div>
                        <div class="psc_kpi_sub" id="psc_kpi_total_sub">—</div>
                    </div>
                    <div class="psc_kpi_card">
                        <div class="psc_kpi_label">Canal Líder: <span id="psc_kpi_lider">—</span></div>
                        <div class="psc_kpi_value" id="psc_kpi_lider_pct">—</div>
                    </div>
                    <div class="psc_kpi_card">
                        <div class="psc_kpi_label">Canal con Mayor Crecimiento:</div>
                        <div style="display:flex;align-items:baseline;gap:6px;">
                            <div class="psc_kpi_value" style="color:#111827;" id="psc_kpi_crec_name">—</div>
                            <div class="psc_kpi_sub" id="psc_kpi_crec_val">—</div>
                        </div>
                    </div>
                    <div class="psc_kpi_card">
                        <div class="psc_kpi_label">Crecimiento General</div>
                        <div class="psc_kpi_value green" id="psc_kpi_growth">—</div>
                    </div>
                </div>

                <!-- Info box -->
                <div class="psc_info_box" id="psc_info_box" style="display:none">
                    <div class="psc_info_accent"></div>
                    <div id="psc_info_text"></div>
                </div>

                <!-- Main row -->
                <div class="psc_main_row">

                    <!-- LEFT: Chart card -->
                    <div class="psc_chart_card">
                        <div class="psc_chart_card_title">
                            Ventas de Partes <em>por Canal</em>
                            <span class="psc_chart_periodo" id="psc_chart_periodo"></span>
                        </div>

                        <!-- Horizontal bars (channel share) -->
                        <div class="psc_hbars" id="psc_hbars">
                            <div class="psc_estado_vacio">No data loaded</div>
                        </div>

                        <!-- Multi-line chart -->
                        <div class="psc_linechart_wrap">
                            <!-- Y left -->
                            <div class="psc_yaxis" id="psc_y_left"></div>

                            <!-- Chart area -->
                            <div class="psc_linechart_area">
                                <!-- Grid lines -->
                                <div class="psc_linechart_grid" id="psc_grid"></div>

                                <!-- SVG lines -->
                                <svg class="psc_linechart_svg" id="psc_svg"
                                     viewBox="0 0 100 100"
                                     preserveAspectRatio="none">
                                </svg>

                                <!-- X-axis labels -->
                                <div class="psc_xaxis" id="psc_xaxis"></div>
                            </div>

                            <!-- Y right -->
                            <div class="psc_yaxis_right" id="psc_y_right"></div>
                        </div>

                        <!-- Legend -->
                        <div class="psc_legend" id="psc_legend"></div>
                    </div><!-- /psc_chart_card -->

                    <!-- RIGHT: side cards -->
                    <div class="psc_right_col">

                        <!-- Card 1: mayor crecimiento -->
                        <div class="psc_side_card">
                            <div class="psc_side_title">Canales con Mayor Crecimiento <em>vs Año Anterior</em></div>
                            <div class="psc_channel_list" id="psc_growth_up">
                                <div class="psc_estado_vacio">No data loaded</div>
                            </div>
                        </div>

                        <!-- Card 2: mayor caída -->
                        <div class="psc_side_card">
                            <div class="psc_side_title">Canales con Mayor Caída <em>vs Año Anterior</em></div>
                            <div class="psc_channel_list" id="psc_growth_down">
                                <div class="psc_estado_vacio">No data loaded</div>
                            </div>
                        </div>

                    </div><!-- /psc_right_col -->

                </div><!-- /psc_main_row -->
            </div><!-- /view_block_psc -->
        </main>
    </div><!-- /content-wrap -->

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
