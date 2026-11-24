// Variable global para mantener el KPI actualmente activo
let currentActiveKPI = 'sales';

document.addEventListener("DOMContentLoaded", function () {
  cargarEmpresas();
  cargarYears();
  // Crear el widget con tabs inmediatamente
  createKpiTabsWidget();

  // Cargar SALES por defecto al iniciar
  setTimeout(() => {
    selectDefaultKPI('sales');
  }, 1000);
});

// Configuración de campos por KPI
const KPI_CONFIGS = {
  sales: {
    title: 'Vehicle Sales',
    icon: 'fa-chart-line',
    table: 'kpi_sales',
    fields: [
      { id: 'unitsalescv', label: 'Units Sales CV (qty)', type: 'number', required: true, icon: 'fa-truck' },
      { id: 'unitsaleslcv', label: 'Units Sales LCV (qty)', type: 'number', required: true, icon: 'fa-van' },
      { id: 'allunitsales', label: 'All Units Sales (qty)', type: 'number', calculated: true, formula: 'calculateAllUnitSales', icon: 'fa-calculator' }
    ]
  },
  workshop: {
    title: 'Workshop Sales',
    icon: 'fa-tools',
    table: 'kpi_workshopsales',
    fields: [
      { id: 'cvworkshoplaborsales', label: 'CV Workshop Labor Sales ($USD)', type: 'number', required: true, icon: 'fa-wrench' },
      { id: 'lcvworkshoplaborsales', label: 'LCV Workshop Labor Sales ($USD)', type: 'number', required: true, icon: 'fa-cogs' },
      { id: 'allworkshoplaborsales', label: 'All Workshop Labor Sales ($USD)', type: 'number', calculated: true, formula: 'calculateAllWorkshopLaborSales', icon: 'fa-calculator' },
      { id: 'cvwarrantylabor', label: 'CV Workshop Warranty Labor Amount ($USD)', type: 'number', required: true, icon: 'fa-shield-alt' },
      { id: 'lcvwarrantylabor', label: 'LCV Workshop Warranty Labor Amount ($USD)', type: 'number', required: true, icon: 'fa-shield-alt' },
      { id: 'allwarrantylabor', label: 'All Workshop Warranty Labor Amount ($USD)', type: 'number', calculated: true, formula: 'calculateAllWarrantyLabor', icon: 'fa-calculator' },
      { id: 'cvwarrantypartsamount', label: 'CV Warranty Parts Amount with Isuzu ($USD)', type: 'number', required: true, icon: 'fa-puzzle-piece' },
      { id: 'lcvwarrantypartsamount', label: 'LCV Warranty Parts Amount with Isuzu ($USD)', type: 'number', required: true, icon: 'fa-puzzle-piece' },
      { id: 'allwarrantypartsamount', label: 'All CV Warranty Parts Amount with Isuzu ($USD)', type: 'number', calculated: true, formula: 'calculateAllWarrantyPartsAmount', icon: 'fa-calculator' },
      { id: 'cvworkshopmaintenance', label: 'CV Workshop Maintenance+ General Repair Job Orders Sales ($USD)', type: 'number', required: true, icon: 'fa-tools' },
      { id: 'lcvworkshopmaintenance', label: 'LCV Workshop Maintenance+ General Repair Job Orders Sales ($USD)', type: 'number', required: true, icon: 'fa-tools' },
      { id: 'totalworkshopmaintenance', label: 'Total Workshop Maintenance+ General Repair Job Orders Sales ($USD)', type: 'number', calculated: true, formula: 'calculateTotalWorkshopMaintenance', icon: 'fa-calculator' }
    ]
  },
  service: {
    title: 'Service Orders',
    icon: 'fa-clipboard-list',
    table: 'kpi_serviceorders',
    fields: [
      { id: 'cvservicejob', label: 'CV Service Job Orders (qty) (All)', type: 'number', required: true, icon: 'fa-clipboard-check' },
      { id: 'lcvservicejob', label: 'LCV Service Job Orders (qty) (All)', type: 'number', required: true, icon: 'fa-clipboard-check' },
      { id: 'totalservicejob', label: 'Total Service Job Orders (qty) (All)', type: 'number', calculated: true, formula: 'calculateTotalServiceJob', icon: 'fa-calculator' },
      { id: 'cvnumberfleetjob', label: 'CV Number of Fleet Job Orders (qty)', type: 'number', required: true, icon: 'fa-shipping-fast' },
      { id: 'lcvnumberfleetjob', label: 'LCV Number of Fleet Job Orders (qty)', type: 'number', required: true, icon: 'fa-shipping-fast' },
      { id: 'totalnumberfleetjob', label: 'Total Number of Fleet Job Orders (qty)', type: 'number', calculated: true, formula: 'calculateTotalNumberFleetJob', icon: 'fa-calculator' },
      { id: 'cvnumberinternaljob', label: 'CV Number of Internal Job Orders (qty)', type: 'number', required: true, icon: 'fa-home' },
      { id: 'lcvnumberinternaljob', label: 'LCV Number of Internal Job Orders (qty)', type: 'number', required: true, icon: 'fa-home' },
      { id: 'totalnumberinternaljob', label: 'Total Number of Internal Job Orders (qty)', type: 'number', calculated: true, formula: 'calculateTotalNumberInternalJob', icon: 'fa-calculator' },
      { id: 'cvnumbermaintenance', label: 'CV Number of Maintenance+General Repair Job Orders (qty)', type: 'number', required: true, icon: 'fa-tools' },
      { id: 'lcvnumbermaintenance', label: 'LCV Number of Maintenance+General Repair Job Orders (qty)', type: 'number', required: true, icon: 'fa-tools' },
      { id: 'totalnumbermaintenance', label: 'Total Number of Maintenance+General Repair Job Orders (qty)', type: 'number', calculated: true, formula: 'calculateTotalNumberMaintenance', icon: 'fa-calculator' },
      { id: 'cvwarrantyjob', label: 'CV Number of Warranty Job Orders (qty)', type: 'number', required: true, icon: 'fa-certificate' },
      { id: 'lcvwarrantyjob', label: 'LCV Number of Warranty Job Orders (qty)', type: 'number', required: true, icon: 'fa-certificate' },
      { id: 'totalwarrantyjob', label: 'Total Number of Warranty Job Orders (qty)', type: 'number', calculated: true, formula: 'calculateTotalWarrantyJob', icon: 'fa-calculator' },
      { id: 'cvpdi', label: 'CV Number of PDI Job Orders (qty)', type: 'number', required: true, icon: 'fa-search' },
      { id: 'lcvpdi', label: 'LCV Number of PDI Job Orders (qty)', type: 'number', required: true, icon: 'fa-search' },
      { id: 'totalpdi', label: 'Total Number of PDI Job Orders (qty)', type: 'number', calculated: true, formula: 'calculateTotalPDI', icon: 'fa-calculator' }
    ]
  },
  vehicles: {
    title: 'Vehicle Serviced',
    icon: 'fa-car',
    table: 'kpi_vechiclesserviced',
    fields: [
      { id: 'cvannualvehiclesserviced', label: 'CV Annual Number of Vehicles Serviced (VIN qty)', type: 'number', required: true, icon: 'fa-truck' },
      { id: 'lcvannualvehiclesserviced', label: 'LCV Annual Number of Vehicles Serviced (VIN qty)', type: 'number', required: true, icon: 'fa-van' },
      { id: 'totalannualvehiclesserviced', label: 'Total Annual Number of Vehicles Serviced (VIN qty)', type: 'number', calculated: true, formula: 'calculateTotalAnnualVehiclesServiced', icon: 'fa-calculator' },
      { id: 'cvrdr', label: 'Number of CV RDR submitted (qty)', type: 'number', required: true, icon: 'fa-chart-pie' }
    ]
  },
  labor: {
    title: 'Labor Rates',
    icon: 'fa-dollar-sign',
    table: 'kpi_laborrates',
    fields: [
      { id: 'customerpaid', label: 'Customer Paid Labor Rate ($USD)', type: 'number', required: true, icon: 'fa-user-check' },
      { id: 'warrantylabor', label: 'Warranty Labor Rate with Isuzu ($USD)', type: 'number', required: true, icon: 'fa-shield-alt' },
      { id: 'totaleffective', label: 'Total effective workshop working days (number of days)', type: 'number', required: true, icon: 'fa-chart-line' }
    ]
  },
  personnel: {
    title: 'Personnel',
    icon: 'fa-users',
    table: 'kpi_personnel',
    fields: [
      { id: 'totalnumberstec', label: 'Total Number of Technicians', type: 'number', required: true, icon: 'fa-user-cog' },
      { id: 'totalnumberssadv', label: 'Total Number of Service Advisors', type: 'number', required: true, icon: 'fa-user-tie' }
    ]
  },
  parts: {
    title: 'Parts Sales',
    icon: 'fa-cog',
    table: 'kpi_parts',
    hasGroups: true,
    groups: [
      {
        title: 'Parts Sales (Cost of Sales)',
        icon: 'fa-chart-line',
        fields: [
          { id: 'counter', label: 'Counter (USD)', type: 'number', required: true, icon: 'fa-hashtag' },
          { id: 'external', label: 'External (USD)', type: 'number', required: true, icon: 'fa-external-link-alt' },
          { id: 'workshop', label: 'Workshop (USD)', type: 'number', required: true, icon: 'fa-wrench' },
          { id: 'fleets', label: 'Fleets (USD)', type: 'number', required: true, icon: 'fa-shipping-fast' },
          { id: 'insurance', label: 'Insurance (USD)', type: 'number', required: true, icon: 'fa-shield-alt' },
          { id: 'firstfillrate', label: 'First Fill Rate (%)', type: 'number', required: true, icon: 'fa-percentage' },
          { id: 'backordersdc', label: 'Back Orders from Dealer to Customer (USD)', type: 'number', required: true, icon: 'fa-undo' },
          { id: 'backorderline', label: 'Back Order Lines (qty)', type: 'number', required: true, icon: 'fa-list' }
        ]
      },
      {
        title: 'Inventory Information',
        icon: 'fa-warehouse',
        fields: [
          { id: 'inventorycost', label: 'Inventory Cost (USD)', type: 'number', required: true, icon: 'fa-warehouse' },
          { id: 'invcostfmp', label: 'Inventory Cost (USD) - Fast moving parts', type: 'number', required: true, icon: 'fa-box' },
          { id: 'invcostmmp', label: 'Inventory Cost (USD) - Medium moving parts', type: 'number', required: true, icon: 'fa-box' },
          { id: 'invcostsmp', label: 'Inventory Cost (USD) - Slow moving parts', type: 'number', required: true, icon: 'fa-box' },
          { id: 'invcostnp', label: 'Inventory Cost (USD) - New parts (< 12 months)', type: 'number', required: true, icon: 'fa-box' },
          { id: 'invcostns1224', label: 'Inventory Cost (USD) - No sales > 12m < 24m', type: 'number', required: true, icon: 'fa-clock' },
          { id: 'invcostns2436', label: 'Inventory Cost (USD) - No sales > 24m < 36m', type: 'number', required: true, icon: 'fa-clock' },
          { id: 'invcostns3648', label: 'Inventory Cost (USD) - No sales > 36m < 48m', type: 'number', required: true, icon: 'fa-clock' },
          { id: 'invcostns4860', label: 'Inventory Cost (USD) - No sales > 48m < 60m', type: 'number', required: true, icon: 'fa-clock' },
          { id: 'invcostmt60', label: 'Inventory Cost (USD) - More than 60m', type: 'number', required: true, icon: 'fa-clock' }
        ]
      },
      {
        title: 'Parts Sales Plan',
        icon: 'fa-bullseye',
        fields: [
          { id: 'partssalesoriginalplan', label: 'Parts Sales Original Plan', type: 'number', required: true, icon: 'fa-bullseye' },
          { id: 'partssalesrevisedplan', label: 'Parts Sales Revised Plan', type: 'number', required: true, icon: 'fa-edit' }
        ]
      },
      {
        title: 'Parts Orders',
        icon: 'fa-shopping-cart',
        fields: [
          { id: 'partspurchaseorderplan', label: 'Parts Purchase Order Plan ($)', type: 'number', required: true, icon: 'fa-shopping-cart' },
          { id: 'partspurchaseorder', label: 'Parts Purchase Order ($)', type: 'number', required: true, icon: 'fa-receipt' }
        ]
      }
    ]
  }
};

