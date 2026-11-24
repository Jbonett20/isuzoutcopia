<?php
    $loc = "saleofservices";
    include_once '../admin/src/seguridad.php';
    @session_start();

    /* ── Load filter data ─────────────────────────────────────── */
    include_once "../models/Kpidata.php";
    include_once '../config/init_db.php';

    $dealers       = Kpidata::listarEmpresas();
    $meses         = Kpidata::getYearsMonth();
    $esDealerKpi   = ($rolActual === '6');
    $dealerSesion  = (int) ($_SESSION['UsrData']['dealerid'] ?? 0);

    $kpiTypes = [
        'workshop'  => 'Workshop',
        'service'   => 'Service',
        'vehicles'  => 'Vehicles Serviced',
        'labor'     => 'Labor Rates',
        'personnel' => 'Personnel',
        'parts'     => 'Parts',
    ];

    /* ── KPI card definitions ─────────────────────────────────── */
    $kpiCards = [
        [
            'id'     => 'ventas_taller',
            'title'  => 'Ventas de Taller',
            'fields' => [
                ['id' => 'ventas_cv_labor',    'label' => 'Ventas CV Taller Labor (USD)'],
                ['id' => 'ventas_lcv_labor',   'label' => 'Ventas LCV Taller Labor (USD)'],
                ['id' => 'ventas_total_labor', 'label' => 'Ventas Totales Taller Labor (USD)'],
            ],
        ],
        [
            'id'     => 'garantia_taller',
            'title'  => 'Trabajos en Garant&iacute;a Taller',
            'fields' => [
                ['id' => 'labor_garantia_cv',    'label' => 'Labor en Garant&iacute;a CV Taller (USD)'],
                ['id' => 'labor_garantia_lcv',   'label' => 'Labor en Garant&iacute;a LCV Taller (USD)'],
                ['id' => 'labor_garantia_total', 'label' => 'Labor Totales en Garant&iacute;a Taller (USD)'],
            ],
        ],
        [
            'id'     => 'ventas_taller2',
            'title'  => 'Ventas &middot; de Taller',
            'fields' => [
                ['id' => 'ventas2_cv_labor',  'label' => 'Ventas CV Taller Labor (USD)'],
                ['id' => 'ventas2_lcv_labor', 'label' => 'Ventas LCV Taller Labor (USD)'],
            ],
        ],
        [
            'id'     => 'partes_taller',
            'title'  => 'Partes del Taller',
            'fields' => [
                ['id' => 'partes_cv',    'label' => 'Venta Partes CV Taller (USD)'],
                ['id' => 'partes_total', 'label' => 'Ventas Totales Partes Taller (USD)'],
            ],
        ],
    ];

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>"/>
    <link rel="stylesheet" href="dashboard/css/saleOfServices.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/saleOfServices.css'); ?>"/>
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

            <section class="view_block_sos">

                <!-- ── HEADER : title + filters ─────────────────────── -->
                <article class="sos_header_card">
                    <div class="sos_heading_block">
                        <span class="sos_overline">ISUZU KPI Platform</span>
                        <h2>VENTAS <strong>DE SERVICIO</strong></h2>
                        <p class="sos_subtitle">ISUZU KPI Platform</p>
                    </div>

                    <div class="sos_filters_panel">
                        <!-- Dealer -->
                        <div class="sos_filter_item">
                            <label for="sos_filtro_dealer">Dealer:</label>
                            <select id="sos_filtro_dealer"
                                    class="select_common"
                                    <?php echo $esDealerKpi ? 'disabled' : ''; ?>>
                                <?php if (!$esDealerKpi): ?>
                                    <option value="">todos</option>
                                    <?php foreach ($dealers as $d): ?>
                                        <option value="<?php echo htmlspecialchars((string)$d['dealerid']); ?>">
                                            <?php echo htmlspecialchars((string)$d['dealer']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($dealers as $d): ?>
                                        <?php if ((int)$d['dealerid'] === $dealerSesion): ?>
                                            <option value="<?php echo htmlspecialchars((string)$d['dealerid']); ?>" selected>
                                                <?php echo htmlspecialchars(strtoupper((string)$d['dealer'])); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- KPI -->
                        <div class="sos_filter_item">
                            <label for="sos_filtro_kpi">KPI:</label>
                            <select id="sos_filtro_kpi" class="select_common">
                                <option value="">todos</option>
                                <?php foreach ($kpiTypes as $val => $label): ?>
                                    <option value="<?php echo htmlspecialchars($val); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Mes -->
                        <div class="sos_filter_item">
                            <label for="sos_filtro_mes">Mes:</label>
                            <select id="sos_filtro_mes" class="select_common">
                                <option value="">todos</option>
                                <?php foreach ($meses as $m): ?>
                                    <option value="<?php echo htmlspecialchars((string)$m['calendarioid']); ?>"
                                            <?php echo !empty($m['is_current']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string)$m['yearmonth']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Search -->
                        <button id="sos_btn_buscar" class="sos_search_btn" aria-label="Buscar">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </article>

                <!-- ── ACTION BUTTONS ROW ────────────────────────────── -->
                <div class="sos_actions_row">
                    <button id="sos_descargar_main" class="sos_btn_secondary">
                        <i class="fa fa-download"></i>
                        Descargar Plantilla Servicio
                    </button>
                    <button id="sos_importar_main" class="sos_btn_primary">
                        <i class="fa fa-cloud-upload"></i>
                        Importar masivamente
                    </button>
                </div>

                <!-- ── INGRESO CARD (2 col) ───────────────────────────── -->
                <article class="sos_ingreso_card">
                    <div class="sos_ingreso_inner">

                        <!-- Left: controls -->
                        <div class="sos_ingreso_left">
                            <div class="sos_card_head">
                                <h2>Carga Masiva de Datos</h2>
                                <p>Importa tus ventas de veh&iacute;culos mediante un archivo Excel est&aacute;ndar</p>
                            </div>

                            <div class="sos_ingreso_btns">
                                <button id="sos_descargar_plantilla" class="sos_btn_sm_secondary">
                                    <i class="fa fa-download"></i>
                                    Descargar Plantilla Servicio
                                </button>
                                <button id="sos_importar_archivo" class="sos_btn_sm_primary">
                                    <i class="fa fa-cloud-upload"></i>
                                    Importar Archivo Servicio
                                </button>
                            </div>

                            <!-- Hidden file input -->
                            <input id="sos_file_input" type="file" accept=".xlsx,.xls,.csv" hidden aria-hidden="true"/>
                        </div>

                        <!-- Right: dropzone -->
                        <div class="sos_ingreso_right">
                            <div id="sos_dropzone" class="sos_dropzone" role="button" tabindex="0"
                                 aria-label="Zona para soltar archivo de servicio">
                                <div class="sos_dropzone_content">
                                    <div class="sos_dropzone_icon" aria-hidden="true">
                                        <i class="fa fa-cloud-upload"></i>
                                    </div>
                                    <h3>Importar Archivo Servicio</h3>
                                    <p>Arrastra y suelta &eacute;l archivo aqu&iacute; o haz clic para seleccionar&hellip;</p>
                                </div>
                            </div>
                        </div>

                    </div><!-- /sos_ingreso_inner -->

                    <!-- ── Message ────────────────────────────── -->
                    <p id="sos_mensaje" class="sos_message" role="alert"></p>

                    <!-- ── Summary row (full width) ───────────── -->
                    <div class="sos_summary sos_summary_fullrow">
                        <div class="sos_summary_item sos_success">
                            <span class="sos_sum_icon"><i class="fa fa-check"></i></span>
                            <strong id="sos_datos_importados">Datos Importados: 0 Registros</strong>
                        </div>
                        <div class="sos_summary_item sos_error">
                            <span class="sos_sum_icon"><i class="fa fa-exclamation-triangle"></i></span>
                            <strong>Errores Detectados: <span id="sos_errores_count">0</span></strong>
                        </div>
                    </div>

                    <!-- ── Histórico + Confirmar row (full width) -->
                    <div class="sos_historico_top sos_historico_fullrow">
                        <button id="sos_historico_toggle" class="sos_historico_toggle"
                                aria-expanded="false" aria-controls="sos_historico_lista">
                            <i class="fa fa-chevron-down" style="font-size:0.75rem"></i>
                            Registros Hist&oacute;ricos (Ver Log)
                        </button>
                        <button id="sos_confirmar_carga" class="sos_btn_confirm" disabled>
                            <i class="fa fa-print"></i>
                            Confirmar Carga
                        </button>
                    </div>
                    <div id="sos_historico_lista" class="sos_historico_list" hidden>
                        <p class="sos_historico_empty">No hay registros hist&oacute;ricos</p>
                    </div>

                </article>

                <!-- ── KPI INPUT CARDS ────────────────────────────────── -->
                <div class="sos_kpi_grid">
                    <?php foreach ($kpiCards as $card): ?>
                    <article class="sos_kpi_card" id="card_<?php echo htmlspecialchars($card['id']); ?>">
                        <div class="sos_kpi_card_head">
                            <div class="sos_kpi_card_title">
                                <?php echo $card['title']; ?>
                                <i class="fa fa-pencil sos_edit_icon" aria-hidden="true"
                                   title="Editar t&iacute;tulo"></i>
                            </div>
                            <button class="sos_kpi_card_action"
                                    aria-label="Editar campos de <?php echo strip_tags($card['title']); ?>"
                                    title="Editar datos">
                                <i class="fa fa-pencil"></i>
                            </button>
                        </div>
                        <div class="sos_kpi_card_body">
                            <?php foreach ($card['fields'] as $field): ?>
                            <div class="sos_field_row">
                                <label class="sos_field_label"
                                       for="<?php echo htmlspecialchars($card['id'] . '_' . $field['id']); ?>">
                                    <?php echo $field['label']; ?>
                                </label>
                                <div class="sos_input_wrap">
                                    <span class="sos_input_prefix">$</span>
                                    <input type="number"
                                           id="<?php echo htmlspecialchars($card['id'] . '_' . $field['id']); ?>"
                                           name="<?php echo htmlspecialchars($card['id'] . '_' . $field['id']); ?>"
                                           class="sos_input_num"
                                           value="0"
                                           min="0"
                                           step="1"
                                           aria-label="<?php echo strip_tags($field['label']); ?>"/>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div><!-- /sos_kpi_grid -->

            </section>

        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>

    <script>
    /* ── Sale of Services – interactive behaviour ───────────── */
    (function () {
        'use strict';

        /* ── Dropzone / file selection ─────────────────────── */
        var dropzone  = document.getElementById('sos_dropzone');
        var fileInput = document.getElementById('sos_file_input');
        var selectedFile = null;

        function triggerFile() { if (fileInput) fileInput.click(); }

        if (dropzone && fileInput) {
            dropzone.addEventListener('click', triggerFile);
            dropzone.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); triggerFile(); }
            });
            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropzone.classList.add('is_dragover');
            });
            dropzone.addEventListener('dragleave', function () {
                dropzone.classList.remove('is_dragover');
            });
            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropzone.classList.remove('is_dragover');
                var files = e.dataTransfer.files;
                if (files && files.length > 0) handleFile(files[0]);
            });

            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files.length > 0) handleFile(fileInput.files[0]);
            });
        }

        function handleFile(file) {
            selectedFile = file;
            var p = dropzone ? dropzone.querySelector('p') : null;
            if (p) p.textContent = 'Archivo seleccionado: ' + file.name;
            showMsg('Archivo listo para importar: ' + file.name, 'is_info');
            var btn = document.getElementById('sos_confirmar_carga');
            if (btn) btn.disabled = false;
        }

        /* ── "Importar Archivo Servicio" button (small) ─────── */
        var btnImportarSm = document.getElementById('sos_importar_archivo');
        if (btnImportarSm) btnImportarSm.addEventListener('click', triggerFile);

        /* ── "Importar masivamente" top button ──────────────── */
        var btnImportarMain = document.getElementById('sos_importar_main');
        if (btnImportarMain) btnImportarMain.addEventListener('click', triggerFile);

        /* ── Descargar buttons (placeholder) ────────────────── */
        function warnDescarga() { alert('Descarga de plantilla de servicio próximamente disponible.'); }
        var btnDescMainTop = document.getElementById('sos_descargar_main');
        var btnDescSmall   = document.getElementById('sos_descargar_plantilla');
        if (btnDescMainTop) btnDescMainTop.addEventListener('click', warnDescarga);
        if (btnDescSmall)   btnDescSmall.addEventListener('click', warnDescarga);

        /* ── Message helper ─────────────────────────────────── */
        var msg = document.getElementById('sos_mensaje');
        function showMsg(text, type) {
            if (!msg) return;
            msg.textContent = text;
            msg.className = 'sos_message ' + type;
        }

        /* ── Confirmar Carga ────────────────────────────────── */
        var btnConfirmar = document.getElementById('sos_confirmar_carga');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function () {
                if (!selectedFile) { showMsg('Selecciona un archivo primero.', 'is_error'); return; }
                showMsg('Carga confirmada: ' + selectedFile.name, 'is_success');
                /* TODO: submit form / AJAX to server */
            });
        }

        /* ── Registros Históricos toggle ─────────────────────── */
        var togBtn  = document.getElementById('sos_historico_toggle');
        var togList = document.getElementById('sos_historico_lista');
        if (togBtn && togList) {
            togBtn.addEventListener('click', function () {
                var exp = togBtn.getAttribute('aria-expanded') === 'true';
                togBtn.setAttribute('aria-expanded', String(!exp));
                togList.hidden = exp;
            });
        }

        /* ── Buscar filter button ────────────────────────────── */
        var btnBuscar = document.getElementById('sos_btn_buscar');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', function () {
                var dealer = document.getElementById('sos_filtro_dealer').value;
                var kpi    = document.getElementById('sos_filtro_kpi').value;
                var mes    = document.getElementById('sos_filtro_mes').value;
                /* TODO: AJAX fetch / filter KPI cards */
                console.log('Filtros → dealer:', dealer, '| kpi:', kpi, '| mes:', mes);
            });
        }

    })();
    </script>

</body>
</html>
