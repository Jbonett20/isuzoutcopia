/*
|--------------------------------------------------------------------------
| DASHBOARD DE RANKING REPORT - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de ranking de dealers por KPIs (admin/rankingReport.php).
|
| Endpoint: controllers/rankingReport.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables (el ranking no se segmenta por CV/LCV y no
       usa comparación). */
    GlobalFilters.setAppliesTo({
        dealer:      true,
        country:     true,
        vehicleType: false,
        period:      true,
        year:        true,
        comparison:  false
    });

    /* ------------------------- estado ------------------------- */
    var KPI_STORAGE_KEY = 'ots_rr_kpi';
    var KPI_URL_PARAM   = 'kpi';
    var filtrosDisponibles = null;
    var datosActuales      = null;
    var kpiSel             = 'unit_sales';
    var debounceTimer      = null;
    var fetchSeq           = 0;
    var sincronizandoFiltros = false;
    var select2Inicializado  = false;

    var ORDEN_KPIS = ['parts_sales', 'unit_sales', 'uio_3', 'uio_5', 'index_3', 'index_5', 'inv_turnover'];

    /* ------------------------- helpers ------------------------- */
    function escapeHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function fmtMoneda(n) {
        if (n === null || n === undefined) return '—';
        var abs = Math.abs(n);
        var sign = n < 0 ? '-' : '';
        if (abs >= 1e9) return sign + '$' + (abs / 1e9).toFixed(2) + 'B';
        if (abs >= 1e6) return sign + '$' + (abs / 1e6).toFixed(2) + 'M';
        if (abs >= 1e3) return sign + '$' + (abs / 1e3).toFixed(1) + 'K';
        return sign + '$' + Math.round(abs);
    }

    function fmtKpiVal(v, fmt) {
        if (v === null || v === undefined) return '—';
        if (fmt === 'entero') return Math.round(Number(v)).toLocaleString('en-US');
        if (fmt === 'decimal') return Number(v).toFixed(2);
        return fmtMoneda(v);
    }

    function leerKpiPersistido() {
        var k = null;
        try {
            var qs = new URLSearchParams(window.location.search);
            if (qs.get(KPI_URL_PARAM)) k = qs.get(KPI_URL_PARAM);
        } catch (e) { /* noop */ }
        if (!k) {
            try { k = window.localStorage.getItem(KPI_STORAGE_KEY); } catch (e) { /* noop */ }
        }
        return ORDEN_KPIS.indexOf(k) >= 0 ? k : null;
    }

    function guardarKpi() {
        try {
            window.localStorage.setItem(KPI_STORAGE_KEY, kpiSel);
        } catch (e) { /* noop */ }
        try {
            var qs = new URLSearchParams(window.location.search);
            qs.set(KPI_URL_PARAM, kpiSel);
            window.history.replaceState({}, '', window.location.pathname + '?' + qs.toString());
        } catch (e) { /* noop */ }
    }

    /* ------------------------- filtros ------------------------- */
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
        if (!filtrosDisponibles) return;
        var $selMes = $('#rr_filter_mes');
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

        var $selDealer = $('#rr_filter_dealer');
        var $selPais   = $('#rr_filter_country');
        var $selAnio   = $('#rr_filter_anio');

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
            $('#rr_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#rr_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#rr_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#rr_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#rr_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#rr_filter_periodo').val(estado.periodType || 'month');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/rankingReport.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('RR filtros: ' + res.message); return; }
                filtrosDisponibles = res.filtros;
                renderFiltros();
            },
            error: function (xhr) { console.error('RR filtros error:', xhr.responseText); }
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
            url: '../controllers/rankingReport.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action:     'getResumen',
                year:       f.year        || '',
                calendarId: f.calendarId  || '',
                dealerIds:  f.dealerIds   || [],
                countryIds: f.countryIds  || [],
                periodType: f.periodType  || 'month'
            },
            success: function (res) {
                if (seq !== fetchSeq) return;
                if (!res.success) { console.error('RR: ' + res.message); return; }
                if (res.sin_periodo || res.sin_datos) {
                    renderVacio();
                    return;
                }
                datosActuales = res;
                renderPodium(res);
                renderInfo(res);
                renderMetrics(res);
                renderKpiSelector(res);
                renderHbars(res);
                renderScore(res);
                renderRankings(res);
            },
            error: function (xhr) { console.error('RR error:', xhr.responseText); }
        });
    }

    function renderVacio() {
        $('#rr_podium_parts').html('<div class="rr_estado_vacio">No data loaded</div>');
        $('#rr_podium_uio').html('<div class="rr_estado_vacio">No data loaded</div>');
        $('#rr_info_box').hide();
        $('#rr_metrics_row').empty();
        $('#rr_kpi_selector').empty();
        $('#rr_hbar_list').html('<div class="rr_estado_vacio">No data loaded</div>');
        $('#rr_score_tbody').html('<tr><td colspan="4" class="rr_estado_vacio">No data loaded</td></tr>');
        $('#rr_rankings_col').html('<div class="rr_estado_vacio">No data loaded</div>');
    }

    /* ------------------------------------------------------------------ *
     *  Render: podio
     * ------------------------------------------------------------------ */
    function renderPodium(datos) {
        var podium = datos.podium || {};
        var idMap = { parts_sales: 'parts', uio_5: 'uio' };
        ['parts_sales', 'uio_5'].forEach(function (clave) {
            var $cont = $('#rr_podium_' + (idMap[clave] || clave));
            $cont.empty();
            var items = podium[clave] || [];
            if (!items.length) {
                $cont.append('<div class="rr_estado_vacio">No data loaded</div>');
                return;
            }
            items.forEach(function (p) {
                var label = p.rank === 1
                    ? (clave === 'parts_sales' ? '#1 Top Parts Sales' : '#1 Top UIO 5yr')
                    : '';
                $cont.append(
                    '<div class="rr_hero_card ' + p.medal + ' rr_clickable" data-dealerid="" title="">' +
                        '<div class="rr_medal ' + p.medal + '">' + p.rank + '</div>' +
                        (label ? '<div class="rr_hero_label">' + escapeHtml(label) + '</div>' : '') +
                        '<div class="rr_hero_name">' + escapeHtml(p.name) + '</div>' +
                        '<div class="rr_hero_value">' + escapeHtml(p.value) + '</div>' +
                    '</div>'
                );
            });
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: info box
     * ------------------------------------------------------------------ */
    function renderInfo(datos) {
        var score = (datos.score && datos.score[0]) || null;
        if (!score) {
            $('#rr_info_box').hide();
            return;
        }
        var html = 'El dealer líder del ranking es <strong>' + escapeHtml(score.name) + '</strong> con un score general de <strong>' + score.score + '</strong>' +
            ' (período ' + escapeHtml(datos.etiqueta_periodo || '') + ').';
        if (datos.metricas && datos.metricas[0]) {
            html += ' El líder en ' + escapeHtml(datos.metricas[0].titulo) + ' es <strong>' + escapeHtml(datos.metricas[0].lider) + '</strong> (' + escapeHtml(datos.metricas[0].valor) + ').';
        }
        $('#rr_info_text').html(html);
        $('#rr_info_box').show();
    }

    /* ------------------------------------------------------------------ *
     *  Render: tarjetas de líderes (metrics)
     * ------------------------------------------------------------------ */
    function renderMetrics(datos) {
        var metricas = datos.metricas || [];
        var $row = $('#rr_metrics_row');
        $row.empty();
        metricas.forEach(function (m) {
            $row.append(
                '<div class="rr_metric_card">' +
                    '<div class="rr_metric_title">' + escapeHtml(m.titulo) + '</div>' +
                    '<div class="rr_metric_leader">' +
                        '<div class="rr_metric_dot" style="background:' + m.color + ';"></div>' +
                        '<span>' + escapeHtml(m.lider) + '</span>' +
                        (m.cambio ? '<span style="color:' + m.color + ';font-weight:700;">' + escapeHtml(m.cambio) + '</span>' : '') +
                    '</div>' +
                    '<div class="rr_metric_value">' + escapeHtml(m.valor) + '</div>' +
                '</div>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: selector de KPI + barras horizontales
     * ------------------------------------------------------------------ */
    function renderKpiSelector(datos) {
        var kpis = datos.kpis || {};
        var $sel = $('#rr_kpi_selector');
        var keys = [];
        // Preservar el orden definido
        ORDEN_KPIS.forEach(function (k) {
            if (kpis[k]) keys.push(k);
        });
        $sel.empty();
        keys.forEach(function (k) {
            $sel.append($('<option>', { value: k }).text(kpis[k].nombre));
        });
        if (keys.indexOf(kpiSel) < 0) kpiSel = keys[0] || 'unit_sales';
        $sel.val(kpiSel);
    }

    function renderHbars(datos) {
        var kpis = datos.kpis || {};
        var kpi = kpis[kpiSel];
        var $list = $('#rr_hbar_list');
        $list.empty();
        if (!kpi || !kpi.rows || !kpi.rows.length) {
            $list.append('<div class="rr_estado_vacio">No data loaded</div>');
            return;
        }
        kpi.rows.forEach(function (r) {
            var score = Math.max(0, Math.min(100, r.score));
            var color = score >= 80 ? '#2d6a4f' : (score >= 60 ? '#a16207' : (score >= 40 ? '#6b7280' : '#dc2626'));
            var trendCls = (r.trend && r.trend.indexOf('▲') === 0) ? 'up' : ((r.trend && r.trend.indexOf('▼') === 0) ? 'down' : '');
            $list.append(
                '<div class="rr_hbar_row rr_clickable" data-dealerid="' + r.dealerid + '" title="Filtrar por este dealer">' +
                    '<div class="rr_hbar_label">' + escapeHtml(r.name) + '</div>' +
                    '<div class="rr_hbar_track">' +
                        '<div class="rr_hbar_fill" style="width:' + score + '%;background:' + color + ';">' +
                            (score > 40 ? escapeHtml(fmtKpiVal(r.valor, kpi.fmt)) : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="rr_hbar_trend ' + trendCls + '">' + escapeHtml(r.trend || '') + '</div>' +
                '</div>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: tabla de ranking general (score)
     * ------------------------------------------------------------------ */
    function renderScore(datos) {
        var score = datos.score || [];
        var $tbody = $('#rr_score_tbody');
        $tbody.empty();
        if (!score.length) {
            $tbody.append('<tr><td colspan="4" class="rr_estado_vacio">No data loaded</td></tr>');
            return;
        }
        score.forEach(function (r) {
            var rankCls = r.rank === 1 ? 'g1' : (r.rank === 2 ? 'g2' : (r.rank === 3 ? 'g3' : ''));
            var tierCls = r.tier;
            var tendTxt = r.tend || '—';
            var tendArrow = '';
            if (r.tier === 'bajo') tendArrow = '&#x25BC; ';
            var tendCls = (tendTxt.indexOf('▲') === 0) ? 'rr_tend_up' : ((tendTxt.indexOf('▼') === 0) ? 'rr_tend_down' : '');
            $tbody.append(
                '<tr class="rr_clickable" data-dealerid="' + r.dealerid + '" title="Filtrar por este dealer">' +
                    '<td><div class="rr_dealer_cell"><span class="rr_rank_num ' + rankCls + '">' + r.rank + '</span>' + escapeHtml(r.name) + '</div></td>' +
                    '<td>' + r.score + '</td>' +
                    '<td>' + escapeHtml(String(r.pos)) + '</td>' +
                    '<td><span class="rr_tier_badge ' + tierCls + '">' + tendArrow + escapeHtml(r.label) + '</span>' +
                        '<span class="rr_tend ' + tendCls + '">' + escapeHtml(tendTxt) + '</span></td>' +
                '</tr>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: rankings laterales (top 3 por KPI)
     * ------------------------------------------------------------------ */
    function renderRankings(datos) {
        var rankings = datos.rankings || [];
        var $col = $('#rr_rankings_col');
        $col.empty();
        if (!rankings.length) {
            $col.append('<div class="rr_estado_vacio">No data loaded</div>');
            return;
        }
        rankings.forEach(function (rk) {
            var $card = $('<div class="rr_rank_card"></div>');
            $card.append('<div class="rr_rank_title">' + escapeHtml(rk.titulo) + '</div>');
            (rk.rows || []).forEach(function (row) {
                var cls = row.n === 1 ? 'g1' : (row.n === 2 ? 'g2' : 'g3');
                $card.append(
                    '<div class="rr_rank_row">' +
                        '<span class="rr_rank_num ' + cls + '">' + row.n + '</span>' +
                        '<span class="rr_rank_name">' + escapeHtml(row.name) + '</span>' +
                        '<span class="rr_rank_val">' + escapeHtml(row.val) + '</span>' +
                    '</div>'
                );
            });
            $col.append($card);
        });
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#rr_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#rr_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#rr_filter_periodo').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#rr_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#rr_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#rr_btn_reset').on('click', function () {
        GlobalFilters.reset();
    });

    // Selector de KPI del gráfico (local persistente)
    $('#rr_kpi_selector').on('change', function () {
        kpiSel = $(this).val();
        guardarKpi();
        if (datosActuales) renderHbars(datosActuales);
    });

    // Cross-filtering: clic en fila del score o del gráfico -> dealerIds (toggle)
    function filtrarPorDealer($el) {
        var id = $el.data('dealerid');
        if (!id) return;
        var actual = GlobalFilters.get().dealerIds || [];
        var mismo = actual.length === 1 && String(actual[0]) === String(id);
        GlobalFilters.set({ dealerIds: mismo ? [] : [Number(id)] });
    }

    $('#rr_score_tbody').on('click', 'tr.rr_clickable', function () { filtrarPorDealer($(this)); });
    $('#rr_hbar_list').on('click', '.rr_hbar_row.rr_clickable', function () { filtrarPorDealer($(this)); });

    /* ------------------------------------------------------------------ *
     *  Inicialización
     * ------------------------------------------------------------------ */
    GlobalFilters.subscribe(function (nuevoEstado, prevEstado, evento) {
        if (evento === 'change') {
            sincronizarFiltrosDesdeStore();
            programarCarga();
        }
    });

    var k = leerKpiPersistido();
    if (k) kpiSel = k;

    cargarFiltros();
    cargarDatos();

})(window);
