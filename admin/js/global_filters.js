/*
|--------------------------------------------------------------------------
| FILTROS GLOBALES - Store compartido entre dashboards
|--------------------------------------------------------------------------
| Módulo pub/sub que mantiene el estado de los filtros globales
| (Dealer | Country | Vehicle Type | Period | Year | Comparison).
|
| Cada dashboard declara qué filtros soporta vía GlobalFilters.appliesTo,
| de modo que los no aplicables se ocultan/ignoran (spec global-filters).
|
| API:
|   GlobalFilters.get()            -> copia del estado actual
|   GlobalFilters.getParam(clave)  -> valor de una clave
|   GlobalFilters.set(parcial)     -> actualiza y notifica (emite 'change')
|   GlobalFilters.reset()          -> restaura defaults y notifica
|   GlobalFilters.subscribe(fn)    -> fn(state, prevState) en cada cambio
|   GlobalFilters.unsubscribe(fn)
|   GlobalFilters.setAppliesTo(...)-> configura filtros aplicables por página
|--------------------------------------------------------------------------
*/
(function (window) {
    'use strict';

    var VERSION     = 1;
    var STORAGE_KEY = 'ots_global_filters';

    /* Defaults del estado global */
    var DEFAULTS = {
        dealerIds:   [],          // int[]
        countryIds:  [],          // int[]
        vehicleType: 'all',       // all | cv | lcv
        periodType:  'month',     // month | ytd | fytd | full_year | l12m | prior_year
        year:        null,        // int | null (año seleccionado)
        calendarId:  null,        // int | null (mes de referencia)
        comparison:  'none'       // none | cy_vs_py | ytd_vs_py_ytd | month_vs_py
    };

    /* Filtros que aplican en el dashboard actual (se declara por página) */
    var APLICA = {
        dealer:     true,
        country:    true,
        vehicleType: true,
        period:     true,
        year:       true,
        comparison: true
    };

    var state     = {};
    var listeners = [];

    /* ------------------------- helpers ------------------------- */

    function clone(val) {
        if (Array.isArray(val)) {
            return val.slice();
        }
        if (val && typeof val === 'object') {
            var out = {};
            Object.keys(val).forEach(function (k) {
                out[k] = val[k];
            });
            return out;
        }
        return val;
    }

    function defaults() {
        var out = {};
        Object.keys(DEFAULTS).forEach(function (k) {
            out[k] = clone(DEFAULTS[k]);
        });
        return out;
    }

    /* ------------------------- persistencia ------------------------- */

    /* Parámetros de URL para cada clave del estado global */
    var PARAMS_URL = {
        dealerIds:   'df',
        countryIds:  'cf',
        vehicleType: 'vt',
        periodType:  'p',
        year:        'y',
        calendarId:  'm',
        comparison:  'c'
    };

    /* Lee localStorage; devuelve el estado solo si la versión coincide */
    function leerStorage() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return null;
            }
            var obj = JSON.parse(raw);
            if (!obj || obj.v !== VERSION || !obj.state) {
                return null;
            }
            return obj.state;
        } catch (e) {
            return null;
        }
    }

    /* Guarda el estado con la versión actual (campo v) */
    function guardarStorage(estado) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
                v:     VERSION,
                state: estado
            }));
        } catch (e) {
            /* cuota/privacidad: no debe romper la app */
        }
    }

    /* Parsea los query params de la URL al estado */
    function paramsDesdeURL() {
        var qs = {};
        var search = window.location.search || '';
        if (search.charAt(0) === '?') {
            search = search.slice(1);
        }
        search.split('&').forEach(function (kv) {
            if (!kv) {
                return;
            }
            var parts = kv.split('=');
            var k = decodeURIComponent(parts[0]);
            var v = parts.length > 1 ? decodeURIComponent(parts[1].replace(/\+/g, ' ')) : '';
            qs[k] = v;
        });

        var estado = {};
        Object.keys(PARAMS_URL).forEach(function (clave) {
            var param = PARAMS_URL[clave];
            if (!Object.prototype.hasOwnProperty.call(qs, param)) {
                return;
            }
            var v = qs[param];
            if (clave === 'dealerIds' || clave === 'countryIds') {
                estado[clave] = v
                    ? v.split(',').map(Number).filter(function (n) { return !isNaN(n); })
                    : [];
            } else if (clave === 'year' || clave === 'calendarId') {
                var n = v ? Number(v) : NaN;
                estado[clave] = isNaN(n) ? null : n;
            } else {
                estado[clave] = v;
            }
        });
        return estado;
    }

    /* Construye el query string a partir del estado */
    function urlDesdeEstado(estado) {
        var params = [];
        Object.keys(PARAMS_URL).forEach(function (clave) {
            var v = estado[clave];
            if (v === undefined || v === null || v === '') {
                return;
            }
            if (clave === 'dealerIds' || clave === 'countryIds') {
                if (Array.isArray(v) && v.length) {
                    params.push(PARAMS_URL[clave] + '=' + v.join(','));
                }
            } else if (clave === 'year' || clave === 'calendarId') {
                if (v) {
                    params.push(PARAMS_URL[clave] + '=' + v);
                }
            } else {
                params.push(PARAMS_URL[clave] + '=' + encodeURIComponent(v));
            }
        });
        return params.join('&');
    }

    /* Refleja el estado en la URL sin recargar la página */
    function actualizarURL(estado) {
        try {
            var qs = urlDesdeEstado(estado);
            var base = window.location.pathname + window.location.hash;
            window.history.replaceState(null, '', qs ? base + '?' + qs : base);
        } catch (e) {
            /* entornos sin history API */
        }
    }

    /* ------------------------- store ------------------------- */

    var GlobalFilters = {
        VERSION:     VERSION,
        STORAGE_KEY: STORAGE_KEY,

        /* Filtros aplicables en la página actual */
        appliesTo: clone(APLICA),

        setAppliesTo: function (filtros) {
            var nuevo = clone(APLICA);
            if (filtros && typeof filtros === 'object') {
                Object.keys(filtros).forEach(function (k) {
                    if (Object.prototype.hasOwnProperty.call(nuevo, k)) {
                        nuevo[k] = !!filtros[k];
                    }
                });
            }
            GlobalFilters.appliesTo = nuevo;
            return GlobalFilters;
        },

        get: function () {
            var out = {};
            Object.keys(state).forEach(function (k) {
                out[k] = clone(state[k]);
            });
            return out;
        },

        getParam: function (clave) {
            return clone(state[clave]);
        },

        /* Helpers de persistencia / URL (también públicos) */
        serializarURL: function () {
            return urlDesdeEstado(state);
        },

        leerURL: function () {
            return paramsDesdeURL();
        },

        cargarPersistido: function () {
            return leerStorage();
        },

        set: function (parcial, opts) {
            opts = opts || {};
            var prev = GlobalFilters.get();
            var mezclado = {};
            Object.keys(DEFAULTS).forEach(function (k) {
                mezclado[k] = Object.prototype.hasOwnProperty.call(state, k) ? clone(state[k]) : clone(DEFAULTS[k]);
            });
            if (parcial && typeof parcial === 'object') {
                Object.keys(parcial).forEach(function (k) {
                    if (Object.prototype.hasOwnProperty.call(DEFAULTS, k)) {
                        mezclado[k] = clone(parcial[k]);
                    }
                });
            }
            state = mezclado;
            if (opts.persist !== false) {
                guardarStorage(state);
                actualizarURL(state);
            }
            if (opts.silent !== true) {
                GlobalFilters.emit('change', GlobalFilters.get(), prev);
            }
            return GlobalFilters;
        },

        reset: function (opts) {
            opts = opts || {};
            var prev = GlobalFilters.get();
            state = defaults();
            if (opts.persist !== false) {
                guardarStorage(state);
                actualizarURL(state);
            }
            if (opts.silent !== true) {
                GlobalFilters.emit('change', GlobalFilters.get(), prev);
            }
            return GlobalFilters;
        },

        subscribe: function (fn) {
            if (typeof fn === 'function' && listeners.indexOf(fn) === -1) {
                listeners.push(fn);
            }
            return GlobalFilters;
        },

        unsubscribe: function (fn) {
            listeners = listeners.filter(function (l) { return l !== fn; });
            return GlobalFilters;
        },

        emit: function (evento, nuevoEstado, prevEstado) {
            listeners.slice().forEach(function (fn) {
                try {
                    fn(nuevoEstado, prevEstado, evento);
                } catch (e) {
                    /* un listener no debe romper a los demás */
                    if (window.console && console.error) {
                        console.error('GlobalFilters: error en listener', e);
                    }
                }
            });
            return GlobalFilters;
        },

        /* Inicialización del estado:
           prioridad URL > localStorage > defaults.
           Al finalizar, persiste el estado resultante (sin tocar la URL). */
        init: function (opciones) {
            opciones = opciones || {};
            var base = defaults();
            var desdeURL = paramsDesdeURL();
            var persistido = opciones.useStorage !== false ? leerStorage() : null;

            var mezclado = base;
            function aplicar(parcial) {
                if (!parcial || typeof parcial !== 'object') {
                    return;
                }
                Object.keys(parcial).forEach(function (k) {
                    if (Object.prototype.hasOwnProperty.call(DEFAULTS, k)) {
                        mezclado[k] = clone(parcial[k]);
                    }
                });
            }
            aplicar(persistido); // localStorage (filtros de la sesión anterior)
            aplicar(desdeURL);   // la URL tiene prioridad (enlace/recarga compartida)

            state = mezclado;
            if (opciones.persist !== false) {
                guardarStorage(state);
            }
            if (opciones.silent !== true) {
                GlobalFilters.emit('change', GlobalFilters.get(), base);
            }
            return GlobalFilters;
        }
    };

    GlobalFilters.init();

    window.GlobalFilters = GlobalFilters;
})(window);