function selectDefaultKPI(kpiType) {
  // Actualizar KPI activo global
  currentActiveKPI = kpiType;

  // Marcar el tab como activo
  $('#kpiTabs .nav-link').removeClass('active');
  $(`#kpiTabs .nav-link[data-kpi="${kpiType}"]`).addClass('active');

  // Cargar datos del KPI seleccionado
  loadKpiData(kpiType);
}

function createKpiTabsWidget() {
  const tabsHtml = `
    <section class="widget widget-with-tabs">
      <div class="widget-header">
        <ul class="nav nav-tabs" id="kpiTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-kpi="sales" type="button" role="tab">
              SALES
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="workshop-tab" data-bs-toggle="tab" data-kpi="workshop" type="button" role="tab">
              WORKSHOP SALES
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="service-tab" data-bs-toggle="tab" data-kpi="service" type="button" role="tab">
              SERVICE ORDERS
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="vehicles-tab" data-bs-toggle="tab" data-kpi="vehicles" type="button" role="tab">
              VEHICLE SERVICED
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="labor-tab" data-bs-toggle="tab" data-kpi="labor" type="button" role="tab">
              LABOR RATES
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="personnel-tab" data-bs-toggle="tab" data-kpi="personnel" type="button" role="tab">
              PERSONNEL
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="parts-tab" data-bs-toggle="tab" data-kpi="parts" type="button" role="tab">
              PARTS
            </button>
          </li>
        </ul>
      </div>
      <div class="kpi-tab-content">
        <div class="tab-content" id="kpiTabContent">
          <div class="tab-pane fade show active" id="kpi-content" role="tabpanel">
            <div class="text-center p-4">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
              <p class="mt-3">Loading KPI data...</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  `;

  $('#TabDetailOrder').html(tabsHtml);

  // Agregar event listeners para los tabs
  $('#kpiTabs .nav-link').on('click', function (e) {
    e.preventDefault();

    // Remover active de todos los tabs
    $('#kpiTabs .nav-link').removeClass('active');

    // Agregar active al tab clickeado
    $(this).addClass('active');

    // Obtener el tipo de KPI
    const kpiType = $(this).data('kpi');

    // Actualizar KPI activo global
    currentActiveKPI = kpiType;

    // Cargar datos del KPI seleccionado
    loadKpiData(kpiType);
  });
}

function generateKpiFields(kpiType) {
  const config = KPI_CONFIGS[kpiType];
  if (!config) return;

  // Actualizar título
  $('#kpiFieldsTitle').html(`<i class="fa ${config.icon} mr-2"></i>${config.title}`);
  $('#titleHeader').html(`<i class="fa fa-filter mr-2"></i>
                ${config.title.toUpperCase()}`);

  // Limpiar container
  $('#kpiFieldsContainer').empty();

  let fieldsHtml = '';

  // Verificar si el KPI tiene grupos (como PARTS)
  if (config.hasGroups && config.groups) {
    // Generar campos agrupados
    config.groups.forEach(group => {
      // Header del grupo
      fieldsHtml += `
        <div class="col-12">
          <div class="kpi-group-header">
            <h5 class="mb-3 mt-4">
              <i class="fa ${group.icon} mr-2" style="color: #007bff;"></i>
              ${group.title}
            </h5>
          </div>
        </div>
      `;

      // Campos del grupo
      group.fields.forEach(field => {
        const colClass = 'col-lg-4 col-md-4 col-sm-6 col-12';
        const calculatedBadge = field.calculated ? '<span class="calculated-badge">Auto</span>' : '';
        const readonlyAttr = field.calculated ? 'readonly' : '';
        const fieldClass = field.calculated ? 'form-control calculated-field' : 'form-control';
        const requiredAttr = field.required ? 'required' : '';

        fieldsHtml += `
          <div class="${colClass}">
            <div class="dynamic-field-group">
              <label for="${field.id}" class="col-md-12">
                <i class="fa ${field.icon} field-icon"></i>
                ${field.label}
                ${calculatedBadge}
              </label>
              <div class="col-md-12">
                <input 
                  type="${field.type}" 
                  id="${field.id}" 
                  name="${field.id}"
                  class="${fieldClass}" 
                  placeholder="Enter ${field.label.toLowerCase()}"
                  ${readonlyAttr}
                  ${requiredAttr}
                  data-kpi="${kpiType}"
                  ${field.calculated ? `data-formula="${field.formula}"` : ''}
                />
              </div>
            </div>
          </div>
        `;
      });
    });
  } else {
    // Generar campos normales (sin grupos)
    config.fields.forEach(field => {
      const colClass = 'col-lg-4 col-md-4 col-sm-4 col-12';
      const calculatedBadge = field.calculated ? '<span class="calculated-badge">Auto</span>' : '';
      const readonlyAttr = field.calculated ? 'readonly' : '';
      const fieldClass = field.calculated ? 'form-control calculated-field' : 'form-control';
      const requiredAttr = field.required ? 'required' : '';

      fieldsHtml += `
        <div class="${colClass}">
          <div class="dynamic-field-group">
            <label for="${field.id}" class="col-md-12">
              <i class="fa ${field.icon} field-icon"></i>
              ${field.label}
              ${calculatedBadge}
            </label>
            <div class="col-md-12">
              <input 
                type="${field.type}" 
                id="${field.id}" 
                name="${field.id}"
                class="${fieldClass}" 
                placeholder="Enter ${field.label.toLowerCase()}"
                ${readonlyAttr}
                ${requiredAttr}
                data-kpi="${kpiType}"
                ${field.calculated ? `data-formula="${field.formula}"` : ''}
              />
            </div>
          </div>
        </div>
      `;
    });
  }

  $('#kpiFieldsContainer').html(fieldsHtml);

  // Mostrar la sección de campos dinámicos
  $('#dynamicFields').slideDown(300);

  // Mostrar los botones de acción
  $('#kpiActionButtons').slideDown(300);

  // Agregar event listeners para cálculos automáticos
  addCalculationListeners(kpiType);

  // Agregar event listeners para los botones de acción
  addActionButtonListeners(kpiType);
}

function addCalculationListeners(kpiType) {
  // Agregar listeners a campos no calculados para trigger cálculos
  $(`input[data-kpi="${kpiType}"]:not([readonly])`).on('input change', function () {
    calculateDynamicFields(kpiType);
  });
}

function addActionButtonListeners(kpiType) {
  // Remover listeners previos para evitar duplicados
  $('#saveKpiData').off('click');
  $('#eraseKpiData').off('click');

  // Listener para Save Data
  $('#saveKpiData').on('click', function (e) {
    e.preventDefault();
    saveKpiData(kpiType);
  });

  // Listener para Erase Data
  $('#eraseKpiData').on('click', function (e) {
    e.preventDefault();
    eraseKpiData(kpiType);
  });
}

function saveKpiData(kpiType) {
  const dealerId = $('#dealerhome').val();
  const year = $('#dealeryear').val();

  // Validar filtros requeridos
  if (!dealerId) {
    showMessage('error', 'Please select a dealer first');
    return;
  }

  if (!year) {
    showMessage('error', 'Please select a year first');
    return;
  }

      // Recopilar datos del formulario
  const formData = {
    action: 'saveKpiData',
    kpiType: kpiType,
    dealerId: dealerId,
    year: year,
    data: {}
  };

  const config = KPI_CONFIGS[kpiType];
  let allFields = [];

  // Obtener todos los campos según la estructura (con o sin grupos)
  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  // Recopilar valores de los campos
  let hasData = false;
  allFields.forEach(field => {
    const value = $(`#${field.id}`).val();
    if (value && value.trim() !== '') {
      formData.data[field.id] = parseFloat(value) || 0;
      hasData = true;
    } else {
      formData.data[field.id] = 0;
    }
  });

  if (!hasData) {
    showMessage('warning', 'Please enter at least one value before saving');
    return;
  }

  // Mostrar loading
  $('#saveKpiData').prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-2"></i>Saving...');

  // Enviar datos al backend
  $.post('../controllers/kpidata.php', formData, function (response) {
    if (response.success) {
      showMessage('success', `${kpiType.toUpperCase()} data saved successfully`);
      // Recargar datos si es necesario
      loadKpiData(kpiType);
    } else {
      showMessage('error', response.message || 'Error saving data');
    }
  }, 'json')
    .fail(function (xhr, status, error) {
      console.error('Save error:', error);
      showMessage('error', 'Error connecting to server');
    })
    .always(function () {
      // Restaurar botón
      $('#saveKpiData').prop('disabled', false).html('<i class="fa fa-save mr-2"></i>Save Data');
    });
}

