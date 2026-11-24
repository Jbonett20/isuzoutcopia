// Variable global
let ultimoItemSeleccionado = null;
$(function(){
    dealerid = 0;
    monthid = 0;
    $("#CompletoDesactivar").hide();
    $("#prodmonthhome").select2({});
    $("#typeorderhome").select2({});
    $("#porthome").select2({});
    $("#statusorderhome").select2({});
    if ($("#changestatus").is('select')) {
        $("#changestatus").select2({});
    }
    
    $("#dealerhome").select2({
        templateResult: formatState,
        templateSelection: formatSelection
    });
    function formatState (state) {
        if (!state.id) {
            return state.text;
        }
        var baseUrl = "../admin/img/country";
        var $state = $(
            '<span><img src="' + baseUrl + '/' + state.title + '" style="width:20px;" /> ' + state.text + '</span>'
        );
        return $state;
    };
    function formatSelection (state) {
        if (!state.id) {
            return state.text;
        }
        var baseUrl = "../admin/img/country";
        var $state = $(
            '<span><img src="' + baseUrl + '/' + state.title + '" style="width:20px;" /> ' + state.text + '</span>'
        );
        // Use .text() instead of HTML string concatenation to avoid script injection issues
        $state.find("span").text(state.text);
        $state.find("img").attr("src", baseUrl + "/" + state.title);
        return $state;
    };
    $('#dealerhome').on('select2:select', function (e) {
        var data = e.params.data;
        CargarHome();
    });
    $('#prodmonthhome').on('select2:select', function (e) {
        var data = e.params.data;
        CargarTable();
    });
    $('#statusorderhome').on('select2:select', function (e) {
        var data = e.params.data;
        CargarTable();
    });
    $('#typeorderhome').on('select2:select', function (e) {
        var data = e.params.data;
        CargarTable();
    });
    $('#porthome').on('select2:select', function (e) {
        var data = e.params.data;
        CargarTable();
    });
    function CargarHome(){
        const select = document.getElementById("dealerhome");
        const texto = select.options[select.selectedIndex].text;
        const countryName = texto.split(/\(/);
        $("#countryhome").val(countryName[1]?.replace(')', ''));
        dealerid = $('#dealerhome').val();
        typeorderid = $('#typeorderhome').val();
        portid = $('#porthome').val();
        monthid = $('#prodmonthhome').val();
        statusid = $('#statusorderhome').val();
        console.log("Dealer: " + dealerid + " | Mes: " + monthid + " | Status: " + statusid);

        let selectedValueType = 0;
        if ($('#typeorderidsel').length > 0) {
            selectedValueType = parseInt($('#typeorderidsel').val()) || 0;
            console.log("Tipo por get: "+selectedValueType);
        }

        let selectedValuePort = 0;
        if ($('#portidsel').length > 0) {
            selectedValuePort = parseInt($('#portidsel').val()) || 0;
            console.log("Puerto por get: "+selectedValuePort);
        }
        $.ajax({
            url: '../admin/datosselect.php?ctrl=tipoordenes&dealerid='+dealerid,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#typeorderhome').empty();
                $('#typeorderhome').append(new Option("ALL TYPES", 0));
                data.forEach(function(item) {
                    let option = new Option(item.text, item.id);
                    // Si coincide con el campo hidden y es mayor que 0 → seleccionar
                    if (selectedValueType > 0 && parseInt(item.id) === selectedValueType) {
                        option.selected = true;
                    }
                    $('#typeorderhome').append(option);
                });
                $('#typeorderhome').select2({
                    placeholder: 'Select one option'
                });
            }
        });
        $.ajax({
            url: '../admin/datosselect.php?ctrl=puertos&dealerid='+dealerid,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#porthome').empty();
                $('#porthome').append(new Option("ALL PORTS", 0));
                data.forEach(function(item) {
                    let optionp = new Option(item.text, item.id);
                    // Si coincide con el campo hidden y es mayor que 0 → seleccionar
                    if (selectedValuePort > 0 && parseInt(item.id) === selectedValuePort) {
                        optionp.selected = true;
                    }
                    $('#porthome').append(optionp);
                });
                $('#porthome').select2({
                    placeholder: 'Select one option'
                });
            }
        });
        CargarTable();
    }
    CargarHome();
    CargaDetOrden();
    CargarMensajes();
});
function CargarTable(){
    dealerid = $('#dealerhome').val();
    typeorderid = $('#typeorderhome').val();
    portid = $('#porthome').val();
    monthid = $('#prodmonthhome').val();
    statusid = $('#statusorderhome').val();
    $('#TabSearchOrder').load('existorderdet.php?month='+monthid+'&dealerid='+dealerid+'&typeorderid='+typeorderid+'&portid='+portid+'&status='+statusid,function(response, status, xhr) {
        if (status === "success") {
            console.log("Contenido cargado exitosamente");
            DataTables();
        } else {
            console.log("Error al cargar:", xhr.status, xhr.statusText);
        }
    });
}
function DataTables(){
    var unsortableColumns = [];
    $('#TableListItems').find('thead th').each(function(){
        if ($(this).hasClass( 'no-sort')){
            unsortableColumns.push({"bSortable": false});
        } else {
            unsortableColumns.push({"bSortable": true});
        }
    });
    $("#TableListItems").dataTable({
        "sDom": "<'row'<'col-lg-6 hidden-sm-down'l><'col-lg-6'f>r>t<'row'<'col-lg-6'i><'col-lg-6'p>>",
        "oLanguage": {
            "sLengthMenu": "_MENU_",
            "sInfo": "Showing <strong>_START_ to _END_</strong> of _TOTAL_ entries"
        },
        "oClasses": {
            "sFilter": "float-right",
            "sFilterInput": "form-control input-rounded ml-sm"
        },
        "aoColumns": unsortableColumns
    });
    $('div.dataTables_length select').addClass('form-control');
}
function DelItem(boton) {
    // Eliminar la fila completa
    const fila = boton.closest('tr');
    if (fila) {
      fila.remove();
      // Recalcular total
      CalcularTotales();
    }
}
function exportarTablaAExcel(){
    const table = document.querySelector('#TableListItems');
    const data = [];

    const rows = table.querySelectorAll('tr');

    rows.forEach((row, rowIndex) => {
        const rowData = [];
        const cells = row.querySelectorAll('th, td');

        cells.forEach((cell, colIndex) => {
            let cellValue = "";

            if (rowIndex === 0) {
                // Fila de encabezado: dejar como está
                cellValue = cell.textContent.trim();
            } else {
                if (colIndex === 0) {
                    // ORDER#: quitar <a> y tomar solo texto
                    const link = cell.querySelector('a');
                    cellValue = link ? link.textContent.trim() : cell.textContent.trim();
                } else if (colIndex === 2) {
                    // PROD MONTH: exportar como texto
                    cellValue = cell.textContent.trim();
                } else if (colIndex === 5) {
                    // UNITS (columna 5 visual): número
                    const raw = cell.textContent.trim();
                    const numeric = parseFloat(raw.replace(/[,$]/g, ""));
                    cellValue = isNaN(numeric) ? 0 : numeric;
                } else if (colIndex === 6) {
                    // PRICE (columna 6 visual): número, se aplicará formato moneda más abajo
                    const raw = cell.textContent.trim();
                    const numeric = parseFloat(raw.replace(/[,$]/g, ""));
                    cellValue = isNaN(numeric) ? 0 : numeric;
                } else {
                    // Otros: solo texto
                    cellValue = cell.textContent.trim();
                }
            }

            rowData.push(cellValue);
        });

        data.push(rowData);
    });

    // Convertir a hoja de Excel
    const worksheet = XLSX.utils.aoa_to_sheet(data);

    const range = XLSX.utils.decode_range(worksheet['!ref']);

    // Forzar PROD MONTH (col 2) como texto
    for (let R = 1; R <= range.e.r; ++R) {
        const prodMonthCell = XLSX.utils.encode_cell({ r: R, c: 2 });
        const cell = worksheet[prodMonthCell];
        if (cell && cell.v) {
            cell.t = 's';
        }
    }

    // Aplicar formato de moneda a columna PRICE
    for (let R = 1; R <= range.e.r; ++R) {
        const priceCell = XLSX.utils.encode_cell({ r: R, c: 6 }); // c:5 → PRICE
        const cell = worksheet[priceCell];
        if (cell && typeof cell.v === 'number') {
            cell.z = "$#,##0.00"; // Formato de moneda
        }
    }

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Hoja1");
    XLSX.writeFile(workbook, "exportado.xlsx");
}
function inicializarSelects() {
    dealerid    = $('#dealerhome').val();
    monthid     = $('#prodmonthhome').val();
    tipoordenid = $('#typeorderhome').val();
    puertoid    = $('#porthome').val();
    console.log('Charge controls select dealerid='+dealerid+'&month='+monthid+'&tipoordenid='+tipoordenid+'&puertoid='+puertoid);
    $('.itemselect').select2({
        ajax: {
        url: '../admin/datosselect.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return {
            q: params.term,
            ctrl: 'busitem',
            dealerid: $('#dealerhome').val(),
            month: $('#prodmonthhome').val(),
            tipoordenid: $('#typeorderhome').val(),
            puertoid: $('#porthome').val()
            };
        },
        processResults: function (data) {
            return { results: data };
        },
        cache: true
        },
        placeholder: 'Search item',
        minimumInputLength: 1,
        templateResult: formatearProducto,
        templateSelection: formatearSeleccion
    });
    setTimeout(() => {
        CapturarUltimo();
    }, 1000);
}
function CapturarUltimo(){
    // Escuchador que se ejecuta UNA VEZ y guarda el ítem seleccionado
    $('#itemselect').on('select2:select', function (e) {
        ultimoItemSeleccionado = e.params.data;
        console.log('Item seleccionado:', ultimoItemSeleccionado);
    });
}
function formatearProducto(producto) {
    //console.log(producto);
    if (!producto.id) return producto.text;
    return $(`
        <div style="display: flex; align-items: center; color: ${producto.rgb}">
        ${producto.imagen}
        <span> ${producto.text}</span>
        </div>
    `);
}
function formatearSeleccion(producto) {
    if (!producto.id) return producto.text;
    return $(`
        <div style="display: flex; align-items: center; color: ${producto.rgb}">
        ${producto.imagen}
        <span> ${producto.text}</span>
        </div>
    `);
}
function CargaDetOrden(){
    const selectmon = document.getElementById("prodmonthhome");
    const textomont = selectmon.options[selectmon.selectedIndex].text;
    $('#TabDetailOrder').load('editorderdet.php?month='+textomont.replace(' ','_')+'&orderid='+$('#ordenidsel').val(),function(){
        setTimeout(() => {
            inicializarSelects();
        }, 1000);
    });
}
function ExecuteCalculeData(){
    itemSeleccionado = $('#itemselect').val();
    dealerid    = $('#dealerhome').val();
    monthid     = $('#prodmonthhome').val();
    tipoordenid = $('#typeorderhome').val();
    puertoid    = $('#porthome').val();
    if (itemSeleccionado === '') {
        $('#modeladd').html('');
        $('#unitpriceilacadd').html('');
        $('#unitpricedlradd').html('');
        $('#qtyoptadd').val('0');
        $('#unitpriceilacmod').val('0');
        $('#priceadd').html('');
        return;
    }
    $.ajax({
        url: '../admin/datosselect.php',
        type: 'POST',
        data: { 
            opcn: "detitem",
            item: itemSeleccionado
        },
        dataType: 'json',
        success: function (response) {
            if (response.length > 0) {
                const item = response[0];
                $('#modeladd').html(item.modelname);
                $('#priceadd').html('$ 0');
                if (item.unitpriceusd != null) {
                    unitDlr = parseFloat(item.unitpriceusd).toLocaleString();
                    $('#unitpriceilacadd').html('$ ' + unitDlr);
                }
                unitIla = parseFloat(item.unitpricecif).toLocaleString();
                $('#unitpricedlradd').html('$ ' + unitIla);
                $('#unitpriceilacmod').val(item.unitpricecif);
                
            } else {
                console.log('No data returned');
            }
        },
        error: function () {
            console.log("Not data found");
        }
    });
}
function RecalculatePrice(){
    let precio = $('#unitpriceilacmod').val();
    let cantidad = $('#qtyoptadd').val();
    valorCompleto = parseFloat(precio * cantidad).toLocaleString();
    $('#priceadd').html('$ '+valorCompleto);
}
function AddItem(){
    $("#CompletoDesactivar").show();
    let prodmonthhome = $('#prodmonthhome option:selected').text();
    let itemId = $('#itemselect').val();
    let model = $('#modeladd').html();
    let priceILAC = $('#unitpriceilacadd').html();
    let priceCIF = $('#unitpricedlradd').html();
    let qty = parseInt($('#qtyoptadd').val());
    let priceAddItem = $('#unitpriceilacmod').val()*$('#qtyoptadd').val();
    let bodyopt = $('#bodyoptadd').val();
    let orderno = $('#ordernoadd').val();
    if(!orderno){
        Messenger().post({
                message: 'Please complete the information, the ORDER NO field is required.!',
                type: 'error',
                showCloseButton: true
            });
        return;
    }
    let prodalloca = $('#allocationadd').val();
    let background  = $('#backgroundadd').val();
    if (!itemId || isNaN(qty) || qty <= 0 || !ultimoItemSeleccionado) {
        Messenger().post({
                message: 'Please complete the information, the number of items must be greater than 0!',
                type: 'error',
                showCloseButton: true
            });
        return;
    }
    let imagen = ultimoItemSeleccionado.imagen || '';
    let textoIt = ultimoItemSeleccionado.text || '';
    let color = ultimoItemSeleccionado.rgb || '';

    console.log(imagen);
    let subtotal = $('#priceadd').html();
    let nuevaFila = "";
    if($('#ctrlR').val() != "1"){
        nuevaFila = `<tr>
            <td>${prodmonthhome}</td>
            <td style="font-size:13px;font-weight: bold;background-color: rgba(0,0,0,0.24);color:${color}">${imagen} ${textoIt}</td>
            <input type="hidden" class="cantUnidades" name="cantUnidades[]" value="${qty}">
            <input type="hidden" class="vlrUnidades" name="vlrUnidades[]" value="${priceAddItem}">
            <input type="hidden" class="itemId" name="itemId[]" value="${itemId}">
            <input type="hidden" class="bodyopt" name="bodyopt[]" value="${bodyopt}">
            <input type="hidden" class="orderno" name="orderno[]" value="${orderno}">
            <input type="hidden" class="prodalloca" name="prodalloca[]" value="${prodalloca}">
            <input type="hidden" class="background" name="background[]" value="${background}">
            <td style="text-align: center;">${model}</td>
            <td style="text-align: right;">${priceCIF}</td>
            <td>${bodyopt}</td>
            <td style="text-align: center;font-size:13px;font-weight: bold;" class="cantidadmod">${qty}</td>
            <td>${orderno}</td>
            <td style="text-align: right;font-size:13px;font-weight: bold;" class="vlrTotal">${subtotal}</td>
            <td>${prodalloca}</td>
            <td>${background}</td>
            <td style="text-align: center;">
                <button type="button" class="btn btn-danger btn-xs mb-xs btnEliminar" onclick="DelItem(this);"><i class="fa fa-minus"></i></button>
            </td>
        </tr>`;
    }else{
        nuevaFila = `<tr>
            <td>${prodmonthhome}</td>
            <td style="font-size:13px;font-weight: bold;background-color: rgba(0,0,0,0.24);color:${color}">${imagen} ${textoIt}</td>
            <input type="hidden" class="cantUnidades" name="cantUnidades[]" value="${qty}">
            <input type="hidden" class="vlrUnidades" name="vlrUnidades[]" value="${priceAddItem}">
            <input type="hidden" class="itemId" name="itemId[]" value="${itemId}">
            <input type="hidden" class="bodyopt" name="bodyopt[]" value="${bodyopt}">
            <input type="hidden" class="orderno" name="orderno[]" value="${orderno}">
            <input type="hidden" class="prodalloca" name="prodalloca[]" value="${prodalloca}">
            <input type="hidden" class="background" name="background[]" value="${background}">
            <td style="text-align: center;">${model}</td>
            <td style="text-align: right;">${priceILAC}</td>
            <td style="text-align: right;">${priceCIF}</td>
            <td>${bodyopt}</td>
            <td style="text-align: center;font-size:13px;font-weight: bold;" class="cantidadmod">${qty}</td>
            <td>${orderno}</td>
            <td style="text-align: right;font-size:13px;font-weight: bold;" class="vlrTotal">${subtotal}</td>
            <td>${prodalloca}</td>
            <td>${background}</td>
            <td style="text-align: center;">
                <button type="button" class="btn btn-danger btn-xs mb-xs btnEliminar" onclick="DelItem(this);"><i class="fa fa-minus"></i></button>
            </td>
        </tr>`;
    }
    
    $('#tablaItems tbody').append(nuevaFila);
    limpiarDatos();
    CalcularTotales();
}
function limpiarDatos(){
    $('#itemselect').val(0);
    inicializarSelects();
    $('#qtyoptadd').val('0');
    $('#bodyoptadd').val('');
    $('#ordernoadd').val('');
    $('#allocationadd').val('');
    $('#backgroundadd').val('');
    $('#priceadd').html('$ 0');
    $('#modeladd').html('');
    $('#unitpriceilacadd').html('');
    $('#unitpricedlradd').html('');
    $('#unitpriceilacmod').val('0');
    ultimoItemSeleccionado = null;
}
function CalcularTotales(){
    let total = 0;
    $('.cantUnidades').each(function () {
      const valor = parseFloat($(this).val()) || 0;
      total += valor;
    });
    $('#CantNewOrder').html(total);

    let totalP = 0;
    $('.vlrUnidades').each(function () {
      const valor = parseFloat($(this).val()) || 0;
      totalP += valor;
    });
    $('#ValNewOrder').html('$ '+totalP.toLocaleString());
}
function exportarDetalleExcel() {
  const tabla = document.getElementById("tablaItems");
  if (!tabla) {
    console.error("No se encontró la tabla con id 'tablaItems'");
    return;
  }

  const filas = Array.from(tabla.querySelectorAll("tbody tr"));
  const encabezados = [];
  const datos = [];

  // Índices importantes
  const idx_model = 2; // MODEL NAME|EZSPEC|YEAR (ya separado en 3)
  const idx_item = 1;  // ITEM#

  // Colores que quieres detectar
  const colores = ["999", "729", "951"];

  // Obtener encabezados (excepto la última columna de operaciones)
  const ths = tabla.querySelectorAll("thead tr th");
  for (let i = 0; i < ths.length - 1; i++) {
    if (i === idx_model) {
      // La columna MODEL NAME se divide en 3
      encabezados.push("MODEL NAME");
      encabezados.push("EZSPEC");
      encabezados.push("YEAR");
    } else if (i === idx_item) {
      // En ITEM# insertamos ITEM# y luego COLOR
      encabezados.push("ITEM#");
      encabezados.push("COLOR");
    } else {
      encabezados.push(ths[i].innerText.trim());
    }
  }

  // Recorrer filas (omitimos la plantilla primera fila)
  for (let i = 1; i < filas.length; i++) {
    const celdas = filas[i].querySelectorAll("td");
    const fila = [];

    for (let j = 0; j < celdas.length - 1; j++) {
      let celda = celdas[j];
      let input = celda.querySelector("input, select");
      let texto = input ? input.value.trim() : celda.textContent.trim();

      if (j === idx_model) {
        // Dividir MODEL NAME|EZSPEC|YEAR en 3 columnas
        const partes = texto.split("|").map(p => p.trim());
        fila.push(partes[0] || "");
        fila.push(partes[1] || "");
        fila.push(partes[2] || "");
      } else if (j === idx_item) {
        // Extraer color y texto sin color
        let color = "";
        let texto_sin_color = texto;

        // Checar si empieza con alguno de los colores
        for (const c of colores) {
          if (texto.startsWith(c + " ")) {
            color = c;
            texto_sin_color = texto.slice(c.length).trim();
            break;
          }
        }

        fila.push(texto_sin_color);
        fila.push(color);
      } else {
        fila.push(texto);
      }
    }

    datos.push(fila);
  }

  // Crear hoja Excel
  const ws = XLSX.utils.aoa_to_sheet([
    encabezados,
    ...datos
  ]);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Datos");
  XLSX.writeFile(wb, "OrderDetails.xlsx");
}


