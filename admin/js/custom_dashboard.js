/*
|--------------------------------------------------------------------------
| DASHBOARD - Lógica de presentación (view)
|--------------------------------------------------------------------------
| Consume el endpoint controllers/dashboard.php (action=getResumen)
| y rellena de forma dinámica el HTML de admin/dashboard.php:
|   · Banner de estado global
|   · Tarjetas de cumplimiento por categoría
|   · Gráfico de barras por dealer + leyenda
|   · Tabla de KPIs críticos (DataTable)
|--------------------------------------------------------------------------
*/

$(document).ready(function () {

    var tablaKpi       = null;
    var datosActuales  = null;       // última respuesta getResumen (drill-down local)
    var categoriaActiva = null;      // contexto local de categoría: { clave, nombre } | null
    var debounceTimer  = null;       // timer para agrupar cambios rápidos
    var fetchSeq       = 0;          // descarta respuestas obsoletas

    /* ------------------------------------------------------------------ *
     *  Mapeo de clases CSS reutilizables
     * ------------------------------------------------------------------ */
    var COLORES = {
        green:  'green_inicio',
        yellow: 'yellow_inicio',
        red:    'red_inicio',
        gray:   'gray_inicio'
    };

    var MAPA_ESTADO_BARRA = {
        on_track: 'green_inicio',
        pending:  'yellow_inicio',
        critical: 'red_inicio',
        inactive: 'gray_inicio'
    };

    var MAPA_CLASE_CARD = {
        parts:     'kpi_parts_inicio',
        sales:     'kpi_sales_inicio',
        service:   'kpi_service_inicio',
        workshop:  'kpi_parts_inicio',
        vehicles:  'kpi_sales_inicio',
        labor:     'kpi_sales_inicio',
        personnel: 'kpi_service_inicio'
    };

    var MAPA_PILL = {
        vencido:    'danger_inicio',
        por_vencer: 'warn_inicio',
        pendiente:  'pending_inicio'
    };

    /* ------------------------------------------------------------------ *
     *  Obtener color de barra según porcentaje
     * ------------------------------------------------------------------ */
    function colorPorPorcentaje(pct) {
        if (pct === 0) return COLORES.gray;
        if (pct >= 80) return COLORES.green;
        if (pct >= 50) return COLORES.yellow;
        return COLORES.red;
    }

    /* HTML de un delta (pp) para banner/tarjetas */
    function deltaHtml(pp) {
        if (pp === null || pp === undefined || isNaN(pp)) {
            return '';
        }
        var icono = pp > 0 ? '<i class="fa fa-arrow-up"></i>' : pp < 0 ? '<i class="fa fa-arrow-down"></i>' : '<i class="fa fa-minus"></i>';
        var clase = pp > 0 ? 'delta_pos_inicio' : pp < 0 ? 'delta_neg_inicio' : 'delta_flat_inicio';
        var texto = (pp > 0 ? '+' : '') + pp + 'pp';
        return '<span class="delta_inicio ' + clase + '">' + icono + ' ' + texto + '</span>';
    }

    /* ------------------------------------------------------------------ *
     *  Renderizado del banner
     * ------------------------------------------------------------------ */
    function renderBanner(datos) {
        var t = datos.totales;
        $('#cumplimiento_global_inicio').text('Overall compliance: ' + t.cumplimiento + '%');
        $('.ok_text_inicio').text(t.on_track + ' Dealers on track');
        $('.danger_text_inicio').text(t.critical + ' Critical dealers');

        // Delta de comparación vs baseline
        var comp = datos.comparacion;
        if (comp && comp.deltas && comp.deltas.delta_pp !== null && comp.deltas.delta_pp !== undefined) {
            $('#delta_comparacion_inicio')
                .html(deltaHtml(comp.deltas.delta_pp) + ' <span class="delta_base_inicio">vs ' + comp.deltas.base + '%</span>')
                .show();
        } else {
            $('#delta_comparacion_inicio').hide();
        }
    }

    /* ------------------------------------------------------------------ *
     *  Renderizado de las tarjetas de categorías
     * ------------------------------------------------------------------ */
    function renderTarjetas(datos) {
        var $grid = $('#grid_kpi_inicio');
        $grid.empty();

        // Estado vacío del grid de tarjetas
        if (!datos.categorias || !datos.categorias.length) {
            $('#empty_kpi_inicio').removeClass('hidden_drill_inicio');
        } else {
            $('#empty_kpi_inicio').addClass('hidden_drill_inicio');
        }

        var resumen = datos.totales;

        // Deltas por categoría (comparación)
        var deltaCats = {};
        var comp = datos.comparacion;
        if (comp && comp.deltas && comp.deltas.categorias) {
            $.each(comp.deltas.categorias, function (i, dc) {
                deltaCats[dc.clave] = dc;
            });
        }

        // Tarjeta principal: Compliance (resumen general)
        var colorCumpl = colorPorPorcentaje(resumen.cumplimiento);
        var cardCompl = $('<article>', {
            class: 'card_common kpi_card_inicio kpi_main_inicio'
        });
        cardCompl.append(
            $('<h3>').text('Compliance'),
            $('<div>', { class: 'kpi_value_inicio' }).text(resumen.cumplimiento + '%'),
            $('<div>', { class: 'progress_inicio' }).append(
                $('<span>').css('width', resumen.cumplimiento + '%')
            ),
            $('<p>', { class: 'trend_up_inicio' }).html(
                '<i class="fa fa-arrow-up trend_icon_up"></i> ' + resumen.cargados + '/' + resumen.requeridos + ' cargas'
            ),
            comp && comp.deltas && comp.deltas.delta_pp !== null && comp.deltas.delta_pp !== undefined
                ? $('<p>', { class: 'trend_delta_inicio' }).html(deltaHtml(comp.deltas.delta_pp) + ' <span class="delta_base_inicio">vs ' + comp.deltas.base + '%</span>')
                : ''
        );
        $grid.append(cardCompl);

        // Tarjetas por categoría
        $.each(datos.categorias, function (i, cat) {
            var claseCard = MAPA_CLASE_CARD[cat.clave] || 'kpi_parts_inicio';
            var colorBarra = colorPorPorcentaje(cat.porcentaje);

            // Flecha de tendencia según el estado
            var trendHtml;
            if (cat.porcentaje >= 80) {
                trendHtml = '<i class="fa fa-arrow-up trend_icon_up"></i> ' + cat.cargados + '/' + cat.requeridos + ' dealers';
            } else if (cat.porcentaje >= 50) {
                trendHtml = '<i class="fa fa-minus trend_icon_flat"></i> ' + cat.cargados + '/' + cat.requeridos + ' dealers';
            } else {
                trendHtml = '<i class="fa fa-arrow-down trend_icon_down"></i> ' + cat.cargados + '/' + cat.requeridos + ' dealers';
            }

            var activa = categoriaActiva && categoriaActiva.clave === cat.clave;
            var card = $('<article>', {
                class: 'card_common kpi_card_inicio ' + claseCard + (activa ? ' is_drill_active_inicio' : ''),
                'data-cat': cat.clave
            });
            var dc = deltaCats[cat.clave];
            var deltaCat = (dc && dc.delta_pp !== null && dc.delta_pp !== undefined)
                ? $('<p>', { class: 'trend_delta_inicio' }).html(deltaHtml(dc.delta_pp) + ' <span class="delta_base_inicio">vs ' + dc.base + '%</span>')
                : '';

            card.append(
                $('<h3>').append(
                    $('<span>', { class: 'kpi_chip_inicio ' + (cat.clave === 'parts' ? 'chip_parts_inicio' : cat.clave === 'sales' ? 'chip_sales_inicio' : 'chip_service_inicio') })
                        .html('<i class="' + cat.icono + '"></i>'),
                    ' ' + cat.nombre
                ),
                $('<div>', { class: 'kpi_value_inicio' }).text(cat.porcentaje + '%'),
                $('<div>', { class: 'progress_inicio' }).append(
                    $('<span>').css('width', cat.porcentaje + '%')
                ),
                $('<p>', { class: 'trend_warn_inicio' }).html(trendHtml),
                deltaCat
            );
            $grid.append(card);
        });

        // Indicador de drill-down de categoría (contexto local)
        $('#drill_info_categoria_inicio').text(
            categoriaActiva ? 'Categoría: ' + categoriaActiva.nombre : 'Categoría: todas'
        );
    }

    /* ------------------------------------------------------------------ *
     *  Renderizado de barras por dealer + leyenda
     * ------------------------------------------------------------------ */
    function renderBarras(datos) {
        var $bars = $('.bars_inicio');
        var t = datos.totales;
        $bars.empty();

        // Estado vacío del panel de dealers
        if (!datos.dealers || !datos.dealers.length) {
            $('#empty_dealer_inicio').removeClass('hidden_drill_inicio');
            $('#panel_dealer_inicio .chart_area_inicio').addClass('hidden_drill_inicio');
            return;
        }
        $('#empty_dealer_inicio').addClass('hidden_drill_inicio');
        $('#panel_dealer_inicio .chart_area_inicio').removeClass('hidden_drill_inicio');

        var maxDealers = Math.min(datos.dealers.length, 24);

        for (var i = 0; i < datos.dealers.length; i++) {
            var d = datos.dealers[i];
            if (i >= maxDealers) break;

            var altura = Math.max(d.porcentaje, 3); // mínimo visible
            var clase = MAPA_ESTADO_BARRA[d.estado] || COLORES.gray;

            var selDealers = GlobalFilters.getParam('dealerIds') || [];
            var barra = $('<div>', {
                class: 'bar_inicio ' + clase + (selDealers.indexOf(d.dealerid) !== -1 ? ' is_drill_active_inicio' : ''),
                css: { height: d.porcentaje + '%' },
                'data-dealerid': d.dealerid
            });
            barra.append(
                $('<span>', { title: d.nombre }).text(abreviarNombre(d.nombre))
            );
            $bars.append(barra);
        }

        // Leyenda
        $('#leyenda_inicio').html(
            '<li><span class="swatch_inicio ' + COLORES.green  + '"></span>On track <b>' + t.on_track + '</b></li>' +
            '<li><span class="swatch_inicio ' + COLORES.yellow + '"></span>Pending <b>' + t.pending + '</b></li>' +
            '<li><span class="swatch_inicio ' + COLORES.red    + '"></span>Critical <b>' + t.critical + '</b></li>' +
            '<li><span class="swatch_inicio ' + COLORES.gray   + '"></span>Inactive <b>' + t.inactive + '</b></li>'
        );
    }

    function abreviarNombre(nombre) {
        var parts = nombre.trim().split(/\s+/);
        var abr = parts.length > 1
            ? parts[0].replace(/,.*$/, '').substring(0, 10) + ' '
            : parts[0].substring(0, 10);
        return abr;
    }

    /* ------------------------------------------------------------------ *
     *  Renderizado del desglose por país
     * ------------------------------------------------------------------ */
    function renderPaises(datos) {
        var $wrap = $('#paises_wrap_inicio');
        $wrap.empty();

        if (!datos.paises || !datos.paises.length) {
            $wrap.append($('<div>', { class: 'empty_state_inicio' }).text('Sin datos'));
            return;
        }

        var selPaises = GlobalFilters.getParam('countryIds') || [];
        $.each(datos.paises, function (i, p) {
            var claseFill = colorPorPorcentaje(p.porcentaje);
            var row = $('<div>', {
                class: 'paises_row_inicio' + (selPaises.indexOf(p.paisid) !== -1 ? ' is_drill_active_inicio' : ''),
                'data-paisid': p.paisid,
                title: p.pais + ' · ' + p.dealers + ' dealers · ' + p.cargados + '/' + p.requeridos
            });
            row.append(
                $('<span>', { class: 'paises_name_inicio' }).append(
                    p.bandera ? $('<span>', { class: 'paises_flag_inicio' }).text(p.bandera + ' ') : '',
                    p.pais
                ),
                $('<div>', { class: 'paises_bar_inicio' }).append(
                    $('<span>', { class: claseFill }).css('width', p.porcentaje + '%')
                ),
                $('<span>', { class: 'paises_pct_inicio' }).text(p.porcentaje + '%')
            );
            $wrap.append(row);
        });
    }

    /* ------------------------------------------------------------------ *
     *  Renderizado de la tendencia mensual
     * ------------------------------------------------------------------ */
    function renderTendencia(datos) {
        var $wrap = $('#tendencia_wrap_inicio');
        $wrap.empty();

        if (!datos.tendencia || !datos.tendencia.length) {
            $wrap.append($('<div>', { class: 'empty_state_inicio' }).text('Sin datos'));
            return;
        }

        // Tendencia base (comparación) indexada por mesnum
        var basePorMes = {};
        if (datos.comparacion && datos.comparacion.tendencia) {
            $.each(datos.comparacion.tendencia, function (i, bt) {
                basePorMes[bt.mesnum] = bt;
            });
        }

        var calActivo = GlobalFilters.getParam('calendarId');
        $.each(datos.tendencia, function (i, t) {
            var altura = Math.max(t.cumplimiento, 2);
            var clase  = colorPorPorcentaje(t.cumplimiento);
            var base   = basePorMes[t.mesnum];
            var titulo = t.mes + ' ' + t.ano + ' · ' + t.cumplimiento + '%' +
                (base && base.cumplimiento !== undefined ? ' vs ' + base.cumplimiento + '%' : '') +
                ' · ' + t.cargados + '/' + t.requeridos;
            var punto  = $('<div>', {
                class: 'tendencia_punto_inicio' + (calActivo === t.calendarioid ? ' is_drill_active_inicio' : ''),
                'data-calendarioid': t.calendarioid,
                title: titulo
            });
            var $bars = $('<div>', { class: 'tendencia_bars_inicio' });
            if (base && base.cumplimiento !== undefined) {
                $bars.append(
                    $('<div>', { class: 'tendencia_col_base_inicio' }).css('height', Math.max(base.cumplimiento, 2) + '%')
                );
            }
            $bars.append(
                $('<div>', { class: 'tendencia_col_inicio ' + clase }).css('height', altura + '%')
            );
            punto.append($bars, $('<span>', { class: 'tendencia_mes_inicio' }).text(t.mes));
            $wrap.append(punto);
        });
    }

    /* ------------------------------------------------------------------ *
     *  Renderizado del panel Information Status (sparklines por categoría)
     * ------------------------------------------------------------------ */
    function renderInformationStatus(datos) {
        var $wrap = $('#informationstatus_wrap_inicio');
        $wrap.empty();

        // Resumen: % general + badges de estado
        var t = datos.totales;
        $('#informationstatus_resumen_inicio').html(
            '<span class="resumen_green_inicio"><span class="resumen_label_inicio">Cumplimiento General</span><span class="resumen_percent_inicio">' + t.cumplimiento + '%</span></span>' +
            '<span class="resumen_badges_inicio">' +
                '<span class="badge_item_inicio aldia_inicio"><strong>' + t.on_track + '</strong> Al Día</span>' +
                '<span class="badge_sep_inicio">·</span>' +
                '<span class="badge_item_inicio pendiente_inicio"><strong>' + t.pending + '</strong> Pendientes</span>' +
                '<span class="badge_sep_inicio">·</span>' +
                '<span class="badge_item_inicio critico_inicio"><strong>' + t.critical + '</strong> Críticos</span>' +
            '</span>'
        );

        if (!datos.tendencia || !datos.tendencia.length) {
            $wrap.append($('<div>', { class: 'empty_state_inicio' }).text('Sin datos'));
            return;
        }

        var claves = ['parts', 'sales', 'workshop', 'service', 'vehicles', 'labor', 'personnel'];
        var nombres = {
            parts:     'Parts Reporting',
            sales:     'Sales Reporting',
            workshop:  'Workshop Reporting',
            service:   'Service Reporting',
            vehicles:  'Vehicles Reporting',
            labor:     'Labor Rates',
            personnel: 'Personnel'
        };

        // % del período por categoría (del resumen)
        var catPorcentaje = {};
        $.each(datos.categorias || [], function (i, c) {
            catPorcentaje[c.clave] = c.porcentaje;
        });

        $.each(claves, function (i, clave) {
            var activaIS = categoriaActiva && categoriaActiva.clave === clave;
            var card = $('<div>', {
                class: 'informationstatus_card_inicio' + (activaIS ? ' is_drill_active_inicio' : ''),
                'data-cat': clave
            });
            var content = $('<div>', { class: 'informationstatus_content_inicio' });
            var $bars  = $('<div>', { class: 'informationstatus_bars_inicio' });

            // Sparkline mensual desde `tendencia[].categorias[clave]`
            $.each(datos.tendencia, function (j, tp) {
                var v = tp.categorias ? tp.categorias[clave] : undefined;
                var h = (v === undefined || v === null) ? 2 : Math.max(v, 2);
                $bars.append(
                    $('<span>', {
                        class: 'informationstatus_bar_inicio bar_' + clave + '_inicio',
                        title: tp.mes + ': ' + (v === undefined || v === null ? '—' : v + '%')
                    }).css('height', h + '%')
                );
            });

            content.append(
                $bars,
                $('<p>', { class: 'informationstatus_pct_inicio' }).text((catPorcentaje[clave] === undefined ? 0 : catPorcentaje[clave]) + '%')
            );

            card.append(
                $('<p>', { class: 'informationstatus_label_inicio' }).append(
                    $('<span>', { class: 'informationstatus_dot_inicio dot_' + clave + '_inicio' }),
                    nombres[clave] || clave
                ),
                content
            );
            $wrap.append(card);
        });
    }

    /* ------------------------------------------------------------------ *
     *  Renderizado de la tabla de KPIs críticos
     * ------------------------------------------------------------------ */
    function renderTabla(datos) {
        var $tbody = $('#tabla_body_inicio');
        $tbody.empty();

        var kpis = datos.kpis_criticos || [];
        if (categoriaActiva) {
            kpis = kpis.filter(function (k) {
                return k.categoria === categoriaActiva.nombre;
            });
        }

        // Estado vacío de la tabla
        if (!kpis.length) {
            $('#empty_tabla_kpi_inicio').removeClass('hidden_drill_inicio');
        } else {
            $('#empty_tabla_kpi_inicio').addClass('hidden_drill_inicio');
        }

        $.each(kpis, function (i, k) {
            var pillClass = MAPA_PILL[k.estado] || 'pending_inicio';
            var tr = $('<tr>').append(
                $('<td>').text(k.dealer),
                $('<td>').text(k.categoria),
                $('<td>').text(k.kpi),
                $('<td>').append(
                    $('<span>', { class: 'pill_inicio ' + pillClass }).html(
                        '<span class="pill_dot"></span> ' + k.estado_label
                    )
                ),
                $('<td>').text(k.periodo || ''),
                $('<td>').text(k.edad || '—'),
                $('<td>').text(k.edad || '—')
            );
            $tbody.append(tr);
        });

        // Re-inicializa DataTable manteniendo las opciones originales
        if (tablaKpi) {
            tablaKpi.destroy();
        }
        tablaKpi = initDataTable();
        tablaKpi.on('draw', actualizarFooterKpi);
        actualizarFooterKpi();
    }

    /* ------------------------------------------------------------------ *
     *  Filtros (dealer y mes)
     * ------------------------------------------------------------------ */
    var filtrosDisponibles   = null;    // opciones de la barra (action=getFiltros)
    var sincronizandoFiltros = false;   // evita loops al sincronizar selects
    var select2Inicializado  = false;

    /* Render de la barra de filtros desde `filtros` (getFiltros) */
    function renderFiltros() {
        if (!filtrosDisponibles) {
            return;
        }
        var $selDealer = $('#filtro_dealer_inicio');
        var $selPais   = $('#filtro_pais_inicio');
        var $selAnio   = $('#filtro_anio_inicio');

        // Dealers (multi-select)
        $selDealer.empty();
        $.each(filtrosDisponibles.dealers, function (i, d) {
            $selDealer.append($('<option>', { value: d.dealerid }).text(d.dealer));
        });

        // Países (multi-select)
        $selPais.empty();
        $.each(filtrosDisponibles.paises, function (i, p) {
            $selPais.append($('<option>', { value: p.paisid }).text(p.pais));
        });

        // Años
        $selAnio.empty();
        $.each(filtrosDisponibles.anios, function (i, a) {
            $selAnio.append($('<option>', { value: a.ano }).text(String(a.ano)));
        });

        // Select2 en los multi-select (chips activos)
        if (!select2Inicializado && $.fn.select2) {
            $('#filtro_dealer_inicio, #filtro_pais_inicio').select2({
                placeholder: 'All',
                allowClear: true,
                width: '100%'
            });
            select2Inicializado = true;
        }

        sincronizarFiltrosDesdeStore();
    }

    /* Año efectivo: el del store o, si es null, el año actual disponible
       (is_current) o el más reciente. Coincide con el default del backend. */
    function anioEfectivo() {
        var estado = GlobalFilters.get();
        if (estado.year) {
            return estado.year;
        }
        var anios = (filtrosDisponibles && filtrosDisponibles.anios) || [];
        for (var i = 0; i < anios.length; i++) {
            if (anios[i].is_current) {
                return anios[i].ano;
            }
        }
        return anios.length ? anios[0].ano : null;
    }

    /* Puebla el select de mes con los meses del año seleccionado */
    function poblarMeses(anio, calendarId) {
        var $selMes = $('#filtro_mes_inicio');
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

    /* Sincroniza los selects con el estado del store (evitando loops) */
    function sincronizarFiltrosDesdeStore() {
        if (!filtrosDisponibles) {
            return;
        }
        var estado = GlobalFilters.get();
        sincronizandoFiltros = true;
        $('#filtro_dealer_inicio').val(estado.dealerIds.length ? estado.dealerIds.map(String) : null).trigger('change');
        $('#filtro_pais_inicio').val(estado.countryIds.length ? estado.countryIds.map(String) : null).trigger('change');
        var anio = anioEfectivo();
        $('#filtro_anio_inicio').val(anio ? String(anio) : null);
        poblarMeses(anio, estado.calendarId);
        $('#filtro_vehiculo_inicio').val(estado.vehicleType || 'all');
        $('#filtro_periodo_inicio').val(estado.periodType || 'month');
        $('#filtro_comparacion_inicio').val(estado.comparison || 'none');
        sincronizandoFiltros = false;
    }

    /* Muestra/oculta los botones "limpiar drill-down" por componente */
    function actualizarBotonesDrill() {
        var f = GlobalFilters.get();
        $('#btn_limpiar_drill_dealer_inicio').toggleClass('hidden_drill_inicio', !(f.dealerIds && f.dealerIds.length));
        $('#btn_limpiar_drill_pais_inicio').toggleClass('hidden_drill_inicio', !(f.countryIds && f.countryIds.length));
        $('#btn_limpiar_drill_tendencia_inicio').toggleClass('hidden_drill_inicio', !f.calendarId);
        $('#btn_limpiar_drill_categoria_inicio').toggleClass('hidden_drill_inicio', !categoriaActiva);
    }

    /* Carga las opciones de la barra (getFiltros) una sola vez */
    function cargarFiltros() {
        $.ajax({
            url: '../controllers/dashboard.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'getFiltros' },
            success: function (res) {
                if (!res.success) {
                    console.error('Dashboard filtros: ' + res.message);
                    return;
                }
                filtrosDisponibles = res.data;
                renderFiltros();
            },
            error: function (xhr) {
                console.error('Dashboard filtros error:', xhr.responseText);
            }
        });
    }

    /* ------------------------------------------------------------------ *
     *  Carga de datos desde el controlador
     * ------------------------------------------------------------------ */
    /* Debounce: agrupa cambios rápidos en un solo fetch */
    function programarCarga() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(cargarDatos, 250);
    }

    function cargarDatos() {
        var f = GlobalFilters.get();
        var seq = ++fetchSeq;
        $.ajax({
            url: '../controllers/dashboard.php',
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
                if (seq !== fetchSeq) {
                    return; // respuesta obsoleta (hay un cambio más reciente)
                }
                if (!res.success) {
                    console.error('Dashboard: ' + res.message);
                    return;
                }
                var datos = res.data;
                datosActuales = datos;
                renderBanner(datos);
                renderTarjetas(datos);
                renderBarras(datos);
                renderPaises(datos);
                renderTendencia(datos);
                renderInformationStatus(datos);
                renderTabla(datos);
                actualizarBotonesDrill();
            },
            error: function (xhr) {
                console.error('Dashboard error:', xhr.responseText);
            }
        });
    }

    /* ------------------------------------------------------------------ *
     *  DataTable + paginación personalizada
     * ------------------------------------------------------------------ */
    function initDataTable() {
        return $('#tabla_kpi_inicio').DataTable({
            dom: 'rt',
            pageLength: 10,
            columnDefs: [
                { targets: -1, orderable: false, searchable: false }
            ],
            language: {
                info:         '_START_ - _END_ de _TOTAL_ KPIs',
                infoEmpty:    '0 KPIs',
                infoFiltered: '_START_ - _END_ de _MAX_ KPIs (filtrado)'
            }
        });
    }

    function renderPaginas(info) {
        var cur   = info.page;
        var total = info.pages;
        var html  = '';

        function btnPag(n) {
            var cls = 'kpi_pnum_btn' + (n === cur ? ' kpi_pnum_active' : '');
            return '<button type="button" class="' + cls + '" data-page="' + n + '">' + (n + 1) + '</button>';
        }

        if (total <= 7) {
            for (var i = 0; i < total; i++) html += btnPag(i);
        } else {
            var pages = [0];
            if (cur > 2) pages.push('...');
            var from = Math.max(1, cur - 1);
            var to   = Math.min(total - 2, cur + 1);
            for (var i = from; i <= to; i++) pages.push(i);
            if (cur < total - 3) pages.push('...');
            pages.push(total - 1);

            for (var j = 0; j < pages.length; j++) {
                if (pages[j] === '...') {
                    html += '<span class="kpi_pnum_ellipsis">…</span>';
                } else {
                    html += btnPag(pages[j]);
                }
            }
        }

        $('#kpi_paginas_inicio').html(html);
    }

    function actualizarFooterKpi() {
        if (!tablaKpi) return;
        var info = tablaKpi.page.info();
        var inicio = info.recordsDisplay === 0 ? 0 : info.start + 1;
        var fin    = info.end;
        var total  = info.recordsTotal;
        var filtrado = info.recordsDisplay;
        var paginaActual = info.recordsDisplay === 0 ? 0 : info.page + 1;
        var totalPaginas = info.pages;

        var texto;
        if (info.recordsDisplay === 0) {
            texto = 'Sin resultados';
        } else if (filtrado < total) {
            texto = inicio + ' - ' + fin + ' de ' + filtrado + ' KPIs (filtrado de ' + total + ')';
        } else {
            texto = inicio + ' - ' + fin + ' de ' + total + ' KPIs';
        }

        $('#kpi_info_inicio').text('Página ' + paginaActual + ' de ' + totalPaginas);
        $('.th_count_inicio').text(texto);

        $('#kpi_btn_prev').prop('disabled', info.page === 0);
        $('#kpi_btn_next').prop('disabled', info.page >= info.pages - 1);
        renderPaginas(info);
    }

    /* ------------------------------------------------------------------ *
     *  Eventos de filtros
     * ------------------------------------------------------------------ */
    $('#filtro_dealer_inicio').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ dealerIds: ($(this).val() || []).map(Number) });
    });

    $('#filtro_pais_inicio').on('change', function () {
        if (sincronizandoFiltros) return;
        GlobalFilters.set({ countryIds: ($(this).val() || []).map(Number) });
    });

    $('#filtro_vehiculo_inicio').on('change', function () {
        GlobalFilters.set({ vehicleType: $(this).val() || 'all' });
    });

    $('#filtro_periodo_inicio').on('change', function () {
        GlobalFilters.set({ periodType: $(this).val() || 'month' });
    });

    $('#filtro_anio_inicio').on('change', function () {
        var v = $(this).val();
        // Al cambiar de año se reinicia el mes de referencia (pertenece al año anterior)
        GlobalFilters.set({ year: v ? Number(v) : null, calendarId: null });
    });

    $('#filtro_mes_inicio').on('change', function () {
        if (sincronizandoFiltros) return;
        var v = $(this).val();
        GlobalFilters.set({ calendarId: v ? Number(v) : null });
    });

    $('#filtro_comparacion_inicio').on('change', function () {
        GlobalFilters.set({ comparison: $(this).val() || 'none' });
    });

    $('#btn_reset_filtros_inicio').on('click', function () {
        GlobalFilters.reset();
    });

    /* ------------------------------------------------------------------ *
     *  Cross-filtering (drill-down por clic)
     * ------------------------------------------------------------------ */
    // Barra de dealer -> dealerIds
    $('.bars_inicio').on('click.drill', '.bar_inicio', function () {
        var did = $(this).data('dealerid');
        if (!did) return;
        var actual = GlobalFilters.getParam('dealerIds') || [];
        GlobalFilters.set({ dealerIds: actual.indexOf(did) === -1 ? [did] : [] });
    });

    // Segmento de país -> countryIds
    $('#paises_wrap_inicio').on('click.drill', '.paises_row_inicio', function () {
        var pid = $(this).data('paisid');
        if (!pid) return;
        var actual = GlobalFilters.getParam('countryIds') || [];
        GlobalFilters.set({ countryIds: actual.indexOf(pid) === -1 ? [pid] : [] });
    });

    // Punto de tendencia -> calendarId (toggle)
    $('#tendencia_wrap_inicio').on('click.drill', '.tendencia_punto_inicio', function () {
        var cal = $(this).data('calendarioid');
        if (!cal) return;
        var actual = GlobalFilters.getParam('calendarId');
        GlobalFilters.set({ calendarId: actual === cal ? null : cal });
    });

    // Contexto local de categoría (toggle) reutilizado por grid + Information Status
    function setCategoriaLocal(clave) {
        if (categoriaActiva && categoriaActiva.clave === clave) {
            categoriaActiva = null;
        } else {
            var cat = null;
            $.each((datosActuales && datosActuales.categorias) || [], function (i, c) {
                if (c.clave === clave) {
                    cat = c;
                    return false;
                }
            });
            if (!cat) {
                cat = { clave: clave, nombre: clave.charAt(0).toUpperCase() + clave.slice(1) };
            }
            categoriaActiva = { clave: cat.clave, nombre: cat.nombre };
        }
        if (datosActuales) {
            renderTarjetas(datosActuales);
            renderInformationStatus(datosActuales);
            renderTabla(datosActuales);
            actualizarBotonesDrill();
        }
    }

    // Tarjeta de categoría (grid) -> contexto local
    $('#grid_kpi_inicio').on('click.drill', '.kpi_card_inicio[data-cat]', function () {
        setCategoriaLocal($(this).data('cat'));
    });

    // Tarjeta de categoría (Information Status) -> contexto local
    $('#informationstatus_wrap_inicio').on('click.drill', '.informationstatus_card_inicio[data-cat]', function () {
        setCategoriaLocal($(this).data('cat'));
    });

    // Botones "limpiar drill-down" por componente
    $('#btn_limpiar_drill_dealer_inicio').on('click', function () {
        GlobalFilters.set({ dealerIds: [] });
    });
    $('#btn_limpiar_drill_pais_inicio').on('click', function () {
        GlobalFilters.set({ countryIds: [] });
    });
    $('#btn_limpiar_drill_tendencia_inicio').on('click', function () {
        GlobalFilters.set({ calendarId: null });
    });
    $('#btn_limpiar_drill_categoria_inicio').on('click', function () {
        categoriaActiva = null;
        if (datosActuales) {
            renderTarjetas(datosActuales);
            renderInformationStatus(datosActuales);
            renderTabla(datosActuales);
            actualizarBotonesDrill();
        }
    });

    // Buscador de la tabla
    $('#buscador_kpi_inicio').on('keyup input', function () {
        if (tablaKpi) tablaKpi.search(this.value).draw();
    });

    // Paginación
    $('#kpi_btn_prev').on('click', function () {
        if (tablaKpi) tablaKpi.page('previous').draw('page');
    });
    $('#kpi_btn_next').on('click', function () {
        if (tablaKpi) tablaKpi.page('next').draw('page');
    });
    $('#kpi_paginas_inicio').on('click', '.kpi_pnum_btn:not(.kpi_pnum_active)', function () {
        if (tablaKpi) tablaKpi.page(parseInt($(this).data('page'), 10)).draw('page');
    });

    /* ------------------------------------------------------------------ *
     *  Inicialización
     * ------------------------------------------------------------------ */
    // Un solo refresco por cada cambio de los filtros globales
    GlobalFilters.subscribe(function (nuevoEstado, prevEstado, evento) {
        if (evento === 'change') {
            sincronizarFiltrosDesdeStore();
            programarCarga();
        }
    });

    // Primera carga: opciones de la barra + datos (el store ya se auto-inicializó)
    cargarFiltros();
    cargarDatos();
});
