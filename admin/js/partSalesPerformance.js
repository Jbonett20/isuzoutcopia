/*
|--------------------------------------------------------------------------
| DASHBOARD DE PERFORMANCE DE VENTA DE PARTES - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de performance de venta de partes
| (admin/partSalesPerformance.php).
|
| Endpoint: controllers/partSalesPerformance.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables en este tablero (las partes no se
       segmentan por CV/LCV, por eso vehicleType queda desactivado). */
    GlobalFilters.setAppliesTo({
        dealer:      true,
        country:     true,
        vehicleType: false,
        period:      true,
        year:        true,
        comparison:  true
    });

    /* ------------------------- estado ------------------------- */
    var CH_STORAGE_KEY = 'ots_psp_canales';
    var CH_URL_PARAM   = 'ch';
    var canalesDisponibles = [];   // [{valor, nombre}]
    var canalesSel          = null; // null = all
    var filtrosDisponibles   = null;
    var datosActuales        = null;
    var debounceTimer        = null;
    var fetchSeq             = 0;
    var sincronizandoFiltros = false;
    var select2Inicializado  = false;
    var tablaPartes          = null;
    var serieActual          = [];

    var ETIQUETA_CANAL = {
        counter:   'Counter',
        workshop:  'Taller',
        external:  'Externo',
        fleets:    'Flotas',
        insurance: 'Insurance'
    };

    var ESTADOS = {
        on:        { label: '✓ Sobre Meta', cls: 'psp_badge_green' },
        near:      { label: '⚠ Cerca Meta', cls: 'psp_badge_amber' },
        under:     { label: '✗ Bajo Meta',   cls: 'psp_badge_red' },
        no_target: { label: 'Sin Objetivo',  cls: 'psp_badge_gray' }
    };

    /* ------------------------- helpers ------------------------- */
    function fmtFull(n) {
        if (n === null || n === undefined) return '—';
        return '$' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 });
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

    function fmtPct(n) {
        if (n === null || n === undefined) return '—';
        return Number(n).toFixed(1) + '%';
    }

    function pctConSigno(n) {
        if (n === null || n === undefined) return '—';
        return (n >= 0 ? '+' : '') + Number(n).toFixed(1) + '%';
    }

    function signo(n) {
        return n >= 0 ? '+' : '';
    }

    /* ------------------------- channel (filtro local persistente) ------------------------- */
    function leerCanalesPersistidos() {
        var canales = null;
        try {
            var qs = new URLSearchParams(window.location.search);
            if (qs.get(CH_URL_PARAM)) {
                canales = qs.get(CH_URL_PARAM).split(',').filter(Boolean);
            }
        } catch (e) { /* noop */ }
        if (canales === null) {
            try {
                var raw = window.localStorage.getItem(CH_STORAGE_KEY);
                if (raw) {
                    canales = JSON.parse(raw);
                }
            } catch (e) { /* noop */ }
        }
        return canales && canales.length ? canales : null;
    }

    function guardarCanales() {
        try {
            window.localStorage.setItem(CH_STORAGE_KEY, JSON.stringify(canalesSel || []));
        } catch (e) { /* noop */ }
        try {
            var qs = new URLSearchParams(window.location.search);
            if (canalesSel && canalesSel.length) {
                qs.set(CH_URL_PARAM, canalesSel.join(','));
            } else {
                qs.delete(CH_URL_PARAM);
            }
            var q = qs.toString();
            window.history.replaceState(null, '', window.location.pathname + (q ? '?' + q : ''));
        } catch (e) { /* noop */ }
    }

    /* ------------------------------------------------------------------ *
     *  Tarjetas KPI (Ventas Actual, Cumplimiento, Gap, Var % vs LY)
     * ------------------------------------------------------------------ */
    function renderTarjetas(datos) {
        var t = datos.totales || {};

        // Ventas Actual
        $('#psp_kpi_actual').text(fmtMoneda(t.actual));
        var $trendActual = $('#psp_kpi_actual_trend');
        if (t.var_pct !== null && t.var_pct !== undefined) {
            $trendActual.text(pctConSigno(t.var_pct) + ' vs LY')
                .removeClass('psp_green psp_red psp_amber')
                .addClass(t.var_pct >= 0 ? 'psp_green' : 'psp_red');
        } else {
            $trendActual.text('sin dato del año anterior').removeClass('psp_green psp_red psp_amber');
        }

        // Cumplimiento vs Objetivo
        var $cum = $('#psp_kpi_cumplimiento');
        var $cumSub = $('#psp_kpi_cumplimiento_sub');
        if (t.tiene_objetivo && t.cumplimiento !== null && t.cumplimiento !== undefined) {
            $cum.text(fmtPct(t.cumplimiento)).removeClass('psp_green psp_red psp_amber psp_muted')
                .addClass(t.cumplimiento >= 100 ? 'psp_green' : (t.cumplimiento >= 95 ? 'psp_amber' : 'psp_red'));
            $cumSub.text(datos.etiqueta_periodo ? 'vs meta · ' + datos.etiqueta_periodo : 'vs objetivo');
        } else {
            $cum.text('Sin objetivo').removeClass('psp_green psp_red psp_amber').addClass('psp_muted');
            $cumSub.text('No hay meta definida para el año');
        }

        // Gap
        var $gap = $('#psp_kpi_gap');
        var $gapSub = $('#psp_kpi_gap_sub');
        if (t.gap !== null && t.gap !== undefined) {
            $gap.text(fmtMoneda(t.gap)).removeClass('psp_green psp_red psp_amber psp_muted')
                .addClass(t.gap >= 0 ? 'psp_green' : 'psp_red');
            $gapSub.text(t.gap >= 0 ? 'Por encima de la meta' : 'Por debajo de la meta');
        } else {
            $gap.text('Sin objetivo').removeClass('psp_green psp_red psp_amber').addClass('psp_muted');
            $gapSub.text('Sin meta para calcular el gap');
        }

        // Var % vs LY
        var $var = $('#psp_kpi_var');
        var $varSub = $('#psp_kpi_var_sub');
        if (t.var_pct !== null && t.var_pct !== undefined) {
            $var.text(pctConSigno(t.var_pct)).removeClass('psp_green psp_red psp_amber psp_muted')
                .addClass(t.var_pct >= 0 ? 'psp_green' : 'psp_red');
            $varSub.text('vs período equivalente LY');
        } else {
            $var.text('—').removeClass('psp_green psp_red psp_amber').addClass('psp_muted');
            $varSub.text('sin dato del año anterior');
        }

        // Delta de comparación (título del gráfico)
        var $delta = $('#psp_delta_comparacion');
        var comp = datos.comparacion;
        if (comp && comp.delta_units !== undefined) {
            var dTxt = fmtMoneda(comp.delta_units);
            var pctD = comp.delta_pct !== null && comp.delta_pct !== undefined ? ' (' + pctConSigno(comp.delta_pct) + ')' : '';
            $delta.html('vs ' + (comp.etiqueta_base || '') + ' <b>' + signo(comp.delta_units) + dTxt + '</b>' + pctD)
                .removeClass('psp_green psp_red')
                .addClass(comp.delta_units >= 0 ? 'psp_green' : 'psp_red')
                .show();
        } else {
            $delta.hide();
        }

        // Título del gráfico con canales activos
        var lblCanal = canalesSel && canalesSel.length
            ? canalesSel.map(function (c) { return ETIQUETA_CANAL[c] || c; }).join(' + ')
            : 'Todos los canales';
        $('#psp_chart_title').text('Ventas de Partes vs Objetivo · ' + lblCanal);
    }

    /* ------------------------------------------------------------------ *
     *  Gráfico: barras actuales + línea objetivo + línea LY (SVG)
     * ------------------------------------------------------------------ */
    function maxRedondo(v) {
        if (v <= 0) return 1;
        var pow = Math.pow(10, Math.floor(Math.log10(v)));
        var n = v / pow;
        var step;
        if (n <= 1) step = 1; else if (n <= 2) step = 2; else if (n <= 5) step = 5; else step = 10;
        return step * pow;
    }

    function renderGrafico(datos) {
        var $bars = $('#psp_bars');
        var $lineSvg = $('#psp_lines_svg');
        var $yLeft = $('#psp_y_left');
        var $grid = $('#psp_chart_grid');
        var serie = datos.serie || [];
        var t = datos.totales || {};
        serieActual = serie;

        $bars.empty();
        $lineSvg.empty();
        $yLeft.empty();
        $grid.empty();

        var hayObjetivo = t.tiene_objetivo && t.objetivo !== null;

        if (!serie.length) {
            $bars.html('<div class="psp_empty">No sales data loaded</div>');
            return;
        }

        // Escala Y (máximo "redondo")
        var maximo = 0;
        serie.forEach(function (p) {
            maximo = Math.max(maximo, p.actual, p.ly, p.objetivo || 0);
        });
        if (hayObjetivo) maximo = Math.max(maximo, t.objetivo);
        var maxY = maxRedondo(maximo);

        var pasos = 5;
        var ticksHtml = '';
        var gridHtml = '';
        for (var i = pasos; i >= 0; i--) {
            ticksHtml += '<span>' + fmtMoneda((maxY * i) / pasos) + '</span>';
            gridHtml += '<span style="bottom:' + (i / pasos) * 100 + '%"></span>';
        }
        $yLeft.html(ticksHtml);
        $grid.html(gridHtml);

        function hPct(valor) { return Math.max(1.5, (valor / maxY) * 100); }

        var selCal = GlobalFilters.getParam('calendarId');

        serie.forEach(function (p) {
            var activo = selCal && String(selCal) === String(p.calendarioid);
            var wrap = $('<div>', {
                class: 'psp_bar_wrap' + (activo ? ' is_active_wrap' : ''),
                'data-calendarioid': p.calendarioid,
                title: p.periodo
            });
            if (p.objetivo !== null && p.objetivo !== undefined) {
                wrap.append($('<div>', { class: 'psp_objetivo_mark', css: { bottom: hPct(p.objetivo) + '%' } }));
            }
            wrap.append(
                $('<div>', { class: 'psp_bar' + (activo ? ' is_active' : ''), css: { height: hPct(p.actual) + '%' } }),
                $('<span class="psp_bar_label">' + (p.periodo || '') + '</span>')
            );
            $bars.append(wrap);
        });

        // Líneas SVG (objetivo sólida, LY punteada)
        var n = serie.length;
        function pt(key, idx) {
            var v = serie[idx][key] || 0;
            return { x: ((idx + 0.5) / n) * 100, y: (1 - (v / maxY)) * 100 };
        }
        // Polilínea continua (LY siempre tiene valor)
        function poly(key) {
            var pts = [];
            for (var i = 0; i < n; i++) {
                var p = pt(key, i);
                pts.push(p.x.toFixed(2) + ',' + p.y.toFixed(2));
            }
            return pts.join(' ');
        }
        // Polilínea por segmentos contiguos donde el valor no es null (evita caídas a 0)
        function polysSkipNull(key) {
            var segments = [];
            var current = null;
            for (var i = 0; i < n; i++) {
                var val = serie[i][key];
                if (val !== null && val !== undefined) {
                    var p = pt(key, i);
                    if (current) {
                        current.pts.push(p);
                    } else {
                        current = { pts: [p] };
                        segments.push(current);
                    }
                } else {
                    current = null;
                }
            }
            return segments.map(function (s) {
                return s.pts.map(function (p) { return p.x.toFixed(2) + ',' + p.y.toFixed(2); }).join(' ');
            });
        }
        var html = '';
        if (hayObjetivo || serie.some(function (p) { return p.objetivo !== null; })) {
            polysSkipNull('objetivo').forEach(function (pts) {
                html += '<polyline class="psp_line_obj" points="' + pts + '"></polyline>';
            });
        }
        html += '<polyline class="psp_line_ly" points="' + poly('ly') + '"></polyline>';
        $lineSvg.attr('viewBox', '0 0 100 100').attr('preserveAspectRatio', 'none').html(html);
    }

    /* ------------------------------------------------------------------ *
     *  Sparkline + badges de tendencia
     * ------------------------------------------------------------------ */
    function renderSparkline(datos) {
        var serie = datos.serie || [];
        var svg = document.getElementById('psp_spark_svg');
        if (!svg) { return; }
        svg.innerHTML = '';

        // badges: % meses al alza / baja / planos vs LY
        var up = 0, dn = 0, flat = 0;
        serie.forEach(function (p) {
            if (p.ly > 0) {
                var v = ((p.actual - p.ly) / p.ly) * 100;
                if (v > 0.5) up++; else if (v < -0.5) dn++; else flat++;
            }
        });
        var total = (up + dn + flat) || 1;
        $('#psp_spark_up').text(Math.round((up / total) * 100) + '%');
        $('#psp_spark_dn').text(Math.round((dn / total) * 100) + '%');
        $('#psp_spark_flat').text(Math.round((flat / total) * 100) + '%');
        $('#psp_spark_x').empty();

        var values = serie.map(function (p) { return p.actual; });
        if (values.length < 2) {
            $('#psp_spark_x').append('<span>' + (serie[0] ? serie[0].periodo : '—') + '</span>');
            return;
        }

        var minV = Math.min.apply(null, values);
        var maxV = Math.max.apply(null, values);
        var rangeV = (maxV - minV) || 1;
        var w = 166, h = 52;

        var pts = values.map(function (v, i) {
            var x = (i / (values.length - 1)) * (w - 10) + 5;
            var y = h - ((v - minV) / rangeV) * (h - 10) - 5;
            return x + ',' + y;
        });
        var pl = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        pl.setAttribute('points', pts.join(' '));
        pl.setAttribute('fill', 'none');
        pl.setAttribute('stroke', '#d1142a');
        pl.setAttribute('stroke-width', '2');
        svg.appendChild(pl);

        values.forEach(function (v, i) {
            var x = (i / (values.length - 1)) * (w - 10) + 5;
            var y = h - ((v - minV) / rangeV) * (h - 10) - 5;
            var c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            c.setAttribute('cx', x); c.setAttribute('cy', y); c.setAttribute('r', '3'); c.setAttribute('fill', '#d1142a');
            svg.appendChild(c);
        });

        var idxs = [0, Math.floor((values.length - 1) / 2), values.length - 1];
        idxs.forEach(function (i) {
            var s = serie[i];
            $('#psp_spark_x').append('<span>' + (s ? s.periodo : '') + '</span>');
        });
    }

    /* ------------------------------------------------------------------ *
     *  Top Dealers (top 5 por ventas)
     * ------------------------------------------------------------------ */
    function renderTopDealers(datos) {
        var $tbody = $('#psp_top_dealers_body');
        var filas = (datos.tabla || []).slice().sort(function (a, b) {
            return b.actual - a.actual;
        }).slice(0, 5);

        $tbody.empty();
        if (!filas.length) {
            $tbody.append('<tr><td colspan="2" class="psp_empty">No sales data loaded</td></tr>');
            return;
        }
        filas.forEach(function (r, i) {
            var rank = i + 1;
            $tbody.append(
                '<tr' + (i === 0 ? ' style="background:#fef9c3;"' : '') + '>' +
                    '<td><span class="psp_rank_badge psp_rank_' + Math.min(rank, 3) + '">' + rank + '</span>' +
                    '&nbsp;' + $('<div>').text(r.dealer).html() + '</td>' +
                    '<td class="psp_td_right">' + fmtMoneda(r.actual) + '</td>' +
                '</tr>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Tabla comparativa (DataTables)
     * ------------------------------------------------------------------ */
    function actualizarInfoTabla() {
        if (!tablaPartes) return;
        var info = tablaPartes.page.info();
        var texto = info.recordsDisplay === 0
            ? '0 de 0'
            : (info.start + 1) + ' - ' + info.end + ' de ' + info.recordsDisplay +
              (info.recordsDisplay < info.recordsTotal ? ' (' + info.recordsTotal + ')' : '');
        $('#psp_table_info').text(texto);
        $('#psp_btn_prev').prop('disabled', info.page === 0);
        $('#psp_btn_next').prop('disabled', info.page >= info.pages - 1);
    }

    function renderTabla(datos) {
        var $tbody = $('#psp_table_body');
        var filas = datos.tabla || [];

        if (tablaPartes) {
            try { tablaPartes.destroy(); } catch (e) { /* noop */ }
            tablaPartes = null;
        }
        $tbody.empty();

        if (!filas.length) {
            $tbody.append('<tr><td colspan="7" class="psp_empty">No sales data loaded</td></tr>');
            $('#psp_table_info').text('0 de 0');
            return;
        }

        filas.forEach(function (r) {
            var cumTxt = r.cumplimiento !== null && r.cumplimiento !== undefined ? fmtPct(r.cumplimiento) : '—';
            var cumCls = r.cumplimiento !== null && r.cumplimiento !== undefined
                ? (r.cumplimiento >= 100 ? 'psp_pct_high' : (r.cumplimiento >= 95 ? 'psp_pct_mid' : 'psp_pct_low'))
                : '';
            var varTxt = r.var_pct !== null && r.var_pct !== undefined ? pctConSigno(r.var_pct) : '—';
            var varCls = r.var_pct !== null && r.var_pct !== undefined
                ? (r.var_pct > 0 ? 'psp_var_up' : (r.var_pct < 0 ? 'psp_var_down' : 'psp_var_zero'))
                : '';
            var objTxt = r.objetivo !== null && r.objetivo !== undefined ? fmtFull(r.objetivo) : '—';
            var est = ESTADOS[r.estado] || ESTADOS.no_target;

            $tbody.append(
                '<tr>' +
                    '<td>' + $('<div>').text(r.dealer).html() + '</td>' +
                    '<td>' + fmtFull(r.actual) + '</td>' +
                    '<td>' + objTxt + '</td>' +
                    '<td class="' + cumCls + '">' + cumTxt + '</td>' +
                    '<td class="' + varCls + '">' + varTxt + '</td>' +
                    '<td><span class="psp_badge ' + est.cls + '">' + est.label + '</span></td>' +
                    '<td></td>' +
                '</tr>'
            );
        });

        tablaPartes = $('#psp_data_table').DataTable({
            dom: 'rt',
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [{ targets: [6], orderable: false, searchable: false }],
            language: { emptyTable: 'No sales data loaded' }
        });
        tablaPartes.on('draw', actualizarInfoTabla);
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
        var $selMes = $('#psp_filter_mes');
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

        var $selDealer = $('#psp_filter_dealer');
        var $selPais   = $('#psp_filter_country');
        var $selAnio   = $('#psp_filter_anio');
        var $selCanal  = $('#psp_filter_channel');

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

        canalesDisponibles = filtrosDisponibles.canales || [];
        $selCanal.empty();
        $.each(canalesDisponibles, function (i, c) {
            $selCanal.append($('<option>', { value: c.valor }).text(c.nombre));
        });

        if (!select2Inicializado && $.fn.select2) {
            $('#psp_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#psp_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            $('#psp_filter_channel').select2({ placeholder: 'All channels', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#psp_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#psp_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#psp_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#psp_filter_periodo').val(estado.periodType || 'month');
        $('#psp_filter_comparacion').val(estado.comparison || 'none');
        if (canalesDisponibles.length) {
            $('#psp_filter_channel').val(canalesSel && canalesSel.length ? canalesSel : null).trigger('change');
        }
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/partSalesPerformance.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('PartSales filtros: ' + res.message); return; }
                filtrosDisponibles = res.data;
                canalesSel = leerCanalesPersistidos();
                renderFiltros();
            },
            error: function (xhr) { console.error('PartSales filtros error:', xhr.responseText); }
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
            url: '../controllers/partSalesPerformance.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action:     'getResumen',
                year:       f.year        || '',
                calendarId: f.calendarId  || '',
                dealerIds:  f.dealerIds   || [],
                countryIds: f.countryIds  || [],
                canales:    canalesSel    || [],
                periodType: f.periodType  || 'month',
                comparison: f.comparison  || 'none'
            },
            success: function (res) {
                if (seq !== fetchSeq) return;
                if (!res.success) { console.error('PartSales: ' + res.message); return; }
                datosActuales = res.data;
                renderTarjetas(datosActuales);
                renderGrafico(datosActuales);
                renderSparkline(datosActuales);
                renderTopDealers(datosActuales);
                renderTabla(datosActuales);
            },
            error: function (xhr) { console.error('PartSales error:', xhr.responseText); }
        });
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#psp_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#psp_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#psp_filter_channel').on('change', function () {
        if (sincronizandoFiltros) return;
        canalesSel = ($(this).val() || []).slice();
        guardarCanales();
        programarCarga();
    });

    $('#psp_filter_periodo').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#psp_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#psp_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#psp_filter_comparacion').on('change', function () {
        GlobalFilters.set({ comparison: $(this).val() || 'none' });
    });

    $('#psp_btn_reset').on('click', function () {
        canalesSel = null;
        guardarCanales();
        GlobalFilters.reset();
    });

    // Clic en una barra/período del gráfico -> calendarId (toggle)
    $('#psp_bars').on('click', '.psp_bar_wrap', function () {
        var cal = $(this).data('calendarioid');
        if (!cal) return;
        var actual = GlobalFilters.getParam('calendarId');
        GlobalFilters.set({ calendarId: actual === cal ? null : cal });
    });

    // Tooltip al pasar el cursor sobre una barra
    $('#psp_bars').on('mouseenter', '.psp_bar_wrap', function () {
        var cal = $(this).data('calendarioid');
        var p = null;
        for (var i = 0; i < serieActual.length; i++) {
            if (String(serieActual[i].calendarioid) === String(cal)) { p = serieActual[i]; break; }
        }
        if (!p) return;
        var $tip = $('#psp_tooltip');
        $tip.find('.psp_tooltip_main').text(fmtMoneda(p.actual));
        var varTxt = (p.ly > 0) ? pctConSigno(((p.actual - p.ly) / p.ly) * 100) : '—';
        $tip.find('.psp_tooltip_sub')
            .text('Objetivo ' + (p.objetivo !== null && p.objetivo !== undefined ? fmtMoneda(p.objetivo) : '—') + ' · ' + varTxt + ' vs LY')
            .attr('class', 'psp_tooltip_sub ' + (p.actual >= (p.objetivo || 0) ? 'psp_up' : 'psp_dn'));
        $tip.addClass('is_visible');

        var area = document.getElementById('psp_bars');
        var areaRect = area.getBoundingClientRect();
        var wrapRect = this.getBoundingClientRect();
        var left = wrapRect.left - areaRect.left + wrapRect.width / 2 - $tip.outerWidth() / 2;
        var top = wrapRect.top - areaRect.top - $tip.outerHeight() - 6;
        $tip.css({ left: Math.max(0, left) + 'px', top: Math.max(0, top) + 'px' });
    });
    $('#psp_bars').on('mouseleave', '.psp_bar_wrap', function () {
        $('#psp_tooltip').removeClass('is_visible');
    });

    // Paginación de la tabla
    $('#psp_btn_prev').on('click', function () {
        if (tablaPartes) tablaPartes.page('previous').draw('page');
    });
    $('#psp_btn_next').on('click', function () {
        if (tablaPartes) tablaPartes.page('next').draw('page');
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