var TipeStatusSave = 0;
var myModal = null;
function ParaDraf(){
    var cantNewOrder = parseInt($('#CantNewOrder').html(), 10); // base 10
    if(cantNewOrder>0){
        myModal = new bootstrap.Modal(document.getElementById('ConfirmModalCenter'));
        myModal.show();
        $('#exampleModalLongTitle').html("Do you want to <b>DRAFT</b> the Order?");
        $('#ModalBody').html("In this Stage, changes to the order are still allowed.");
        $('#btnAccion').text('Save Draft');
        TipeStatusSave = 7;
    }else{
        Messenger().post({
            message: 'The order must contain items, otherwise it cannot be processed.!',
            type: 'error',
            showCloseButton: true
        });
    }
}
function ParaNew(){
    var cantNewOrder = parseInt($('#CantNewOrder').html(), 10); // base 10
    if(cantNewOrder>0){
        myModal = new bootstrap.Modal(document.getElementById('ConfirmModalCenter'));
        myModal.show();
        $('#exampleModalLongTitle').html("Do you want to <b>SUBMIT</b> the Order?");
        $('#ModalBody').html("Please make sure you input all necessary remarks [Order No, Prod Allocation, Background Request and Qty items]<br><br>No order Change or cancellation allowed");
        $('#btnAccion').text('Submit');
        TipeStatusSave = 2;
    }else{
        Messenger().post({
            message: 'The order must contain items, otherwise it cannot be processed.!',
            type: 'error',
            showCloseButton: true
        });
    }
}

