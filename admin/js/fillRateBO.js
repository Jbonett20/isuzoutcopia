/*
|--------------------------------------------------------------------------
| DASHBOARD DE FILL RATE & B/O - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de nivel de servicio en la entrega de repuestos
| (admin/fillRateBO.php).
|
| Endpoint: controllers/fillRateBO.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables (el nivel de servicio no se segmenta por
       CV/LCV y no usa comparación). */
    GlobalFilters.setAppliesTo({
        dealer:      true,
        country:     true,
        vehicleType: false,
        period:      true,
        year:        true,
        comparison:  false
    });

    /* ------------------------- estado ------------------------- */
    var filtrosDisponibles = null;
    var datosActuales      = null;
    var debounceTimer      = null;
    var fetchSeq           = 0;
    var sincronizandoFiltros = false;
    var select2Inicializado  = false;

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
        return Number(n).toFixed(1) + '%';
    }

    function fmtGap(n) {
        if (n === null || n === undefined) return '—';
        var v = Number(n);
        return (v > 0 ? '+' : '') + v.toFixed(1) + ' pts';
    }

    function fmtEnt(n) {
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
        var $selMes = $('#fr_filter_mes');
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

        var $selDealer = $('#fr_filter_dealer');
        var $selPais   = $('#fr_filter_country');
        var $selAnio   = $('#fr_filter_anio');

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
            $('#fr_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#fr_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#fr_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#fr_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#fr_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#fr_filter_periodo').val(estado.periodType || 'month');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/fillRateBO.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('FR filtros: ' + res.message); return; }
                filtrosDisponibles = res.filtros;
                renderFiltros();
            },
            error: function (xhr) { console.error('FR filtros error:', xhr.responseText); }
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
            url: '../controllers/fillRateBO.php',
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
                if (!res.success) { console.error('FR: ' + res.message); return; }
                if (res.sin_periodo || res.sin_datos) {
                    renderVacio();
                    return;
                }
                datosActuales = res;
                renderKpis(res);
                renderFrChart(res);
                renderBoChart(res);
                renderBoPanel(res);
                renderDealers(res);
            },
            error: function (xhr) { console.error('FR error:', xhr.responseText); }
        });
    }

    function renderVacio() {
        $('#fr_kpi_fillrate').text('—');
        $('#fr_kpi_target').text('—');
        $('#fr_kpi_gap').text('—');
        $('#fr_kpi_bo').text('—');
        $('#fr_kpi_riesgo').text('—');
        $('#fr_fr_vacio').show();
        $('#fr_bo_vacio').show();
        $('#fr_bo2_vacio').show();
        $('#fr_fr_svg').empty();
        $('#fr_yticks').empty();
        $('#fr_yticks_right').empty();
        $('#fr_fr_grid').empty();
        $('#fr_fr_xaxis').empty();
        $('#fr_bo_bars').empty();
        $('#fr_bo_grid').empty();
        $('#fr_bo_xaxis').empty();
        $('#fr_bo_yticks').empty();
        $('#fr_bo2_bars').empty();
        $('#fr_bo2_grid').empty();
        $('#fr_bo2_xaxis').empty();
        $('#fr_bo2_yticks').empty();
        $('#fr_br_riesgo').text('—');
        $('#fr_br_alert').empty();
        $('#fr_br_trend').empty();
        $('#fr_dealers_tbody').html('<tr><td colspan="7" class="fr_estado_vacio">No data loaded</td></tr>');
        $('#fr_dealers_meta').text('—');
    }

    /* ------------------------------------------------------------------ *
     *  Render: resumen ejecutivo (KPI cards)
     * ------------------------------------------------------------------ */
    function renderKpis(datos) {
        var k = datos.kpis || {};

        $('#fr_kpi_fillrate').text(fmtPct(k.fill_rate));
        var frSub = k.dealers_con_datos !== undefined
            ? k.dealers_con_datos + ' dealer' + (k.dealers_con_datos === 1 ? '' : 's') + ' con datos'
            : '';
        $('#fr_kpi_fr_sub').text(frSub ? frSub : '');

        $('#fr_kpi_target').text(fmtPct(k.target));
        $('#fr_kpi_target_sub').text('Meta de nivel de servicio');

        var gapTxt = fmtGap(k.gap);
        var gapOk = k.gap !== null && k.gap >= 0;
        $('#fr_kpi_gap').text(gapTxt).removeClass('red green').addClass(gapOk ? 'green' : 'red');

        $('#fr_kpi_bo').text(fmtMoneda(k.back_orders));
        $('#fr_kpi_bo_sub').text((k.back_order_lines !== undefined ? fmtEnt(k.back_order_lines) + ' líneas' : ''));

        $('#fr_kpi_riesgo').text(fmtMoneda(k.ventas_en_riesgo));

        $('#fr_target_badge').html('Target ' + fmtPct(k.target) + ' &#9660;');
    }

    /* ------------------------------------------------------------------ *
     *  Render: tendencia Fill Rate vs Target (SVG)
     * ------------------------------------------------------------------ */
    function renderFrChart(datos) {
        var serie = datos.serie_fillrate || [];
        var target = Number((datos.kpis && datos.kpis.target) || 97);
        var $svg = $('#fr_fr_svg');
        var $grid = $('#fr_fr_grid');
        var $x = $('#fr_fr_xaxis');
        var $yL = $('#fr_yticks');
        var $yR = $('#fr_yticks_right');

        $svg.empty(); $grid.empty(); $x.empty(); $yL.empty(); $yR.empty();

        if (!serie.length) {
            $('#fr_fr_vacio').show();
            return;
        }
        $('#fr_fr_vacio').hide();

        var vals = serie.map(function (p) { return Number(p.valor); });
        vals.push(target);
        var minV = Math.min.apply(null, vals);
        var maxV = Math.max.apply(null, vals);
        var yMin = Math.max(0, Math.floor(minV - 2));
        var yMax = Math.min(100, Math.ceil(maxV + 2));
        if (yMax - yMin < 10) yMax = yMin + 10;

        function ypos(v) { return (1 - (v - yMin) / (yMax - yMin)) * 100; }
        function xpos(i) { return serie.length > 1 ? (i / (serie.length - 1)) * 100 : 50; }

        var ns = 'http://www.w3.org/2000/svg';
        function el(tag, attrs) {
            var e = document.createElementNS(ns, tag);
            for (var k in attrs) e.setAttribute(k, attrs[k]);
            return e;
        }

        // Ticks izquierda/derecha
        var ticks = [yMax, target, yMin].filter(function (v, i, a) { return a.indexOf(v) === i; });
        ticks.forEach(function (v) {
            var s = document.createElement('span');
            s.textContent = v + '%';
            s.style.top = ypos(v) + '%';
            $yL.append(s);
            var s2 = document.createElement('span');
            s2.textContent = v + '%';
            s2.style.top = ypos(v) + '%';
            $yR.append(s2);
        });

        // Grid
        ticks.forEach(function (v) {
            var s = document.createElement('span');
            s.style.top = ypos(v) + '%';
            s.style.position = 'absolute';
            s.style.width = '100%';
            s.style.borderTop = '1px solid #f0f0f0';
            s.style.display = 'block';
            $grid.append(s);
        });

        // X labels
        serie.forEach(function (p) {
            var s = document.createElement('span');
            s.textContent = p.mes ? p.mes.slice(0, 3) : p.mesnum;
            $x.append(s);
        });

        // Banda verde 100 -> target
        if (target > yMin && target <= 100) {
            var defs = el('defs', {});
            var grad = el('linearGradient', { id: 'frBandGrad', x1: '0', y1: '0', x2: '0', y2: '1' });
            var stop1 = el('stop', { offset: '0%', 'stop-color': '#bbf7d0', 'stop-opacity': '0.30' });
            var stop2 = el('stop', { offset: '100%', 'stop-color': '#16a34a', 'stop-opacity': '0.55' });
            grad.appendChild(stop1); grad.appendChild(stop2);
            defs.appendChild(grad);
            $svg.append(defs);
            $svg.append(el('rect', {
                x: '0', y: ypos(100), width: '100',
                height: Math.max(0, ypos(target) - ypos(100)),
                fill: 'url(#frBandGrad)'
            }));
        }

        // Línea target
        var tPts = serie.map(function (p, i) { return xpos(i) + ',' + ypos(target); }).join(' ');
        $svg.append(el('polyline', { points: tPts, fill: 'none', stroke: '#16a34a', 'stroke-width': '2', 'vector-effect': 'non-scaling-stroke' }));

        // Línea actual + puntos
        var aPts = serie.map(function (p, i) { return xpos(i) + ',' + ypos(Number(p.valor)); }).join(' ');
        $svg.append(el('polyline', { points: aPts, fill: 'none', stroke: '#991b1b', 'stroke-width': '2', 'vector-effect': 'non-scaling-stroke' }));
        serie.forEach(function (p, i) {
            $svg.append(el('circle', {
                cx: xpos(i), cy: ypos(Number(p.valor)), r: '1.6', fill: '#991b1b', 'vector-effect': 'non-scaling-stroke'
            }));
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: evolución de Back Orders (barras)
     *  dibujarBo(prefix, serie) dibuja en los contenedores {prefix}_bars/_grid/_xaxis/_yticks/_vacio
     * ------------------------------------------------------------------ */
    function dibujarBo(prefix, serie) {
        var $bars  = $('#' + prefix + '_bars');
        var $grid  = $('#' + prefix + '_grid');
        var $x     = $('#' + prefix + '_xaxis');
        var $y     = $('#' + prefix + '_yticks');
        var $vacio = $('#' + prefix + '_vacio');

        $bars.empty(); $grid.empty(); $x.empty(); $y.empty();

        if (!serie.length) {
            if ($vacio.length) $vacio.show();
            return;
        }
        if ($vacio.length) $vacio.hide();

        var maxV = 0;
        serie.forEach(function (p) { if (Number(p.valor) > maxV) maxV = Number(p.valor); });
        if (maxV <= 0) maxV = 1;

        // Ticks del eje Y (0..max, 4 niveles)
        [0, 0.33, 0.66, 1].forEach(function (f) {
            var s = document.createElement('span');
            s.textContent = fmtMoneda(maxV * f);
            $y.append(s);
            var g = document.createElement('span');
            $grid.append(g);
        });

        // X labels + barras
        serie.forEach(function (p) {
            var s = document.createElement('span');
            s.textContent = p.mes ? p.mes.slice(0, 3) : p.mesnum;
            $x.append(s);

            var grp = document.createElement('div');
            grp.className = 'fr_bo_bar_group';
            var bar = document.createElement('div');
            bar.className = 'fr_bo_bar';
            bar.style.height = Math.max(2, (Number(p.valor) / maxV) * 100) + '%';
            bar.title = p.mes + ': ' + fmtMoneda(p.valor);
            grp.appendChild(bar);
            $bars.append(grp);
        });
    }

    function renderBoChart(datos) {
        var serie = datos.serie_backorders || [];
        dibujarBo('fr_bo', serie);
        dibujarBo('fr_bo2', serie);
    }

    /* ------------------------------------------------------------------ *
     *  Render: panel de Ventas en Riesgo / alerta / tendencia
     * ------------------------------------------------------------------ */
    function renderBoPanel(datos) {
        var k = datos.kpis || {};
        var frSerie = datos.serie_fillrate || [];
        var boSerie = datos.serie_backorders || [];

        $('#fr_br_riesgo').text(fmtMoneda(k.ventas_en_riesgo));

        // Alerta: tendencia negativa del fill rate 2 meses consecutivos,
        // o nivel de servicio bajo/sobre el objetivo.
        var alarma = '';
        if (frSerie.length >= 3) {
            var n = frSerie.length;
            var d1 = Number(frSerie[n - 1].valor) - Number(frSerie[n - 2].valor);
            var d2 = Number(frSerie[n - 2].valor) - Number(frSerie[n - 3].valor);
            if (d1 < 0 && d2 < 0) {
                alarma = '&#9888; Tendencia negativa 2 meses consecutivos.';
            }
        }
        if (!alarma) {
            if (k.gap !== null && k.gap < 0) {
                alarma = '&#9888; Nivel de servicio bajo el objetivo (' + fmtGap(k.gap) + ').';
            } else if (k.gap !== null) {
                alarma = '&#10003; Nivel de servicio sobre el objetivo.';
            }
        }
        var $alert = $('#fr_br_alert');
        if (alarma) {
            $alert.html(alarma);
            $alert.css('color', alarma.indexOf('&#10003;') === 0 ? 'var(--fr_green)' : 'var(--fr_red)');
        } else {
            $alert.empty();
        }

        // Tendencia de Back Orders (últimos 2 meses)
        var tend = '';
        var dotColor = '#9ca3af';
        if (boSerie.length >= 2) {
            var a = Number(boSerie[boSerie.length - 2].valor);
            var b = Number(boSerie[boSerie.length - 1].valor);
            if (b > a) {
                tend = '&#9650; ' + fmtMoneda(b - a);
                dotColor = 'var(--fr_red)';   // B/O subiendo = riesgo
            } else if (b < a) {
                tend = '&#9660; ' + fmtMoneda(a - b);
                dotColor = 'var(--fr_green)'; // B/O bajando = mejora
            } else {
                tend = '&#9679; Estable';
            }
        }
        $('#fr_br_trend').html(
            '<span class="fr_br_dot" style="color:' + dotColor + ';">&#9679;</span> Tendencia ' + tend
        );
    }

    /* ------------------------------------------------------------------ *
     *  Render: desglose por dealer
     * ------------------------------------------------------------------ */
    function renderDealers(datos) {
        var dealers = datos.dealers || [];
        var $tbody = $('#fr_dealers_tbody');
        $tbody.empty();

        var meta = dealers.length + ' dealer' + (dealers.length === 1 ? '' : 's');
        var conDatos = 0;
        dealers.forEach(function (d) { if (d.estado !== 'sin_datos') conDatos++; });
        $('#fr_dealers_meta').text(meta + ' · ' + conDatos + ' con datos');

        if (!dealers.length) {
            $tbody.append('<tr><td colspan="7" class="fr_estado_vacio">No data loaded</td></tr>');
            return;
        }

        dealers.forEach(function (d) {
            var gap = d.gap === null || d.gap === undefined ? '—' : fmtGap(d.gap);
            var gapCls = d.gap !== null && d.gap >= 0 ? 'fr_txt_green' : 'fr_txt_red';
            var estado = estadoInfo(d.estado);
            $tbody.append(
                '<tr class="fr_clickable" data-dealerid="' + d.dealerid + '" title="Filtrar por este dealer">' +
                    '<td class="fr_dealer_name">' + escapeHtml(d.name) + '</td>' +
                    '<td>' + fmtPct(d.fill_rate) + '</td>' +
                    '<td>' + fmtPct(d.target) + '</td>' +
                    '<td class="' + gapCls + '">' + gap + '</td>' +
                    '<td>' + fmtMoneda(d.back_orders) + '</td>' +
                    '<td>' + fmtMoneda(d.ventas_en_riesgo) + '</td>' +
                    '<td><span class="fr_badge ' + estado.cls + '">' + escapeHtml(estado.label) + '</span></td>' +
                '</tr>'
            );
        });
    }

    function estadoInfo(estado) {
        switch (estado) {
            case 'sobre':    return { cls: 'fr_badge_green', label: 'Sobre objetivo' };
            case 'atencion': return { cls: 'fr_badge_amber', label: 'Atención' };
            case 'bajo':     return { cls: 'fr_badge_red',   label: 'Bajo objetivo' };
            default:         return { cls: 'fr_badge_gray',  label: 'Sin datos' };
        }
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#fr_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#fr_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#fr_filter_periodo').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#fr_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#fr_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#fr_btn_reset').on('click', function () {
        GlobalFilters.reset();
    });

    // Cross-filtering: clic en fila del desglose -> dealerIds (toggle)
    function filtrarPorDealer($el) {
        var id = $el.data('dealerid');
        if (!id) return;
        var actual = GlobalFilters.get().dealerIds || [];
        var mismo = actual.length === 1 && String(actual[0]) === String(id);
        GlobalFilters.set({ dealerIds: mismo ? [] : [Number(id)] });
    }

    $('#fr_dealers_tbody').on('click', 'tr.fr_clickable', function () { filtrarPorDealer($(this)); });

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
