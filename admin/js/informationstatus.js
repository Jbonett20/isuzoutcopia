/*
|--------------------------------------------------------------------------
| DASHBOARD DE INFORMATION STATUS - Render y cross-filtering
|--------------------------------------------------------------------------
| Consume el store compartido GlobalFilters (admin/js/global_filters.js)
| para el tablero de cumplimiento de carga (admin/informationstatus.php).
|
| Endpoint: controllers/informationstatus.php (getResumen, getFiltros)
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var $ = window.jQuery;
    if (!window.GlobalFilters || !$) {
        return;
    }

    /* Filtros globales aplicables en este tablero (el cumplimiento de
       carga no se segmenta por CV/LCV, por eso vehicleType queda off). */
    GlobalFilters.setAppliesTo({
        dealer:      true,
        country:     true,
        vehicleType: false,
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

    var ETIQUETAS_ESTADO = {
        aldia:     'Al Día',
        pendiente: 'Pendiente',
        critico:   'Crítico',
        inactivo:  'Inactivo'
    };

    var CAT_ORDEN = ['parts', 'sales', 'service', 'inventory'];

    /* ------------------------- helpers ------------------------- */
    function escapeHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function fmtPct(n) {
        if (n === null || n === undefined) return '—';
        return Number(n) + '%';
    }

    function pctConSigno(n) {
        if (n === null || n === undefined) return '—';
        return (n >= 0 ? '+' : '') + Number(n) + 'pp';
    }

    /* Clase de barra según el % de cumplimiento de un dealer */
    function claseBarchart(pct) {
        if (pct >= 80) return 'barchart_verde_informationstatus';
        if (pct >= 60) return 'barchart_verde_claro_informationstatus';
        if (pct >= 40) return 'barchart_amarillo_informationstatus';
        if (pct > 0)   return 'barchart_rojo_informationstatus';
        return 'barchart_rojo_oscuro_informationstatus';
    }

    /* Clase de celda % según el pct de una categoría para un dealer */
    function clasePct(pct) {
        if (pct >= 80) return 'pct_verde_informationstatus';
        if (pct >= 50) return 'pct_amarillo_informationstatus';
        return 'pct_rojo_informationstatus';
    }

    /* Clase del Overall según el estado general del dealer */
    function claseOverall(estado) {
        switch (estado) {
            case 'aldia':     return 'overall_aldia_informationstatus';
            case 'pendiente': return 'overall_critico_amarillo_informationstatus';
            case 'critico':   return 'overall_critico_informationstatus';
            default:          return 'overall_critico_rojo_informationstatus';
        }
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
        var $selMes = $('#is_filter_mes');
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

        var $selDealer = $('#is_filter_dealer');
        var $selPais   = $('#is_filter_country');
        var $selAnio   = $('#is_filter_anio');

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
            $('#is_filter_dealer').select2({ placeholder: 'All dealers', allowClear: true, width: '100%' });
            $('#is_filter_country').select2({ placeholder: 'All countries', allowClear: true, width: '100%' });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) return;
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#is_filter_dealer').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#is_filter_country').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#is_filter_anio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#is_filter_periodo').val(estado.periodType || 'month');
        $('#is_filter_comparacion').val(estado.comparison || 'none');
        sincronizandoFiltros = false;
    }

    function cargarFiltros() {
        $.ajax({
            url: '../controllers/informationstatus.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) { console.error('InformationStatus filtros: ' + res.message); return; }
                filtrosDisponibles = res.filtros;
                renderFiltros();
            },
            error: function (xhr) { console.error('InformationStatus filtros error:', xhr.responseText); }
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
            url: '../controllers/informationstatus.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action:     'getResumen',
                year:       f.year        || '',
                calendarId: f.calendarId  || '',
                dealerIds:  f.dealerIds   || [],
                countryIds: f.countryIds  || [],
                periodType: f.periodType  || 'month',
                comparison: f.comparison  || 'none'
            },
            success: function (res) {
                if (seq !== fetchSeq) return;
                if (!res.success) { console.error('InformationStatus: ' + res.message); return; }
                if (res.sin_periodo) {
                    renderVacio();
                    return;
                }
                datosActuales = res;
                renderResumen(res);
                renderKpiCards(res);
                renderBarchart(res);
                renderCriticos(res);
                renderTabla(res);
            },
            error: function (xhr) { console.error('InformationStatus error:', xhr.responseText); }
        });
    }

    function renderVacio() {
        $('#is_resumen_pct').text('—');
        $('#is_resumen_delta').hide();
        $('#is_badge_aldia').text('—');
        $('#is_badge_pendiente').text('—');
        $('#is_badge_critico').text('—');
        $('#is_kpi_grid').html('<div class="is_estado_vacio">No data loaded</div>');
        $('#is_barchart').html('<div class="is_estado_vacio">No data loaded</div>');
        $('#is_kpicriticos_list').html('<div class="is_estado_vacio">No critical KPIs</div>');
        $('#is_kpicriticos_list_bottom').html('<div class="is_estado_vacio">No critical KPIs</div>');
        $('#is_tabla_body').html('<tr><td colspan="6" class="is_estado_vacio">No data loaded</td></tr>');
        $('#is_chart_periodo').text('');
    }

    /* ------------------------------------------------------------------ *
     *  Render: resumen
     * ------------------------------------------------------------------ */
    function renderResumen(datos) {
        var r = datos.resumen || {};
        $('#is_resumen_pct').text(fmtPct(r.cumplimiento));
        var c = r.conteos || {};
        $('#is_badge_aldia').text(c.aldia != null ? c.aldia : '—');
        $('#is_badge_pendiente').text(c.pendiente != null ? c.pendiente : '—');
        $('#is_badge_critico').text(c.criticos_total != null ? c.criticos_total : '—');

        var cmp = r.comparacion;
        var $delta = $('#is_resumen_delta');
        if (cmp && cmp.delta_pp != null && cmp.delta_pp !== undefined) {
            var d = cmp.delta_pp;
            $delta
                .text(pctConSigno(d) + ' vs ' + (cmp.etiqueta_base || 'base'))
                .attr('class', 'resumen_delta_informationstatus ' + (d >= 0 ? 'resumen_delta_up_informationstatus' : 'resumen_delta_down_informationstatus'))
                .show();
        } else {
            $delta.hide();
        }
    }

    /* ------------------------------------------------------------------ *
     *  Render: tarjetas por categoría (mini-barras mensuales)
     * ------------------------------------------------------------------ */
    function renderKpiCards(datos) {
        var cats = datos.categorias || [];
        var $grid = $('#is_kpi_grid');
        $grid.empty();

        // Orden fijo: parts, sales, service, inventory
        cats.sort(function (a, b) {
            var ia = CAT_ORDEN.indexOf(a.clave);
            var ib = CAT_ORDEN.indexOf(b.clave);
            return (ia < 0 ? 99 : ia) - (ib < 0 ? 99 : ib);
        });

        var cmpCats = {};
        var cmp = (datos.resumen && datos.resumen.comparacion) || null;
        if (cmp && cmp.categorias) {
            $.each(cmp.categorias, function (i, c) { cmpCats[c.clave] = c.delta_pp; });
        }

        $.each(cats, function (i, cat) {
            var serie = cat.serie || [];
            var bars = '';
            $.each(serie, function (j, m) {
                var h = Math.max(2, Math.min(100, m.porcentaje || 0));
                var op = serie.length > 1 ? (0.45 + 0.55 * ((j + 1) / serie.length)) : 0.9;
                bars += '<span class="bar_informationstatus" style="height:' + h + '%;opacity:' + op.toFixed(2) + '"></span>';
            });
            if (!bars) bars = '<span class="bar_informationstatus" style="height:2%;opacity:.5"></span>';

            var delta = cmpCats[cat.clave];
            var deltaHtml = '';
            if (delta != null && delta !== undefined) {
                deltaHtml = '<span class="is_kpi_delta ' + (delta >= 0 ? 'is_kpi_delta_up' : 'is_kpi_delta_down') + '">' +
                    pctConSigno(delta) + '</span>';
            }

            $grid.append(
                '<div class="kpi_card_informationstatus">' +
                    '<div class="kpi_label_bar_informationstatus">' +
                        '<span class="kpi_label_informationstatus">' +
                            '<span class="kpi_dot_informationstatus kpi_dot_' + cat.clave + '_informationstatus"></span>' +
                            '<span class="kpi_text_informationstatus">' + escapeHtml(cat.nombre) + ' Reporting</span>' +
                        '</span>' +
                    '</div>' +
                    '<div class="kpi_content_informationstatus">' +
                        '<div class="kpi_chart_informationstatus"><div class="kpi_bars_informationstatus">' + bars + '</div></div>' +
                        '<div class="is_kpi_meta">' +
                            '<p class="kpi_percent_informationstatus">' + fmtPct(cat.porcentaje) + '</p>' +
                            deltaHtml +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: barchart por dealer
     * ------------------------------------------------------------------ */
    function renderBarchart(datos) {
        var dealers = datos.dealers || [];
        var $chart = $('#is_barchart');
        $chart.empty();

        var pa = datos.resumen && datos.resumen.periodo_actual;
        var etiqueta = (datos.resumen && datos.resumen.etiqueta_periodo) || '';
        $('#is_chart_periodo').text(etiqueta + (pa && pa.mes ? ' · ' + pa.mes + ' ' + pa.ano : ''));

        if (!dealers.length) {
            $chart.append('<div class="is_estado_vacio">No data loaded</div>');
            return;
        }

        $.each(dealers, function (i, d) {
            $chart.append(
                '<div class="barchart_row_informationstatus is_clickable" data-dealerid="' + d.dealerid + '" title="Filtrar por este dealer">' +
                    '<span class="barchart_label_informationstatus">' + escapeHtml(d.nombre) + '</span>' +
                    '<div class="barchart_bar_wrap_informationstatus">' +
                        '<div class="barchart_bar_informationstatus ' + claseBarchart(d.porcentaje) + '" style="width:' + Math.min(100, Math.max(2, d.porcentaje)) + '%"></div>' +
                        '<span class="barchart_val_informationstatus">' + fmtPct(d.porcentaje) + '</span>' +
                    '</div>' +
                '</div>'
            );
        });

        $chart.append(
            '<div class="barchart_eje_informationstatus">' +
                '<span>0%</span><span>10%</span><span>20%</span><span>30%</span><span>40%</span><span>50%</span>' +
                '<span>60%</span><span>70%</span><span>80%</span><span>90%</span><span>100%</span>' +
            '</div>'
        );
    }

    /* ------------------------------------------------------------------ *
     *  Render: KPIs críticos (top + bottom)
     * ------------------------------------------------------------------ */
    function renderCriticos(datos) {
        var criticos = datos.kpisCriticos || [];
        var $top = $('#is_kpicriticos_list');
        var $bottom = $('#is_kpicriticos_list_bottom');
        $top.empty();
        $bottom.empty();

        if (!criticos.length) {
            $top.append('<div class="is_estado_vacio">No critical KPIs</div>');
            $bottom.append('<div class="is_estado_vacio">No critical KPIs</div>');
            return;
        }

        $.each(criticos, function (i, k) {
            var estadoTxt = ETIQUETAS_ESTADO[k.estado] || k.estado;
            $top.append(
                '<div class="kpicriticos_item_informationstatus is_clickable" data-dealerid="' + k.dealerid + '" title="Filtrar por este dealer">' +
                    '<span class="kpicriticos_dealer_informationstatus">' + escapeHtml(k.dealer) + '</span>' +
                    '<span class="kpicriticos_pct_informationstatus critico_text_informationstatus">' + fmtPct(k.pct) + '</span>' +
                    '<span class="kpicriticos_estado_informationstatus">' + escapeHtml(estadoTxt) + '</span>' +
                '</div>'
            );
            $bottom.append(
                '<div class="kpicriticos_item_informationstatus kpicriticos_item_detalle_informationstatus is_clickable" data-dealerid="' + k.dealerid + '" title="Filtrar por este dealer">' +
                    '<span class="kpicriticos_dealer_informationstatus">' + escapeHtml(k.dealer) + '</span>' +
                    '<span class="kpicriticos_kpi_informationstatus">' + escapeHtml(k.categoria) + '</span>' +
                    '<span class="kpicriticos_sub_informationstatus">' + escapeHtml(estadoTxt) + '</span>' +
                    '<span class="kpicriticos_badge_critico_informationstatus">' + escapeHtml(estadoTxt) + '</span>' +
                '</div>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Render: tabla de detalle por dealer
     * ------------------------------------------------------------------ */
    function renderTabla(datos) {
        var dealers = datos.dealers || [];
        var $tbody = $('#is_tabla_body');
        $tbody.empty();

        if (!dealers.length) {
            $tbody.append('<tr><td colspan="6" class="is_estado_vacio">No data loaded</td></tr>');
            return;
        }

        $.each(dealers, function (i, d) {
            var celdas = '';
            $.each(CAT_ORDEN, function (j, clave) {
                var cat = d.categorias[clave];
                if (!cat) { celdas += '<td>—</td>'; return; }
                var estado = cat.estado;
                var pct = cat.pct != null ? cat.pct : 0;

                if (estado === 'aldia') {
                    celdas += '<td><span class="badge_tabla_informationstatus badge_aldia_informationstatus">Al Día</span></td>';
                } else if (estado === 'inactivo') {
                    celdas += '<td><span class="badge_tabla_informationstatus badge_critico_rojo_informationstatus">Inactivo</span></td>';
                } else if (estado === 'critico' && pct === 0) {
                    celdas += '<td><span class="badge_tabla_informationstatus badge_critico_informationstatus">Crítico</span></td>';
                } else {
                    celdas += '<td><span class="pct_tabla_informationstatus ' + clasePct(pct) + '">' + fmtPct(pct) + '</span></td>';
                }
            });

            var overallTxt = ETIQUETAS_ESTADO[d.estado] || d.estado;
            $tbody.append(
                '<tr class="is_clickable" data-dealerid="' + d.dealerid + '" title="Filtrar por este dealer">' +
                    '<td><strong>' + escapeHtml(d.nombre) + '</strong></td>' +
                    celdas +
                    '<td><span class="overall_informationstatus ' + claseOverall(d.estado) + '">&#9650; ' + escapeHtml(overallTxt) + '</span></td>' +
                '</tr>'
            );
        });
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros y cross-filtering
     * ------------------------------------------------------------------ */
    $('#is_filter_dealer').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#is_filter_country').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#is_filter_periodo').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#is_filter_anio').on('change', function () {
        var v = $(this).val();
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#is_filter_mes').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#is_filter_comparacion').on('change', function () {
        GlobalFilters.set({ comparison: $(this).val() || 'none' });
    });

    $('#is_btn_reset').on('click', function () {
        GlobalFilters.reset();
    });

    // Cross-filtering: clic en dealer (barchart / tabla / críticos) -> dealerIds (toggle)
    function filtrarPorDealer($el) {
        var id = $el.data('dealerid');
        if (!id) return;
        var actual = GlobalFilters.get().dealerIds || [];
        var mismo = actual.length === 1 && String(actual[0]) === String(id);
        GlobalFilters.set({ dealerIds: mismo ? [] : [Number(id)] });
    }

    $('#is_barchart').on('click', '.is_clickable', function () { filtrarPorDealer($(this)); });
    $('#is_tabla_body').on('click', 'tr.is_clickable', function () { filtrarPorDealer($(this)); });
    $('#is_kpicriticos_list').on('click', '.is_clickable', function () { filtrarPorDealer($(this)); });
    $('#is_kpicriticos_list_bottom').on('click', '.is_clickable', function () { filtrarPorDealer($(this)); });

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
