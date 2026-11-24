<?php
    $loc = "dashboard";
    include_once '../admin/src/seguridad.php';
    @session_start();

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once "src/header.php"; ?>
  <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>" />
  <link rel="stylesheet" href="dashboard/css/inicio.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/inicio.css'); ?>" />
</head>

<body class="">
    <?php require_once "src/navbar.php"; ?>
    <nav id="sidebar" class="sidebar" role="navigation">
        <div class="js-sidebar-content">
            <?php require_once "src/logo.php"; ?>
            <?php require_once "src/sidebar.php"; ?>
        </div>
    </nav>

    <div class="content-wrap">
        <main id="content" class="content" role="main">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">You are in</li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>

            <h1 class="page-title">Dashboard</h1>

            <section id="seccion_inicio" class="view_block_inicio">
              <div id="estado_global_inicio" class="card_common panel_common status_banner_inicio">
                <div class="status_line_inicio">
                  <span class="dot_ok_inicio"></span>
                  <strong id="cumplimiento_global_inicio">Overall compliance: 56%</strong>
                  <span class="ok_text_inicio">12 Dealers on track</span>
                  <span class="danger_text_inicio">8 Critical dealers</span>
                  <span id="delta_comparacion_inicio" class="delta_inicio" style="display:none"></span>
                </div>
                <!-- Barra de filtros globales (opciones renderizadas dinámicamente) -->
                <div class="filters_inicio filters_global_inicio">
                  <div class="filter_group_inicio">
                    <label class="filter_label_inicio" for="filtro_dealer_inicio">Dealer</label>
                    <select id="filtro_dealer_inicio" class="select_common select_multi_inicio" multiple data-placeholder="All dealers"></select>
                  </div>
                  <div class="filter_group_inicio">
                    <label class="filter_label_inicio" for="filtro_pais_inicio">Country</label>
                    <select id="filtro_pais_inicio" class="select_common select_multi_inicio" multiple data-placeholder="All countries"></select>
                  </div>
                  <div class="filter_group_inicio">
                    <label class="filter_label_inicio" for="filtro_vehiculo_inicio">Vehicle</label>
                    <select id="filtro_vehiculo_inicio" class="select_common">
                      <option value="all">All (CV + LCV)</option>
                      <option value="cv">CV</option>
                      <option value="lcv">LCV</option>
                    </select>
                  </div>
                  <div class="filter_group_inicio">
                    <label class="filter_label_inicio" for="filtro_periodo_inicio">Period</label>
                    <select id="filtro_periodo_inicio" class="select_common">
                      <option value="month">Month</option>
                      <option value="ytd">YTD</option>
                      <option value="fytd">Fiscal YTD</option>
                      <option value="full_year">Full Year</option>
                      <option value="l12m">Last 12 Months</option>
                      <option value="prior_year">Prior Year</option>
                    </select>
                  </div>
                  <div class="filter_group_inicio">
                    <label class="filter_label_inicio" for="filtro_anio_inicio">Year</label>
                    <select id="filtro_anio_inicio" class="select_common"></select>
                  </div>
                  <div class="filter_group_inicio">
                    <label class="filter_label_inicio" for="filtro_mes_inicio">Month</label>
                    <select id="filtro_mes_inicio" class="select_common"></select>
                  </div>
                  <div class="filter_group_inicio">
                    <label class="filter_label_inicio" for="filtro_comparacion_inicio">Comparison</label>
                    <select id="filtro_comparacion_inicio" class="select_common">
                      <option value="none">None</option>
                      <option value="cy_vs_py">CY vs PY</option>
                      <option value="ytd_vs_py_ytd">YTD vs PY YTD</option>
                      <option value="month_vs_py">Month vs PY</option>
                    </select>
                  </div>
                  <button type="button" id="btn_reset_filtros_inicio" class="btn_reset_filtros_inicio" title="Reset filters" aria-label="Reset filters">
                    <i class="fa fa-undo"></i>
                  </button>
                </div>
              </div>

              <div class="drill_bar_inicio" id="drill_categoria_inicio">
                <span id="drill_info_categoria_inicio" class="drill_info_inicio">Categoría: todas</span>
                <button type="button" id="btn_limpiar_drill_categoria_inicio" class="btn_limpiar_drill_inicio hidden_drill_inicio" title="Limpiar drill-down de categoría" aria-label="Limpiar drill-down de categoría">
                  <i class="fa fa-times-circle"></i> Limpiar drill-down
                </button>
              </div>
              <div id="grid_kpi_inicio" class="kpi_grid_inicio">
                <!-- Las tarjetas de cumplimiento se renderizan dinámicamente desde la BD -->
              </div>
              <div id="empty_kpi_inicio" class="empty_state_inicio">Sin datos</div>

              <section id="panel_dealer_inicio" class="card_common panel_common">
                <div class="panel_head_common panel_head_drill_inicio">
                  <h2>Dealer Compliance Status</h2>
                  <button type="button" id="btn_limpiar_drill_dealer_inicio" class="btn_limpiar_drill_inicio hidden_drill_inicio" title="Limpiar drill-down de dealer" aria-label="Limpiar drill-down de dealer">
                    <i class="fa fa-times-circle"></i> Limpiar drill-down
                  </button>
                </div>
                <div class="chart_area_inicio" aria-label="Bar chart">
                  <div class="y_axis_inicio">
                    <div class="y_axis_label_inicio">% Compliance</div>
                    <div class="y_axis_ticks_inicio">
                      <span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0%</span>
                    </div>
                  </div>
                  <div class="bars_inicio">
                    <!-- Barras por dealer renderizadas dinámicamente -->
                  </div>
                  <ul id="leyenda_inicio" class="legend_inicio">
                    <!-- Leyenda renderizada dinámicamente -->
                  </ul>
                </div>
                <div id="empty_dealer_inicio" class="empty_state_inicio">Sin datos</div>
              </section>

              <!-- Desglose por país + tendencia mensual -->
              <div class="duo_panels_inicio">
                <section id="panel_paises_inicio" class="card_common panel_common">
                  <div class="panel_head_common panel_head_drill_inicio">
                    <h2>Compliance by Country</h2>
                    <button type="button" id="btn_limpiar_drill_pais_inicio" class="btn_limpiar_drill_inicio hidden_drill_inicio" title="Limpiar drill-down de país" aria-label="Limpiar drill-down de país">
                      <i class="fa fa-times-circle"></i> Limpiar drill-down
                    </button>
                  </div>
                  <div id="paises_wrap_inicio" class="paises_wrap_inicio">
                    <!-- Segmentos/barras por país renderizados dinámicamente -->
                    <div class="empty_state_inicio">Sin datos</div>
                  </div>
                </section>

                <section id="panel_tendencia_inicio" class="card_common panel_common">
                  <div class="panel_head_common panel_head_drill_inicio">
                    <h2>Monthly Trend</h2>
                    <button type="button" id="btn_limpiar_drill_tendencia_inicio" class="btn_limpiar_drill_inicio hidden_drill_inicio" title="Limpiar drill-down de mes" aria-label="Limpiar drill-down de mes">
                      <i class="fa fa-times-circle"></i> Limpiar drill-down
                    </button>
                  </div>
                  <div id="tendencia_wrap_inicio" class="tendencia_wrap_inicio">
                    <!-- Puntos/barras de tendencia mensual renderizados dinámicamente -->
                    <div class="empty_state_inicio">Sin datos</div>
                  </div>
                </section>
              </div>

              <!-- Panel Information Status integrado (sparklines por categoría) -->
              <section id="panel_informationstatus_inicio" class="card_common panel_common">
                <div class="panel_head_common panel_head_informationstatus_inicio">
                  <h2>Information Status</h2>
                  <div id="informationstatus_resumen_inicio" class="informationstatus_resumen_inicio">
                    <!-- % general + badges renderizados dinámicamente desde getResumen -->
                  </div>
                </div>
                <div id="informationstatus_wrap_inicio" class="informationstatus_wrap_inicio">
                  <!-- Sparklines mensuales por categoría renderizados dinámicamente desde `tendencia` -->
                  <div class="empty_state_inicio">Sin datos</div>
                </div>
              </section>

              <!-- Panel Actual vs Target (objetivos por dealer) DESACTIVADO:
                   el objetivo ahora es objetivo=unidades (vehículos) y objetivovlr=USD (repuestos),
                   y este panel comparaba % de carga, que ya no tiene meta en %. Se oculta. -->

              <section id="panel_kpis_inicio" class="card_common panel_common">
                <div class="panel_head_common panel_head_kpis_inicio">
                  <h2>Critical KPI Status</h2>
                  <div class="kpi_search_inicio">
                    <i class="fa fa-search"></i>
                    <input id="buscador_kpi_inicio" class="input_common" type="text" placeholder="Buscar" />
                    <i class="fa fa-angle-down"></i>
                  </div>
                </div>
                <div class="table_wrap_inicio">
                  <table id="tabla_kpi_inicio">
                    <thead>
                      <tr>
                        <th>Dealer</th>
                        <th>Categoría</th>
                        <th>KPI</th>
                        <th>Estado</th>
                        <th>KPI Value</th>
                        <th>Edad</th>
                        <th class="th_count_inicio">Página 1 de 1</th>
                      </tr>
                    </thead>
                                        <tbody id="tabla_body_inicio">
                      <!-- Filas de KPIs críticos renderizadas dinámicamente -->
                    </tbody>
                  </table>
                  <div id="empty_tabla_kpi_inicio" class="empty_state_inicio">Sin datos</div>
                </div>
                <div class="kpi_table_footer_inicio">
                  <div class="kpi_footer_left_inicio" id="kpi_info_inicio"></div>
                  <div class="kpi_footer_right_inicio">
                    <button type="button" class="kpi_page_btn_inicio" id="kpi_btn_prev" aria-label="Anterior"><i class="fa fa-angle-left"></i></button>
                    <div id="kpi_paginas_inicio" class="kpi_paginas_inicio"></div>
                    <button type="button" class="kpi_page_btn_inicio" id="kpi_btn_next" aria-label="Siguiente"><i class="fa fa-angle-right"></i></button>
                  </div>
                </div>
              </section>
            </section>

            <footer class="content-footer hidden-print">
                <?php require_once "src/footer.php"; ?>
            </footer>
        </main>
    </div>
        <?php require_once "src/scripts.php"; ?>
</body>
</html>