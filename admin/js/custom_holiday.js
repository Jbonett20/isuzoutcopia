const myModal = new bootstrap.Modal(document.getElementById('modalAdd'))

$(function () {
  $("#fechaPicker").datetimepicker({
    format: "YYYY-MM-DD",
     minDate: moment(),
  });

  $('#estado').select2({
    placeholder: 'Select an option'
  })

  $('#country').select2({
    placeholder: 'Select an option',
    templateResult: formatState,
    templateSelection: formatSelection,
  })

  // Formato personalizado para select con banderas
  function formatState(state) {
    if (!state.id) return state.text;
    var baseUrl = "../admin/img/country";
    var $state = $(
      '<span><img src="' +
        baseUrl +
        "/" +
        state.title +
        '" style="width:20px;" /> ' +
        state.text +
        "</span>"
    );
    return $state;
  }

  function formatSelection(state) {
    if (!state.id) return state.text;
    var baseUrl = "../admin/img/country";
    var $state = $(
      '<span><img src="' +
        baseUrl +
        "/" +
        state.title +
        '" style="width:20px;" /> ' +
        state.text +
        "</span>"
    );
    $state.find("span").text(state.text);
    $state.find("img").attr("src", baseUrl + "/" + state.title);
    return $state;
  }

  CargarData()
})

// ✅ Declaramos CargarData en global para que se pueda llamar desde cualquier parte
function CargarData () {
  $('#TabItems').load(
    'listdetailholiday.php',
    function (response, status, xhr) {
      if (status === 'success') {
        console.log('Contenido cargado exitosamente')
        DataTables()
      } else {
        console.log('Error al cargar:', xhr.status, xhr.statusText)
      }
    }
  )
}

// Inicialización de DataTable con columnas ordenables y no ordenables
function DataTables () {
  var unsortableColumns = []
  $('#TableListHolidays')
    .find('thead th')
    .each(function () {
      if ($(this).hasClass('no-sort')) {
        unsortableColumns.push({ bSortable: false })
      } else {
        unsortableColumns.push({ bSortable: true })
      }
    })

  $('#TableListHolidays').dataTable({
    sDom: "<'row'<'col-lg-6 hidden-sm-down'l><'col-lg-6'f>r>t<'row'<'col-lg-6'i><'col-lg-6'p>>",
    oLanguage: {
      sLengthMenu: '_MENU_',
      sInfo: 'Showing <strong>_START_ to _END_</strong> of _TOTAL_ entries'
    },
    oClasses: {
      sFilter: 'float-right',
      sFilterInput: 'form-control input-rounded ml-sm'
    },
    aoColumns: unsortableColumns
  })

  $('div.dataTables_length select').addClass('form-control')
}

function NewRegistro () {
  document.getElementById('frmReg').reset()
  myModal.show()

  if (document.getElementById('estado')) {
    document.getElementById('contentEstado').style.display = 'none'
    if ($('#estado').hasClass('select2-hidden-accessible')) {
      $('#estado').select2('destroy')
    }
    document.getElementById('estado').removeAttribute('required')
    document.getElementById('estado').value = ''
  }

  //$("#rol").val("").trigger("change");
  // $('#rol').val(parseInt($('#rol').find('option:selected').val())).trigger('change');
  // $("#dealer").val("").trigger("change");
  $("#country").val("").trigger("change");

  document.getElementById('registroid').value = ''
}

function EditRegistro (el) {
  myModal.show()

  const item = JSON.parse(el.getAttribute('data-item'))

  document.getElementById('registroid').value = item.festivoid
  document.getElementById('holiday').value = item.nombre
  document.getElementById('fecha').value = item.fecha
  if (document.getElementById('estado')) {
    document.getElementById('contentEstado').style.display = 'flex'
    document.getElementById('estado').value = item.estadoid

    if (!$('#estado').hasClass('select2-hidden-accessible')) {
      $('#estado').select2({
        placeholder: 'Select an option'
      })
    }
    document.getElementById('estado').setAttribute('required', 'required')
    $('#estado').val(item.estadoid).trigger('change')
  }
  

  $('#country').val(item.paisid).trigger('change')
}

document
  .getElementById('frmReg')
  .addEventListener('submit', async function (e) {
    e.preventDefault()

    const form = e.target
    const formData = new FormData(form)

    const registroid = document.getElementById('registroid')

    if (registroid.value === '') {
      formData.delete('estado')
      $('#estado').prop('required', false)
    }

    formData.append('opc', registroid.value === '' ? 'crear' : 'editar')

    if (!form.checkValidity()) {
      form.reportValidity()
      return
    }

    if (formData.get('opc') === 'crear') {
      formData.delete('registroid')
    }

    try {
      const response = await fetch('../controllers/holidays.php', {
        method: 'POST',
        body: formData
      })

      if (!response.ok)
        throw new Error(
          'The data must contain errors, otherwise it cannot be processed.!'
        )

      const data = await response.json()

      if (!data.success) {
        throw new Error(
          data.message ||
            'The data must contain errors, otherwise it cannot be processed.!'
        )
      }

      myModal.hide()
      document.getElementById('frmReg').reset()

      Messenger().post({
        message: data.message,
        type: 'success',
        showCloseButton: true
      })

      CargarData()
    } catch (error) {
      Messenger().post({
        message: error.message,
        type: 'error',
        showCloseButton: true
      })
    }
  })
