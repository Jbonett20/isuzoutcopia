/*
|--------------------------------------------------------------------------
| DASHBOARD DE WORKSHOP REPORT (SERVICIO) - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de reporte de taller (admin/workshopReport.php).
|
| Endpoint: controllers/workshopReport.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables (el servicio no se segmenta por CV/LCV y la
       comparación vs año anterior es intrínseca al tablero). */
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

    var COLOR_ORDERS  = '#1e3a5f';
    var COLOR_TARGET  = '#dc2626';

    /* ------------------------- helpers ------------------------- */
    function escapeHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function fmtEntero(n) {
        if (n === null || n === undefined) return '—';
        return Math.round(Number(n)).toLocaleString('en-US');
    }

    function fmtPct(n) {
        if (n === null || n === undefined) return '—';
        return (n >= 0 ? '+' : '') + Number(n).toFixed(1) + '%';
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
        var $selMes = $('#wr_filter_mes');
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

        var $selDealer = $('#wr_filter_dealer');
        var $selPais   = $('#wr_filter_country');
        var $selAnio   = $('#wr_filter_anio');

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
            $('#wr_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#wr_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#wr_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#wr_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#wr_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#wr_filter_periodo').val(estado.periodType || 'month');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/workshopReport.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('WR filtros: ' + res.message); return; }
                filtrosDisponibles = res.filtros;
                renderFiltros();
            },
            error: function (xhr) { console.error('WR filtros error:', xhr.responseText); }
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
            url: '../controllers/workshopReport.php',
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
                if (!res.success) { console.error('WR: ' + res.message); return; }
                if (res.sin_periodo || res.sin_datos) {
                    renderVacio();
                    return;
                }
                datosActuales = res;
                renderKpis(res);
                renderInfo(res);
                renderGrafico(res);
                renderStatus(res);
            },
            error: function (xhr) { console.error('WR error:', xhr.responseText); }
        });
    }

    function renderVacio() {
        $('#wr_kpi_orders').text('—');
        $('#wr_kpi_orders_sub').text('—');
        $('#wr_kpi_var').removeClass('green red').text('—');
        $('#wr_kpi_retention').text('—');
        $('#wr_kpi_open').text('—');
        $('#wr_info_box').hide();
        $('#wr_chart_periodo').text('');
        $('#wr_yticks_left').empty();
        $('#wr_yticks_right').empty();
        $('#wr_grid').empty();
        $('#wr_xaxis').empty();
        $('#wr_bars').empty();
        $('#wr_svg').empty();
        $('#wr_status_list').html('<div class="wr_estado_vacio">No data loaded</div>');
        $('#wr_health_dot').hide();
    }

    /* ------------------------------------------------------------------ *
     *  Render: KPI cards
     * ------------------------------------------------------------------ */
    function renderKpis(datos) {
        var r = datos.resumen || {};

        $('#wr_kpi_orders').text(fmtEntero(r.orders));
        $('#wr_kpi_orders_sub').text('LY ' + fmtEntero(r.orders_ly));

        var v = r.var_ly;
        $('#wr_kpi_var')
            .text(fmtPct(v))
            .removeClass('green red')
            .addClass(v === null || v === undefined ? '' : (v >= 0 ? 'green' : 'red'));

        $('#wr_kpi_retention').text(r.retention === null || r.retention === undefined ? '—' : fmtPct(r.retention));
        $('#wr_kpi_open').text(r.open_orders === null || r.open_orders === undefined ? '—' : fmtEntero(r.open_orders));
    }

    /* ------------------------------------------------------------------ *
     *  Render: mensaje de resumen
     * ------------------------------------------------------------------ */
    function renderInfo(datos) {
        var r = datos.resumen || {};
        if (r.orders === null || r.orders === undefined) {
            $('#wr_info_box').hide();
            return;
        }
        var html = 'El taller registró <strong>' + fmtEntero(r.orders) + '</strong> órdenes de servicio';
        if (r.var_ly !== null && r.var_ly !== undefined) {
            html += ', con una variación de <strong class="' + (r.var_ly >= 0 ? 'wr_var_green' : 'wr_var_red') + '">' + fmtPct(r.var_ly) + '</strong> frente al mismo período del año anterior (LY ' + fmtEntero(r.orders_ly) + ').';
        } else {
            html += ' (sin dato de año anterior).';
        }
        if (r.retention === null || r.retention === undefined) {
            html += ' <span class="wr_muted">La retención no tiene fuente de datos disponible.</span>';
        }
        $('#wr_info_text').html(html);
        $('#wr_info_box').show();
    }

    /* ------------------------------------------------------------------ *
     *  Render: gráfico (barras de órdenes + línea objetivo de retención)
     * ------------------------------------------------------------------ */
    function renderGrafico(datos) {
        var serie = datos.serie || [];
        var perf = datos.performance || { target: 12 };

        var pa = datos.periodo_actual;
        $('#wr_chart_periodo').text(datos.etiqueta_periodo + (pa && pa.mes ? ' · ' + pa.mes + ' ' + pa.ano : ''));

        if (!serie.length) {
            $('#wr_yticks_left').empty();
            $('#wr_yticks_right').empty();
            $('#wr_grid').empty();
            $('#wr_xaxis').empty();
            $('#wr_bars').empty();
            $('#wr_svg').empty();
            return;
        }

        var maxOrders = 0;
        serie.forEach(function (p) { if (p.orders > maxOrders) maxOrders = p.orders; });
        var top = maxOrders > 0 ? Math.ceil(maxOrders / 1000) * 1000 : 1000;
        var maxR = 20; // eje derecho (%) retención

        function pctY(v) { return Math.max(2, Math.min(100, (v / top) * 100)); }
        function pctR(v) { return Math.max(2, Math.min(100, (v / maxR) * 100)); }

        // Eje izquierdo (órdenes)
        var $yL = $('#wr_yticks_left').empty();
        var paso = Math.max(1, Math.round(top / 5 / 500) * 500);
        for (var t = top; t >= 0; t -= paso) { $yL.append('<span>' + fmtEntero(t) + '</span>'); }

        // Eje derecho (retención %)
        var $yR = $('#wr_yticks_right').empty();
        [20, 15, 10, 5, 0].forEach(function (v) { $yR.append('<span>' + v + '%</span>'); });

        // Grid
        var $grid = $('#wr_grid').empty();
        [0, 25, 50, 75, 100].forEach(function (p) {
            var s = document.createElement('span');
            s.style.top = p + '%';
            $grid.append(s);
        });

        // Eje X (meses)
        var $xaxis = $('#wr_xaxis').empty();
        serie.forEach(function (p) {
            $xaxis.append('<span>' + (p.mes ? p.mes.charAt(0) + p.mes.slice(1).toLowerCase() : '') + '</span>');
        });

        // Barras (órdenes)
        var $bars = $('#wr_bars').empty();
        var maxPunto = serie.reduce(function (a, p) { return p.orders > a.orders ? p : a; }, serie[0]);
        serie.forEach(function (p) {
            var $g = $('<div class="wr_bar_group wr_clickable" data-calendarioid="' + p.calendarioid + '" title="Fijar mes de referencia"></div>');
            var $bar = $('<div class="wr_bar_stacked"></div>')
                .css('height', pctY(p.orders) + '%')
                .css('background', 'linear-gradient(to bottom, #3b82f6 50%, ' + COLOR_ORDERS + ' 50%)');
            if (String(p.calendarioid) === String(maxPunto.calendarioid)) {
                $bar.addClass('is_max');
            }
            var $lbl = $('<div class="wr_bar_top_label"></div>').text(fmtEntero(p.orders));
            $bar.append($lbl);
            $g.append($bar);
            $bars.append($g);
        });

        // Línea objetivo de retención (SVG, eje derecho)
        var $svg = $('#wr_svg').empty();
        var n = serie.length;
        function xPos(i) { return n > 1 ? (i / (n - 1)) * 100 : 50; }
        var yT = 100 - pctR(perf.target);
        var pts = [];
        serie.forEach(function (p, i) { pts.push(xPos(i).toFixed(2) + ',' + yT.toFixed(2)); });
        if (pts.length >= 2) {
            var line = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
            line.setAttribute('points', pts.join(' '));
            line.setAttribute('fill', 'none');
            line.setAttribute('stroke', COLOR_TARGET);
            line.setAttribute('stroke-width', '1.5');
            line.setAttribute('stroke-dasharray', '4 3');
            line.setAttribute('vector-effect', 'non-scaling-stroke');
            $svg.append(line);
            serie.forEach(function (p, i) {
                var c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                c.setAttribute('cx', xPos(i).toFixed(2));
                c.setAttribute('cy', yT.toFixed(2));
                c.setAttribute('r', '1.5');
                c.setAttribute('fill', COLOR_TARGET);
                $svg.append(c);
            });
        }

        // Tooltip + cross-filtering en barras
        $bars.off('.wr').on('mouseenter.wr', '.wr_bar_group', function () {
            var idx = $(this).index();
            var p = serie[idx];
            if (!p) return;
            var $tip = $('#wr_tooltip');
            $tip.html(
                '<b>' + escapeHtml(p.mes) + '</b><br>' +
                'Orders: ' + fmtEntero(p.orders) + '<br>' +
                'Target Retention: ' + perf.target + '%'
            ).show();
        }).on('mousemove.wr', '.wr_bar_group', function (e) {
            var $tip = $('#wr_tooltip');
            var rect = document.getElementById('wr_chart_area').getBoundingClientRect();
            $tip.css({ left: (e.clientX - rect.left + 10) + 'px', top: (e.clientY - rect.top - 60) + 'px' });
        }).on('mouseleave.wr', '.wr_bar_group', function () {
            $('#wr_tooltip').hide();
        }).on('click.wr', '.wr_bar_group', function () {
            var cal = $(this).data('calendarioid');
            if (!cal) return;
            var actual = GlobalFilters.getParam('calendarId');
            GlobalFilters.set({ calendarId: actual === cal ? null : cal });
        });

        // Dot del health strip (retención no disponible -> oculto)
        $('#wr_health_dot').hide();
    }

    /* ------------------------------------------------------------------ *
     *  Render: panel de estado de retención (rangos de desempeño)
     * ------------------------------------------------------------------ */
    function renderStatus(datos) {
        var perf = datos.performance || {};
        var rangos = perf.rangos || [];
        var $list = $('#wr_status_list');
        $list.empty();

        if (!rangos.length) {
            $list.append('<div class="wr_estado_vacio">No data loaded</div>');
            return;
        }

        rangos.forEach(function (r, i) {
            var desc = (r.min > 12) ? '> 12%' : ((r.min >= 9) ? '9% – 12%' : '< 9%');
            $list.append(
                '<div class="wr_status_row' + (i < rangos.length - 1 ? ' has_divider' : '') + '">' +
                    '<div class="wr_status_dot" style="background:' + r.color + ';"></div>' +
                    '<span>' + escapeHtml(r.label) + '</span>' +
                    '<span class="wr_status_val">' + desc + '</span>' +
                '</div>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros
     * ------------------------------------------------------------------ */
    $('#wr_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#wr_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#wr_filter_periodo').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#wr_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#wr_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#wr_btn_reset').on('click', function () {
        GlobalFilters.reset();
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
