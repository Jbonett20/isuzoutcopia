<?php
    $loc = "parts";
    include_once '../admin/src/seguridad.php';
    @session_start();

    /* ── Load filter data ───────────────────────────────────── */
    include_once "../models/Kpidata.php";
    include_once '../config/init_db.php';

    $dealers      = Kpidata::listarEmpresas();
    $meses        = Kpidata::getYearsMonth();
    $esDealerKpi  = ($rolActual === '6');
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

    /*
     * Field types:
     *   currency         → $ | number
     *   percent          → number | %
     *   currency_percent → $ | number | %
     *   plain            → number only
     */
    $kpiCards = [
        [
            'id'    => 'ventas_repuestos',
            'title' => 'Ventas de Repuestos',
            'fields' => [
                ['id' => 'counter',          'label' => 'Counter (USD)',                          'type' => 'currency'],
                ['id' => 'externos',         'label' => 'Externos',                               'type' => 'currency'],
                ['id' => 'taller',           'label' => 'Taller',                                 'type' => 'currency'],
                ['id' => 'flotas',           'label' => 'Flotas',                                 'type' => 'currency'],
                ['id' => 'insurance',        'label' => 'Insurance',                              'type' => 'currency_percent'],
                ['id' => 'ratio_first_fill', 'label' => 'Ratio First Fill (%)',                   'type' => 'percent'],
                ['id' => 'back_orders',      'label' => 'Back Orders desde Dealer a Customer (USD)', 'type' => 'plain'],
                ['id' => 'lineas_back',      'label' => 'Lineas Back Order (qty)',                'type' => 'currency'],
            ],
        ],
        [
            'id'    => 'info_inventario',
            'title' => 'Informaci&oacute;n de Inventario',
            'fields' => [
                ['id' => 'inventory_cost',      'label' => 'Inventory Cost (USD)',                    'type' => 'currency'],
                ['id' => 'partes_rapido',       'label' => 'Partes de r&aacute;pido movimiento &ndash; (USD)', 'type' => 'currency'],
                ['id' => 'partes_medio',        'label' => 'Partes de movimiento medio &ndash; (USD)', 'type' => 'currency'],
                ['id' => 'inv_cost_12',         'label' => 'Inventory cost (&lt; 12 meses) &ndash; (USD)', 'type' => 'currency'],
                ['id' => 'sin_ventas_12_24',    'label' => 'Sin ventas &gt; 12m &lt; 24m',           'type' => 'currency'],
                ['id' => 'sin_ventas_24_36',    'label' => 'Sin ventas &gt; 24m &lt; 36m',           'type' => 'currency'],
                ['id' => 'sin_ventas_36_48',    'label' => 'Sin ventas &gt; 36m &lt; 48m',           'type' => 'currency'],
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
    <link rel="stylesheet" href="dashboard/css/parts.css?v=<?php echo filemtime(__DIR__ . '/dashboard/css/parts.css'); ?>"/>
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

            <section class="view_block_parts">

                <!-- ── HEADER : title + filters ────────────────────── -->
                <article class="pts_header_card">
                    <div class="pts_heading_block">
                        <span class="pts_overline">ISUZU KPI Platform</span>
                        <h2>PARTES</h2>
                        <p class="pts_subtitle">ISUZU KPI Platform</p>
                    </div>

                    <div class="pts_filters_panel">
                        <!-- Dealer -->
                        <div class="pts_filter_item">
                            <label for="pts_filtro_dealer">Dealer:</label>
                            <select id="pts_filtro_dealer" class="select_common"
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
                        <div class="pts_filter_item">
                            <label for="pts_filtro_kpi">KPI:</label>
                            <select id="pts_filtro_kpi" class="select_common">
                                <option value="">todos</option>
                                <?php foreach ($kpiTypes as $val => $lbl): ?>
                                    <option value="<?php echo htmlspecialchars($val); ?>">
                                        <?php echo htmlspecialchars($lbl); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Mes -->
                        <div class="pts_filter_item">
                            <label for="pts_filtro_mes">Mes:</label>
                            <select id="pts_filtro_mes" class="select_common">
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
                        <button id="pts_btn_buscar" class="pts_search_btn" aria-label="Buscar">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </article>

                <!-- ── TOP ACTION BUTTONS ───────────────────────────── -->
                <div class="pts_actions_row">
                    <button id="pts_descargar_main" class="pts_btn_secondary">
                        <i class="fa fa-download"></i>
                        Descargar Plantilla Parts
                    </button>
                    <button id="pts_importar_main" class="pts_btn_primary">
                        <i class="fa fa-cloud-upload"></i>
                        Importar Archivo Parts
                    </button>
                </div>

                <!-- ── INGRESO CARD ──────────────────────────────────── -->
                <article class="pts_ingreso_card">

                    <!-- 2-col: controls | dropzone -->
                    <div class="pts_ingreso_inner">

                        <!-- Left -->
                        <div class="pts_ingreso_left">
                            <div class="pts_card_head">
                                <h2>Carga Masiva de Datos</h2>
                                <p>Importa tus ventas m&uacute;ltiples pde archivo Excel est&aacute;ndar</p>
                            </div>
                            <div class="pts_ingreso_btns">
                                <button id="pts_descargar_plantilla" class="pts_btn_sm_secondary">
                                    <i class="fa fa-download"></i>
                                    Descargar Plantilla Parts
                                </button>
                                <button id="pts_importar_archivo" class="pts_btn_sm_primary">
                                    <i class="fa fa-cloud-upload"></i>
                                    Importar Archivo Parts
                                </button>
                            </div>
                            <input id="pts_file_input" type="file" accept=".xlsx,.xls,.csv" hidden aria-hidden="true"/>
                        </div>

                        <!-- Right: dropzone -->
                        <div class="pts_ingreso_right">
                            <div id="pts_dropzone" class="pts_dropzone" role="button" tabindex="0"
                                 aria-label="Zona para soltar archivo Parts">
                                <div class="pts_dropzone_content">
                                    <div class="pts_dropzone_icon" aria-hidden="true">
                                        <i class="fa fa-cloud-upload"></i>
                                    </div>
                                    <h3>Importar Archivo Parts</h3>
                                    <p>Arrastra y suelta el archivo aqu&iacute; o haz clic para seleccionar&nbsp;&hellip;</p>
                                </div>
                            </div>
                        </div>

                    </div><!-- /pts_ingreso_inner -->

                    <!-- ── Full-width message ────────────────────────── -->
                    <p id="pts_mensaje" class="pts_message" role="alert"></p>

                    <!-- ── Summary row — 3 cols ──────────────────────── -->
                    <div class="pts_summary">
                        <div class="pts_summary_item pts_success">
                            <span class="pts_sum_icon"><i class="fa fa-check-circle"></i></span>
                            <strong id="pts_datos_importados">Datos Importados: 0 Registros</strong>
                        </div>
                        <div class="pts_summary_item pts_warning">
                            <span class="pts_sum_icon"><i class="fa fa-info-circle"></i></span>
                            <strong>Inconsistencias Detectadas: <span id="pts_inconsistencias">0</span></strong>
                        </div>
                        <div class="pts_summary_item pts_error">
                            <span class="pts_sum_icon"><i class="fa fa-exclamation-triangle"></i></span>
                            <strong>Campos Incompletos: <span id="pts_campos_incompletos">0</span></strong>
                        </div>
                    </div>

                    <!-- ── Histórico + Confirmar ─────────────────────── -->
                    <div class="pts_historico_row">
                        <button id="pts_historico_toggle" class="pts_historico_toggle"
                                aria-expanded="false" aria-controls="pts_historico_lista">
                            <i class="fa fa-chevron-down" style="font-size:0.75rem"></i>
                            Registros Historicos (Ver Log)
                        </button>
                        <button id="pts_confirmar_carga" class="pts_btn_confirm" disabled>
                            <i class="fa fa-print"></i>
                            Confirmar Carga
                        </button>
                    </div>
                    <div id="pts_historico_lista" class="pts_historico_list" hidden>
                        <p class="pts_historico_empty">No hay registros hist&oacute;ricos</p>
                    </div>

                </article><!-- /pts_ingreso_card -->

                <!-- ── KPI INPUT CARDS ───────────────────────────────── -->
                <div class="pts_kpi_grid">
                    <?php foreach ($kpiCards as $card): ?>
                    <article class="pts_kpi_card" id="card_<?php echo htmlspecialchars($card['id']); ?>">

                        <div class="pts_kpi_card_head">
                            <div class="pts_kpi_card_title">
                                <?php echo $card['title']; ?>
                                <i class="fa fa-pencil pts_edit_icon" aria-hidden="true" title="Editar t&iacute;tulo"></i>
                            </div>
                            <button class="pts_kpi_card_action"
                                    aria-label="Editar <?php echo strip_tags($card['title']); ?>"
                                    title="Editar datos">
                                <i class="fa fa-pencil"></i>
                            </button>
                        </div>

                        <div class="pts_kpi_card_body">
                            <?php foreach ($card['fields'] as $f):
                                $fid = htmlspecialchars($card['id'] . '_' . $f['id']);
                                $ftype = $f['type'];
                            ?>
                            <div class="pts_field_row">
                                <label class="pts_field_label" for="<?php echo $fid; ?>">
                                    <?php echo $f['label']; ?>
                                </label>

                                <?php if ($ftype === 'currency'): ?>
                                    <div class="pts_input_wrap">
                                        <span class="pts_input_prefix">$</span>
                                        <input type="number" id="<?php echo $fid; ?>" name="<?php echo $fid; ?>"
                                               class="pts_input_num" value="0" min="0" step="1"
                                               aria-label="<?php echo strip_tags($f['label']); ?>"/>
                                    </div>

                                <?php elseif ($ftype === 'currency_percent'): ?>
                                    <div class="pts_input_wrap">
                                        <span class="pts_input_prefix">$</span>
                                        <input type="number" id="<?php echo $fid; ?>" name="<?php echo $fid; ?>"
                                               class="pts_input_num" value="0" min="0" step="0.1"
                                               aria-label="<?php echo strip_tags($f['label']); ?>"/>
                                        <span class="pts_input_suffix">%</span>
                                    </div>

                                <?php elseif ($ftype === 'percent'): ?>
                                    <div class="pts_input_wrap no_prefix">
                                        <input type="number" id="<?php echo $fid; ?>" name="<?php echo $fid; ?>"
                                               class="pts_input_num" value="0" min="0" step="0.1"
                                               aria-label="<?php echo strip_tags($f['label']); ?>"/>
                                        <span class="pts_input_suffix">%</span>
                                    </div>

                                <?php else: /* plain */ ?>
                                    <div class="pts_input_wrap no_prefix">
                                        <input type="number" id="<?php echo $fid; ?>" name="<?php echo $fid; ?>"
                                               class="pts_input_num" value="0" min="0" step="1"
                                               aria-label="<?php echo strip_tags($f['label']); ?>"/>
                                    </div>
                                <?php endif; ?>

                            </div>
                            <?php endforeach; ?>
                        </div>

                    </article>
                    <?php endforeach; ?>
                </div><!-- /pts_kpi_grid -->

            </section>

        </main>
    </div>

    <?php require_once("src/scripts.php"); ?>

    <script>
    (function () {
        'use strict';

        /* ── Dropzone ───────────────────────────────────────── */
        var dropzone  = document.getElementById('pts_dropzone');
        var fileInput = document.getElementById('pts_file_input');
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
            var btn = document.getElementById('pts_confirmar_carga');
            if (btn) btn.disabled = false;
        }

        /* ── Small "Importar" button ────────────────────────── */
        var btnSmall = document.getElementById('pts_importar_archivo');
        if (btnSmall) btnSmall.addEventListener('click', triggerFile);

        /* ── Top "Importar Archivo Parts" button ────────────── */
        var btnMain = document.getElementById('pts_importar_main');
        if (btnMain) btnMain.addEventListener('click', triggerFile);

        /* ── Descargar buttons (placeholder) ─────────────────── */
        function warnDescarga() { alert('Descarga de plantilla Parts próximamente disponible.'); }
        ['pts_descargar_main', 'pts_descargar_plantilla'].forEach(function (id) {
            var b = document.getElementById(id);
            if (b) b.addEventListener('click', warnDescarga);
        });

        /* ── Message helper ──────────────────────────────────── */
        var msg = document.getElementById('pts_mensaje');
        function showMsg(text, type) {
            if (!msg) return;
            msg.textContent = text;
            msg.className = 'pts_message ' + type;
        }

        /* ── Confirmar Carga ─────────────────────────────────── */
        var btnConfirmar = document.getElementById('pts_confirmar_carga');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function () {
                if (!selectedFile) { showMsg('Selecciona un archivo primero.', 'is_error'); return; }
                showMsg('Carga confirmada: ' + selectedFile.name, 'is_success');
                /* TODO: AJAX submit */
            });
        }

        /* ── Históricos toggle ───────────────────────────────── */
        var togBtn  = document.getElementById('pts_historico_toggle');
        var togList = document.getElementById('pts_historico_lista');
        if (togBtn && togList) {
            togBtn.addEventListener('click', function () {
                var exp = togBtn.getAttribute('aria-expanded') === 'true';
                togBtn.setAttribute('aria-expanded', String(!exp));
                togList.hidden = exp;
            });
        }

        /* ── Search filter ───────────────────────────────────── */
        var btnBuscar = document.getElementById('pts_btn_buscar');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', function () {
                var dealer = document.getElementById('pts_filtro_dealer').value;
                var kpi    = document.getElementById('pts_filtro_kpi').value;
                var mes    = document.getElementById('pts_filtro_mes').value;
                /* TODO: AJAX fetch */
                console.log('Filtros → dealer:', dealer, '| kpi:', kpi, '| mes:', mes);
            });
        }

    })();
    </script>

</body>
</html>
