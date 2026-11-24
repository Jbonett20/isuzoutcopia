<?php
$loc = "kpidata";
include_once '../admin/src/seguridad.php';
@session_start();
include_once("../controllers/new.php");
?>
<!DOCTYPE html>
<html>

<head>
  <?php require_once("src/header.php"); ?>
  <style>
    .kpi-navigation {
      margin-top: 15px;
    }

    .btn-kpi {
      padding: 18px 15px;
      border: 1px solid #e3e6f0;
      border-radius: 6px;
      background-color: #ffffff;
      color: #5a5c69;
      font-weight: 500;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.2s ease-in-out;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
      position: relative;
      overflow: hidden;
    }

    .btn-kpi:hover {
      background-color: #f8f9fc;
      border-color: #d1d3e2;
      color: #3a3b45;
      transform: translateY(-1px);
      box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
    }

    .btn-kpi i {
      font-size: 24px;
      margin-bottom: 8px;
      display: block;
      color: #858796;
      transition: color 0.2s ease-in-out;
    }

    .btn-kpi:hover i {
      color: #5a5c69;
    }

    .btn-kpi.active {
      background-color: #FF0604;
      border-color: #FF0604;
      color: white;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(255, 6, 4, 0.4);
    }

    .btn-kpi.active i {
      color: white;
    }

    .btn-kpi span {
      font-size: 11px;
      line-height: 1.2;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .btn-kpi {
        padding: 15px 10px;
        font-size: 11px;
      }

      .btn-kpi i {
        font-size: 20px;
        margin-bottom: 5px;
      }

      .btn-kpi span {
        font-size: 10px;
      }
    }

    @media (max-width: 576px) {
      .btn-kpi {
        padding: 20px 15px;
        font-size: 12px;
      }

      .btn-kpi i {
        font-size: 24px;
        margin-bottom: 8px;
      }

      .btn-kpi span {
        font-size: 11px;
      }
    }

    /* nuevo */
    .btn-kpi {
      padding: 15px 8px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      background: white;
      font-weight: 600;
      font-size: 10px;
      text-transform: uppercase;
      transition: all .3s ease;
      min-height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      line-height: 1.1;
      word-wrap: break-word;
      hyphens: auto;
    }

    .btn-kpi:hover {
      border-color: #ff3333;
      color: #ff3333;
      background: #fff6f6;
    }

    .btn-kpi span {
      font-size: 10px;
      line-height: 1.1;
      word-break: break-word;
      hyphens: auto;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
      .btn-kpi {
        font-size: 9px;
        padding: 12px 6px;
        min-height: 55px;
      }

      .btn-kpi span {
        font-size: 9px;
      }
    }

    @media (max-width: 992px) {
      .btn-kpi {
        font-size: 11px;
        padding: 15px 10px;
        min-height: 65px;
      }

      .btn-kpi span {
        font-size: 11px;
      }
    }

    @media (max-width: 768px) {
      .btn-kpi {
        font-size: 10px;
        padding: 12px 8px;
        min-height: 60px;
      }

      .btn-kpi span {
        font-size: 10px;
      }
    }

    @media (max-width: 576px) {
      .btn-kpi {
        font-size: 12px;
        padding: 18px 15px;
        min-height: 70px;
      }

      .btn-kpi span {
        font-size: 12px;
        line-height: 1.2;
      }
    }

    /* Header Styles (sin acordeón) */
    .kpi-header {
      padding: 15px 20px;
      background: #f8f9fc;
      border-radius: .625rem .625rem 0 0;
      border-bottom: 1px solid #e3e6f0;
    }

    .kpi-header h6 {
      margin: 0;
      display: flex;
      align-items: center;
      width: 100%;
    }

    /* Mantener estilos de acordeón para compatibilidad */
    .kpi-accordion-header {
      cursor: pointer;
      transition: all 0.3s ease;
      border-bottom: 1px solid #e3e6f0;
      padding: 15px 20px;
      background: #f8f9fc;
      border-radius: .625rem .625rem 0 0;
    }

    .kpi-accordion-header:hover {
      background: #eaecf4;
    }

    .kpi-accordion-header h6 {
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
    }

    /* Widget body siempre visible */
    .widget-body {
      transition: none;
      display: block !important;
    }

    .widget-with-tabs {
      padding: 0px !important;
    }


    /* Dynamic Fields Styles */
    .dynamic-field-group {
      margin-bottom: 15px;
    }

    .dynamic-field-group label {
      font-size: 1rem;
      /* font-weight: 600; */
      /* color: #5a5c69; */
      margin-bottom: 5px;
    }

    .dynamic-field-group .form-control {
      /* border: 1px solid #d1d3e2; */
      border-radius: 4px;
      font-size: inherit !important;
    }

    .calculated-field {
      background-color: #f8f9fc !important;
      border-color: #e3e6f0 !important;
      color: #6c757d !important;
    }

    /* KPI Group Headers */
    .kpi-group-header {
      width: 100%;
      margin-bottom: 15px;
    }

    .kpi-group-header h5 {
      background: linear-gradient(135deg, #1d1d1d 60%, #FF0604 100%);
      color: white;
      padding: 12px 20px;
      margin: 0;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
    }

    .kpi-group-header h5 i {
      margin-right: 8px;
    }

    /* KPI Action Buttons */
    #kpiActionButtons {
      margin-top: 20px;
    }

    #saveKpiData {
      /* background: linear-gradient(135deg, #1cc88a 0%, #36b9cc 100%); */
      border: none;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 3px 15px rgba(28, 200, 138, 0.3);
      transition: all 0.3s ease;
    }

    #saveKpiData:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(28, 200, 138, 0.4);
    }

    #eraseKpiData {
      background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%);
      border: none;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 3px 15px rgba(231, 74, 59, 0.3);
      transition: all 0.3s ease;
    }

    #eraseKpiData:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(231, 74, 59, 0.4);
    }

    .calculated-field:focus {
      box-shadow: none !important;
      border-color: #e3e6f0 !important;
    }

    .field-icon {
      color: #858796;
      margin-right: 5px;
    }

    .calculated-badge {
      background-color: #e7e9ed;
      color: #5a5c69;
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 3px;
      margin-left: 5px;
    }

    /* Estilos para tabla responsive */
    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    #TableListItems th,
    #TableListItems td {
      white-space: nowrap;
      min-width: 80px;
    }

    /* Scroll horizontal visible */
    .table-responsive::-webkit-scrollbar {
      height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    /* Mantener tabla compacta y sin separaciones */
    #TableListItems {
      margin-bottom: 0 !important;
      border-collapse: collapse !important;
    }

    .dataTables_wrapper {
      overflow-x: visible;
    }

    /* Estilos para KPI Tabs */
    #kpiTabs {
      border-bottom: 2px solid #ffffff;
      margin-bottom: 0;
      background: #ffffff;
      border-radius: 0;
      padding: 0;
      border-top: 1px solid #ffffff;
    }

    #kpiTabs2 .nav-link {
      background: transparent;
      border: none;
      border-bottom: 3px solid transparent;
      border-radius: 0;
      color: #5a5c69;
      font-weight: 600;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 12px 20px;
      margin-right: 0;
      transition: all 0.3s ease;
      position: relative;
      border-right: 1px solid rgba(227, 230, 240, 0.5);
    }

    #kpiTabs .nav-link:last-child {
      border-right: none;
    }

    #kpiTabs .nav-link:hover {
      background: rgba(255, 6, 4, 0.1);
      color: #FF0604;
      border-bottom-color: rgba(255, 6, 4, 0.3);
    }

    #kpiTabs .nav-link.active {
      background: #fff;
      color: #FF0604;
      border-bottom-color: #FF0604;
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
      position: relative;
      z-index: 1;
    }

    #kpiTabs .nav-link i {
      margin-right: 8px;
      font-size: 14px;
    }

    /* Contenido del tab pegado */
    .kpi-tab-content {
      background-color: rgb(254 254 254) !important;
      border-radius: 0;
      min-height: 400px;
      margin-top: 0;
      border-top: none;
    }

    .tab-content {
      background-color: rgb(254 254 254) !important;
      margin-top: 0;
      border-top: none;
    }

    /* Widget sin bordes inferiores para tabs */
    .widget-with-tabs {
      border-radius: .625rem .625rem 0 0;
    }

    .widget-with-tabs .widget-body {
      border-radius: 0 0 .625rem .625rem;
      margin-top: 0;
      padding-top: 0;
    }

    /* Responsive tabs */
    @media (max-width: 768px) {
      #kpiTabs {
        padding: 5px 10px 0;
      }

      #kpiTabs .nav-link {
        font-size: 10px;
        padding: 10px 15px;
        margin-right: 2px;
      }

      #kpiTabs .nav-link i {
        margin-right: 5px;
        font-size: 12px;
      }
    }

    @media (max-width: 576px) {
      #kpiTabs .nav-link {
        font-size: 9px;
        padding: 8px 12px;
        margin-right: 1px;
      }
    }

    /* Estilos para Select2 en modal con texto blanco */
    .modal .select2-container .select2-selection--single {
      /* background-color: transparent !important; */
      /* border: 1px solid #ced4da !important; */
      border-radius: 4px !important;
      height: calc(1.5em + 0.75rem + 2px) !important;
    }

    .modal .select2-container .select2-selection--single .select2-selection__rendered {
      color: white !important;
      line-height: calc(1.5em + 0.75rem) !important;
      padding-left: 12px !important;
      padding-right: 20px !important;
    }

    .modal .select2-container .select2-selection--single .select2-selection__placeholder {
      color: rgba(255, 255, 255, 0.7) !important;
    }

    .modal .select2-container .select2-selection--single .select2-selection__arrow {
      height: calc(1.5em + 0.75rem) !important;
      right: 10px !important;
    }

    .modal .select2-container .select2-selection--single .select2-selection__arrow b {
      border-color: white transparent transparent transparent !important;
    }

    /* Dropdown de Select2 */
    .select2-dropdown {
      background-color: white !important;
      /* border: 1px solid #ced4da !important; */
    }

    .select2-dropdown .select2-results__option {
      color: #333 !important;
    }

    .select2-dropdown .select2-results__option--highlighted {
      background-color: #007bff !important;
      color: white !important;
    }

    table .form-control:focus,
    table .form-control:active {
      color: #040620 !important;
    }

    /* Estilos para edición en línea en tabla */
    .editable-cell {
      position: relative;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .editable-cell:not(.calculated-field):hover {
      background-color: #f8f9fa !important;
    }

    .editable-cell.calculated-field {
      background-color: #e9ecef !important;
      color: #6c757d !important;
    }

    .table-edit-input {
      border: 1px solid #ffc107 !important;
      background-color: #fff3cd !important;
      box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
      font-size: 11px !important;
      padding: 4px 8px !important;
      height: auto !important;
      min-height: 30px;
    }

    .table-edit-input:focus {
      border-color: #ffc107 !important;
      box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.5) !important;
      outline: none !important;
    }

    /* Botones de acción de tabla mejorados */
    .btn-xs {
      padding: 4px 8px;
      font-size: 11px;
      line-height: 1.2;
      border-radius: 3px;
    }

    .edit-row-btn {
      background-color: #ffc107 !important;
      border-color: #ffc107 !important;
      color: #212529 !important;
    }

    .edit-row-btn:hover {
      background-color: #e0a800 !important;
      border-color: #d39e00 !important;
      color: #212529 !important;
    }

    .save-row-btn {
      background-color: #28a745 !important;
      border-color: #28a745 !important;
      color: white !important;
    }

    .save-row-btn:hover {
      background-color: #218838 !important;
      border-color: #1e7e34 !important;
    }

    .cancel-row-btn {
      background-color: #6c757d !important;
      border-color: #6c757d !important;
      color: white !important;
    }

    .cancel-row-btn:hover {
      background-color: #5a6268 !important;
      border-color: #545b62 !important;
    }

    /* Indicador visual para fila en edición */
    tr.editing-row {
      background-color: #fff3cd !important;
    }

    tr.editing-row td {
      border-color: #ffc107 !important;
    }
  </style>
