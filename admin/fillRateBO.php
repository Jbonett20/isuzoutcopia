<?php
    $loc = "fillratebo";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fill Rate &amp; B/O</title>
    <?php include_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/fillRateBO.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/fillRateBO.css'); ?>">
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

            <div class="view_block_fr">

                <!-- ── Title ─────────────────────────────────────── -->
                <h1 class="fr_title">Fill Rate &amp; B/O</h1>

                <!-- ── TOOLBAR DE FILTROS GLOBALES ──────────────── -->
                <article class="fr_toolbar">
                    <div class="fr_toolbar_title">
                        <h2 class="fr_toolbar_heading">Nivel de Servicio en Entrega de Repuestos</h2>
                        <span class="fr_toolbar_date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="fr_toolbar_filters">
                        <div class="fr_toolbar_item">
                            <span class="fr_toolbar_lbl">Dealer:</span>
                            <select id="fr_filter_dealer" class="fr_toolbar_select fr_select_multi" multiple data-placeholder="All dealers"></select>
                        </div>
                        <div class="fr_toolbar_item">
                            <span class="fr_toolbar_lbl">Country:</span>
                            <select id="fr_filter_country" class="fr_toolbar_select fr_select_multi" multiple data-placeholder="All countries"></select>
                        </div>
                        <div class="fr_toolbar_item">
                            <span class="fr_toolbar_lbl">Period:</span>
                            <select id="fr_filter_periodo" class="fr_toolbar_select">
                                <option value="month" selected>Month</option>
                                <option value="ytd">YTD</option>
                                <option value="fytd">Fiscal YTD</option>
                                <option value="full_year">Full Year</option>
                                <option value="l12m">Last 12 Months</option>
                                <option value="prior_year">Prior Year</option>
                            </select>
                        </div>
                        <div class="fr_toolbar_item">
                            <span class="fr_toolbar_lbl">Year:</span>
                            <select id="fr_filter_anio" class="fr_toolbar_select"></select>
                        </div>
                        <div class="fr_toolbar_item">
                            <span class="fr_toolbar_lbl">Month:</span>
                            <select id="fr_filter_mes" class="fr_toolbar_select"></select>
                        </div>
                        <button id="fr_btn_reset" class="fr_btn_reset" aria-label="Reset filters" title="Reset filters">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </article><!-- /fr_toolbar -->

                <!-- ── Resumen ejecutivo (KPI cards) ────────────── -->
                <div class="fr_kpi_row" id="fr_kpi_row">
                    <div class="fr_hero_card">
                        <div class="fr_hero_pct" id="fr_kpi_fillrate">—</div>
                        <div class="fr_hero_label">Fill Rate</div>
                        <div class="fr_hero_check" id="fr_kpi_fr_sub">—</div>
                    </div>
                    <div class="fr_kpi_card">
                        <div class="fr_kpi_value" id="fr_kpi_target">—</div>
                        <div class="fr_kpi_label">Target</div>
                        <div class="fr_kpi_sub" id="fr_kpi_target_sub">—</div>
                    </div>
                    <div class="fr_kpi_card">
                        <div class="fr_kpi_value red" id="fr_kpi_gap">—</div>
                        <div class="fr_kpi_label">Gap</div>
                        <div class="fr_kpi_sub" id="fr_kpi_gap_sub">Fill Rate − Target</div>
                    </div>
                    <div class="fr_kpi_card">
                        <div class="fr_kpi_value" id="fr_kpi_bo">—</div>
                        <div class="fr_kpi_label">Back Orders</div>
                        <div class="fr_kpi_sub" id="fr_kpi_bo_sub">—</div>
                    </div>
                    <div class="fr_risk_card">
                        <div class="fr_risk_label"><span style="color:#d97706;">&#9679;</span> Ventas en Riesgo por B/O</div>
                        <div class="fr_risk_value" id="fr_kpi_riesgo">—</div>
                        <div class="fr_kpi_sub" id="fr_kpi_riesgo_sub">Estimación</div>
                    </div>
                </div><!-- .fr_kpi_row -->

                <!-- ── Gráficos ──────────────────────────────────── -->
                <div class="fr_charts_row">

                    <!-- LEFT: Fill Rate vs Target line chart -->
                    <div class="fr_chart_card fr_chart_card--fr">
                        <div class="fr_chart_header">
                            <span class="fr_chart_title" style="margin-bottom:0;">Fill Rate vs Target</span>
                            <span class="fr_fr_target_badge" id="fr_target_badge">Target 97% &#9660;</span>
                        </div>

                        <div class="fr_fillrate_shell">
                            <div class="fr_fr_y_wrap">
                                <div class="fr_fr_y_ticks" id="fr_yticks"></div>
                            </div>

                            <div class="fr_fr_area">
                                <div class="fr_fr_grid" id="fr_fr_grid"></div>
                                <svg class="fr_fr_svg" id="fr_fr_svg" viewBox="0 0 100 100" preserveAspectRatio="none"></svg>
                                <div class="fr_fr_estado_vacio" id="fr_fr_vacio" style="display:none;">No data loaded</div>
                                <div class="fr_fr_xaxis" id="fr_fr_xaxis"></div>
                            </div>

                            <div class="fr_fr_y_wrap_right">
                                <div class="fr_fr_y_ticks_right" id="fr_yticks_right"></div>
                            </div>
                        </div>

                        <div class="fr_legend">
                            <div class="fr_legend_item">
                                <span class="fr_legend_line" style="background:#991b1b;"></span>
                                Fill Rate
                            </div>
                            <div class="fr_legend_item">
                                <span class="fr_legend_line" style="background:#16a34a;"></span>
                                Target
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Back Orders bar chart -->
                    <div class="fr_chart_card fr_chart_card--bo">
                        <div class="fr_chart_title">Evolución de Back Orders</div>

                        <div class="fr_bo_shell">
                            <div class="fr_bo_y_wrap" id="fr_bo_yticks"></div>
                            <div class="fr_bo_area" id="fr_bo_area">
                                <div class="fr_bo_grid" id="fr_bo_grid"></div>
                                <div class="fr_bo_bars" id="fr_bo_bars"></div>
                                <div class="fr_bo_estado_vacio" id="fr_bo_vacio" style="display:none;">No data loaded</div>
                                <div class="fr_bo_xaxis" id="fr_bo_xaxis"></div>
                            </div>
                        </div>

                        <div class="fr_legend" style="justify-content:flex-end;margin-top:8px;">
                            <div class="fr_legend_item">
                                <div class="fr_legend_dot" style="background:#4b5563;"></div>
                                Valor Back Orders ($)
                            </div>
                        </div>
                    </div>

                </div><!-- .fr_charts_row -->

                <!-- ── Back Orders + Ventas en Riesgo ────────────── -->
                <div class="fr_bottom_card">
                    <div class="fr_bottom_left">
                        <div class="fr_chart_title">Back Orders</div>
                        <div class="fr_bo_shell_lg">
                            <div class="fr_bo_y_wrap" id="fr_bo2_yticks"></div>
                            <div class="fr_bo_area" id="fr_bo2_area">
                                <div class="fr_bo_grid" id="fr_bo2_grid"></div>
                                <div class="fr_bo_bars" id="fr_bo2_bars"></div>
                                <div class="fr_bo_estado_vacio" id="fr_bo2_vacio" style="display:none;">No data loaded</div>
                                <div class="fr_bo_xaxis" id="fr_bo2_xaxis"></div>
                            </div>
                        </div>
                    </div>
                    <div class="fr_bottom_divider"></div>
                    <div class="fr_bottom_right">
                        <div class="fr_br_top">
                            <div class="fr_br_reorting">Ventas en Riesgo por B/O</div>
                            <div class="fr_br_value" id="fr_br_riesgo">—</div>
                        </div>
                        <div class="fr_br_alert" id="fr_br_alert"></div>
                        <div class="fr_br_trend" id="fr_br_trend"></div>
                    </div>
                </div><!-- .fr_bottom_card -->

                <!-- ── Desglose por dealer ───────────────────────── -->
                <div class="fr_bottom_card">
                    <div class="fr_dealers_section">
                        <div class="fr_dealers_header">
                            <div class="fr_chart_title" style="margin-bottom:0;">Desglose por Dealer</div>
                            <span class="fr_dealers_meta" id="fr_dealers_meta">—</span>
                        </div>
                        <div class="fr_dealers_scroll">
                            <table class="fr_dealers_table">
                                <thead>
                                    <tr>
                                        <th>Dealer</th>
                                        <th>Fill Rate</th>
                                        <th>Target</th>
                                        <th>Gap</th>
                                        <th>Back Orders</th>
                                        <th>Ventas en Riesgo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="fr_dealers_tbody">
                                    <tr><td colspan="7" class="fr_estado_vacio">No data loaded</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- .fr_bottom_card -->

            </div><!-- .view_block_fr -->

        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>
</body>
</html>
