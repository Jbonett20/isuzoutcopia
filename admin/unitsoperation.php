<?php
    $loc = "unitsoperation";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>" />
    <link rel="stylesheet" href="dashboard/css/unitsoperation.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/unitsoperation.css'); ?>" />
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
            <section id="seccion_unitsoperation" class="view_block_unitsoperation">

                <!-- ── TOOLBAR DE FILTROS ──────────────────────────── -->
                <article class="card_common panel_common toolbar_unitsoperation">
                    <div class="uo_toolbar_title">
                        <h2 class="titulo_unitsoperation">Units in Operation</h2>
                        <span class="uo_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="filtros_unitsoperation">
                        <div class="filtro_item_unitsoperation">
                            <span class="filtro_lbl_unitsoperation">Dealer:</span>
                            <select id="uo_filter_dealer" class="select_unitsoperation select_multi_unitsoperation" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="filtro_item_unitsoperation">
                            <span class="filtro_lbl_unitsoperation">Country:</span>
                            <select id="uo_filter_country" class="select_unitsoperation select_multi_unitsoperation" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="filtro_item_unitsoperation">
                            <span class="filtro_lbl_unitsoperation">Vehicle:</span>
                            <select id="uo_filter_tipo" class="select_unitsoperation">
                                <option value="all" selected>All (CV + LCV)</option>
                                <option value="cv">CV</option>
                                <option value="lcv">LCV</option>
                            </select>
                        </div>
                        <div class="filtro_item_unitsoperation">
                            <span class="filtro_lbl_unitsoperation">Year:</span>
                            <select id="uo_filter_anio" class="select_unitsoperation"></select>
                        </div>
                        <div class="filtro_item_unitsoperation">
                            <span class="filtro_lbl_unitsoperation">Month:</span>
                            <select id="uo_filter_mes" class="select_unitsoperation"></select>
                        </div>
                        <button id="uo_btn_reset" class="uo_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /toolbar -->

                <!-- ── KPI GRID ─────────────────────────────────────── -->
                <div class="kpi_grid_unitsoperation">

                    <!-- Card 1: UIO Total -->
                    <article class="card_common kpi_card_unitsoperation card_total_unitsoperation">
                        <span class="kpi_kicker_unitsoperation"><i class="fa fa-circle"></i> UIO Total</span>
                        <p class="kpi_value_unitsoperation"><span id="uo_kpi_total">—</span></p>
                        <p class="uo_kpi_sub" id="uo_kpi_total_sub">—</p>
                    </article>

                    <!-- Card 2: Crecimiento 3 años -->
                    <article class="card_common kpi_card_unitsoperation">
                        <p class="kpi_percent_unitsoperation"><span id="uo_kpi_crec3">—</span></p>
                        <p class="kpi_meta_unitsoperation"><strong id="uo_kpi_crec3_strong">—</strong> <span class="uo_kpi_meta_lbl">3yr Growth</span></p>
                    </article>

                    <!-- Card 3: Crecimiento 5 años -->
                    <article class="card_common kpi_card_unitsoperation">
                        <p class="kpi_percent_unitsoperation"><span id="uo_kpi_crec5">—</span></p>
                        <p class="kpi_meta_unitsoperation"><strong id="uo_kpi_crec5_strong">—</strong> <span class="uo_kpi_meta_lbl">5yr Growth</span></p>
                    </article>

                    <!-- Card 4: Mix LCV -->
                    <article class="card_common kpi_card_unitsoperation">
                        <p class="kpi_percent_unitsoperation"><span id="uo_kpi_mix">—</span></p>
                        <p class="kpi_meta_unitsoperation"><strong id="uo_kpi_mix_strong">—</strong> <span class="uo_kpi_meta_lbl">LCV Mix</span></p>
                    </article>

                </div><!-- /kpi_grid -->

                <!-- ── CONTENIDO: GRÁFICO + RANKING ─────────────────── -->
                <div class="contenido_unitsoperation">

                    <!-- Chart card -->
                    <section class="card_common panel_common panel_chart_unitsoperation">
                        <div class="mix_rows_unitsoperation" id="uo_mix_rows">
                            <div class="mix_row_unitsoperation">
                                <span class="mix_label_unitsoperation green_unitsoperation">LCV</span>
                                <div class="mix_track_unitsoperation"><span class="mix_fill_unitsoperation green_bg_unitsoperation" id="uo_mix_lcv" style="width:0%">0%</span></div>
                            </div>
                            <div class="mix_row_unitsoperation">
                                <span class="mix_label_unitsoperation">CV</span>
                                <div class="mix_track_unitsoperation"><span class="mix_fill_unitsoperation blue_bg_unitsoperation" id="uo_mix_cv" style="width:0%">0%</span></div>
                            </div>
                        </div>

                        <div class="chart_header_unitsoperation">
                            <h3>Unit Sales and UIO 3/5 Year Average</h3>
                        </div>

                        <div class="chart_area_unitsoperation" aria-label="Unit Sales chart">
                            <div class="y_axis_unitsoperation">Unit Sales</div>
                            <div class="uo_y_left_ticks" id="uo_y_left"></div>
                            <div class="uo_bars" id="uo_bars"></div>
                            <div class="uo_x_labels" id="uo_x_labels"></div>
                            <div class="uo_tooltip" id="uo_tooltip" aria-live="polite"></div>
                        </div>

                        <ul class="legend_unitsoperation">
                            <li><span class="legend_dot_unitsoperation uo_legend_cv"></span>CV</li>
                            <li><span class="legend_dot_unitsoperation uo_legend_lcv"></span>LCV</li>
                        </ul>
                    </section><!-- /chart -->

                    <!-- Ranking por dealer -->
                    <section class="card_common panel_common panel_dealers_unitsoperation">
                        <h3>Top Dealers</h3>
                        <div class="table_unitsoperation">
                            <div class="table_head_unitsoperation">
                                <span>Ranking</span><span>UIO</span>
                            </div>
                            <div id="uo_ranking" class="uo_ranking"></div>
                        </div>
                    </section><!-- /ranking -->

                </div><!-- /contenido -->

            </section><!-- /seccion_unitsoperation -->
        </main>
    </div><!-- /content-wrap -->

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