function eraseKpiData(kpiType) {
  // Confirmación antes de borrar
  // if (!confirm('Are you sure you want to erase all data? This action cannot be undone.')) {
  //   return;
  // }

  const config = KPI_CONFIGS[kpiType];
  let allFields = [];

  // Obtener todos los campos según la estructura (con o sin grupos)
  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  // Limpiar todos los campos
  allFields.forEach(field => {
    $(`#${field.id}`).val('');
  });

  // Recalcular campos calculados
  calculateDynamicFields(kpiType);

  showMessage('info', 'All fields have been cleared');
}

function newKpiEntry(kpiType) {
  const config = KPI_CONFIGS[kpiType];

  // Actualizar título de la modal
  $('#modalKpiTitle').text(`${config.title.toUpperCase()}`);

  // Cargar dealers y períodos en la modal
  // loadModalSelectData();

  // Generar los campos dinámicos en la modal
  generateModalKpiFields(kpiType);

  // Limpiar todos los campos de la modal
  clearModalFields(kpiType);

      // Obtener valores seleccionados de los filtros principales
  const selectedDealer = $('#dealerhome').val();

  // Pre-seleccionar dealer en la modal si está seleccionado en los filtros
  if (selectedDealer && selectedDealer !== '' && selectedDealer !== 'all') {
    $('#modalDealerhome').val(selectedDealer).trigger('change');
  }

  // Cargar dealers y períodos en la modal
  loadModalSelectData();

  // Abrir la modal
  $('#newKpiModal').modal('show');

  // Configurar event listeners para la modal
  setupModalEventListeners(kpiType);
}

function loadModalSelectData() {
  // Cargar dealers en la modal
  const datos = {
    action: "getEmpresas"
  }
  $.post("../controllers/kpidata.php", datos, function (data) {
    $("#modalDealerhome").empty();
    $("#modalDealerhome").append('<option value="">Select a dealer</option>');

    if (data && Array.isArray(data)) {
      data.forEach(function (dealer) {
        const option = new Option(dealer.dealer, dealer.dealerid);
        $(option).attr('data-bandera', dealer.bandera);
        $(option).attr('data-pais', dealer.pais);
        $("#modalDealerhome").append(option);
      });
    }

    if ($("#modalDealerhome").hasClass('select2-hidden-accessible')) {
      $("#modalDealerhome").select2('destroy');
    }

    $("#modalDealerhome").select2({
      templateResult: formatDealer,
      templateSelection: formatDealerSelection,
      escapeMarkup: function (markup) { return markup; },
      dropdownParent: $('#newKpiModal')
    });
  }, 'json');

  // Cargar períodos en la modal
  const datosYears = {
    action: "getYearsMonth"
  }
  $.post("../controllers/kpidata.php", datosYears, function (data) {
    $("#modalDealeryearmonth").empty();
    $("#modalDealeryearmonth").append('<option value="">Select Year-Month</option>');

    if (data && Array.isArray(data)) {
      data.forEach(function (item) {
        const option = new Option(item.yearmonth, item.calendarioid);
        $("#modalDealeryearmonth").append(option);
      });
    }

    if ($("#modalDealeryearmonth").hasClass('select2-hidden-accessible')) {
      $("#modalDealeryearmonth").select2('destroy');
    }

    $("#modalDealeryearmonth").select2({
      templateResult: formatYearMonth,
      templateSelection: formatYearMonthSelection,
      escapeMarkup: function (markup) { return markup; },
      width: '100%',
      dropdownParent: $('#newKpiModal')
    });
  }, 'json');
}

function generateModalKpiFields(kpiType) {
  const config = KPI_CONFIGS[kpiType];
  if (!config) return;

  // Actualizar título
  $('#modalKpiFieldsTitle').html(`<i class="fa ${config.icon} mr-2"></i>${config.title} Configuration`);

  // Limpiar container
  $('#modalKpiFieldsContainer').empty();

  let fieldsHtml = '';

  // Verificar si el KPI tiene grupos (como PARTS)
  if (config.hasGroups && config.groups) {
    // Generar campos agrupados
    config.groups.forEach(group => {
      // Header del grupo
      fieldsHtml += `
        <div class="col-12">
          <div class="kpi-group-header">
            <h5 class="mb-3 mt-4">
              <i class="fa ${group.icon} mr-2" style="color: #007bff;"></i>
              ${group.title}
            </h5>
          </div>
        </div>
      `;

      // Campos del grupo
      group.fields.forEach(field => {
        const colClass = 'col-lg-4 col-md-4 col-sm-4 col-12';
        const readonlyAttr = field.calculated ? 'readonly' : '';
        const fieldClass = field.calculated ? 'form-control calculated-field' : 'form-control';
        const requiredAttr = field.required ? 'required' : '';

        fieldsHtml += `
          <div class="${colClass}">
            <div class="dynamic-field-group">
              <label for="modal_${field.id}" class="text-white">
                  ${field.label}
              </label>
              <input 
                type="${field.type}" 
                id="modal_${field.id}" 
                name="modal_${field.id}"
                class="text-white ${fieldClass}" 
                placeholder="Enter ${field.label.toLowerCase()}"
                ${readonlyAttr}
                ${requiredAttr}
                data-kpi="${kpiType}"
                data-original-id="${field.id}"
                ${field.calculated ? `data-formula="${field.formula}"` : ''}
              />
            </div>
          </div>
        `;
      });
    });
  } else {
    // Generar campos normales (sin grupos)
    config.fields.forEach(field => {
      const colClass = 'col-lg-4 col-md-6 col-sm-6 col-12';
      const calculatedBadge = field.calculated ? '<span class="calculated-badge">Auto</span>' : '';
      const readonlyAttr = field.calculated ? 'readonly' : '';
      const fieldClass = field.calculated ? 'form-control calculated-field' : 'form-control';
      const requiredAttr = field.required ? 'required' : '';

      fieldsHtml += `
        <div class="${colClass}">
          <div class="dynamic-field-group">
            <label for="modal_${field.id}" class="text-white">
              ${field.label}
            </label>
              <input 
                type="${field.type}" 
                id="modal_${field.id}" 
                name="modal_${field.id}"
                class="text-white ${fieldClass}" 
                placeholder="Enter ${field.label.toLowerCase()}"
                ${readonlyAttr}
                ${requiredAttr}
                data-kpi="${kpiType}"
                data-original-id="${field.id}"
                ${field.calculated ? `data-formula="${field.formula}"` : ''}
              />
          </div>
        </div>
      `;
    });
  }

  $('#modalKpiFieldsContainer').html(fieldsHtml);

  // Agregar event listeners para cálculos automáticos en la modal
  addModalCalculationListeners(kpiType);
}

function setupModalEventListeners(kpiType) {
  // Limpiar listeners previos
  $('#modalSaveKpiData').off('click');
  $('#modalEraseKpiData').off('click');

  // Listener para Save Data en modal
  $('#modalSaveKpiData').on('click', function (e) {
    e.preventDefault();
    saveModalKpiData(kpiType);
  });

  // Listener para Clear Fields en modal
  $('#modalEraseKpiData').on('click', function (e) {
    e.preventDefault();
    clearModalFields(kpiType);
  });
}

function addModalCalculationListeners(kpiType) {
  // Agregar listeners a campos no calculados para trigger cálculos en la modal
  $(`input[data-kpi="${kpiType}"]:not([readonly])`).on('input change', function () {
    calculateModalDynamicFields(kpiType);
  });
}

function calculateModalDynamicFields(kpiType) {
  const config = KPI_CONFIGS[kpiType];
  if (!config) return;

  let allFields = [];

  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  allFields.forEach(field => {
    if (field.calculated && field.formula) {
      const calculatedValue = calculateModalFormula(field.formula, kpiType);
      $(`#modal_${field.id}`).val(calculatedValue);
    }
  });
}

function calculateModalFormula(formula, kpiType) {
  // Adaptar las funciones de cálculo para la modal
  switch (formula) {
    case 'calculateAllUnitSales':
      const cv1 = parseFloat($('#modal_unitsalescv').val()) || 0;
      const lcv1 = parseFloat($('#modal_unitsaleslcv').val()) || 0;
      return (cv1 + lcv1).toFixed(0);

    case 'calculateAllWorkshopLaborSales':
      const cv2 = parseFloat($('#modal_cvworkshoplaborsales').val()) || 0;
      const lcv2 = parseFloat($('#modal_lcvworkshoplaborsales').val()) || 0;
      return (cv2 + lcv2).toFixed(0);

    case 'calculateAllWarrantyLabor':
      const cv3 = parseFloat($('#modal_cvwarrantylabor').val()) || 0;
      const lcv3 = parseFloat($('#modal_lcvwarrantylabor').val()) || 0;
      return (cv3 + lcv3).toFixed(0);

    case 'calculateAllWarrantyPartsAmount':
      const cv4 = parseFloat($('#modal_cvwarrantypartsamount').val()) || 0;
      const lcv4 = parseFloat($('#modal_lcvwarrantypartsamount').val()) || 0;
      return (cv4 + lcv4).toFixed(0);

    case 'calculateTotalWorkshopMaintenance':
      const cv5 = parseFloat($('#modal_cvworkshopmaintenance').val()) || 0;
      const lcv5 = parseFloat($('#modal_lcvworkshopmaintenance').val()) || 0;
      return (cv5 + lcv5).toFixed(0);

    case 'calculateTotalServiceJob':
      const cv6 = parseFloat($('#modal_cvservicejob').val()) || 0;
      const lcv6 = parseFloat($('#modal_lcvservicejob').val()) || 0;
      return (cv6 + lcv6).toFixed(0);

    case 'calculateTotalNumberFleetJob':
      const cv7 = parseFloat($('#modal_cvnumberfleetjob').val()) || 0;
      const lcv7 = parseFloat($('#modal_lcvnumberfleetjob').val()) || 0;
      return (cv7 + lcv7).toFixed(0);

    case 'calculateTotalNumberInternalJob':
      const cv8 = parseFloat($('#modal_cvnumberinternaljob').val()) || 0;
      const lcv8 = parseFloat($('#modal_lcvnumberinternaljob').val()) || 0;
      return (cv8 + lcv8).toFixed(0);

    case 'calculateTotalNumberMaintenance':
      const cv9 = parseFloat($('#modal_cvnumbermaintenance').val()) || 0;
      const lcv9 = parseFloat($('#modal_lcvnumbermaintenance').val()) || 0;
      return (cv9 + lcv9).toFixed(0);

    case 'calculateTotalWarrantyJob':
      const cv10 = parseFloat($('#modal_cvwarrantyjob').val()) || 0;
      const lcv10 = parseFloat($('#modal_lcvwarrantyjob').val()) || 0;
      return (cv10 + lcv10).toFixed(0);

    case 'calculateTotalPDI':
      const cv11 = parseFloat($('#modal_cvpdi').val()) || 0;
      const lcv11 = parseFloat($('#modal_lcvpdi').val()) || 0;
      return (cv11 + lcv11).toFixed(0);

    case 'calculateTotalAnnualVehiclesServiced':
      const cv12 = parseFloat($('#modal_cvannualvehiclesserviced').val()) || 0;
      const lcv12 = parseFloat($('#modal_lcvannualvehiclesserviced').val()) || 0;
      return (cv12 + lcv12).toFixed(0);

    default:
      return 0;
  }
}