function GuardarBorrador(){
    myModal.hide();
    //e.preventDefault();
    var estadoOrden = TipeStatusSave;
    var cantNewOrder = parseInt($('#CantNewOrder').html(), 10); // base 10
    if(cantNewOrder>0){
        Messenger().post({
            message: 'The information is being loaded, please wait a moment until it finishes.!',
            type: 'info',
            showCloseButton: true
        });
        var prodmonthhome   = $('#prodmonthhome').val();
        var prodmonthhome   = $('#prodmonthhome').val();
        var orderhome       = $('#orderhome').val();
        var dealerhome      = $('#dealerhome').val();
        var typeorderhome   = $('#typeorderhome').val();
        var porthome        = $('#porthome').val();
        var ordenidsel      = $('#ordenidsel').val();
        
        var datos = [];
        console.log('Filas en tabla:', $('#tablaItems tbody tr').length);

        $('#tablaItems tbody tr').each(function(i, elem) { 
            console.log('Procesando fila', i + 1);
            //var itemSelectElement = $(this).find('[name="itemselect[]"]');
            /*console.log('Valor itemselect:', itemSelectElement.val());
            console.log('Texto itemselect:', itemSelectElement.find('option:selected').text());*/
            if ($(this).find('[name="itemId[]"]').val() !== undefined && $(this).find('[name="itemId[]"]').val() !== '') {
                var fila = {
                    itemselect: $(this).find('[name="itemId[]"]').val(),
                    bodyoptadd: $(this).find('[name="bodyopt[]"]').val(),
                    qtyoptadd: $(this).find('[name="cantUnidades[]"]').val(),
                    ordernoadd: $(this).find('[name="orderno[]"]').val(),
                    allocationadd: $(this).find('[name="prodalloca[]"]').val(),
                    backgroundadd: $(this).find('[name="background[]"]').val()
                };
                datos.push(fila);
            }
        });
        console.log('Datos recopilados:', datos);
        // Enviar por AJAX
        $.ajax({
            url: '../controllers/neworderdet.php',
            method: 'POST',
            data: {
                items: JSON.stringify(datos),
                prodmonthhome: prodmonthhome,
                orderhome: orderhome,
                dealerhome: dealerhome,
                estadoOrden: estadoOrden,
                typeorderhome: typeorderhome,
                porthome:porthome,
                ordenid:ordenidsel,
                opcn: "UpdateData"
            },
            success: function(respuesta) {
                if (respuesta.status === 'success') {
                    console.log('Guardado correctamente:', respuesta);
                    let msg = Messenger().post({
                        message: 'The information has been stored correctly, you can continue with another order or simply go to the home page.',
                        actions: {
                            cancel: {
                                label: 'Go Home',
                                action: function() {
                                    window.location.href = '../admin';
                                }
                            }
                        },
                        type: 'success',
                        showCloseButton: true
                    });
                    /*Messenger().post({
                        message: 'The information has been stored correctly, you can continue with another order or simply go to the home page.!',
                        type: 'success',
                        showCloseButton: true
                    });*/
                    dealerid = $('#dealerhome').val();
                    monthid = $('#prodmonthhome').val();
                    $('#SafeDraft').prop('disabled', true);
                    $('#SafeSubmit').prop('disabled', true);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    console.error('Error:' + respuesta.message);
                    Messenger().post({
                        message: 'The order could not be stored, please check your connection and the information provided and try again.!',
                        type: 'error',
                        showCloseButton: true
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Messenger().post({
                    message: 'The order could not be stored, please check your connection and the information provided and try again.!',
                    type: 'error',
                    showCloseButton: true
                });
            }
        });
    }else{
        Messenger().post({
            message: 'The order must contain items, otherwise it cannot be processed.!',
            type: 'error',
            showCloseButton: true
        });
    }
}

function EnviarNota(){
    var statusid    = $('#changestatus').val();
    var note        = $('#changenote').val();
    var visible     = "0";
    var ordenid     = $('#ordenidsel').val();
    var orderhome   = $('#orderhome').val();
    if ($("#changevisible").is(':checkbox')) {
        // Si es checkbox, tomar si está marcado
        visible = $('#changevisible').is(':checked') ? 1 : 0;
    } else {
        // Si es input hidden u otro, tomar el value
        visible = $('#changevisible').val();
    }
    $.ajax({
        url: '../controllers/neworderdet.php',
        method: 'POST',
        data: {
            statusid: statusid,
            note: note,
            visible: visible,
            ordenid: ordenid,
            orderhome: orderhome,
            opcn: "SendChange"
        },
        success: function(respuesta) {
            if (respuesta.status === 'success') {
                console.log('Guardado correctamente:', respuesta);
                let msg = Messenger().post({
                    message: 'The information has been sent correctly',
                    type: 'success',
                    showCloseButton: true
                });
                $('#changenote').val('');
                CargarMensajes();
            } else {
                console.error('Error:' + respuesta.message);
                Messenger().post({
                    message: 'The information could not be sent, please check your connection and try again.!',
                    type: 'error',
                    showCloseButton: true
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Messenger().post({
                message: 'The information could not be sent, please check your connection and try again.!',
                type: 'error',
                showCloseButton: true
            });
        }
    });
}

function CargarMensajes(){
    console.log("Consultando mensajes actualizados");
    $('#MensajesActuales').load('mensajesactuales.php?orderid='+$('#ordenidsel').val(),function(){
        //setTimeout(() => { CargarMensajes(); }, 10000);
    });
}

function MarcarLeido(ordennotaid){
    $.ajax({
        url: '../controllers/neworderdet.php',
        method: 'POST',
        data: {
            ordennotaid: ordennotaid,
            opcn: "SendReadNote"
        },
        success: function(respuesta) {
            CargarMensajes();
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Messenger().post({
                message: 'The information could not be sent, please check your connection and try again.!',
                type: 'error',
                showCloseButton: true
            });
        }
    });
}

function BorrarOrdenMsg(){
    myModal = new bootstrap.Modal(document.getElementById('ConfirmModalCenterDelete'));
    myModal.show();
}

function BorrarOrden(){
    $.ajax({
        url: '../controllers/neworderdet.php',
        method: 'POST',
        data: {
            ordenid: $('#ordenidsel').val(),
            opcn: "DeleteOrder"
        },
        success: function(respuesta) {
            console.log('Guardado correctamente:', respuesta);
            let msg = Messenger().post({
                message: 'The order has been successfully deleted',
                type: 'success',
                showCloseButton: true
            });
            myModal.hide();
            setTimeout(() => {
                history.back();
            }, 7000);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Messenger().post({
                message: 'The information could not be sent, please check your connection and try again.!',
                type: 'error',
                showCloseButton: true
            });
        }
    });
}