</head>

<body class="">
  <?php require_once("src/navbar.php"); ?>
  <!--
  Main sidebar seen on the left. may be static or collapsing depending on selected state.

    * Collapsing - navigation automatically collapse when mouse leaves it and expand when enters.
    * Static - stays always open.
-->
  <nav id="sidebar" class="sidebar" role="navigation">
    <!-- need this .js class to initiate slimscroll -->
    <div class="js-sidebar-content">
      <?php require_once("src/logo.php"); ?>
      <!-- seems like lots of recent admin template have this feature of user info in the sidebar.
             looks good, so adding it and enhancing with notifications -->
      <?php require_once("src/sidebar.php"); ?>
    </div>
  </nav>


  <div class="content-wrap">
    <!-- main page content. the place to put widgets in. usually consists of .row > .col-lg-* > .widget.  -->
    <main id="content" class="content" role="main">
      <!-- Page content -->
      <ol class="breadcrumb">
        <li class="breadcrumb-item">You are in</li>
        <li class="breadcrumb-item active">KPI Platform</li>
      </ol>
      <h1 class="page-title">KPI
        <small>
          <small>Platform</small>
        </small>
      </h1>
      <div class="row">
        <div class="col-lg-12">
          <section class="widget">
            <header>
              <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
                GENERAL FILTERS (<?php if (isset($_SESSION['DatLoc']['dealer'])) { ?> | <img src="../admin/img/country/<?php echo ($_SESSION['DatLoc']['bandera']); ?>" style="width:20px;" alt="<?php echo ($_SESSION['DatLoc']['pais']); ?>" /> <?php echo (strtoupper($_SESSION['DatLoc']['dealer'])); ?><?php } else { ?> <img src="../admin/img/country/worldsa.svg" style="width:20px;" alt="All World" /> | GENERAL ADMIN <?php } ?>)
              </h6>
            </header>
            <div class="widget-body">
              <form class="form-vertical" role="form">
                <div class="row">
                                                                        <div class="col-lg-3">
                    <label for="dealeryear" class="col-md-12" style="color:black;">Year:</label>
                    <div class="col-md-12 pr-0">
                      <select id="dealeryear" data-placeholder="All Years" class="select2 form-control" tabindex="-1" name="dealeryear">
                        <!--  -->
                      </select>
                    </div>
                  </div>
                  <div class="col-lg-3">
                    <label for="dealerhome" class="col-md-12" style="color:black;">Dealer:</label>
                    <div class="col-md-12 pr-0">
                      <select id="dealerhome" data-placeholder="All Dealers" class="select2 form-control" tabindex="-1" name="dealerhome">
                        <!-- Options will be loaded dynamically -->
                      </select>
                    </div>
                  </div>
                </div>
              </form>

            </div>
          </section>
        </div>
        <div class="col-lg-12" id="TabDetailOrder">

        </div>
      </div>

      <footer class="content-footer hidden-print">
        <?php require_once("src/footer.php"); ?>
      </footer>
    </main>
  </div>
  <!-- nueva modal -->
  <div class="modal fade" id="newKpiModal" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalKpiTitle">Holiday</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="ModalBody">
          <form class="form-vertical" role="form" id="kpiModalForm">
            <!-- Filtros de Dealer y Year-Month -->
            <div class="row mb-3">
              <div class="col-lg-4">
                <label for="modalDealerhome" class="text-white text-md-left">Dealer</label>
                <select id="modalDealerhome" data-placeholder="Select Dealer" class="select2 form-control text-white" tabindex="-1" name="modalDealerhome">
                  <option value="">Loading dealers...</option>
                </select>
              </div>
              <div class="col-lg-4">
                <label for="modalDealeryearmonth" class="text-white">Year - Month</label>
                <select id="modalDealeryearmonth" data-placeholder="Select Period" class="select2 form-control text-white" tabindex="-1" name="modalDealeryearmonth">
                  <option value="">Select Year-Month</option>
                </select>
              </div>
            </div>

            <!-- Campos dinámicos según el KPI seleccionado -->
            <div class="row" id="modalDynamicFields">
              <div class="col-12">
                <div id="modalKpiFieldsContainer" class="row">
                  <!-- Los campos se generarán dinámicamente aquí -->
                </div>
              </div>
            </div>

            <!-- Mensajes de respuesta -->
            <div class="row mt-3">
              <div class="col-12">
                <div id="modalKpiSaveMessage" style="display: none;"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fa fa-times mr-2"></i>Cancel
          </button>
          <button type="button" id="modalEraseKpiData" class="btn btn-warning">
            <i class="fa fa-eraser mr-2"></i>Clear Fields
          </button>
          <button type="submit" id="modalSaveKpiData" class="btn btn-success">
            <i class="fa fa-save mr-2"></i>Save Data
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- end nueva modal -->

  <!-- Modal para Carga Masiva -->
  <div class="modal fade" id="bulkUploadModal" role="dialog" aria-labelledby="bulkUploadModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-white" id="bulkUploadModalTitle">
            <i class="fa fa-file-excel-o mr-2"></i>BULK UPLOAD - <span id="bulkKpiTypeTitle"></span>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Select de Dealer -->
          <div class="row mb-3">
            <div class="col-lg-12">
              <div class="form-group">
                <label for="bulkDealerhome" class="text-white text-md-left" style="display:block;">Dealer</label>
                <select id="bulkDealerhome" data-placeholder="Select Dealer" class="select2 form-control text-white" tabindex="-1" name="bulkDealerhome" style="width: 100%;">
                  <option value="">Select dealer</option>
                </select>
              </div>
            </div>
          </div>

          <div class="alert" role="alert" style="background-color: #232642">
            <i class="fa fa-info-circle mr-2"></i>
            <strong>Instructions:</strong>
            <ol class="mb-0 mt-2">
              <li>Select the Dealer for the import</li>
              <li>Download the Excel template for <strong id="bulkKpiType"></strong></li>
              <li>In column A, enter the period using format: <strong>YEAR - MONTH</strong> (e.g., 2024 - January)</li>
              <li>Fill in the remaining KPI data columns</li>
              <li>Upload the completed Excel file - it will be processed automatically</li>
            </ol>
          </div>

          <!-- Subir archivo -->
          <div class="card mb-3">
            <div class="card-header text-white" style="background-color: #1a1c32">
              <i class="fa fa-upload mr-2"></i>Upload Excel File
            </div>
            <div class="card-body" style="background-color: #232642;">
              <div class="form-group">
                <label for="bulkUploadFile" class="text-muted">Select Excel file (.xlsx, .xls):</label>
                <input type="file" class="form-control-file" id="bulkUploadFile" accept=".xlsx,.xls">
              </div>
              <p class="text-muted mt-2 mb-0">
                <small><i class="fa fa-info-circle mr-1"></i>The file will be processed automatically after upload.</small>
              </p>
            </div>
          </div>

          <!-- Mensajes de resultado -->
          <div id="bulkUploadMessage" style="display: none;"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fa fa-times mr-2"></i>Close
          </button>
          <button type="button" class="btn btn-primary" id="downloadTemplateBtn">
            <i class="fa fa-download mr-2"></i>Download Template
          </button>
          <button type="button" class="btn btn-success" id="uploadExcelBtn" disabled>
            <i class="fa fa-upload mr-2"></i>Upload and Process
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- end bulk upload modal -->

  <?php require_once("src/scripts.php"); ?>
</body>

</html>