function clearModalFields(kpiType) {
  const config = KPI_CONFIGS[kpiType];
  let allFields = [];

  // Obtener todos los campos según la estructura (con o sin grupos)
  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  // Limpiar todos los campos de la modal
  allFields.forEach(field => {
    $(`#modal_${field.id}`).val('');
  });

  // Recalcular campos calculados en la modal
  calculateModalDynamicFields(kpiType);

  showModalMessage('info', 'All fields have been cleared');
}

function saveModalKpiData(kpiType) {
  const dealerId = $('#modalDealerhome').val();
  const yearMonth = $('#modalDealeryearmonth').val();

  // Validar filtros requeridos
  if (!dealerId) {
    showModalMessage('error', 'Please select a dealer first');
    return;
  }

  if (!yearMonth) {
    showModalMessage('error', 'Please select a year-month period first');
    return;
  }

  // Recopilar datos del formulario de la modal
  const formData = {
    action: 'saveKpiData',
    kpiType: kpiType,
    dealerId: dealerId,
    yearMonth: yearMonth,
    data: {}
  };

  const config = KPI_CONFIGS[kpiType];
  let allFields = [];

  // Obtener todos los campos según la estructura (con o sin grupos)
  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  // Recopilar valores de los campos de la modal
  let hasData = false;
  allFields.forEach(field => {
    const value = $(`#modal_${field.id}`).val();
    if (value && value.trim() !== '') {
      formData.data[field.id] = parseFloat(value) || 0;
      hasData = true;
    } else {
      formData.data[field.id] = 0;
    }
  });

  if (!hasData) {
    showModalMessage('warning', 'Please enter at least one value before saving');
    return;
  }

  // Mostrar loading
  $('#modalSaveKpiData').prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-2"></i>Saving...');

  // Enviar datos al backend
  $.post('../controllers/kpidata.php', formData, function (response) {
    if (response.success) {
      showModalMessage('success', `${kpiType.toUpperCase()} data saved successfully`);

      // Cerrar modal después de 2 segundos y recargar datos
      setTimeout(() => {
        $('#newKpiModal').modal('hide');
        loadKpiData(kpiType); // Recargar la tabla
      }, 2000);
    } else {
      showModalMessage('error', response.message || 'Error saving data');
    }
  }, 'json')
    .fail(function (xhr, status, error) {
      console.error('Save error:', error);
      showModalMessage('error', 'Error connecting to server');
    })
    .always(function () {
      // Restaurar botón
      $('#modalSaveKpiData').prop('disabled', false).html('<i class="fa fa-save mr-2"></i>Save Data');
    });
}

function showModalMessage(type, message) {
  const alertClass = type === 'success' ? 'alert-success' :
    type === 'error' ? 'alert-danger' :
      type === 'warning' ? 'alert-warning' : 'alert-info';

  const icon = type === 'success' ? 'fa-check-circle' :
    type === 'error' ? 'fa-exclamation-circle' :
      type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

  const html = `
    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
      <i class="fa ${icon} mr-2"></i>
      ${message}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  `;

  $('#modalKpiSaveMessage').html(html).show();

  // Auto-hide después de 5 segundos para mensajes de éxito
  if (type === 'success' || type === 'info') {
    setTimeout(() => {
      $('#modalKpiSaveMessage .alert').fadeOut();
    }, 5000);
  }
}

function showMessage(type, message) {
  const alertClass = type === 'success' ? 'alert-success' :
    type === 'error' ? 'alert-danger' :
      type === 'warning' ? 'alert-warning' : 'alert-info';

  const icon = type === 'success' ? 'fa-check-circle' :
    type === 'error' ? 'fa-exclamation-circle' :
      type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

  const html = `
    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
      <i class="fa ${icon} mr-2"></i>
      ${message}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  `;

  $('#kpiSaveMessage').html(html).show();

  // Auto-hide después de 5 segundos para mensajes de éxito
  if (type === 'success' || type === 'info') {
    setTimeout(() => {
      $('#kpiSaveMessage .alert').fadeOut();
    }, 5000);
  }
}

function calculateDynamicFields(kpiType) {
  const config = KPI_CONFIGS[kpiType];
  if (!config) return;

  config.fields.forEach(field => {
    if (field.calculated && field.formula) {
      const calculatedValue = window[field.formula](kpiType);
      $(`#${field.id}`).val(calculatedValue);
    }
  });
}

// Funciones de cálculo específicas

// SALES
function calculateAllUnitSales(data) {
  // Si data es un objeto (bulk upload), usar valores del objeto
  if (typeof data === 'object' && data !== null) {
    const cv = parseFloat(data.unitsalescv) || 0;
    const lcv = parseFloat(data.unitsaleslcv) || 0;
    return (cv + lcv).toFixed(0);
  }
  // Si no, leer del formulario (inline edit)
  const cv = parseFloat($('#unitsalescv').val()) || 0;
  const lcv = parseFloat($('#unitsaleslcv').val()) || 0;
  return (cv + lcv).toFixed(0);
}

