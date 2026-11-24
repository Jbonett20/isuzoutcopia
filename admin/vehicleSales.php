<?php
    $loc = "vehiclesales";
    include_once '../admin/src/seguridad.php';
    @session_start();

    /* ── Load filter data for dropdowns ──────────────────────── */
    include_once "../models/Kpidata.php";
    include_once '../config/init_db.php';

    $dealers   = Kpidata::listarEmpresas();
    $meses     = Kpidata::getYearsMonth();

    $esDealerKpi = ($rolActual === '6');
    $dealerSesion = (int) ($_SESSION['UsrData']['dealerid'] ?? 0);

    $kpiTypes = [
        'sales'     => 'Sales',
        'workshop'  => 'Workshop',
        'service'   => 'Service',
        'vehicles'  => 'Vehicles Serviced',
        'labor'     => 'Labor Rates',
        'personnel' => 'Personnel',
        'parts'     => 'Parts',
    ];

    include_once "../controllers/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("src/header.php"); ?>
    <link rel="stylesheet" href="dashboard/css/global.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/global.css'); ?>"/>
    <link rel="stylesheet" href="dashboard/css/vehicleSales.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/vehicleSales.css'); ?>"/>
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

            <section class="view_block_vs">

                <!-- ── HEADER : title + filters ─────────────────────── -->
                <article class="vs_header_card">
                    <div class="vs_heading_block">
                        <span class="vs_overline">ISUZU KPI Platform</span>
                        <h2>VENTAS <strong>DE VEH&Iacute;CULOS</strong></h2>
                        <p class="vs_subtitle">ISUZU KPI Platform</p>
                    </div>

                    <div class="vs_filters_panel">
                        <!-- Dealer -->
                        <div class="vs_filter_item">
                            <label for="vs_filtro_dealer">Dealer:</label>
                            <select id="vs_filtro_dealer"
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
                        <div class="vs_filter_item">
                            <label for="vs_filtro_kpi">KPI:</label>
                            <select id="vs_filtro_kpi" class="select_common">
                                <option value="">todos</option>
                                <?php foreach ($kpiTypes as $val => $label): ?>
                                    <option value="<?php echo htmlspecialchars($val); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Mes -->
                        <div class="vs_filter_item">
                            <label for="vs_filtro_mes">Mes:</label>
                            <select id="vs_filtro_mes" class="select_common">
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
                        <button id="vs_btn_buscar" class="vs_search_btn" aria-label="Buscar">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </article>

                <!-- ── ACTION BUTTONS ────────────────────────────────── -->
                <div class="vs_actions_row">
                    <button id="vs_descargar_plantilla" class="vs_btn_secondary">
                        <span class="vs_icon"><i class="fa fa-download"></i></span>
                        Descargar Plantilla Excel
                    </button>
                    <button id="vs_importar_masivo" class="vs_btn_primary">
                        <span class="vs_icon"><i class="fa fa-cloud-upload"></i></span>
                        Importar masivamente
                    </button>
                </div>

                <!-- ── INGRESO CARD ───────────────────────────────────── -->
                <article id="vs_panel_ingreso" class="vs_ingreso_card">

                    <div class="vs_card_head">
                        <h2>Carga Masiva de Datos</h2>
                        <p>Importa tus ventas de veh&iacute;culos mediante un archivo Excel est&aacute;ndar</p>
                    </div>

                    <!-- Drag & Drop -->
                    <div id="vs_dropzone" class="vs_dropzone" role="button" tabindex="0"
                         aria-label="Zona para soltar archivo Excel">
                        <div class="vs_dropzone_content">
                            <div class="vs_dropzone_icon" aria-hidden="true">
                                <i class="fa fa-cloud-upload"></i>
                            </div>
                            <h3>Importar Archivo Excel</h3>
                            <p>Arrastra y suelta el archivo aqu&iacute; o haz clic para seleccionar&hellip;</p>
                        </div>
                        <input id="vs_file_input" type="file" accept=".xlsx,.xls,.csv" hidden aria-hidden="true"/>
                    </div>

                    <!-- Validate button -->
                    <div class="vs_validate_section">
                        <button id="vs_validar_datos" class="vs_btn_validate">
                            Validar Datos
                        </button>
                    </div>

                    <!-- Info note -->
                    <p class="vs_info_text">
                        Por favor descarga la plantilla y sigue las instrucciones antes de importar para asegurar una carga correcta.
                    </p>

                    <!-- Summary -->
                    <div class="vs_summary">
                        <div class="vs_summary_item vs_success">
                            <span class="vs_sum_icon" aria-hidden="true">
                                <i class="fa fa-check"></i>
                            </span>
                            <strong id="vs_datos_importados">Datos Importados: 0 Registros</strong>
                        </div>
                        <div class="vs_summary_item vs_error">
                            <span class="vs_sum_icon" aria-hidden="true">
                                <i class="fa fa-exclamation-triangle"></i>
                            </span>
                            <strong id="vs_errores_detectados">Errores Detectados: <span id="vs_errores_count">0</span></strong>
                        </div>
                    </div>

                    <!-- Message area -->
                    <p id="vs_mensaje" class="vs_message" role="alert"></p>

                    <!-- Historical + Confirm -->
                    <div class="vs_historico">
                        <div class="vs_historico_top">
                            <button id="vs_historico_toggle" class="vs_historico_toggle"
                                    aria-expanded="false" aria-controls="vs_historico_lista">
                                <i class="fa fa-clock-o"></i>
                                Registros Hist&oacute;ricos (Ver Log)
                            </button>
                            <button id="vs_confirmar_carga" class="vs_btn_confirm" disabled>
                                <i class="fa fa-check-square-o"></i>
                                Confirmar Carga
                            </button>
                        </div>
                        <div id="vs_historico_lista" class="vs_historico_list" hidden>
                            <p class="vs_historico_empty">No hay registros hist&oacute;ricos</p>
                        </div>
                    </div>

                </article><!-- /vs_panel_ingreso -->

            </section>

        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>

    <script>
    /* ── Vehicle Sales – interactive behaviour ──────────────── */
    (function () {
        'use strict';

        /* ── Dropzone ─────────────────────────────────────────── */
        var dropzone  = document.getElementById('vs_dropzone');
        var fileInput = document.getElementById('vs_file_input');
        var selectedFile = null;

        if (dropzone && fileInput) {
            dropzone.addEventListener('click', function () { fileInput.click(); });

            dropzone.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
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
                if (files && files.length > 0) { handleFile(files[0]); }
            });

            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files.length > 0) {
                    handleFile(fileInput.files[0]);
                }
            });
        }

        function handleFile(file) {
            selectedFile = file;
            var p = dropzone.querySelector('p');
            if (p) { p.textContent = 'Archivo seleccionado: ' + file.name; }
        }

        /* ── Validar Datos ────────────────────────────────────── */
        var btnValidar    = document.getElementById('vs_validar_datos');
        var lblImportados = document.getElementById('vs_datos_importados');
        var lblErrores    = document.getElementById('vs_errores_count');
        var msg           = document.getElementById('vs_mensaje');
        var btnConfirmar  = document.getElementById('vs_confirmar_carga');

        if (btnValidar) {
            btnValidar.addEventListener('click', function () {
                if (!selectedFile) {
                    showMsg('Por favor selecciona un archivo antes de validar.', 'is_error');
                    return;
                }
                showMsg('Validando archivo: ' + selectedFile.name + '…', 'is_info');
                /* TODO: implement real AJAX validation upload */
            });
        }

        function showMsg(text, type) {
            if (!msg) return;
            msg.textContent = text;
            msg.className = 'vs_message ' + type;
        }

        /* ── Registros Históricos toggle ─────────────────────── */
        var togBtn  = document.getElementById('vs_historico_toggle');
        var togList = document.getElementById('vs_historico_lista');

        if (togBtn && togList) {
            togBtn.addEventListener('click', function () {
                var expanded = togBtn.getAttribute('aria-expanded') === 'true';
                togBtn.setAttribute('aria-expanded', String(!expanded));
                togList.hidden = expanded;
            });
        }

        /* ── Descargar Plantilla (placeholder) ───────────────── */
        var btnDescargar = document.getElementById('vs_descargar_plantilla');
        if (btnDescargar) {
            btnDescargar.addEventListener('click', function () {
                /* TODO: point to actual template file */
                alert('Descarga de plantilla próximamente disponible.');
            });
        }

        /* ── Importar Masivo (trigger file selector) ─────────── */
        var btnImportar = document.getElementById('vs_importar_masivo');
        if (btnImportar && fileInput) {
            btnImportar.addEventListener('click', function () { fileInput.click(); });
        }

    })();
    </script>

</body>
</html>
