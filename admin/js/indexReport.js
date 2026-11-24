/*
|--------------------------------------------------------------------------
| DASHBOARD DE INDEX REPORT ($/UIO) - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de índice de repuestos por unidad en operación
| (admin/indexReport.php).
|
| Endpoint: controllers/indexReport.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables (las ventas de repuestos no se segmentan
       por CV/LCV y el tablero ya compara 3yr vs 5yr). */
    GlobalFilters.setAppliesTo({
        dealer:      true,
        country:     true,
        vehicleType: false,
        period:      true,
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

    var COLOR_UIO   = '#1a4870';
    var COLOR_INDEX3 = '#d1142a';
    var COLOR_INDEX5 = '#2563eb';

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

    function fmtFull(n) {
        if (n === null || n === undefined) return '—';
        return '$' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 });
    }

    function fmtIndice(n) {
        if (n === null || n === undefined) return '—';
        return '$' + Math.round(Number(n)).toLocaleString('en-US');
    }

    function fmtPct(n) {
        if (n === null || n === undefined) return '—';
        return (n >= 0 ? '+' : '') + Number(n).toFixed(2) + '%';
    }

    function fmtUio(n) {
        if (n === null || n === undefined) return '—';
        return Math.round(Number(n)).toLocaleString('en-US');
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
        var $selMes = $('#ir_filter_mes');
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

        var $selDealer = $('#ir_filter_dealer');
        var $selPais   = $('#ir_filter_country');
        var $selAnio   = $('#ir_filter_anio');

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
            $('#ir_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#ir_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#ir_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#ir_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#ir_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#ir_filter_periodo').val(estado.periodType || 'month');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/indexReport.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('IndexReport filtros: ' + res.message); return; }
                filtrosDisponibles = res.filtros;
                renderFiltros();
            },
            error: function (xhr) { console.error('IndexReport filtros error:', xhr.responseText); }
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
            url: '../controllers/indexReport.php',
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
                if (!res.success) { console.error('IndexReport: ' + res.message); return; }
                if (res.sin_periodo || res.sin_datos) {
                    renderVacio();
                    return;
                }
                datosActuales = res;
                serieActual = res.serie || [];
                renderKpis(res);
                renderInfo(res);
                renderGrafico(res);
                renderRanking(res);
            },
            error: function (xhr) { console.error('IndexReport error:', xhr.responseText); }
        });
    }

    function renderVacio() {
        $('#ir_kpi_indice').text('—');
        $('#ir_kpi_indice_sub').text('—');
        $('#ir_kpi_var3').html('—<span class="ir_kpi_arrow"></span>');
        $('#ir_kpi_var5').html('—<span class="ir_kpi_arrow"></span>');
        $('#ir_kpi_uio3').text('—');
        $('#ir_kpi_uio5').text('—');
        $('#ir_info_box').hide();
        $('#ir_chart_periodo').text('');
        $('#ir_y_left').empty();
        $('#ir_y_right').empty();
        $('#ir_chart_grid').empty();
        $('#ir_lines_svg').empty();
        $('#ir_bars').html('<div class="ir_estado_vacio">No data loaded</div>');
        $('#ir_dealers_tbody').html('<tr><td colspan="4" class="ir_estado_vacio">No data loaded</td></tr>');
    }

    /* ------------------------------------------------------------------ *
     *  Render: KPI cards
     * ------------------------------------------------------------------ */
    function renderKpis(datos) {
        var r = datos.resumen || {};

        $('#ir_kpi_indice').text(fmtIndice(r.indice));
        $('#ir_kpi_indice_sub').text('Ventas ' + fmtMoneda(r.ventas) + ' · UIO ' + fmtUio(r.uio_5));

        var v3 = r.var_3;
        var v5 = r.var_5;
        $('#ir_kpi_var3').html(
            (v3 === null || v3 === undefined ? '—' : fmtPct(v3)) +
            (v3 !== null && v3 !== undefined ? '<span class="ir_kpi_arrow">' + (v3 >= 0 ? '↑' : '↓') + '</span>' : '')
        ).removeClass('ir_green ir_red')
         .addClass(v3 === null || v3 === undefined ? '' : (v3 >= 0 ? 'ir_green' : 'ir_red'));

        $('#ir_kpi_var5').html(
            (v5 === null || v5 === undefined ? '—' : fmtPct(v5)) +
            (v5 !== null && v5 !== undefined ? '<span class="ir_kpi_arrow">' + (v5 >= 0 ? '↑' : '↓') + '</span>' : '')
        ).removeClass('ir_green ir_red')
         .addClass(v5 === null || v5 === undefined ? '' : (v5 >= 0 ? 'ir_green' : 'ir_red'));

        var g3 = r.uio_3_growth;
        var g5 = r.uio_5_growth;
        $('#ir_kpi_uio3').text(fmtPct(g3)).removeClass('ir_green ir_red')
            .addClass(g3 === null || g3 === undefined ? '' : (g3 >= 0 ? 'ir_green' : 'ir_red'));
        $('#ir_kpi_uio5').text(fmtPct(g5)).removeClass('ir_green ir_red')
            .addClass(g5 === null || g5 === undefined ? '' : (g5 >= 0 ? 'ir_green' : 'ir_red'));
    }

    /* ------------------------------------------------------------------ *
     *  Render: mensaje de resumen
     * ------------------------------------------------------------------ */
    function renderInfo(datos) {
        var r = datos.resumen || {};
        var v3 = r.var_3;
        var v5 = r.var_5;
        var $box = $('#ir_info_box');

        if (r.indice === null || r.indice === undefined) {
            $box.hide();
            return;
        }

        var html = 'El índice <strong>$ / UIO</strong> es de <strong>' + fmtIndice(r.indice) + '</strong> (anualizado, UIO 5 años). ';
        if (v3 !== null && v3 !== undefined) {
            html += 'Frente al promedio de <strong>3 años</strong> varía <span class="' + (v3 >= 0 ? 'ir_green' : 'ir_red') + '">' + fmtPct(v3) + '</span> · ';
        }
        if (v5 !== null && v5 !== undefined) {
            html += 'frente al promedio de <strong>5 años</strong> varía <span class="' + (v5 >= 0 ? 'ir_green' : 'ir_red') + '">' + fmtPct(v5) + '</span>.';
        }
        $('#ir_info_text').html(html);
        $box.show();
    }

    /* ------------------------------------------------------------------ *
     *  Render: gráfico de tendencia (barras UIO + líneas $/UIO)
     * ------------------------------------------------------------------ */
    function renderGrafico(datos) {
        var serie = datos.serie || [];

        var pa = datos.periodo_actual;
        $('#ir_chart_periodo').text(datos.etiqueta_periodo + (pa && pa.mes ? ' · ' + pa.mes + ' ' + pa.ano : ''));

        if (!serie.length) {
            $('#ir_bars').html('<div class="ir_estado_vacio">No data loaded</div>');
            $('#ir_lines_svg').empty();
            $('#ir_y_left').empty();
            $('#ir_y_right').empty();
            return;
        }

        // Escala UIO (eje derecho, barras): 0 .. max redondeado
        var maxUio = 0;
        serie.forEach(function (p) { if (p.uio > maxUio) maxUio = p.uio; });
        var uioTop = Math.ceil(maxUio / 1000) * 1000 || 1000;

        // Escala $/UIO (eje izquierdo, líneas): min/max de index3/index5 con margen
        var lineMin = Infinity, lineMax = -Infinity;
        serie.forEach(function (p) {
            [p.index3, p.index5].forEach(function (v) {
                if (v !== null && v !== undefined) {
                    if (v < lineMin) lineMin = v;
                    if (v > lineMax) lineMax = v;
                }
            });
        });
        if (lineMin === Infinity) { lineMin = 0; lineMax = 100; }
        var pad = (lineMax - lineMin) * 0.2 || 50;
        lineMin = Math.max(0, Math.floor(lineMin - pad));
        lineMax = Math.ceil(lineMax + pad);

        function pctUio(v) { return Math.max(2, Math.min(100, (v / uioTop) * 100)); }
        function pctLine(v) {
            if (v === null || v === undefined) return null;
            return Math.max(2, Math.min(100, ((v - lineMin) / (lineMax - lineMin)) * 100));
        }

        // Grid
        var $grid = $('#ir_chart_grid').empty();
        [0, 0.2, 0.4, 0.6, 0.8, 1.0].forEach(function (t) {
            var s = document.createElement('span');
            s.style.bottom = (t * 100) + '%';
            $grid.append(s);
        });

        // Ejes
        var $yLeft = $('#ir_y_left').empty();
        [lineMax, lineMax - (lineMax - lineMin) * 0.25, lineMax - (lineMax - lineMin) * 0.5,
         lineMax - (lineMax - lineMin) * 0.75, lineMin].forEach(function (v) {
            $yLeft.append('<span>' + fmtIndice(Math.round(v)) + '</span>');
        });
        var $yRight = $('#ir_y_right').empty();
        [uioTop, uioTop * 0.8, uioTop * 0.6, uioTop * 0.4, uioTop * 0.2, 0].forEach(function (v) {
            $yRight.append('<span>' + fmtUio(v) + '</span>');
        });

        // Barras (UIO)
        var $bars = $('#ir_bars').empty();
        var n = serie.length;
        serie.forEach(function (p, idx) {
            var $wrap = $('<div class="ir_bar_wrap"></div>').data('idx', idx);
            var $bar = $('<div class="ir_bar"></div>').css('height', pctUio(p.uio) + '%');
            var $lbl = $('<span class="ir_bar_label"></span>').text(p.mes.charAt(0) + p.mes.slice(1).toLowerCase());
            $wrap.append($bar).append($lbl);
            $bars.append($wrap);

            $wrap.on('mouseenter', function () { showTooltip($wrap, p, idx); });
            $wrap.on('mouseleave', function () { hideTooltip(); });
        });

        // Líneas SVG ($/UIO 3yr y 5yr)
        var $svg = $('#ir_lines_svg').empty();
        var step = n > 1 ? 100 / (n - 1) : 100;
        function xPos(i) { return n > 1 ? step * i : 50; }
        function yPos(v) {
            var p = pctLine(v);
            return p === null ? null : 100 - p;
        }

        function drawLine(key, stroke, dash) {
            var pts = [];
            serie.forEach(function (p, i) {
                var y = yPos(p[key]);
                if (y !== null) {
                    pts.push(xPos(i) + ',' + y);
                }
            });
            if (pts.length < 2) return;
            var pl = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
            pl.setAttribute('points', pts.join(' '));
            pl.setAttribute('fill', 'none');
            pl.setAttribute('stroke', stroke);
            pl.setAttribute('stroke-width', '1.5');
            pl.setAttribute('stroke-dasharray', dash);
            pl.setAttribute('vector-effect', 'non-scaling-stroke');
            $svg.append(pl);

            serie.forEach(function (p, i) {
                var y = yPos(p[key]);
                if (y === null) return;
                var c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                c.setAttribute('cx', xPos(i));
                c.setAttribute('cy', y);
                c.setAttribute('r', '1.8');
                c.setAttribute('fill', stroke);
                c.setAttribute('vector-effect', 'non-scaling-stroke');
                $svg.append(c);
            });
        }

        drawLine('index3', COLOR_INDEX3, '4 3');
        drawLine('index5', COLOR_INDEX5, '4 3');
    }

    function showTooltip($wrap, p, idx) {
        var $tip = $('#ir_tooltip');
        $tip.find('.ir_tooltip_main').text('$' + (p.index3 !== null && p.index3 !== undefined ? p.index3 : '—'));
        var sub = 'UIO: ' + fmtUio(p.uio);
        sub += ' · 5yr: ' + (p.index5 !== null && p.index5 !== undefined ? '$' + p.index5 : '—');
        $tip.find('.ir_tooltip_sub').text(sub).attr('class', 'ir_tooltip_sub');
        $tip.addClass('is_visible');

        var $bars = $('#ir_bars');
        var areaRect = $bars[0].getBoundingClientRect();
        var wrapRect = $wrap[0].getBoundingClientRect();
        var left = wrapRect.left - areaRect.left + wrapRect.width / 2 - $tip.outerWidth() / 2;
        var top = wrapRect.top - areaRect.top - $tip.outerHeight() - 6;
        $tip.css({ left: Math.max(0, left) + 'px', top: Math.max(0, top) + 'px' });
    }

    function hideTooltip() {
        $('#ir_tooltip').removeClass('is_visible');
    }

    /* ------------------------------------------------------------------ *
     *  Render: ranking de dealers por $/UIO
     * ------------------------------------------------------------------ */
    function renderRanking(datos) {
        var ranking = datos.ranking || [];
        var $tbody = $('#ir_dealers_tbody');
        $tbody.empty();

        if (!ranking.length) {
            $tbody.append('<tr><td colspan="4" class="ir_estado_vacio">No data loaded</td></tr>');
            return;
        }

        ranking.forEach(function (d) {
            var badge = 'ir_rank_badge';
            if (d.rank === 1) badge += ' ir_rank_1';
            else if (d.rank === 2) badge += ' ir_rank_2';
            else if (d.rank === 3) badge += ' ir_rank_3';

            var $tr = $('<tr class="ir_clickable" data-dealerid="' + d.dealerid + '" title="Filtrar por este dealer"></tr>');
            $tr.append(
                '<td><span class="' + badge + '">' + d.rank + '</span>&nbsp;' + escapeHtml(d.dealer) + '</td>' +
                '<td class="ir_td_right">' + fmtMoneda(d.ventas) + '</td>' +
                '<td class="ir_td_right">' + fmtUio(d.uio) + '</td>' +
                '<td class="ir_td_right ir_td_indice">' + fmtIndice(d.indice) + '</td>'
            );
            $tbody.append($tr);
        });
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#ir_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#ir_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#ir_filter_periodo').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#ir_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#ir_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#ir_btn_reset').on('click', function () {
        GlobalFilters.reset();
    });

    // Cross-filtering: clic en fila del ranking -> dealerIds (toggle)
    $('#ir_dealers_tbody').on('click', 'tr.ir_clickable', function () {
        var id = $(this).data('dealerid');
        if (!id) return;
        var actual = GlobalFilters.get().dealerIds || [];
        var mismo = actual.length === 1 && String(actual[0]) === String(id);
        GlobalFilters.set({ dealerIds: mismo ? [] : [Number(id)] });
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