// WORKSHOP SALES
function calculateAllWorkshopLaborSales(data) {
  if (typeof data === 'object' && data !== null) {
    const cv = parseFloat(data.cvworkshoplaborsales) || 0;
    const lcv = parseFloat(data.lcvworkshoplaborsales) || 0;
    return (cv + lcv).toFixed(0);
  }
  const cv = parseFloat($('#cvworkshoplaborsales').val()) || 0;
  const lcv = parseFloat($('#lcvworkshoplaborsales').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function calculateAllWarrantyLabor(data) {
  if (typeof data === 'object' && data !== null) {
    const cv = parseFloat(data.cvwarrantylabor) || 0;
    const lcv = parseFloat(data.lcvwarrantylabor) || 0;
    return (cv + lcv).toFixed(0);
  }
  const cv = parseFloat($('#cvwarrantylabor').val()) || 0;
  const lcv = parseFloat($('#lcvwarrantylabor').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function calculateAllWarrantyPartsAmount(data) {
  if (typeof data === 'object' && data !== null) {
    const cv = parseFloat(data.cvwarrantypartsamount) || 0;
    const lcv = parseFloat(data.lcvwarrantypartsamount) || 0;
    return (cv + lcv).toFixed(0);
  }
  const cv = parseFloat($('#cvwarrantypartsamount').val()) || 0;
  const lcv = parseFloat($('#lcvwarrantypartsamount').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function calculateTotalWorkshopMaintenance(data) {
  if (typeof data === 'object' && data !== null) {
    const cv = parseFloat(data.cvworkshopmaintenance) || 0;
    const lcv = parseFloat(data.lcvworkshopmaintenance) || 0;
    return (cv + lcv).toFixed(0);
  }
  const cv = parseFloat($('#cvworkshopmaintenance').val()) || 0;
  const lcv = parseFloat($('#lcvworkshopmaintenance').val()) || 0;
  return (cv + lcv).toFixed(0);
}

// SERVICE ORDERS
function calculateTotalServiceJob(data) {
  if (typeof data === 'object' && data !== null) {
    const cv = parseFloat(data.cvservicejob) || 0;
    const lcv = parseFloat(data.lcvservicejob) || 0;
    return (cv + lcv).toFixed(0);
  }
  const cv = parseFloat($('#cvservicejob').val()) || 0;
  const lcv = parseFloat($('#lcvservicejob').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function calculateTotalNumberFleetJob(data) {
  if (typeof data === 'object' && data !== null) {
    const cv = parseFloat(data.cvnumberfleetjob) || 0;
    const lcv = parseFloat(data.lcvnumberfleetjob) || 0;
    return (cv + lcv).toFixed(0);
  }
  const cv = parseFloat($('#cvnumberfleetjob').val()) || 0;
  const lcv = parseFloat($('#lcvnumberfleetjob').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function calculateTotalNumberInternalJob(kpiType) {
  const cv = parseFloat($('#cvnumberinternaljob').val()) || 0;
  const lcv = parseFloat($('#lcvnumberinternaljob').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function calculateTotalNumberMaintenance(kpiType) {
  const cv = parseFloat($('#cvnumbermaintenance').val()) || 0;
  const lcv = parseFloat($('#lcvnumbermaintenance').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function calculateTotalWarrantyJob(kpiType) {
  const cv = parseFloat($('#cvwarrantyjob').val()) || 0;
  const lcv = parseFloat($('#lcvwarrantyjob').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function calculateTotalPDI(kpiType) {
  const cv = parseFloat($('#cvpdi').val()) || 0;
  const lcv = parseFloat($('#lcvpdi').val()) || 0;
  return (cv + lcv).toFixed(0);
}

// VEHICLES SERVICED
function calculateTotalAnnualVehiclesServiced(kpiType) {
  const cv = parseFloat($('#cvannualvehiclesserviced').val()) || 0;
  const lcv = parseFloat($('#lcvannualvehiclesserviced').val()) || 0;
  return (cv + lcv).toFixed(0);
}

function loadKpiData(kpiType) {
  const dealerId = $('#dealerhome').val();
  const year = $('#dealeryear').val();

  // Obtener texto de los selects para mostrar en mensajes
  const dealerText = dealerId ? $('#dealerhome option:selected').text() : 'All Dealers';
  const yearText = year ? $('#dealeryear option:selected').text() : 'All Years';

  // Mostrar loading en el contenedor de tabs con info de filtros
  $('#kpi-content').html(`
    <div class="text-center p-4">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
      </div>
      <p class="mt-3">Loading ${kpiType.toUpperCase()} data...</p>
      <small class="text-muted">Filters: ${dealerText} | ${yearText}</small>
    </div>
  `);

  // Cargar datos reales del backend
  const formData = {
    action: 'getKpiData',
    kpiType: kpiType,
    dealerId: dealerId,
    year: year
  };

  $.post('../controllers/kpidata.php', formData, function (response) {
    if (response.success) {
      generateKpiTable(kpiType, response.data, dealerId, year);
    } else {
      $('#kpi-content').html(`
        <div class="p-4">
          <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            ${response.message || 'No data found for the selected filters'}
            <br><small>Dealer: ${dealerText} | Year: ${yearText}</small>
          </div>
        </div>
      `);
    }
  }, 'json')
    .fail(function (xhr, status, error) {
      console.error('Load error:', error);
      $('#kpi-content').html(`
        <div class="p-4">
          <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            Error loading data: ${error}
          </div>
        </div>
      `);
    });
}

function generateKpiTable(kpiType, data, dealerId, year) {
  const config = KPI_CONFIGS[kpiType];
  if (!config) return;

  let allFields = [];

  // Obtener todos los campos según la estructura (con o sin grupos)
  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  // Generar headers de la tabla
  let tableHeaders = '<tr>';
  tableHeaders += '<th class="text-center no-sort" style="width: 3%;font-size:11px;">#</th>';
  tableHeaders += '<th class="text-center" style="width: 8%;font-size:11px;">DEALER</th>';
  tableHeaders += '<th class="text-center" style="width: 8%;font-size:11px;">PERIOD</th>';

  allFields.forEach(field => {
    const width = allFields.length > 15 ? '5%' : '6%';
    tableHeaders += `<th class="text-center" style="width: ${width};font-size:11px;">${field.label}</th>`;
  });

  tableHeaders += '<th class="text-center" style="width: 8%;font-size:11px;">LAST MODIFIED</th>';
  tableHeaders += '<th class="text-center no-sort" style="width: 8%;font-size:11px;">ACTIONS</th>';
  tableHeaders += '</tr>';

  // Generar filas de datos
  let tableRows = '';
  if (data && data.length > 0) {
    data.forEach((row, index) => {
      tableRows += '<tr>';
      tableRows += `<td class="text-center">${index + 1}</td>`;
      tableRows += `<td class="text-left">${row.dealer || 'N/A'}</td>`;
      tableRows += `<td class="text-center">${row.yearmonth || 'N/A'}</td>`;

      allFields.forEach(field => {
        const value = row[field.id] || 0;
        let formattedValue = '0';

        if (field.type === 'number' && value > 0) {
          if (field.label.includes('Rate') || field.label.includes('%')) {
            formattedValue = parseFloat(value).toFixed(0);
          } else {
            formattedValue = parseFloat(value).toLocaleString();
          }
        }

        const isReadonly = field.calculated ? 'readonly' : '';
        const cellClass = field.calculated ? 'editable-cell calculated-field' : 'editable-cell';

        tableRows += `<td class="text-right ${cellClass}" 
                         data-field="${field.id}" 
                         data-original-value="${value}"
                         data-row-index="${index}"
                         ${isReadonly}>${formattedValue}</td>`;
      });

      const fechaFormatted = row.fecha ? new Date(row.fecha).toLocaleString() : 'N/A';
      tableRows += `<td class="text-center" style="font-size:10px;">${fechaFormatted}</td>`;
      tableRows += `
        <td class="text-center">
          <button type="button" class="btn btn-warning btn-xs edit-row-btn" 
                  data-dealer="${row.dealerid}" 
                  data-period="${row.calendarioid}"
                  data-row-index="${index}"
                  title="Edit record">
            <i class="fa fa-edit"></i>
          </button>
          <button type="button" class="btn btn-success btn-xs save-row-btn ml-1" 
                  data-dealer="${row.dealerid}" 
                  data-period="${row.calendarioid}"
                  data-row-index="${index}"
                  title="Save changes"
                  style="display: none;">
            <i class="fa fa-save"></i>
          </button>
          <button type="button" class="btn btn-secondary btn-xs cancel-row-btn ml-1" 
                  data-dealer="${row.dealerid}" 
                  data-period="${row.calendarioid}"
                  data-row-index="${index}"
                  title="Cancel editing"
                  style="display: none;">
            <i class="fa fa-times"></i>
          </button>
        </td>
      `;
      tableRows += '</tr>';
    });
  } else {
    const colspan = 5 + allFields.length;
    tableRows = `
      <tr>
        <td colspan="${colspan}" class="text-center text-muted" style="padding: 40px;">
          <i class="fa fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
          <strong>No data found for the selected filters</strong>
          <br><small>Try adjusting your filter criteria</small>
        </td>
      </tr>
    `;
  }

  // Obtener información de filtros para mostrar
  const dealerText = dealerId ? $('#dealerhome option:selected').text() : 'All Dealers';
  const yearText = year ? $('#dealeryear option:selected').text() : 'All Years';
  const recordCount = data && data.length > 0 ? data.length : 0;

  // Generar contenido de la tabla para el tab activo
  const tableHtml = `
    <div class="p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 style="color:black;font-weight:bold;margin-bottom:0;">
          <i class="fa ${config.icon} mr-2"></i>
          ${config.title.toUpperCase()} - ${recordCount} RECORD(S) FOUND
        </h6>
                                <small class="text-muted">
          <i class="fa fa-filter mr-1"></i>
          Filters: <strong>${dealerText}</strong> | <strong>${yearText}</strong>
        </small>
      </div>
      <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table id="TableListItems" name="TableListItems" class="table table-bordered table-lg mb-10 table-hover" style="font-size: 11px; min-width: 100%;">
          <thead>
            ${tableHeaders}
          </thead>
          <tbody>
            ${tableRows}
          </tbody>
        </table>
      </div>
      <div class="row" style="padding-top: 15px;">
        <div class="col-12 d-flex justify-content-end">
          <button type="button" class="btn btn-info btn-sm mb-xs mr-2" onclick="openBulkUploadModal('${kpiType}');">
            <i class="fa fa-cloud-upload"></i> BULK UPLOAD
          </button>
          <button type="button" class="btn btn-success btn-sm mb-xs mr-2" onclick="newKpiEntry('${kpiType}');">
            <i class="fa fa-plus"></i> NEW ${config.title.toUpperCase()}
          </button>

          ${data && data.length > 0 ? `
            <button type="button" class="btn btn-warning btn-sm mb-xs" onclick="exportKpiTableToExcel();">
              <i class="fa fa-file-excel-o"></i> EXPORT DATA
            </button>
          ` : ''}
        </div>
      </div>

    </div>
  `;

  $('#kpi-content').html(tableHtml);

  // Inicializar DataTable si hay datos
  if (data && data.length > 0) {
    initKpiDataTable();
  }

  // Agregar event listeners para los botones de acciones
  addTableActionListeners(kpiType);
}

function initKpiDataTable() {
  // Destruir DataTable existente si existe
  if ($.fn.DataTable.isDataTable('#TableListItems')) {
    $('#TableListItems').DataTable().clear().destroy();
  }

  // Configurar columnas no ordenables
  var unsortableColumns = [];
  $('#TableListItems').find('thead th').each(function () {
    if ($(this).hasClass('no-sort')) {
      unsortableColumns.push({ "bSortable": false });
    } else {
      unsortableColumns.push({ "bSortable": true });
    }
  });

  // Contar el número total de columnas para determinar el índice de la columna de fecha
  const totalColumns = $('#TableListItems').find('thead th').length;
  const dateColumnIndex = totalColumns - 2; // Fecha está antes de la columna de acciones

  // Inicializar DataTable con scroll pero manteniendo header y body juntos
  $("#TableListItems").DataTable({
    "sDom": "<'row'<'col-lg-6 hidden-sm-down'l><'col-lg-6'f>r>t<'row'<'col-lg-6'i><'col-lg-6'p>>",
    "scrollX": false,
    "autoWidth": true,
    "oLanguage": {
      "sLengthMenu": "_MENU_",
      "sInfo": "Showing <strong>_START_ to _END_</strong> of _TOTAL_ entries",
      "sSearch": "Search:",
      "sZeroRecords": "No matching records found",
      "sPaginate": {
        "sFirst": "First",
        "sLast": "Last",
        "sNext": "Next",
        "sPrevious": "Previous"
      }
    },
    "oClasses": {
      "sFilter": "float-right",
      "sFilterInput": "form-control input-rounded ml-sm"
    },
                "aoColumns": unsortableColumns,
    "pageLength": 12,
    "lengthMenu": [[12, 24, 48, 96, 120, -1], [12, 24, 48, 96, 120, "All"]],
    "order": [], // Respetar el orden del servidor (dealer + mes)
    "destroy": true // Permitir reinicialización
  });

  // Aplicar clases adicionales
  $('div.dataTables_length select').addClass('form-control');

  // Ajustar tabla después de inicialización
  setTimeout(() => {
    if ($.fn.DataTable.isDataTable('#TableListItems')) {
      $('#TableListItems').DataTable().columns.adjust().draw();
    }
  }, 100);
}

function exportKpiTableToExcel() {
  const table = document.querySelector('#TableListItems');
  const data = [];

  // Obtener headers (excluyendo acciones)
  const headers = [];
  table.querySelectorAll('thead th').forEach((th, index) => {
    if (index < table.querySelectorAll('thead th').length - 1) { // Excluir última columna (acciones)
      headers.push(th.textContent.trim());
    }
  });
  data.push(headers);

  // Obtener datos de las filas
  table.querySelectorAll('tbody tr').forEach(row => {
    const rowData = [];
    row.querySelectorAll('td').forEach((td, index) => {
      if (index < row.querySelectorAll('td').length - 1) { // Excluir última columna (acciones)
        rowData.push(td.textContent.trim());
      }
    });
    if (rowData.length > 0) {
      data.push(rowData);
    }
  });

  // Crear y descargar archivo Excel
  const ws = XLSX.utils.aoa_to_sheet(data);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "KPI Data");

  const currentDate = new Date().toISOString().slice(0, 10);
  const filename = `KPI_Data_${currentDate}.xlsx`;

  XLSX.writeFile(wb, filename);
}
function addTableActionListeners(kpiType) {
  // Botón para editar fila
  $('.edit-row-btn').on('click', function () {
    const rowIndex = $(this).data('row-index');
    enableRowEditing(rowIndex, kpiType);
  });

  // Botón para guardar cambios
  $('.save-row-btn').on('click', function () {
    const dealerId = $(this).data('dealer');
    const periodId = $(this).data('period');
    const rowIndex = $(this).data('row-index');
    saveRowChanges(kpiType, dealerId, periodId, rowIndex);
  });

  // Botón para cancelar edición
  $('.cancel-row-btn').on('click', function () {
    const rowIndex = $(this).data('row-index');
    cancelRowEditing(rowIndex, kpiType);
  });
}

function enableRowEditing(rowIndex, kpiType) {
  // Deshabilitar otros botones de edición
  $('.edit-row-btn').prop('disabled', true);

  // Agregar clase visual a la fila en edición
  $(`.editable-cell[data-row-index="${rowIndex}"]`).closest('tr').addClass('editing-row');

  // Mostrar botones de guardar/cancelar para esta fila
  $(`.edit-row-btn[data-row-index="${rowIndex}"]`).hide();
  $(`.save-row-btn[data-row-index="${rowIndex}"]`).show();
  $(`.cancel-row-btn[data-row-index="${rowIndex}"]`).show();

  // Convertir celdas editables en inputs
  let firstInput = null;
  $(`.editable-cell[data-row-index="${rowIndex}"]`).each(function () {
    const cell = $(this);
    const fieldId = cell.data('field');
    const originalValue = cell.data('original-value') || 0;
    const isReadonly = cell.hasClass('calculated-field');

    if (!isReadonly) {
      // Obtener la configuración del campo para determinar el tipo de formato
      const config = KPI_CONFIGS[kpiType];
      let allFields = [];

      if (config.hasGroups && config.groups) {
        config.groups.forEach(group => {
          allFields = allFields.concat(group.fields);
        });
      } else {
        allFields = config.fields;
      }

      const field = allFields.find(f => f.id === fieldId);
      const isDecimalField = field && (field.label.includes('Rate') || field.label.includes('%'));

      // Formatear valor según el tipo de campo
      let displayValue, stepValue;
      if (isDecimalField) {
        displayValue = parseFloat(originalValue || 0).toFixed(2);
        stepValue = "0.01";
      } else {
        displayValue = Math.round(parseFloat(originalValue) || 0);
        stepValue = "1";
      }

      const input = $(`<input type="number" 
                             class="form-control table-edit-input" 
                             value="${displayValue}" 
                             data-field="${fieldId}"
                             data-row-index="${rowIndex}"
                             style="width: 100%; border: none; background: #fff3cd; text-align: right;"
                             step="${stepValue}">`);

      cell.html(input);

      // Guardar referencia al primer input
      if (!firstInput) {
        firstInput = input;
      }
    }
  });

  // Focus en el primer input
  if (firstInput) {
    setTimeout(() => {
      firstInput.focus().select();
    }, 100);
  }

  // Agregar listeners para cálculos automáticos
  addTableCalculationListeners(rowIndex, kpiType);
}

function cancelRowEditing(rowIndex, kpiType) {
  // Remover clase visual de la fila
  $(`.editable-cell[data-row-index="${rowIndex}"]`).closest('tr').removeClass('editing-row');

  // Restaurar celdas originales
  $(`.editable-cell[data-row-index="${rowIndex}"]`).each(function () {
    const cell = $(this);
    const originalValue = cell.data('original-value') || 0;
    const fieldId = cell.data('field');

    // Obtener la configuración del campo para el formato
    const config = KPI_CONFIGS[kpiType];
    let allFields = [];

    if (config.hasGroups && config.groups) {
      config.groups.forEach(group => {
        allFields = allFields.concat(group.fields);
      });
    } else {
      allFields = config.fields;
    }

    const field = allFields.find(f => f.id === fieldId);
    let formattedValue = '0';

    if (field && field.type === 'number' && originalValue > 0) {
      if (field.label.includes('Rate') || field.label.includes('%')) {
        formattedValue = parseFloat(originalValue).toFixed(0);
      } else {
        formattedValue = parseFloat(originalValue).toLocaleString();
      }
    }

    cell.html(formattedValue);
  });

  // Restaurar botones
  $(`.edit-row-btn[data-row-index="${rowIndex}"]`).show();
  $(`.save-row-btn[data-row-index="${rowIndex}"]`).hide();
  $(`.cancel-row-btn[data-row-index="${rowIndex}"]`).hide();

  // Rehabilitar otros botones de edición
  $('.edit-row-btn').prop('disabled', false);
}

function addTableCalculationListeners(rowIndex, kpiType) {
  // Agregar listeners para cálculos automáticos en la tabla
  $(`.table-edit-input[data-row-index="${rowIndex}"]`).on('input change', function () {
    calculateTableDynamicFields(rowIndex, kpiType);
  });

  // Agregar listeners para teclas
  $(`.table-edit-input[data-row-index="${rowIndex}"]`).on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      // Mover al siguiente input o guardar si es el último
      const currentInput = $(this);
      const allInputs = $(`.table-edit-input[data-row-index="${rowIndex}"]`);
      const currentIndex = allInputs.index(currentInput);

      if (currentIndex < allInputs.length - 1) {
        allInputs.eq(currentIndex + 1).focus().select();
      } else {
        // Es el último input, guardar cambios
        $(`.save-row-btn[data-row-index="${rowIndex}"]`).click();
      }
    } else if (e.key === 'Escape') {
      e.preventDefault();
      // Cancelar edición
      $(`.cancel-row-btn[data-row-index="${rowIndex}"]`).click();
    } else if (e.key === 'Tab') {
      // Permitir navegación con Tab normalmente
      // No hacer nada, dejar que el navegador maneje Tab
    }
  });
}

function calculateTableDynamicFields(rowIndex, kpiType) {
  const config = KPI_CONFIGS[kpiType];
  if (!config) return;

  let allFields = [];

  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  allFields.forEach(field => {
    if (field.calculated && field.formula) {
      const calculatedValue = calculateTableFormula(field.formula, rowIndex, kpiType);
      const calculatedCell = $(`.editable-cell[data-row-index="${rowIndex}"][data-field="${field.id}"]`);
      if (calculatedCell.length) {
        let formattedValue = calculatedValue;
        if (field.label.includes('Rate') || field.label.includes('%')) {
          formattedValue = parseFloat(calculatedValue).toFixed(0);
        } else {
          formattedValue = parseFloat(calculatedValue).toLocaleString();
        }
        calculatedCell.html(formattedValue);
        calculatedCell.data('original-value', calculatedValue);
      }
    }
  });
}

function calculateTableFormula(formula, rowIndex, kpiType) {
  // Adaptar las funciones de cálculo para la tabla
  const getTableValue = (fieldId) => {
    const input = $(`.table-edit-input[data-row-index="${rowIndex}"][data-field="${fieldId}"]`);
    if (input.length) {
      return parseFloat(input.val()) || 0;
    }
    const cell = $(`.editable-cell[data-row-index="${rowIndex}"][data-field="${fieldId}"]`);
    return parseFloat(cell.data('original-value')) || 0;
  };

  switch (formula) {
    case 'calculateAllUnitSales':
      const cv1 = getTableValue('unitsalescv');
      const lcv1 = getTableValue('unitsaleslcv');
      return (cv1 + lcv1).toFixed(0);

    case 'calculateAllWorkshopLaborSales':
      const cv2 = getTableValue('cvworkshoplaborsales');
      const lcv2 = getTableValue('lcvworkshoplaborsales');
      return (cv2 + lcv2).toFixed(0);

    case 'calculateAllWarrantyLabor':
      const cv3 = getTableValue('cvwarrantylabor');
      const lcv3 = getTableValue('lcvwarrantylabor');
      return (cv3 + lcv3).toFixed(0);

    case 'calculateAllWarrantyPartsAmount':
      const cv4 = getTableValue('cvwarrantypartsamount');
      const lcv4 = getTableValue('lcvwarrantypartsamount');
      return (cv4 + lcv4).toFixed(0);

    case 'calculateTotalWorkshopMaintenance':
      const cv5 = getTableValue('cvworkshopmaintenance');
      const lcv5 = getTableValue('lcvworkshopmaintenance');
      return (cv5 + lcv5).toFixed(0);

    case 'calculateTotalServiceJob':
      const cv6 = getTableValue('cvservicejob');
      const lcv6 = getTableValue('lcvservicejob');
      return (cv6 + lcv6).toFixed(0);

    case 'calculateTotalNumberFleetJob':
      const cv7 = getTableValue('cvnumberfleetjob');
      const lcv7 = getTableValue('lcvnumberfleetjob');
      return (cv7 + lcv7).toFixed(0);

    case 'calculateTotalNumberInternalJob':
      const cv8 = getTableValue('cvnumberinternaljob');
      const lcv8 = getTableValue('lcvnumberinternaljob');
      return (cv8 + lcv8).toFixed(0);

    case 'calculateTotalNumberMaintenance':
      const cv9 = getTableValue('cvnumbermaintenance');
      const lcv9 = getTableValue('lcvnumbermaintenance');
      return (cv9 + lcv9).toFixed(0);

    case 'calculateTotalWarrantyJob':
      const cv10 = getTableValue('cvwarrantyjob');
      const lcv10 = getTableValue('lcvwarrantyjob');
      return (cv10 + lcv10).toFixed(0);

    case 'calculateTotalPDI':
      const cv11 = getTableValue('cvpdi');
      const lcv11 = getTableValue('lcvpdi');
      return (cv11 + lcv11).toFixed(0);

    case 'calculateTotalAnnualVehiclesServiced':
      const cv12 = getTableValue('cvannualvehiclesserviced');
      const lcv12 = getTableValue('lcvannualvehiclesserviced');
      return (cv12 + lcv12).toFixed(0);

    default:
      return 0;
  }
}

function saveRowChanges(kpiType, dealerId, periodId, rowIndex) {
  // Recopilar datos de la fila editada
  const formData = {
    action: 'saveKpiData',
    kpiType: kpiType,
    dealerId: dealerId,
    yearMonth: periodId,
    data: {}
  };

  const config = KPI_CONFIGS[kpiType];
  let allFields = [];

  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  let hasChanges = false;

  // Recopilar valores de los campos editados
  allFields.forEach(field => {
    const input = $(`.table-edit-input[data-row-index="${rowIndex}"][data-field="${field.id}"]`);
    const cell = $(`.editable-cell[data-row-index="${rowIndex}"][data-field="${field.id}"]`);

    let value;
    if (input.length) {
      value = parseFloat(input.val()) || 0;
    } else {
      value = parseFloat(cell.data('original-value')) || 0;
    }

    formData.data[field.id] = value;
    if (value > 0) hasChanges = true;
  });

  if (!hasChanges) {
    showMessage('warning', 'No changes detected');
    return;
  }

  // Mostrar loading en el botón de guardar
  const saveBtn = $(`.save-row-btn[data-row-index="${rowIndex}"]`);
  const originalHtml = saveBtn.html();
  saveBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

  // Enviar datos al backend
  $.post('../controllers/kpidata.php', formData, function (response) {
    if (response.success) {
      showMessage('success', `${kpiType.toUpperCase()} data updated successfully`);

      // Actualizar valores originales en las celdas
      allFields.forEach(field => {
        const cell = $(`.editable-cell[data-row-index="${rowIndex}"][data-field="${field.id}"]`);
        if (cell.length) {
          cell.data('original-value', formData.data[field.id]);
        }
      });

      // Cancelar modo edición
      cancelRowEditing(rowIndex, kpiType);

      // Opcional: recargar la tabla completa para mostrar fecha de modificación actualizada
      setTimeout(() => {
        loadKpiData(kpiType);
      }, 1500);
    } else {
      showMessage('error', response.message || 'Error updating data');
    }
  }, 'json')
    .fail(function (xhr, status, error) {
      console.error('Save error:', error);
      showMessage('error', 'Error connecting to server');
    })
    .always(function () {
      // Restaurar botón
      saveBtn.prop('disabled', false).html(originalHtml);
    });
}
// bloque cargar datos empresas
function cargarEmpresas() {
  const datos = {
    action: "getEmpresas"
  }
  $.post("../controllers/kpidata.php", datos, function (data) {
    // Limpiar el select
    $("#dealerhome").empty();
    $("#modalDealerhome").empty();

    // Agregar opción por defecto y opción "All"
    $("#dealerhome").append('<option value="">Select dealer</option>');
    $("#dealerhome").append('<option value="all">All Dealers</option>');
    $("#modalDealerhome").append('<option value="">Select dealer</option>');
    // Procesar los datos recibidos
    if (data && Array.isArray(data)) {
      data.forEach(function (dealer) {
        const option = new Option(dealer.dealer, dealer.dealerid);
        $(option).attr('data-bandera', dealer.bandera);
        $(option).attr('data-pais', dealer.pais);
        $("#dealerhome").append(option);
        $("#modalDealerhome").append(option.cloneNode(true));
      });
    }

    // Destruir Select2 si ya está inicializado
    if ($("#dealerhome").hasClass('select2-hidden-accessible')) {
      $("#dealerhome").select2('destroy');
    }
    if ($("#modalDealerhome").hasClass('select2-hidden-accessible')) {
      $("#modalDealerhome").select2('destroy');
    }

    // Inicializar Select2 con template personalizado
    $("#dealerhome").select2({
      templateResult: formatDealer,
      templateSelection: formatDealerSelection,
      escapeMarkup: function (markup) {
        return markup;
      }
    });

    // Event listener para filtrar cuando cambia el dealer
    $('#dealerhome').on('change', function () {
      const selectedValue = $(this).val();
      // Si se selecciona "all", limpiar el valor para que el backend traiga todos
      if (selectedValue === 'all') {
        $(this).val('').trigger('change.select2');
      }
      if (currentActiveKPI) {
        loadKpiData(currentActiveKPI);
      }
    });
    $("#modalDealerhome").select2({
      templateResult: formatDealer,
      templateSelection: formatDealerSelection,
      escapeMarkup: function (markup) {
        return markup;
      },
      width: '100%',
      dropdownParent: $('#newKpiModal')
    });

  }, 'json').fail(function (xhr, status, error) {
    console.error("Error al cargar empresas:", error);
  });
}

function formatDealer(dealer) {
  if (!dealer.id) {
    return dealer.text;
  }

  const $dealer = $(dealer.element);
  const bandera = $dealer.data('bandera');
  const pais = $dealer.data('pais');

  if (!bandera) {
    return dealer.text;
  }

  const $result = $(
    '<div style="display: flex; align-items: center;">' +
    '<img src="../admin/img/country/' + bandera + '" ' +
    'style="width:20px; margin-right:8px;" ' +
    'alt="' + pais + '" />' +
    '<span>' + dealer.text + '</span>' +
    '</div>'
  );

  return $result;
}

function formatDealerSelection(dealer) {
  if (!dealer.id) {
    return dealer.text;
  }

  const $dealer = $(dealer.element);
  const bandera = $dealer.data('bandera');
  const pais = $dealer.data('pais');

  if (!bandera) {
    return dealer.text;
  }

  return $(
    '<div style="display: flex; align-items: center;">' +
    '<img src="../admin/img/country/' + bandera + '" ' +
    'style="width:16px; margin-right:5px;" ' +
    'alt="' + pais + '" />' +
    '<span>' + dealer.text + '</span>' +
    '</div>'
  );
}
// bloque cargar datos years
function cargarYears() {
  const datos = { action: "getYears" };

  $.post("../controllers/kpidata.php", datos, function (data) {

    $("#dealeryear").empty();
    $("#dealeryear").append('<option value="">Seleccione Year</option>');
    $("#dealeryear").append('<option value="all">All Years</option>');

    let selectedValue = null;

    if (data && Array.isArray(data)) {
      data.forEach(function (item) {

        // Crear opción (value = año, texto = año)
        const option = new Option(item.ano, item.ano);

        // Si SQL devolvió is_current = 1 -> marcar como selected
        if (item.is_current == 1) {
          option.selected = true;
          selectedValue = item.ano; // Guardamos el valor
        }

        $("#dealeryear").append(option);
      });
    }

    // Si Select2 estaba cargado, destruirlo antes de reiniciar
    if ($("#dealeryear").hasClass('select2-hidden-accessible')) {
      $("#dealeryear").select2('destroy');
    }

    // Inicializar Select2
    $("#dealeryear").select2({
      templateResult: formatYear,
      templateSelection: formatYearSelection,
      escapeMarkup: markup => markup
    });

    // Event listener para filtrar cuando cambia el año
    $('#dealeryear').on('change', function () {
      const selectedValue = $(this).val();
      // Si se selecciona "all", limpiar el valor para que el backend traiga todos
      if (selectedValue === 'all') {
        $(this).val('').trigger('change.select2');
      }
      if (currentActiveKPI) {
        loadKpiData(currentActiveKPI);
      }
    });

    // Forzar selección en Select2
    if (selectedValue) {
      $("#dealeryear").val(selectedValue).trigger('change');
    }

  }, 'json')
    .fail(function (xhr, status, error) {
      console.error("Error al cargar años:", error);
    });
}

// Mantener función antigua para la modal (año-mes completo)
function cargarYearsMonth() {
  const datos = { action: "getYearsMonth" };

  $.post("../controllers/kpidata.php", datos, function (data) {

    $("#modalDealeryearmonth").empty();
    $("#modalDealeryearmonth").append('<option value="">Select Year-Month</option>');

    if (data && Array.isArray(data)) {
      data.forEach(function (item) {
        const option = new Option(item.yearmonth, item.calendarioid);
        $("#modalDealeryearmonth").append(option);
      });
    }

    if ($("#modalDealeryearmonth").hasClass('select2-hidden-accessible')) {
      $("#modalDealeryearmonth").select2('destroy');
    }

    $("#modalDealeryearmonth").select2({
      templateResult: formatYearMonth,
      templateSelection: formatYearMonthSelection,
      escapeMarkup: markup => markup,
      width: '100%',
      dropdownParent: $('#newKpiModal')
    });

  }, 'json')
    .fail(function (xhr, status, error) {
      console.error("Error al cargar años-meses:", error);
    });
}

function formatYear(year) {
  if (!year.id) {
    return year.text;
  }

  const $result = $(
    '<div style="display: flex; align-items: center;">' +
    '<i class="fa fa-calendar-o" style="margin-right:8px; color:#007bff;"></i>' +
    '<span>' + year.text + '</span>' +
    '</div>'
  );

  return $result;
}

function formatYearSelection(year) {
  if (!year.id) {
    return year.text;
  }

  return $(
    '<div style="display: flex; align-items: center;">' +
    '<i class="fa fa-calendar-o" style="margin-right:5px; color:#007bff;"></i>' +
    '<span>' + year.text + '</span>' +
    '</div>'
  );
}

function formatYearMonth(yearmonth) {
  if (!yearmonth.id) {
    return yearmonth.text;
  }

  const $result = $(
    '<div style="display: flex; align-items: center;">' +
    '<i class="fa fa-calendar" style="margin-right:8px; color:#007bff;"></i>' +
    '<span>' + yearmonth.text + '</span>' +
    '</div>'
  );

  return $result;
}

function formatYearMonthSelection(yearmonth) {
  if (!yearmonth.id) {
    return yearmonth.text;
  }

  return $(
    '<div style="display: flex; align-items: center;">' +
    '<i class="fa fa-calendar" style="margin-right:5px; color:#007bff;"></i>' +
    '<span>' + yearmonth.text + '</span>' +
    '</div>'
  );
}

// ============================================================================
// BULK UPLOAD FUNCTIONS
// ============================================================================

let bulkUploadData = null;
let currentBulkKpiType = null;
let currentBulkFieldIds = []; // Variable global para los field IDs

function openBulkUploadModal(kpiType) {
  currentBulkKpiType = kpiType;
  const config = KPI_CONFIGS[kpiType];

  $('#bulkKpiTypeTitle').text(config.title.toUpperCase());
  $('#bulkKpiType').text(config.title);

  // Obtener y guardar los field IDs en orden
  let allFields = [];
  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  // Guardar solo los field IDs de campos no calculados en el orden correcto
  currentBulkFieldIds = [];
  allFields.forEach(field => {
    if (!field.calculated) {
      currentBulkFieldIds.push(field.id);
    }
  });

  console.log('Field IDs for bulk upload:', currentBulkFieldIds);

  // Reset modal
  $('#bulkUploadFile').val('');
  $('#uploadExcelBtn').prop('disabled', true);
  $('#bulkUploadMessage').hide();
  bulkUploadData = null;

  // Inicializar select de Dealer
  initializeBulkDealerSelect();

  // Pre-seleccionar dealer según filtro principal
  const selectedDealer = $('#dealerhome').val();

  if (selectedDealer && selectedDealer !== '' && selectedDealer !== 'all') {
    $('#bulkDealerhome').val(selectedDealer).trigger('change');
  } else {
    $('#bulkDealerhome').val('').trigger('change');
  }

  $('#bulkUploadModal').modal('show');
}

function initializeBulkDealerSelect() {
  // Copiar opciones del select principal de dealers
  if ($('#bulkDealerhome option').length <= 1) {
    $('#bulkDealerhome').html($('#dealerhome').html());
  }

  // Inicializar Select2 si no está inicializado
  if (!$('#bulkDealerhome').hasClass('select2-hidden-accessible')) {
    $('#bulkDealerhome').select2({
      templateResult: formatDealer,
      templateSelection: formatDealerSelection,
      escapeMarkup: function (markup) { return markup; },
      dropdownParent: $('#bulkUploadModal')
    });
  }
}

// Habilitar botón de upload cuando se selecciona un archivo
$(document).on('change', '#bulkUploadFile', function () {
  const file = this.files[0];
  if (file) {
    $('#uploadExcelBtn').prop('disabled', false);
  } else {
    $('#uploadExcelBtn').prop('disabled', true);
  }
});

// Descargar plantilla de Excel
$(document).on('click', '#downloadTemplateBtn', function () {
  if (!currentBulkKpiType) return;

  const config = KPI_CONFIGS[currentBulkKpiType];
  let allFields = [];

  // Obtener todos los campos según la estructura
  if (config.hasGroups && config.groups) {
    config.groups.forEach(group => {
      allFields = allFields.concat(group.fields);
    });
  } else {
    allFields = config.fields;
  }

  // Crear estructura de Excel con columna Calendar primero (YEAR - MONTH)
  const headers = ['Calendar (YEAR - MONTH)'];

  allFields.forEach(field => {
    if (!field.calculated) {
      headers.push(field.label);
    }
  });

  console.log('Generating template with headers:', headers);

  // Crear workbook con headers
  const ws = XLSX.utils.aoa_to_sheet([headers]);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Data');

  // Crear hoja de referencia con períodos disponibles
  const calendarData = [['Available Periods (Use format: YEAR - MONTH)']];
  calendarData.push(['Example: 2024 - January']);
  calendarData.push(['']);
        $('#modalDealeryearmonth option').each(function () {
    const text = $(this).text();
    if (text && text !== '' && text !== 'All Periods') {
      calendarData.push([text]);
    }
  });
  const wsCalendars = XLSX.utils.aoa_to_sheet(calendarData);
  XLSX.utils.book_append_sheet(wb, wsCalendars, 'Calendars Reference');

  // Descargar archivo
  const filename = `${config.title.replace(/ /g, '_')}_Template.xlsx`;
  XLSX.writeFile(wb, filename);

  showBulkMessage('success', 'Template downloaded! Use format YEAR - MONTH in column A (check reference sheet).');
});

// Procesar archivo Excel subido
$(document).on('click', '#uploadExcelBtn', function () {
  const fileInput = document.getElementById('bulkUploadFile');
  const file = fileInput.files[0];

  if (!file) {
    showBulkMessage('error', 'Please select a file first.');
    return;
  }

  // Validar que se haya seleccionado dealer
  const selectedDealer = $('#bulkDealerhome').val();

  if (!selectedDealer || selectedDealer === '') {
    showBulkMessage('error', 'Please select a Dealer first.');
    return;
  }

  // Deshabilitar botón y mostrar loading
  const btn = $(this);
  btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-2"></i>Processing...');

  const reader = new FileReader();

  reader.onload = function (e) {
    try {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });

      // Obtener la primera hoja
      const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
      const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

      if (jsonData.length < 1) {
        showBulkMessage('error', 'The Excel file is empty or has invalid format.');
        btn.prop('disabled', false).html('<i class="fa fa-upload mr-2"></i>Upload and Process');
        return;
      }

      // La primera fila son los headers, el resto son datos
      const headers = jsonData[0];
      const dataRows = jsonData.slice(1);

      if (dataRows.length === 0) {
        showBulkMessage('error', 'No data rows found in the Excel file.');
        btn.prop('disabled', false).html('<i class="fa fa-upload mr-2"></i>Upload and Process');
        return;
      }

      console.log('Headers:', headers);
      console.log('Using field IDs:', currentBulkFieldIds);
      console.log('Sample data row:', dataRows[0]);

      // Obtener configuración del KPI actual para procesar campos calculados
      const config = KPI_CONFIGS[currentBulkKpiType];
      let allFields = [];
      if (config.hasGroups && config.groups) {
        config.groups.forEach(group => {
          allFields = allFields.concat(group.fields);
        });
      } else {
        allFields = config.fields;
      }

      // Convertir a formato de objetos
      bulkUploadData = dataRows.map((row, rowIndex) => {
        // La columna 0 es Calendar (formato YEAR - MONTH)
        const calendarText = row[0];

        if (!calendarText) {
          console.warn(`Row ${rowIndex + 2}: Missing Calendar`);
          return null;
        }

        const obj = {
          dealerId: selectedDealer,
          calendar: calendarText
        };

        // Mapear los campos del Excel (empezando desde la columna 1)
        currentBulkFieldIds.forEach((fieldId, index) => {
          const colIndex = index + 1; // +1 porque la columna 0 es Calendar
          obj[fieldId] = row[colIndex] !== undefined && row[colIndex] !== null && row[colIndex] !== '' ? row[colIndex] : 0;
        });

        // Calcular los campos calculados
        allFields.forEach(field => {
          if (field.calculated && field.formula) {
            if (typeof window[field.formula] === 'function') {
              obj[field.id] = window[field.formula](obj);
            }
          }
        });

        return obj;
      }).filter(row => row !== null); // Filtrar filas inválidas

      console.log('Processed data sample:', bulkUploadData[0]);

      showBulkMessage('info', `File loaded successfully. ${bulkUploadData.length} record(s) ready to import.`);

      // Procesar automáticamente
      processImport(btn);

    } catch (error) {
      console.error('Error processing Excel:', error);
      showBulkMessage('error', 'Error processing Excel file: ' + error.message);
      btn.prop('disabled', false).html('<i class="fa fa-upload mr-2"></i>Upload and Process');
    }
  };

  reader.readAsArrayBuffer(file);
});

function processImport(btn) {
  if (!bulkUploadData || bulkUploadData.length === 0) {
    showBulkMessage('error', 'No data to import.');
    btn.prop('disabled', false).html('<i class="fa fa-upload mr-2"></i>Upload and Process');
    return;
  }

  // Enviar datos al servidor usando JSON
  // Cada fila ya contiene dealerId y calendar (texto)
  $.ajax({
    url: '../controllers/kpidata.php',
    type: 'POST',
    data: JSON.stringify({
      action: 'bulkUploadKpiData',
      kpiType: currentBulkKpiType,
      data: bulkUploadData
    }),
    contentType: 'application/json',
    dataType: 'json',
    success: function (response) {
      if (response.success) {
        const successMsg = `
          <strong>Import completed!</strong><br>
          <i class="fa fa-check-circle text-success mr-1"></i> ${response.details.success} record(s) imported successfully<br>
          ${response.details.errors > 0 ? `<i class="fa fa-exclamation-circle text-danger mr-1"></i> ${response.details.errors} record(s) failed` : ''}
        `;
        showBulkMessage('success', successMsg);

        // Recargar tabla después de un momento
        setTimeout(() => {
          $('#bulkUploadModal').modal('hide');
          loadKpiData(currentBulkKpiType);
        }, 2000);
      } else {
        showBulkMessage('error', response.message || 'Error importing data.');
        btn.prop('disabled', false).html('<i class="fa fa-upload mr-2"></i>Upload and Process');
      }
    },
    error: function (xhr, status, error) {
      console.error('Import error:', error);
      showBulkMessage('error', 'Server error: ' + error);
      btn.prop('disabled', false).html('<i class="fa fa-upload mr-2"></i>Upload and Process');
    }
  });
}

// Cancelar importación ya no es necesario (se procesa automáticamente)

function showBulkMessage(type, message) {
  const alertClass = {
    'success': 'alert-success',
    'error': 'alert-danger',
    'info': 'alert-info',
    'warning': 'alert-warning'
  }[type] || 'alert-info';

  const icon = {
    'success': 'fa-check-circle',
    'error': 'fa-exclamation-circle',
    'info': 'fa-info-circle',
    'warning': 'fa-exclamation-triangle'
  }[type] || 'fa-info-circle';

  $('#bulkUploadMessage').html(`
    <div class="alert ${alertClass}">
      <i class="fa ${icon} mr-2"></i>${message}
    </div>
  `).show();
}