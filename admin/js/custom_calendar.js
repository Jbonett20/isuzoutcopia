const myModal = new bootstrap.Modal(document.getElementById('modalAdd'))
$(function () {
  $('#estado').select2({
    placeholder: 'Select an option'
  })
  $('#month').select2({
    placeholder: 'Select an option'
  })

  $('#yearPicker').datetimepicker({
    format: 'YYYY',
    viewMode: 'years',
    icons: {
      time: 'fa fa-clock',
      date: 'fa fa-calendar',
      up: 'fa fa-chevron-up',
      down: 'fa fa-chevron-down',
      previous: 'fa fa-chevron-left',
      next: 'fa fa-chevron-right',
      today: 'fa fa-calendar-check-o',
      clear: 'fa fa-trash',
      close: 'fa fa-times'
    }
  })

  $('#startDate').datetimepicker({
    format: 'YYYY-MM-DD HH:mm:ss'
  })

  $('#endDate').datetimepicker({
    format: 'YYYY-MM-DD HH:mm:ss'
  })

  $('#year').on('focus', function () {
    $('#yearPicker').datetimepicker('show')
  })

  $('#inicia').on('focus', function () {
    $('#startDate').datetimepicker('show')
  })

  $('#finaliza').on('focus', function () {
    $('#endDate').datetimepicker('show')
  })

  CargarData()
  getCalendario()
})

function CargarData () {
  $('#TabItems').load(
    'listdetailcalendar.php',
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

function DataTables () {
  var unsortableColumns = []
  $('#TableListUsers')
    .find('thead th')
    .each(function () {
      if ($(this).hasClass('no-sort')) {
        unsortableColumns.push({ bSortable: false })
      } else {
        unsortableColumns.push({ bSortable: true })
      }
    })

  $('#TableListUsers').dataTable({
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
  document.getElementById('contentEstado').style.display = 'none'

  if ($('#estado').hasClass('select2-hidden-accessible')) {
    $('#estado').select2('destroy')
  }
  document.getElementById('estado').removeAttribute('required')

  document.getElementById('estado').value = ''
  document.getElementById('registroid').value = ''
}

function EditRegistro (el) {
  myModal.show()

  const item = JSON.parse(el.getAttribute('data-item'))

  document.getElementById('registroid').value = item.calendarioid
  document.getElementById('estado').value = item.estadoid
  document.getElementById('year').value = item.ano
  document.getElementById('month').value = item.mesnum
  document.getElementById('inicia').value = item.inicia
  document.getElementById('finaliza').value = item.finaliza

  if (!$('#estado').hasClass('select2-hidden-accessible')) {
    $('#estado').select2({
      placeholder: 'Select an option'
    })
  }
  document.getElementById('contentEstado').style.display = 'flex'
  document.getElementById('estado').setAttribute('required', 'required')

  $('#estado').val(item.estadoid).trigger('change')
  $('#month').val(item.mesnum).trigger('change')
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
      const response = await fetch('../controllers/calendar.php', {
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

/* Calendario */

async function getCalendario () {
  const date = new Date()
  const d = date.getDate()
  const m = date.getMonth()
  const y = date.getFullYear()

  const formData = new FormData()
  formData.append('opc', 'listCalendar')

  try {
    const response = await fetch('../controllers/calendar.php', {
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

    function getRandomColor () {
      const colors = [
        '#007bff',
        '#28a745',
        '#dc3545',
        '#ffc107',
        '#17a2b8',
        '#6f42c1',
        '#fd7e14',
        '#20c997',
        '#6610f2'
      ]
      return colors[Math.floor(Math.random() * colors.length)]
    }

    const mappedEvents = data.eventos.map(item => ({
      title: `${item.title}`,
      start: item.inicia,
      end: item.finaliza,
      backgroundColor: item.color,
      textColor: '#fff'
    }))

    $('#calendar').fullCalendar({
      header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month'
      },
      editable: false,
      droppable: false,
      selectable: false,
      eventLimit: true,
      events: mappedEvents,
      eventRender: function (event, element) {
        element.attr('title', event.title) // Tooltip básico del navegador
      }
    })
  } catch (error) {
    console.error(error.message)
  }
}
