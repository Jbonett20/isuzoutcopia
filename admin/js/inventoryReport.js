/*
|--------------------------------------------------------------------------
| DASHBOARD DE INVENTORY REPORT - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de reporte de inventario (admin/inventoryReport.php).
|
| Endpoint: controllers/inventoryReport.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables (el inventario no se segmenta por CV/LCV y
       no usa comparación). */
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

    var COLOR_COST     = '#4b5563';
    var COLOR_MONTHS   = '#f97316';
    var COLOR_TURNOVER = '#2563eb';
    var COLOR_TARGET   = '#64748b';

    var ESTADOS = {
        bajo:       { label: 'Bajo Stock',        icono: '▼', cls: 'bajo' },
        saludable:  { label: 'Saludable',         icono: '✔', cls: 'saludable' },
        moderado:   { label: 'Exceso Moderado',   icono: '⚠', cls: 'moderado' },
        sobre:      { label: 'Sobre Stock',       icono: '▲', cls: 'sobre' },
        'n/a':      { label: 'Sin datos',         icono: '·', cls: 'n_a' }
    };

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

    function fmtNum(n) {
        if (n === null || n === undefined) return '—';
        return Number(n).toFixed(2);
    }

    function fmtMonths(n) {
        if (n === null || n === undefined) return '—';
        return Number(n).toFixed(1);
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
        var $selMes = $('#inv_filter_mes');
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

        var $selDealer = $('#inv_filter_dealer');
        var $selPais   = $('#inv_filter_country');
        var $selAnio   = $('#inv_filter_anio');

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
            $('#inv_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#inv_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#inv_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#inv_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#inv_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#inv_filter_periodo').val(estado.periodType || 'month');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/inventoryReport.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('INV filtros: ' + res.message); return; }
                filtrosDisponibles = res.filtros;
                renderFiltros();
            },
            error: function (xhr) { console.error('INV filtros error:', xhr.responseText); }
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
            url: '../controllers/inventoryReport.php',
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
                if (!res.success) { console.error('INV: ' + res.message); return; }
                if (res.sin_periodo || res.sin_datos) {
                    renderVacio();
                    return;
                }
                datosActuales = res;
                renderKpis(res);
                renderInfo(res);
                renderGrafico(res);
                renderDealers(res);
            },
            error: function (xhr) { console.error('INV error:', xhr.responseText); }
        });
    }

    function renderVacio() {
        $('#inv_kpi_inv').text('—');
        $('#inv_kpi_inv_sub').text('—');
        $('#inv_kpi_months').removeClass('red green').text('—');
        $('#inv_kpi_turnover').text('—');
        $('#inv_kpi_target').text('—');
        $('#inv_kpi_capital').removeClass('red green').text('—');
        $('#inv_info_box').hide();
        $('#inv_chart_periodo').text('');
        $('#ir_yticks_left').empty();
        $('#ir_yticks_right').empty();
        $('#ir_grid').empty();
        $('#ir_xaxis').empty();
        $('#ir_bars').empty();
        $('#ir_svg').empty();
        $('#inv_dealer_tbody').html('<tr><td colspan="4" class="inv_estado_vacio">No data loaded</td></tr>');
        $('#inv_health_dot').hide();
    }

    /* ------------------------------------------------------------------ *
     *  Render: KPI cards
     * ------------------------------------------------------------------ */
    function renderKpis(datos) {
        var r = datos.resumen || {};

        $('#inv_kpi_inv').text(fmtMoneda(r.inventory));
        $('#inv_kpi_inv_sub').text('Consumo ' + fmtMoneda(r.consumo));

        var m = r.months;
        $('#inv_kpi_months')
            .text(fmtMonths(m))
            .removeClass('red green')
            .addClass(m === null || m === undefined ? '' : (m > r.target ? 'red' : 'green'));

        $('#inv_kpi_turnover').text(fmtNum(r.turnover));
        $('#inv_kpi_target').text(r.target === null || r.target === undefined ? '—' : r.target + ' meses');

        var c = r.capital_riesgo;
        $('#inv_kpi_capital')
            .text(c === null || c === undefined ? '—' : fmtMoneda(c))
            .removeClass('red green')
            .addClass(c === null || c === undefined ? '' : (c > 0 ? 'red' : 'green'));
    }

    /* ------------------------------------------------------------------ *
     *  Render: mensaje de resumen
     * ------------------------------------------------------------------ */
    function renderInfo(datos) {
        var r = datos.resumen || {};
        if (r.inventory === null || r.inventory === undefined) {
            $('#inv_info_box').hide();
            return;
        }
        var html = 'El inventario total es de <strong>' + fmtMoneda(r.inventory) + '</strong>';
        if (r.months !== null && r.months !== undefined) {
            html += ', equivalente a <strong>' + fmtMonths(r.months) + ' meses</strong> de inventario (target ' + r.target + ' meses, turnover ' + fmtNum(r.turnover) + ').';
        }
        if (r.capital_riesgo !== null && r.capital_riesgo !== undefined) {
            html += ' El capital en riesgo es de <strong class="' + (r.capital_riesgo > 0 ? 'inv_var_red' : 'inv_var_green') + '">' + fmtMoneda(Math.abs(r.capital_riesgo)) + '</strong> ' + (r.capital_riesgo > 0 ? '(sobre stock).' : '(bajo stock).');
        }
        $('#inv_info_text').html(html);
        $('#inv_info_box').show();
    }

    /* ------------------------------------------------------------------ *
     *  Render: gráfico (barras de costo + líneas months/turnover/target)
     * ------------------------------------------------------------------ */
    function renderGrafico(datos) {
        var serie = datos.serie || [];
        var target = (datos.resumen && datos.resumen.target) || 10;

        var pa = datos.periodo_actual;
        $('#inv_chart_periodo').text(datos.etiqueta_periodo + (pa && pa.mes ? ' · ' + pa.mes + ' ' + pa.ano : ''));

        if (!serie.length) {
            $('#ir_yticks_left').empty();
            $('#ir_yticks_right').empty();
            $('#ir_grid').empty();
            $('#ir_xaxis').empty();
            $('#ir_bars').empty();
            $('#ir_svg').empty();
            return;
        }

        // Escalas
        var maxCost = 0, maxMonths = 0, maxTurn = 0;
        serie.forEach(function (p) {
            if (p.cost > maxCost) maxCost = p.cost;
            if (p.months !== null && p.months !== undefined && p.months > maxMonths) maxMonths = p.months;
            if (p.turnover !== null && p.turnover !== undefined && p.turnover > maxTurn) maxTurn = p.turnover;
        });
        if (target > maxMonths) maxMonths = target;
        maxMonths = Math.max(Math.ceil(maxMonths), 5);
        var costTop = maxCost > 0 ? Math.ceil(maxCost / 1000000) * 1000000 : 1000000;

        function pctCost(v) { return Math.max(2, Math.min(100, (v / costTop) * 100)); }
        function pctRight(v) {
            if (v === null || v === undefined) return null;
            return Math.max(2, Math.min(100, (v / maxMonths) * 100));
        }

        // Ejes
        var $yL = $('#ir_yticks_left').empty();
        [costTop, costTop * 0.75, costTop * 0.5, costTop * 0.25, 0].forEach(function (v) {
            $yL.append('<span>' + fmtMoneda(v) + '</span>');
        });
        var $yR = $('#ir_yticks_right').empty();
        var pasoR = Math.max(1, Math.round(maxMonths / 4));
        for (var t = maxMonths; t >= 0; t -= pasoR) { $yR.append('<span>' + Math.round(t) + '</span>'); }

        // Grid
        var $grid = $('#ir_grid').empty();
        [0, 25, 50, 75, 100].forEach(function (p) {
            var s = document.createElement('span');
            s.style.top = p + '%';
            $grid.append(s);
        });

        // Eje X
        var $xaxis = $('#ir_xaxis').empty();
        serie.forEach(function (p) {
            $xaxis.append('<span>' + (p.mes ? p.mes.charAt(0) + p.mes.slice(1).toLowerCase() : '') + '</span>');
        });

        // Barras (Inventory Cost)
        var $bars = $('#ir_bars').empty();
        serie.forEach(function (p) {
            var $b = $('<div class="ir_bar"></div>')
                .css('height', pctCost(p.cost) + '%')
                .css('background', COLOR_COST);
            var $g = $('<div class="ir_bar_group inv_clickable" data-calendarioid="' + p.calendarioid + '" title="Fijar mes de referencia"></div>');
            $g.append($b);
            $bars.append($g);
        });

        // Líneas SVG (months, turnover, target)
        var $svg = $('#ir_svg').empty();
        var n = serie.length;
        function xPos(i) { return n > 1 ? (i / (n - 1)) * 100 : 50; }

        function drawLine(key, stroke, dash, getVal) {
            var pts = [];
            serie.forEach(function (p, i) {
                var v = getVal(p);
                var y = pctRight(v);
                if (y !== null) pts.push(xPos(i).toFixed(2) + ',' + (100 - y).toFixed(2));
            });
            if (pts.length < 2) return;
            var line = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
            line.setAttribute('points', pts.join(' '));
            line.setAttribute('fill', 'none');
            line.setAttribute('stroke', stroke);
            line.setAttribute('stroke-width', '1.6');
            if (dash) line.setAttribute('stroke-dasharray', dash);
            line.setAttribute('vector-effect', 'non-scaling-stroke');
            $svg.append(line);
            serie.forEach(function (p, i) {
                var v = getVal(p);
                var y = pctRight(v);
                if (y === null) return;
                var c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                c.setAttribute('cx', xPos(i).toFixed(2));
                c.setAttribute('cy', (100 - y).toFixed(2));
                c.setAttribute('r', '1.5');
                c.setAttribute('fill', stroke);
                $svg.append(c);
            });
        }

        drawLine('months', COLOR_MONTHS, null, function (p) { return p.months; });
        drawLine('turnover', COLOR_TURNOVER, '4 3', function (p) { return p.turnover; });
        // Línea de target (constante)
        var yT = 100 - pctRight(target);
        var ptsT = serie.map(function (p, i) { return xPos(i).toFixed(2) + ',' + yT.toFixed(2); });
        var tl = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        tl.setAttribute('points', ptsT.join(' '));
        tl.setAttribute('fill', 'none');
        tl.setAttribute('stroke', COLOR_TARGET);
        tl.setAttribute('stroke-width', '1.5');
        tl.setAttribute('stroke-dasharray', '2 3');
        tl.setAttribute('vector-effect', 'non-scaling-stroke');
        $svg.append(tl);

        // Tooltip + cross-filtering
        $bars.off('.inv').on('mouseenter.inv', '.ir_bar_group', function () {
            var idx = $(this).index();
            var p = serie[idx];
            if (!p) return;
            $('#ir_tooltip').html(
                '<b>' + escapeHtml(p.mes) + '</b><br>' +
                'Cost: ' + fmtMoneda(p.cost) + '<br>' +
                'Months: ' + fmtMonths(p.months) + '<br>' +
                'Turnover: ' + fmtNum(p.turnover) + '<br>' +
                'Target: ' + target
            ).show();
        }).on('mousemove.inv', '.ir_bar_group', function (e) {
            var $tip = $('#ir_tooltip');
            var rect = document.getElementById('ir_chart_area').getBoundingClientRect();
            $tip.css({ left: (e.clientX - rect.left + 10) + 'px', top: (e.clientY - rect.top - 70) + 'px' });
        }).on('mouseleave.inv', '.ir_bar_group', function () {
            $('#ir_tooltip').hide();
        }).on('click.inv', '.ir_bar_group', function () {
            var cal = $(this).data('calendarioid');
            if (!cal) return;
            var actual = GlobalFilters.getParam('calendarId');
            GlobalFilters.set({ calendarId: actual === cal ? null : cal });
        });

        // Dot del health strip (meses de inventario actuales)
        var mesesAct = datos.resumen && datos.resumen.months;
        var $dot = $('#inv_health_dot');
        if (mesesAct !== null && mesesAct !== undefined) {
            var leftPct = Math.max(2, Math.min(98, (mesesAct / maxMonths) * 100));
            $dot.css('left', leftPct + '%').show();
        } else {
            $dot.hide();
        }
    }

    /* ------------------------------------------------------------------ *
     *  Render: tabla por dealer
     * ------------------------------------------------------------------ */
    function renderDealers(datos) {
        var dealers = datos.dealers || [];
        var $tbody = $('#inv_dealer_tbody');
        $tbody.empty();

        if (!dealers.length) {
            $tbody.append('<tr><td colspan="4" class="inv_estado_vacio">No data loaded</td></tr>');
            return;
        }

        dealers.forEach(function (d) {
            var est = ESTADOS[d.estado] || ESTADOS['n/a'];
            $tbody.append(
                '<tr class="inv_clickable" data-dealerid="' + d.dealerid + '" title="Filtrar por este dealer">' +
                    '<td class="td_name">' + escapeHtml(d.dealer) + '</td>' +
                    '<td>' + fmtMoneda(d.inv) + '</td>' +
                    '<td>' + fmtMonths(d.months) + '</td>' +
                    '<td><span class="ir_status_badge ' + est.cls + '">' + est.icono + ' ' + escapeHtml(est.label) + '</span></td>' +
                '</tr>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#inv_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#inv_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#inv_filter_periodo').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#inv_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#inv_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#inv_btn_reset').on('click', function () {
        GlobalFilters.reset();
    });

    // Cross-filtering: clic en fila del dealer -> dealerIds (toggle)
    $('#inv_dealer_tbody').on('click', 'tr.inv_clickable', function () {
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
