// Variable global
let ultimoItemSeleccionado = null;

$(function(){
    dealerid = 0;
    monthid = 0;
    $("#CompletoDesactivar").hide();
    $("#prodmonthhome").select2({});
    $("#typeorderhome").select2({});
    $("#porthome").select2({});
    
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
        CargarHome();
    });
    function CargarHome(){
        const select = document.getElementById("dealerhome");
        const texto = select.options[select.selectedIndex].text;
        const countryName = texto.split(/\(/);
        $("#countryhome").val(countryName[1]?.replace(')', ''));
        dealerid = $('#dealerhome').val();
        monthid = $('#prodmonthhome').val();
        console.log("Dealer: " + dealerid + " | Mes: " + monthid);
        $.get('../admin/datosselect.php?ctrl=codorden&dealerid='+dealerid+'&month='+monthid, function(respuesta) {
            $('#orderhome').val(respuesta);
        });
        //Deja campos iniciales en blanco
        $('#typeorderhome').append(new Option("Select one", "0"));
        $('#porthome').append(new Option("Select one", "0"));
        //Deja campos iniciales en blanco
        $.ajax({
            url: '../admin/datosselect.php?ctrl=tipoordenes&dealerid='+dealerid,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#typeorderhome').empty();
                //Deja campos iniciales en blanco
                $('#typeorderhome').append(new Option("Select one", "0"));
                data.forEach(function(item) {
                    $('#typeorderhome').append(new Option(item.text, item.id));
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
                //Deja campos iniciales en blanco
                $('#porthome').append(new Option("Select one", "0"));
                data.forEach(function(item) {
                    $('#porthome').append(new Option(item.text, item.id));
                });
                $('#porthome').select2({
                    placeholder: 'Select one option'
                });
            }
        });
        const selectmon = document.getElementById("prodmonthhome");
        const textomont = selectmon.options[selectmon.selectedIndex].text;
        //console.log(textomont.replace(' ','_'));
        $('#TabDetailOrder').load('neworderdet.php?month='+textomont.replace(' ','_'),function(){
            setTimeout(() => {
                inicializarSelects();
                validarBotones();
            }, 1000);
        });
    }
    CargarHome();
});

function validarBotones(){
    // Tomar la fecha desde el input hidden
    let fechaFinaliza = $("#fechaFinaliza").val();
    // Convertir a formato compatible universal
    let fechaLimite = new Date(fechaFinaliza.replace(" ", "T"));
    // Fecha actual
    let ahora = new Date();
    // Comparar y ocultar botones si ya venció
    if (ahora > fechaLimite) {
        $("#btn-SaveDraft").hide();
        $("#btn-SubmitOrder").hide();
    }
    console.log("Maxima: "+fechaFinaliza+" Limite: "+fechaLimite+" Ahora: "+ahora);
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
    let prodalloca = $('#allocationadd').val();
    let background  = $('#backgroundadd').val();
    if(!orderno){
        Messenger().post({
                message: 'Please complete the information, the ORDER NO field is required.!',
                type: 'error',
                showCloseButton: true
            });
        return;
    }
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

function DelItem(boton) {
    // Eliminar la fila completa
    const fila = boton.closest('tr');
    if (fila) {
      fila.remove();
      // Recalcular total
      CalcularTotales();
    }
}

function LimpiarTabla(){
    const selectmon = document.getElementById("prodmonthhome");
    const textomont = selectmon.options[selectmon.selectedIndex].text;
    //console.log(textomont.replace(' ','_'));
    $('#TabDetailOrder').load('neworderdet.php?month='+textomont.replace(' ','_'),function(){
        setTimeout(() => {
            inicializarSelects();
        }, 500);
    });
    $('#CantNewOrder').html('0');
    $('#ValNewOrder').html('$ 0');
    $("#CompletoDesactivar").hide();

}

function exportarTablaAExcel() {
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
        if($("#typeorderhome").val()=="0"){
            Messenger().post({
                message: 'Please select the type of order.!',
                type: 'error',
                showCloseButton: true
            });
        }else{
            if($("#porthome").val()=="0"){
                Messenger().post({
                    message: 'Please select the port.!',
                    type: 'error',
                    showCloseButton: true
                });
            }else{
        myModal = new bootstrap.Modal(document.getElementById('ConfirmModalCenter'));
        myModal.show();
        $('#exampleModalLongTitle').html("Do you want to <b>DRAFT</b> the Order?");
        $('#ModalBody').html("In this Stage, changes to the order are still allowed.");
        $('#btnAccion').text('Save Draft');
        TipeStatusSave = 7;
            }
        }
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
        if($("#typeorderhome").val()=="0"){
                Messenger().post({
                    message: 'Please select the type of order.!',
                    type: 'error',
                    showCloseButton: true
                });
        }else{
            if($("#porthome").val()=="0"){
                Messenger().post({
                    message: 'Please select the port.!',
                    type: 'error',
                    showCloseButton: true
                });
            }else{
                myModal = new bootstrap.Modal(document.getElementById('ConfirmModalCenter'));
                myModal.show();
                $('#exampleModalLongTitle').html("Do you want to <b>SUBMIT</b> the Order?");
                $('#ModalBody').html("Please make sure your input all necessary remarks [Order No, Prod Allocation, Background Request and Qty items]<br><br>No order Change or cancellation allowed");
                $('#btnAccion').text('Submit');
                TipeStatusSave = 2;
            }
        }
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
        var orderhome       = $('#orderhome').val();
        var dealerhome      = $('#dealerhome').val();
        var typeorderhome   = $('#typeorderhome').val();
        var porthome        = $('#porthome').val();
        
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
                opcn: "SaveData"
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
                    $.get('../admin/datosselect.php?ctrl=codorden&dealerid='+dealerid+'&month='+monthid, function(respuesta) {
                        $('#orderhome').val(respuesta);
                    });
                    LimpiarTabla();
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
