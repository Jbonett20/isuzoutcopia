/*
|--------------------------------------------------------------------------
| DASHBOARD DE UNIDADES EN OPERACIÓN - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de unidades en operación (admin/unitsoperation.php).
|
| Endpoint: controllers/unitsoperation.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables en este tablero (Period y Comparison NO
       aplican: el UIO es un cálculo de parque y su crecimiento ya es métrica). */
    GlobalFilters.setAppliesTo({
        dealer:      true,
        country:     true,
        vehicleType: true,
        period:      false,
        year:        true,
        comparison:  false
    });

    /* ------------------------- estado ------------------------- */
    var filtrosDisponibles   = null;
    var datosActuales        = null;
    var debounceTimer        = null;
    var fetchSeq             = 0;
    var sincronizandoFiltros = false;
    var select2Inicializado  = false;
    var serieActual          = [];
    var serieUIOActual       = [];

    /* ------------------------- helpers ------------------------- */
    function fmt(n) {
        if (n === null || n === undefined) return '—';
        return Number(n).toLocaleString('en-US');
    }

    function fmtShort(n) {
        if (n === null || n === undefined) return '—';
        if (n >= 1000) {
            var k = n / 1000;
            return (Math.round(k * 10) / 10) + 'k';
        }
        return String(n);
    }

    function pctConSigno(n) {
        if (n === null || n === undefined) return '—';
        return (n >= 0 ? '+' : '') + Number(n).toFixed(1) + '%';
    }

    function pct(n) {
        if (n === null || n === undefined) return '—';
        return Number(n).toFixed(1) + '%';
    }

    /* Pasos "bonitos" para el máximo del eje (la línea llena casi todo el alto) */
    var NICE_STEPS = [1, 2, 2.5, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000, 2000, 2500, 5000, 10000, 20000, 25000, 50000, 100000, 200000, 250000, 500000];

    function maxRedondo(v) {
        if (v <= 0) return 1;
        for (var i = 0; i < NICE_STEPS.length; i++) {
            var s = NICE_STEPS[i];
            var m = s * Math.ceil(v / s);
            if (m >= v && m <= v * 1.25) {
                return m;
            }
        }
        return Math.ceil(v / 1000) * 1000;
    }

    /* ------------------------------------------------------------------ *
     *  Tarjetas KPI (UIO Total, Crecimiento 3/5 años, Mix LCV)
     * ------------------------------------------------------------------ */
    function renderTarjetas(datos) {
        var t = datos.totales || {};

        // UIO Total
        $('#uo_kpi_total').text(fmt(t.uio_total));
        $('#uo_kpi_total_sub').text(
            'Horizonte ' + (datos.horizonte || 5) + ' años · ' +
            (datos.mes_referencia && datos.mes_referencia.mes ? datos.mes_referencia.mes + ' ' + datos.anio : datos.anio)
        );

        // Crecimiento 3 años
        var c3 = t.crecimiento_3yr;
        var $c3 = $('#uo_kpi_crec3');
        $c3.text(pctConSigno(c3)).removeClass('green_unitsoperation blue_unitsoperation uo_flat')
            .addClass(c3 === null || c3 === undefined ? 'uo_flat' : (c3 >= 0 ? 'green_unitsoperation' : 'uo_neg'));
        $('#uo_kpi_crec3_strong').text(pctConSigno(c3));

        // Crecimiento 5 años
        var c5 = t.crecimiento_5yr;
        var $c5 = $('#uo_kpi_crec5');
        $c5.text(pctConSigno(c5)).removeClass('green_unitsoperation blue_unitsoperation uo_flat')
            .addClass(c5 === null || c5 === undefined ? 'uo_flat' : (c5 >= 0 ? 'blue_unitsoperation' : 'uo_neg'));
        $('#uo_kpi_crec5_strong').text(pctConSigno(c5));

        // Mix LCV
        var mix = t.mix_lcv;
        var $mix = $('#uo_kpi_mix');
        $mix.text(pct(mix)).removeClass('green_unitsoperation blue_unitsoperation uo_flat')
            .addClass(mix === null || mix === undefined ? 'uo_flat' : 'green_unitsoperation');
        $('#uo_kpi_mix_strong').text(pct(mix));
    }

    /* ------------------------------------------------------------------ *
     *  Mix LCV/CV
     * ------------------------------------------------------------------ */
    function renderMix(datos) {
        var t = datos.totales || {};
        var lcv = t.mix_lcv !== null && t.mix_lcv !== undefined ? t.mix_lcv : 0;
        var cv = t.mix_cv !== null && t.mix_cv !== undefined ? t.mix_cv : 0;
        $('#uo_mix_lcv').css('width', lcv + '%').text(pct(lcv));
        $('#uo_mix_cv').css('width', cv + '%').text(pct(cv));
    }

    /* ------------------------------------------------------------------ *
     *  Gráfico de tendencia: barras mensuales + línea UIO acumulado
     * ------------------------------------------------------------------ */
    function renderGrafico(datos) {
        var $bars = $('#uo_bars');
        var $labels = $('#uo_x_labels');
        var $yLeft = $('#uo_y_left');
        var serie = datos.serie || [];
        serieActual = serie;

        $bars.empty();
        $labels.empty();
        $yLeft.empty();

        if (!serie.length) {
            $bars.html('<div class="uo_empty">No data loaded</div>');
            return;
        }

        // Escala izquierda (ventas mensuales)
        var maxVentas = 0;
        serie.forEach(function (p) {
            maxVentas = Math.max(maxVentas, p.ventas);
        });
        var maxY = maxRedondo(maxVentas);

        function hPct(v) { return Math.max(1.5, (v / maxY) * 100); }
        function segPct(v) { return Math.max(0, (v / maxY) * 100); }

        // Ticks del eje izquierdo (números completos)
        var pasos = 6;
        var leftHtml = '';
        for (var i = pasos - 1; i >= 0; i--) {
            leftHtml += '<span>' + fmt(Math.round((maxY * i) / (pasos - 1))) + '</span>';
        }
        $yLeft.html(leftHtml);

        // Barras apiladas (CV oscuro abajo + LCV claro arriba) + etiquetas
        var esAll = datos.tipo_vehiculo === 'all';
        serie.forEach(function (p, idx) {
            var wrap = $('<div>', {
                class: 'uo_bar_wrap',
                'data-calendarioid': p.calendarioid,
                title: p.periodo
            });
            if (esAll) {
                wrap.append(
                    $('<div>', { class: 'uo_bar_seg uo_bar_cv', css: { height: segPct(p.ventas_cv) + '%' } }),
                    $('<div>', { class: 'uo_bar_seg uo_bar_lcv', css: { height: segPct(p.ventas_lcv) + '%' } })
                );
            } else {
                wrap.append($('<div>', { class: 'uo_bar_seg uo_bar_alone', css: { height: hPct(p.ventas) + '%' } }));
            }
            $bars.append(wrap);

            var lbl = p.mes ? p.mes.charAt(0) + p.mes.slice(1, 3).toLowerCase() : '';
            if (idx % 6 === 0 || idx === serie.length - 1) {
                $labels.append('<span>' + lbl + '</span>');
            } else {
                $labels.append('<span></span>');
            }
        });
    }

    /* ------------------------------------------------------------------ *
     *  Ranking por dealer (ordenado por UIO)
     * ------------------------------------------------------------------ */
    function renderRanking(datos) {
        var $rank = $('#uo_ranking');
        var filas = datos.ranking || [];
        $rank.empty();

        if (!filas.length) {
            $rank.html('<div class="uo_empty">No data loaded</div>');
            return;
        }
        filas.forEach(function (r, i) {
            var $row = $('<div>', { class: 'table_row_unitsoperation uo_rank_row', 'data-dealerid': r.dealerid, title: 'Filtrar por ' + r.dealer });
            $row.append('<span><span class="uo_rank_badge">' + (i + 1) + '</span> ' + $('<span>').text(r.dealer).html() + '</span>');
            $row.append('<strong>' + fmt(r.uio) + '</strong>');
            $rank.append($row);
        });
    }

    /* ------------------------------------------------------------------ *
     *  Barra de filtros (opciones desde getFiltros)
     * ------------------------------------------------------------------ */
    function anioEfectivo() {
        var estado = GlobalFilters.get();
        if (estado.year) return estado.year;
        var anios = (filtrosDisponibles && filtrosDisponibles.anios) || [];
        for (var i = 0; i < anios.length; i++) {
            if (anios[i].is_current) return anios[i].ano;
        }
        return anios.length ? anios[0].ano : null;
    }

    function poblarMeses(anio, calendarId) {
        var $selMes = $('#uo_filter_mes');
        var meses = [];
        $.each(filtrosDisponibles.anios || [], function (i, a) {
            if (String(a.ano) === String(anio)) {
                meses = a.meses || [];
                return false;
            }
        });
        $selMes.empty();
        $.each(meses, function (i, m) {
            var label = m.mes ? m.mes.charAt(0) + m.mes.slice(1).toLowerCase() : 'Mes ' + m.mesnum;
            $selMes.append($('<option>', { value: m.calendarioid }).text(label));
        });
        var valor = calendarId ? String(calendarId) : null;
        var pertenece = false;
        $.each(meses, function (i, m) {
            if (String(m.calendarioid) === valor) { pertenece = true; return false; }
        });
        $selMes.val(pertenece ? valor : null);
    }

    function renderFiltros() {
        if (!filtrosDisponibles) return;

        var $selDealer = $('#uo_filter_dealer');
        var $selPais   = $('#uo_filter_country');
        var $selAnio   = $('#uo_filter_anio');

        $selDealer.empty();
        $.each(filtrosDisponibles.dealers, function (i, d) {
            $selDealer.append($('<option>', { value: d.dealerid }).text(d.dealer));
        });
        $selPais.empty();
        $.each(filtrosDisponibles.paises, function (i, p) {
            $selPais.append($('<option>', { value: p.paisid }).text(p.pais));
        });
        $selAnio.empty();
        $.each(filtrosDisponibles.anios, function (i, a) {
            $selAnio.append($('<option>', { value: a.ano }).text(String(a.ano)));
        });

        if (!select2Inicializado && $.fn.select2) {
            $('#uo_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: 'resolve' });
            $('#uo_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: 'resolve' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#uo_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#uo_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#uo_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#uo_filter_tipo').val(estado.vehicleType || 'all');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/unitsoperation.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('UIO filtros: ' + res.message); return; }
                filtrosDisponibles = res.data;
                renderFiltros();
            },
            error: function (xhr) { console.error('UIO filtros error:', xhr.responseText); }
        });
    }

    /* ------------------------------------------------------------------ *
     *  Carga de datos (un solo fetch por cambio)
     * ------------------------------------------------------------------ */
    function programarCarga() {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(cargarDatos, 250);
    }

    function cargarDatos() {
        var f = GlobalFilters.get();
        var seq = ++fetchSeq;
        $.ajax({
            url: '../controllers/unitsoperation.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action:     'getResumen',
                year:       f.year       || '',
                calendarId: f.calendarId || '',
                dealerIds:  f.dealerIds  || [],
                countryIds: f.countryIds || [],
                vehicleType: f.vehicleType || 'all'
            },
            success: function (res) {
                if (seq !== fetchSeq) return;
                if (!res.success) { console.error('UIO: ' + res.message); return; }
                datosActuales = res.data;
                renderTarjetas(datosActuales);
                renderMix(datosActuales);
                renderGrafico(datosActuales);
                renderRanking(datosActuales);
            },
            error: function (xhr) { console.error('UIO error:', xhr.responseText); }
        });
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#uo_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#uo_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#uo_filter_tipo').on('change', function () {
        GlobalFilters.set({ vehicleType: $(this).val() || 'all' });
    });

    $('#uo_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#uo_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#uo_btn_reset').on('click', function () {
        GlobalFilters.reset();
    });

    // Clic en una fila del ranking -> dealerIds (toggle)
    $('#uo_ranking').on('click', '.uo_rank_row', function () {
        var did = Number($(this).data('dealerid'));
        if (!did) return;
        var actual = GlobalFilters.getParam('dealerIds') || [];
        var next = actual.indexOf(did) >= 0 ? [] : [did];
        GlobalFilters.set({ dealerIds: next });
    });

    // Tooltip al pasar el cursor sobre una barra de la tendencia
    $('#uo_bars').on('mouseenter', '.uo_bar_wrap', function () {
        var cal = $(this).data('calendarioid');
        var p = null;
        for (var i = 0; i < serieActual.length; i++) {
            if (String(serieActual[i].calendarioid) === String(cal)) { p = serieActual[i]; break; }
        }
        if (!p) return;
        var $tip = $('#uo_tooltip');
        $tip.html(
            '<strong>' + p.periodo + '</strong>' +
            '<span>Ventas: <b>' + fmt(p.ventas) + '</b></span>' +
            '<span>UIO: <b>' + fmt(p.uio) + '</b></span>'
        ).addClass('is_visible');

        var area = document.getElementById('uo_bars');
        var areaRect = area.getBoundingClientRect();
        var wrapRect = this.getBoundingClientRect();
        var left = wrapRect.left - areaRect.left + wrapRect.width / 2 - $tip.outerWidth() / 2;
        var top = wrapRect.top - areaRect.top - $tip.outerHeight() - 6;
        $tip.css({ left: Math.max(0, left) + 'px', top: Math.max(0, top) + 'px' });
    });
    $('#uo_bars').on('mouseleave', '.uo_bar_wrap', function () {
        $('#uo_tooltip').removeClass('is_visible');
    });

    // Clic en una barra de la tendencia -> calendarId (mes de referencia)
    $('#uo_bars').on('click', '.uo_bar_wrap', function () {
        var cal = $(this).data('calendarioid');
        if (!cal) return;
        var actual = GlobalFilters.getParam('calendarId');
        GlobalFilters.set({ calendarId: actual === cal ? null : cal });
    });

    /* ------------------------------------------------------------------ *
     *  Inicialización
     * ------------------------------------------------------------------ */
    GlobalFilters.subscribe(function (nuevoEstado, prevEstado, evento) {
        if (evento === 'change') {
            sincronizarFiltrosDesdeStore();
            programarCarga();
        }
    });

    cargarFiltros();
    cargarDatos();

})(window);
