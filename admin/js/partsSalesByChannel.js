/*
|--------------------------------------------------------------------------
| DASHBOARD DE PARTS SALES BY CHANNEL - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de venta de partes por canal (admin/partsSalesByChannel.php).
|
| Endpoint: controllers/partsSalesByChannel.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables (las partes no se segmentan por CV/LCV y la
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
    var CH_STORAGE_KEY = 'ots_psc_canal';
    var CH_URL_PARAM   = 'ch';
    var filtrosDisponibles = null;
    var datosActuales      = null;
    var canalSel           = null; // null = all | clave de canal
    var debounceTimer      = null;
    var fetchSeq           = 0;
    var sincronizandoFiltros = false;
    var select2Inicializado  = false;
    var serieActual          = [];

    var CANALES = [
        { clave: 'workshop',  nombre: 'Workshop',  color: '#0f766e' },
        { clave: 'counter',   nombre: 'Counter',   color: '#d97706' },
        { clave: 'external',  nombre: 'External',  color: '#1e3a5f' },
        { clave: 'fleets',    nombre: 'Fleets',    color: '#3b82f6' },
        { clave: 'insurance', nombre: 'Insurance', color: '#7dd3fc' }
    ];
    var colorDe = {};
    CANALES.forEach(function (c) { colorDe[c.clave] = c.color; });

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

    function fmtPct(n) {
        if (n === null || n === undefined) return '—';
        return (n >= 0 ? '+' : '') + Number(n).toFixed(1) + '%';
    }

    function fmtShare(n) {
        if (n === null || n === undefined) return '—';
        return Number(n).toFixed(1) + '%';
    }

    function leerCanalPersistido() {
        var c = null;
        try {
            var qs = new URLSearchParams(window.location.search);
            if (qs.get(CH_URL_PARAM)) c = qs.get(CH_URL_PARAM);
        } catch (e) { /* noop */ }
        if (!c) {
            try { c = window.localStorage.getItem(CH_STORAGE_KEY); } catch (e) { /* noop */ }
        }
        return c && colorDe[c] ? c : null;
    }

    function guardarCanal() {
        try {
            window.localStorage.setItem(CH_STORAGE_KEY, canalSel || '');
        } catch (e) { /* noop */ }
        try {
            var qs = new URLSearchParams(window.location.search);
            if (canalSel) { qs.set(CH_URL_PARAM, canalSel); } else { qs.delete(CH_URL_PARAM); }
            var ns = qs.toString();
            var nueva = window.location.pathname + (ns ? '?' + ns : '');
            window.history.replaceState({}, '', nueva);
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
        var $selMes = $('#psc_filter_mes');
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

        var $selDealer = $('#psc_filter_dealer');
        var $selPais   = $('#psc_filter_country');
        var $selAnio   = $('#psc_filter_anio');

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
            $('#psc_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#psc_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#psc_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#psc_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#psc_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#psc_filter_periodo').val(estado.periodType || 'month');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/partsSalesByChannel.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('PSC filtros: ' + res.message); return; }
                filtrosDisponibles = res.filtros;
                renderFiltros();
            },
            error: function (xhr) { console.error('PSC filtros error:', xhr.responseText); }
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
            url: '../controllers/partsSalesByChannel.php',
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
                if (!res.success) { console.error('PSC: ' + res.message); return; }
                if (res.sin_periodo || res.sin_datos) {
                    renderVacio();
                    return;
                }
                datosActuales = res;
                serieActual = res.serie || [];
                renderKpis(res);
                renderInfo(res);
                renderBarras(res);
                renderGrafico(res);
                renderCrecimiento(res);
            },
            error: function (xhr) { console.error('PSC error:', xhr.responseText); }
        });
    }

    function renderVacio() {
        $('#psc_kpi_total').text('—');
        $('#psc_kpi_total_sub').text('—');
        $('#psc_kpi_lider').text('—');
        $('#psc_kpi_lider_pct').text('—');
        $('#psc_kpi_crec_name').text('—');
        $('#psc_kpi_crec_val').text('—');
        $('#psc_kpi_growth').removeClass('green red').text('—');
        $('#psc_info_box').hide();
        $('#psc_chart_periodo').text('');
        $('#psc_hbars').html('<div class="psc_estado_vacio">No data loaded</div>');
        $('#psc_y_left').empty();
        $('#psc_y_right').empty();
        $('#psc_grid').empty();
        $('#psc_xaxis').empty();
        $('#psc_svg').empty();
        $('#psc_legend').empty();
        $('#psc_growth_up').html('<div class="psc_estado_vacio">No data loaded</div>');
        $('#psc_growth_down').html('<div class="psc_estado_vacio">No data loaded</div>');
    }

    /* ------------------------------------------------------------------ *
     *  Render: KPI cards
     * ------------------------------------------------------------------ */
    function renderKpis(datos) {
        var r = datos.resumen || {};

        $('#psc_kpi_total').text(fmtMoneda(r.total));
        $('#psc_kpi_total_sub').text('LY ' + fmtMoneda(r.total_ly));

        if (r.canal_lider) {
            $('#psc_kpi_lider').text(r.canal_lider.nombre);
            $('#psc_kpi_lider_pct').text(fmtShare(r.canal_lider.share));
        } else {
            $('#psc_kpi_lider').text('—');
            $('#psc_kpi_lider_pct').text('—');
        }

        if (r.canal_crecimiento) {
            $('#psc_kpi_crec_name').text(r.canal_crecimiento.nombre);
            $('#psc_kpi_crec_val')
                .text(fmtPct(r.canal_crecimiento.var))
                .removeClass('green red')
                .addClass(r.canal_crecimiento.var >= 0 ? 'green' : 'red');
        } else {
            $('#psc_kpi_crec_name').text('—');
            $('#psc_kpi_crec_val').text('—');
        }

        var vt = r.var_total;
        $('#psc_kpi_growth')
            .text(fmtPct(vt))
            .removeClass('green red')
            .addClass(vt === null || vt === undefined ? '' : (vt >= 0 ? 'green' : 'red'));
    }

    /* ------------------------------------------------------------------ *
     *  Render: mensaje de resumen
     * ------------------------------------------------------------------ */
    function renderInfo(datos) {
        var r = datos.resumen || {};
        if (r.total === null || r.total === undefined) {
            $('#psc_info_box').hide();
            return;
        }
        var html = 'Las ventas de partes del período son <strong>' + fmtMoneda(r.total) + '</strong>';
        if (r.canal_lider) {
            html += ', lideradas por <strong>' + escapeHtml(r.canal_lider.nombre) + '</strong> (' + fmtShare(r.canal_lider.share) + ' de participación)';
        }
        if (r.var_total !== null && r.var_total !== undefined) {
            html += '. Frente al mismo período del año anterior varían <strong class="' + (r.var_total >= 0 ? 'psc_var_green' : 'psc_var_red') + '">' + fmtPct(r.var_total) + '</strong>.';
        }
        $('#psc_info_text').html(html);
        $('#psc_info_box').show();
    }

    /* ------------------------------------------------------------------ *
     *  Render: barras horizontales de participación
     * ------------------------------------------------------------------ */
    function renderBarras(datos) {
        var canales = datos.canales || [];
        var $hbars = $('#psc_hbars');
        $hbars.empty();

        // Orden por participación descendente
        canales.sort(function (a, b) { return b.share - a.share; });

        if (!canales.length) {
            $hbars.append('<div class="psc_estado_vacio">No data loaded</div>');
            return;
        }

        canales.forEach(function (c) {
            var activo = !canalSel || canalSel === c.clave;
            var cls = 'psc_hbar_row psc_clickable' + (activo ? '' : ' is_dim');
            $hbars.append(
                '<div class="' + cls + '" data-canal="' + c.clave + '" title="Filtrar por ' + escapeHtml(c.nombre) + '">' +
                    '<div class="psc_hbar_track">' +
                        '<div class="psc_hbar_fill" style="width:' + Math.max(2, c.share) + '%;background:' + c.color + ';">' +
                            '<span class="psc_hbar_inner_name">' + escapeHtml(c.nombre) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<span class="psc_hbar_pct">' + fmtShare(c.share) + ' · ' + fmtMoneda(c.ventas) + '</span>' +
                '</div>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: gráfico de líneas mensual por canal
     * ------------------------------------------------------------------ */
    function renderGrafico(datos) {
        var serie = datos.serie || [];

        var pa = datos.periodo_actual;
        $('#psc_chart_periodo').text(datos.etiqueta_periodo + (pa && pa.mes ? ' · ' + pa.mes + ' ' + pa.ano : ''));

        if (!serie.length) {
            $('#psc_grid').empty();
            $('#psc_xaxis').empty();
            $('#psc_svg').empty();
            $('#psc_y_left').empty();
            $('#psc_y_right').empty();
            $('#psc_legend').empty();
            return;
        }

        // Canales a dibujar (filtro local aplicado)
        var canales = CANALES.filter(function (c) { return !canalSel || canalSel === c.clave; });

        // Escala Y: 0 .. max de todos los canales visibles
        var maxV = 0;
        serie.forEach(function (p) {
            canales.forEach(function (c) {
                var v = p[c.clave] || 0;
                if (v > maxV) maxV = v;
            });
        });
        var top = maxV > 0 ? Math.ceil(maxV / 500000) * 500000 : 500000;

        function pctY(v) { return Math.max(2, Math.min(100, (v / top) * 100)); }

        // Grid
        var $grid = $('#psc_grid').empty();
        [0, 0.25, 0.5, 0.75, 1.0].forEach(function (t) {
            var s = document.createElement('span');
            s.style.bottom = (t * 100) + '%';
            $grid.append(s);
        });

        // Ejes
        var $yL = $('#psc_y_left').empty();
        [top, top * 0.75, top * 0.5, top * 0.25, 0].forEach(function (v) {
            $yL.append('<span>' + fmtMoneda(v) + '</span>');
        });
        var $yR = $('#psc_y_right').empty();
        [top, top * 0.75, top * 0.5, top * 0.25, 0].forEach(function (v) {
            $yR.append('<span>' + fmtMoneda(v) + '</span>');
        });

        // Eje X (meses)
        var $xaxis = $('#psc_xaxis').empty();
        serie.forEach(function (p) {
            $xaxis.append('<span>' + (p.mes ? p.mes.charAt(0) + p.mes.slice(1).toLowerCase() : '') + '</span>');
        });

        // Líneas SVG
        var $svg = $('#psc_svg').empty();
        var n = serie.length;
        function xPos(i) { return n > 1 ? (i / (n - 1)) * 100 : 50; }

        canales.forEach(function (c) {
            var vals = serie.map(function (p) { return p[c.clave] || 0; });
            var pts = vals.map(function (v, i) {
                return xPos(i).toFixed(2) + ',' + (100 - pctY(v)).toFixed(2);
            }).join(' ');

            var poly = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
            poly.setAttribute('points', pts);
            poly.setAttribute('fill', 'none');
            poly.setAttribute('stroke', c.color);
            poly.setAttribute('stroke-width', '1.8');
            poly.setAttribute('stroke-linejoin', 'round');
            poly.setAttribute('stroke-linecap', 'round');
            $svg.append(poly);

            vals.forEach(function (v, i) {
                var cDot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                cDot.setAttribute('cx', xPos(i).toFixed(2));
                cDot.setAttribute('cy', (100 - pctY(v)).toFixed(2));
                cDot.setAttribute('r', '1.6');
                cDot.setAttribute('fill', c.color);
                cDot.setAttribute('stroke', '#fff');
                cDot.setAttribute('stroke-width', '0.8');
                $svg.append(cDot);
            });
        });

        // Leyenda (solo canales visibles)
        var $legend = $('#psc_legend').empty();
        canales.forEach(function (c) {
            $legend.append(
                '<div class="psc_legend_item">' +
                    '<div class="psc_legend_line" style="background:' + c.color + ';"></div>' +
                    '<span>' + escapeHtml(c.nombre) + '</span>' +
                '</div>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: paneles de crecimiento / caída vs año anterior
     * ------------------------------------------------------------------ */
    function renderCrecimiento(datos) {
        var crec = (datos.crecimiento || []).slice();
        var $up = $('#psc_growth_up').empty();
        var $down = $('#psc_growth_down').empty();

        if (!crec.length) {
            $up.append('<div class="psc_estado_vacio">No data loaded</div>');
            $down.append('<div class="psc_estado_vacio">No data loaded</div>');
            return;
        }

        var positivos = crec.filter(function (c) { return c.var >= 0; });
        var negativos = crec.filter(function (c) { return c.var < 0; });

        function fila(c, i, len) {
            var cls = 'psc_channel_row psc_clickable' + (i < len - 1 ? ' has_divider' : '');
            var dir = c.var >= 0 ? 'up' : 'down';
            var flecha = c.var >= 0 ? '▲' : '▼';
            return '<div class="' + cls + '" data-canal="' + c.clave + '" title="Filtrar por ' + escapeHtml(c.nombre) + '">' +
                '<span class="psc_channel_name">' + escapeHtml(c.nombre) + '</span>' +
                '<span class="psc_channel_val ' + dir + '">' + flecha + ' ' + fmtPct(c.var) + '</span>' +
            '</div>';
        }

        var ups = positivos.length ? positivos : crec.slice(0, 3);
        var downs = negativos.length ? negativos : [];

        if (!ups.length) {
            $up.append('<div class="psc_estado_vacio">Sin canales en crecimiento</div>');
        } else {
            ups.forEach(function (c, i) { $up.append(fila(c, i, ups.length)); });
        }

        if (!downs.length) {
            $down.append('<div class="psc_estado_vacio">Sin canales en caída</div>');
        } else {
            downs.forEach(function (c, i) { $down.append(fila(c, i, downs.length)); });
        }
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#psc_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#psc_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#psc_filter_periodo').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#psc_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#psc_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#psc_btn_reset').on('click', function () {
        canalSel = null;
        guardarCanal();
        GlobalFilters.reset();
    });

    // Cross-filtering: clic en un canal (barras o paneles) -> toggle canal local
    $('#psc_hbars').on('click', '.psc_clickable', function () {
        var c = $(this).data('canal');
        toggleCanal(c);
    });
    $('#psc_growth_up').on('click', '.psc_clickable', function () {
        toggleCanal($(this).data('canal'));
    });
    $('#psc_growth_down').on('click', '.psc_clickable', function () {
        toggleCanal($(this).data('canal'));
    });

    function toggleCanal(clave) {
        if (!clave) return;
        canalSel = canalSel === clave ? null : clave;
        guardarCanal();
        if (datosActuales) {
            renderBarras(datosActuales);
            renderGrafico(datosActuales);
            renderCrecimiento(datosActuales);
        }
    }

    /* ------------------------------------------------------------------ *
     *  Inicialización
     * ------------------------------------------------------------------ */
    GlobalFilters.subscribe(function (nuevoEstado, prevEstado, evento) {
        if (evento === 'change') {
            sincronizarFiltrosDesdeStore();
            programarCarga();
        }
    });

    canalSel = leerCanalPersistido();

    cargarFiltros();
    cargarDatos();

})(window);
