/*
|--------------------------------------------------------------------------
| DASHBOARD DE VENTAS - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de venta de vehículos (admin/dashboardSales.php).
|
| Endpoint: controllers/dashboardSales.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables en este tablero */
    GlobalFilters.setAppliesTo({
        dealer:      true,
        country:     true,
        vehicleType: true,
        period:      true,
        year:        true,
        comparison:  true
    });

    /* ------------------------- estado ------------------------- */
    var filtrosDisponibles   = null;
    var datosActuales        = null;
    var debounceTimer        = null;
    var fetchSeq             = 0;
    var sincronizandoFiltros = false;
    var select2Inicializado  = false;
    var tablaSales           = null;
    var serieActual          = [];

    var ETIQUETAS_TIPO = { all: 'Vehicle Sales', cv: 'CV Sales', lcv: 'LCV Sales' };
    var ETIQUETAS_TIPO_CORTO = { all: 'All (CV + LCV)', cv: 'CV', lcv: 'LCV' };

    function fmt(n) {
        if (n === null || n === undefined) return '—';
        return Number(n).toLocaleString('en-US');
    }

    function pctConSigno(v) {
        if (v === null || v === undefined) return '—';
        return (v >= 0 ? '+' : '') + v + '%';
    }

    function numConSigno(v) {
        if (v === null || v === undefined) return '—';
        return (v >= 0 ? '+' : '') + v;
    }

    /* ------------------------------------------------------------------ *
     *  Tarjetas KPI (Current Sales, Var % vs LY, Goal, Gap)
     * ------------------------------------------------------------------ */
    function renderTarjetas(datos) {
        var t = datos.totales || {};

        // Current Sales
        $('#kpi_actual_tablero').text(fmt(t.actual));
        var trendActual = (t.var_pct !== null && t.var_pct !== undefined)
            ? pctConSigno(t.var_pct) + ' vs LY'
            : '— vs LY';
        $('#kpi_trend_actual_tablero').text(trendActual);
        $('#kpi_trend_actual_tablero').closest('.kpi_trend_tablero')
            .toggleClass('up_tablero', t.var_pct !== null && t.var_pct >= 0)
            .toggleClass('down_tablero', t.var_pct !== null && t.var_pct < 0)
            .toggleClass('neutral_tablero', t.var_pct === null || t.var_pct === undefined);
        var pctActual = 0;
        if (t.objetivo && t.objetivo > 0) {
            pctActual = Math.min(100, Math.round((t.actual / t.objetivo) * 100));
        } else if (t.actual > 0) {
            pctActual = 100;
        }
        $('#kpi_progress_actual_tablero').css('width', pctActual + '%');

        // Var % vs LY
        var $var = $('#kpi_var_tablero').text(numConSigno(t.var_pct));
        $var.toggleClass('positivo_tablero', t.var_pct !== null && t.var_pct >= 0)
            .toggleClass('negativo_tablero', t.var_pct !== null && t.var_pct < 0);
        $('#kpi_trend_var_tablero').text((t.var_pct !== null && t.var_pct !== undefined) ? 'vs período equivalente LY' : 'sin dato del año anterior');
        $('#kpi_progress_var_tablero').css('width', (t.var_pct === null || t.var_pct === undefined ? 0 : Math.min(100, Math.abs(t.var_pct))) + '%');

        // Goal
        var $obj = $('#kpi_objetivo_tablero');
        if (t.tiene_objetivo && t.objetivo !== null) {
            $obj.text(fmt(t.objetivo)).removeClass('kpi_sin_objetivo_tablero');
            $('#kpi_trend_objetivo_tablero').text('Meta acumulada' + (t.objetivo_mensual !== null ? ' · ' + fmt(t.objetivo_mensual) + '/mes' : ''));
            $('#kpi_progress_objetivo_tablero').css('width', '100%');
        } else {
            $obj.text('Sin objetivo').addClass('kpi_sin_objetivo_tablero');
            $('#kpi_trend_objetivo_tablero').text('No hay meta definida para el año');
            $('#kpi_progress_objetivo_tablero').css('width', '0%');
        }

        // Gap
        var $gap = $('#kpi_gap_tablero');
        if (t.gap !== null && t.gap !== undefined) {
            $gap.text((t.gap >= 0 ? '+' : '') + fmt(t.gap)).removeClass('kpi_sin_objetivo_tablero')
                .toggleClass('positivo_tablero', t.gap >= 0)
                .toggleClass('negativo_tablero', t.gap < 0);
            $('#kpi_trend_gap_tablero').text(t.gap >= 0 ? 'Por encima de la meta' : 'Por debajo de la meta');
            $('#kpi_progress_gap_tablero').css('width', (t.objetivo && t.objetivo > 0 ? Math.min(100, Math.round((Math.abs(t.gap) / t.objetivo) * 100)) : 0) + '%');
        } else {
            $gap.text('Sin objetivo').addClass('kpi_sin_objetivo_tablero');
            $('#kpi_trend_gap_tablero').text('Sin meta para calcular el gap');
            $('#kpi_progress_gap_tablero').css('width', '0%');
        }

        // Delta de comparación (título del gráfico)
        var $delta = $('#delta_comparacion_tablero');
        var comp = datos.comparacion;
        if (comp && comp.delta_units !== undefined) {
            var s = comp.delta_units >= 0 ? '+' : '';
            var pctD = comp.delta_pct !== null && comp.delta_pct !== undefined ? ' (' + pctConSigno(comp.delta_pct) + ')' : '';
            $delta.html('vs ' + (comp.etiqueta_base || '') + ' <b>' + s + fmt(comp.delta_units) + ' uds</b>' + pctD)
                .removeClass('positivo_tablero negativo_tablero')
                .addClass(comp.delta_units >= 0 ? 'positivo_tablero' : 'negativo_tablero')
                .show();
        } else {
            $delta.hide();
        }

        // Título del gráfico según tipo de vehículo
        $('#chart_metric_label_tablero').text(ETIQUETAS_TIPO[datos.tipo_vehiculo] || 'Sales');
    }

    /* ------------------------------------------------------------------ *
     *  Gráfico: barras actuales + línea LY (SVG) + marcadores de objetivo
     * ------------------------------------------------------------------ */
    function renderGrafico(datos) {
        var $bars = $('#bars_tablero');
        var $lineSvg = $('#linea_ly_tablero');
        var serie = datos.serie || [];
        var t = datos.totales || {};
        serieActual = serie;

        $bars.empty();
        $lineSvg.empty();
        $('#chart_y_ticks_tablero').empty();
        $('#chart_grid_tablero').empty();

        var hayObjetivo = t.tiene_objetivo && t.objetivo !== null;

        if (!serie.length) {
            $bars.html('<div class="empty_state_tablero">No sales data loaded</div>');
            $('.chart_target_line_tablero').hide();
            return;
        }

        // Escala Y (máximo redondeado a múltiplos de 100)
        var maximo = 0;
        serie.forEach(function (p) {
            maximo = Math.max(maximo, p.actual, p.ly, p.objetivo || 0);
        });
        if (hayObjetivo) maximo = Math.max(maximo, t.objetivo);
        if (maximo <= 0) maximo = 1;
        var maxY = Math.ceil(maximo / 100) * 100;
        if (maxY <= 0) maxY = 100;

        var pasos = 5;
        var ticksHtml = '';
        var gridHtml = '';
        for (var i = pasos; i >= 0; i--) {
            ticksHtml += '<span>' + Math.round((maxY * i) / pasos) + '</span>';
            gridHtml += '<span style="bottom:' + (i / pasos) * 100 + '%"></span>';
        }
        $('#chart_y_ticks_tablero').html(ticksHtml);
        $('#chart_grid_tablero').html(gridHtml);

        function hPct(valor) { return Math.max(1.5, (valor / maxY) * 100); }

        var selCal = GlobalFilters.getParam('calendarId');

        serie.forEach(function (p) {
            var activo = selCal && selCal === p.calendarioid;
            var wrap = $('<div>', {
                class: 'bar_wrap_tablero' + (activo ? ' is_activo_tablero' : ''),
                'data-calendarioid': p.calendarioid,
                title: p.periodo
            });
            if (p.objetivo !== null && p.objetivo !== undefined) {
                wrap.append($('<div>', { class: 'bar_objetivo_mark_tablero', css: { bottom: hPct(p.objetivo) + '%' } }));
            }
            wrap.append(
                $('<div>', { class: 'bar_tablero' + (activo ? ' highlight_bar_tablero' : ''), css: { height: hPct(p.actual) + '%' } }),
                $('<span class="' + (activo ? 'x_active_tablero' : '') + '">' + p.periodo + '</span>')
            );
            $bars.append(wrap);
        });

        // Línea LY (SVG con viewBox 0..100)
        var n = serie.length;
        var pts = serie.map(function (p, idx) {
            return { x: ((idx + 0.5) / n) * 100, y: (1 - (p.ly / maxY)) * 100, cal: p.calendarioid };
        });
        var poly = '<polyline class="ly_poly_tablero" points="' +
            pts.map(function (pt) { return pt.x.toFixed(2) + ',' + pt.y.toFixed(2); }).join(' ') +
            '"></polyline>';
        var circles = pts.map(function (pt) {
            var cls = 'ly_point_tablero' + (selCal && selCal === pt.cal ? ' ly_point_active_tablero' : '');
            return '<circle class="' + cls + '" cx="' + pt.x.toFixed(2) + '" cy="' + pt.y.toFixed(2) + '" r="2.5"></circle>';
        }).join('');
        $lineSvg.attr('viewBox', '0 0 100 100').attr('preserveAspectRatio', 'none').html(poly + circles);

        // Línea horizontal de objetivo (headline) + badge
        var $targetLine = $('.chart_target_line_tablero');
        var $badge = $('.target_badge_tablero');
        if (hayObjetivo) {
            $targetLine.css('top', (1 - (t.objetivo / maxY)) * 100 + '%').show();
            $badge.text(fmt(t.objetivo));
        } else {
            $targetLine.hide();
        }
    }

    /* ------------------------------------------------------------------ *
     *  Tabla comparativa (DataTables)
     * ------------------------------------------------------------------ */
    function actualizarInfoTabla() {
        if (!tablaSales) return;
        var info = tablaSales.page.info();
        var texto = info.recordsDisplay === 0
            ? '0 de 0'
            : (info.start + 1) + ' - ' + info.end + ' de ' + info.recordsDisplay +
              (info.recordsDisplay < info.recordsTotal ? ' (' + info.recordsTotal + ')' : '');
        $('#kpi_info_tablero').text(texto);
        $('#kpi_btn_prev_tablero').prop('disabled', info.page === 0);
        $('#kpi_btn_next_tablero').prop('disabled', info.page >= info.pages - 1);
    }

    function renderTabla(datos) {
        var $tbody = $('#tabla_comparativa_body_tablero');
        var filas = datos.tabla || [];

        // Destruir el DataTable ANTES de limpiar (destroy restaura filas previas)
        if (tablaSales) {
            try { tablaSales.destroy(); } catch (e) { /* noop */ }
            tablaSales = null;
        }
        $tbody.empty();

        if (!filas.length) {
            $tbody.append('<tr><td colspan="8" class="empty_tablero">No sales data loaded</td></tr>');
            $('#kpi_info_tablero').text('0 de 0');
            return;
        }

        filas.forEach(function (r) {
            var varTxt = (r.var_pct !== null && r.var_pct !== undefined) ? pctConSigno(r.var_pct) : '—';
            var objTxt = r.objetivo !== null && r.objetivo !== undefined ? fmt(r.objetivo) : '—';
            var gapTxt = r.gap !== null && r.gap !== undefined ? ((r.gap >= 0 ? '+' : '') + fmt(r.gap)) : '—';
            $tbody.append(
                '<tr>' +
                    '<td>' + r.dealer + '</td>' +
                    '<td>' + (ETIQUETAS_TIPO_CORTO[r.tipo] || r.tipo) + '</td>' +
                    '<td>' + fmt(r.actual) + '</td>' +
                    '<td>' + fmt(r.ly) + '</td>' +
                    '<td>' + varTxt + '</td>' +
                    '<td>' + objTxt + '</td>' +
                    '<td>' + gapTxt + '</td>' +
                    '<td></td>' +
                '</tr>'
            );
        });

        tablaSales = $('#tabla_comparativa_tablero').DataTable({
            dom: 'rt',
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [{ targets: [7], orderable: false, searchable: false }],
            language: {
                emptyTable: 'No sales data loaded'
            }
        });
        tablaSales.on('draw', actualizarInfoTabla);
        actualizarInfoTabla();
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
        var $selMes = $('#filtro_mes_tablero');
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

        var $selDealer = $('#filtro_dealer_tablero');
        var $selPais   = $('#filtro_pais_tablero');
        var $selAnio   = $('#filtro_anio_tablero');

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
            $('#filtro_dealer_tablero').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#filtro_pais_tablero').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#filtro_dealer_tablero').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#filtro_pais_tablero').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#filtro_anio_tablero').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#filtro_tipo_tablero').val(estado.vehicleType || 'all');
        $('#filtro_periodo_tablero').val(estado.periodType || 'month');
        $('#filtro_comparacion_tablero').val(estado.comparison || 'none');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/dashboardSales.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('DashboardSales filtros: ' + res.message); return; }
                filtrosDisponibles = res.data;
                renderFiltros();
            },
            error: function (xhr) { console.error('DashboardSales filtros error:', xhr.responseText); }
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
            url: '../controllers/dashboardSales.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action:     'getResumen',
                year:       f.year        || '',
                calendarId: f.calendarId  || '',
                dealerIds:  f.dealerIds   || [],
                countryIds: f.countryIds  || [],
                vehicleType: f.vehicleType || 'all',
                periodType:  f.periodType  || 'month',
                comparison:  f.comparison  || 'none'
            },
            success: function (res) {
                if (seq !== fetchSeq) return;
                if (!res.success) { console.error('DashboardSales: ' + res.message); return; }
                datosActuales = res.data;
                renderTarjetas(datosActuales);
                renderGrafico(datosActuales);
                renderTabla(datosActuales);
            },
            error: function (xhr) { console.error('DashboardSales error:', xhr.responseText); }
        });
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#filtro_dealer_tablero').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#filtro_pais_tablero').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#filtro_tipo_tablero').on('change', function () {
        GlobalFilters.set({ vehicleType: $(this).val() || 'all' });
    });

    $('#filtro_periodo_tablero').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#filtro_anio_tablero').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#filtro_mes_tablero').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#filtro_comparacion_tablero').on('change', function () {
        GlobalFilters.set({ comparison: $(this).val() || 'none' });
    });

    $('#btn_reset_tablero').on('click', function () {
        GlobalFilters.reset();
    });

    // Clic en una barra/período del gráfico -> calendarId (toggle)
    $('#bars_tablero').on('click', '.bar_wrap_tablero', function () {
        var cal = $(this).data('calendarioid');
        if (!cal) return;
        var actual = GlobalFilters.getParam('calendarId');
        GlobalFilters.set({ calendarId: actual === cal ? null : cal });
    });

    // Tooltip al pasar el cursor sobre una barra
    $('#bars_tablero').on('mouseenter', '.bar_wrap_tablero', function () {
        var cal = $(this).data('calendarioid');
        var p = null;
        for (var i = 0; i < serieActual.length; i++) {
            if (serieActual[i].calendarioid === cal) { p = serieActual[i]; break; }
        }
        if (!p) return;
        $('#tt_periodo_tablero').text(p.periodo);
        $('#tt_actual_tablero').text(fmt(p.actual));
        $('#tt_objetivo_tablero').text(p.objetivo !== null && p.objetivo !== undefined ? fmt(p.objetivo) : '—');
        var varTxt = (p.ly != 0) ? (((p.actual - p.ly) / p.ly) * 100).toFixed(1) + '%' : '—';
        $('#tt_var_tablero').text(varTxt).attr('class', String(varTxt).indexOf('-') === 0 ? 'negativo_tablero' : 'positivo_tablero');
        $('#tooltip_tablero').addClass('is_visible_tablero');
    });
    $('#bars_tablero').on('mouseleave', '.bar_wrap_tablero', function () {
        $('#tooltip_tablero').removeClass('is_visible_tablero');
    });

    // Paginación de la tabla
    $('#kpi_btn_prev_tablero').on('click', function () {
        if (tablaSales) tablaSales.page('previous').draw('page');
    });
    $('#kpi_btn_next_tablero').on('click', function () {
        if (tablaSales) tablaSales.page('next').draw('page');
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
