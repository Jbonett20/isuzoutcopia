$(function(){
    dealerid = 0;
    monthid = 0;
    $("#prodmonthhome").select2({});
    
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
        $.ajax({
            url: '../admin/datosselect.php?ctrl=tipoordenes&dealerid='+dealerid,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#typeorderhome').empty();
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
        $('#TabListItems').load('listdetail.php?month='+monthid+'&dealer='+dealerid,function(response, status, xhr) {
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
    CargarHome();
});

function exportarTablaAExcel(){
    const table = $('#TableListItems').DataTable();
    const wb = XLSX.utils.book_new();
    const ws_data = [];
    const headers = table.columns().header().toArray().map(header => header.innerText.trim());
    ws_data.push(headers);
    table.rows().every(function(rowIdx, tableLoop, rowLoop) {
        const rowData = this.data();
        const row = [];
        const rowNode = this.node();
        $(rowNode).find('td').each(function(c) {
            let cellValue = $(this).text().trim();
            if (c === 0) {
                row.push(cellValue.toString());
            } else {
                if (cellValue.startsWith('$')) {
                    let numericValue = cellValue.replace('$', '').replace(/,/g, '').replace(/\s/g, '');
                    let floatValue = parseFloat(numericValue);
                    if (!isNaN(floatValue)) {
                        row.push(floatValue);
                    } else {
                        row.push(cellValue);
                    }
                } else {
                    row.push(cellValue);
                }
            }
        });
        ws_data.push(row);
    });
    const ws = XLSX.utils.aoa_to_sheet(ws_data);
    XLSX.utils.book_append_sheet(wb, ws, "Price List Data");
    XLSX.writeFile(wb, 'PriceList.xlsx');
}

function exportarTablaAPDF() {
    const table = $('#TableListItems').DataTable();
    const headers = table.columns().header().toArray().map(header => header.innerText.trim());
    const rows = [];

    table.rows().every(function () {
        const rowNode = this.node();
        const rowData = [];
        $(rowNode).find('td').each(function (c) {
            let cellValue = $(this).text().trim();
            if (cellValue.startsWith('$')) {
                let numericValue = cellValue.replace('$', '').replace(/,/g, '').replace(/\s/g, '');
                let floatValue = parseFloat(numericValue);
                if (!isNaN(floatValue)) {
                    rowData.push(floatValue.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
                } else {
                    rowData.push(cellValue);
                }
            } else {
                rowData.push(cellValue);
            }
        });
        rows.push(rowData);
    });

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'pt',
        format: 'a4'
    });

    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 40,
        theme: 'grid',
        headStyles: { fillColor: [52, 58, 64] }, // dark gray
        styles: { fontSize: 8, cellPadding: 4 },
        margin: { top: 40 }
    });

    doc.save('PriceList.pdf');
}