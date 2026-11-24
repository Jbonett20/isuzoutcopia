<?php
    $loc = "rankingreport";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ranking Report</title>
    <?php include_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/rankingReport.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/rankingReport.css'); ?>">
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

            <div class="view_block_rr">

                <!-- ── Title ─────────────────────────────────────── -->
                <h1 class="rr_title">Ranking Report</h1>

                <!-- ── TOOLBAR DE FILTROS GLOBALES ──────────────── -->
                <article class="rr_toolbar">
                    <div class="rr_toolbar_title">
                        <h2 class="rr_toolbar_heading">Ranking de Dealers por KPIs</h2>
                        <span class="rr_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="rr_toolbar_filters">
                        <div class="rr_toolbar_item">
                            <span class="rr_toolbar_lbl">Dealer:</span>
                            <select id="rr_filter_dealer" class="rr_toolbar_select rr_select_multi" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="rr_toolbar_item">
                            <span class="rr_toolbar_lbl">Country:</span>
                            <select id="rr_filter_country" class="rr_toolbar_select rr_select_multi" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="rr_toolbar_item">
                            <span class="rr_toolbar_lbl">Period:</span>
                            <select id="rr_filter_periodo" class="rr_toolbar_select">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="rr_toolbar_item">
                            <span class="rr_toolbar_lbl">Year:</span>
                            <select id="rr_filter_anio" class="rr_toolbar_select"></select>
                        </div>
                        <div class="rr_toolbar_item">
                            <span class="rr_toolbar_lbl">Month:</span>
                            <select id="rr_filter_mes" class="rr_toolbar_select"></select>
                        </div>
                        <button id="rr_btn_reset" class="rr_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /rr_toolbar -->

                <!-- ── MAIN GRID (left: podium+bottom | right: rankings) ── -->
                <div class="rr_main_grid">

                    <!-- LEFT COLUMN -->
                    <div class="rr_left_col">

                        <!-- Podium block -->
                        <div class="rr_podium_block">

                            <div class="rr_podium_row">
                                <div class="rr_podium_label">Top Parts Sales</div>
                                <div class="rr_podium_cards" id="rr_podium_parts">
                                    <div class="rr_estado_vacio">No data loaded</div>
                                </div>
                            </div>
                            <div class="rr_podium_row">
                                <div class="rr_podium_label">Top UIO 5yr</div>
                                <div class="rr_podium_cards" id="rr_podium_uio">
                                    <div class="rr_estado_vacio">No data loaded</div>
                                </div>
                            </div>

                            <!-- Info box -->
                            <div class="rr_info_box" id="rr_info_box" style="display:none">
                                <div class="rr_info_accent"></div>
                                <span id="rr_info_text"></span>
                            </div>

                            <!-- Metrics row -->
                            <div class="rr_metrics_row" id="rr_metrics_row"></div>

                        </div><!-- .rr_podium_block -->

                        <!-- Bottom row -->
                        <div class="rr_bottom_row">

                            <!-- Horizontal bar chart -->
                            <div class="rr_bar_section">
                                <div class="rr_kpi_selector_row">
                                    <label>Seleccionar KPI</label>
                                    <select id="rr_kpi_selector" class="rr_toolbar_select"></select>
                                </div>
                                <div class="rr_hbar_list" id="rr_hbar_list">
                                    <div class="rr_estado_vacio">No data loaded</div>
                                </div>
                            </div>

                            <!-- Score table -->
                            <div class="rr_score_section">
                                <div class="rr_score_title">Ranking General — Score</div>
                                <table class="rr_score_table">
                                    <thead>
                                        <tr>
                                            <th>Dealer</th>
                                            <th>General Score</th>
                                            <th>Posición</th>
                                            <th>Tendencia</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rr_score_tbody">
                                        <tr><td colspan="4" class="rr_estado_vacio">No data loaded</td></tr>
                                    </tbody>
                                </table>
                            </div>

                        </div><!-- .rr_bottom_row -->

                    </div><!-- .rr_left_col -->

                    <!-- RIGHT COLUMN: rankings -->
                    <div class="rr_rankings_col" id="rr_rankings_col">
                        <div class="rr_estado_vacio">No data loaded</div>
                    </div>

                </div><!-- .rr_main_grid -->

            </div><!-- .view_block_rr -->

        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
