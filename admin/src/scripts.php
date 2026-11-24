    <!-- The Loader. Is shown when pjax happens -->
    <div class="loader-wrap hiding hide">
        <i class="fa fa-circle-o-notch fa-spin-fast"></i>
    </div>
    <!-- common libraries. required for every page-->
    <script src="../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../node_modules/jquery-pjax/jquery.pjax.js"></script>
    <script src='../node_modules/popper.js/dist/umd/popper.js'></script>
    <script src="../node_modules/bootstrap/dist/js/bootstrap.js"></script>
    <script src="../node_modules/bootstrap/js/dist/util.js"></script>
    <script src="../node_modules/widgster/widgster.js"></script>
    <script src="../node_modules/hammerjs/hammer.js"></script>
    <script src="../node_modules/jquery-hammerjs/jquery.hammer.js"></script>
    <!-- common app js -->
    <script src="../admin/js/settings.js"></script>
    <script src="../admin/js/app.js"></script>
    <!-- Page scripts -->
    <script id="test" src="../node_modules/underscore/underscore.js"></script>
    <script src="../node_modules/jquery-sparkline/jquery.sparkline.js"></script>
    <script src="../node_modules/d3/d3.min.js"></script>
  
    <script src="../node_modules/raphael/raphael.min.js"></script>
    <script src="../node_modules/jquery-mapael/js/jquery.mapael.js"></script>
    <script src="../node_modules/jquery-mapael/js/maps/usa_states.js"></script>
    <script src="../node_modules/jquery-mapael/js/maps/world_countries.js"></script>
    <script src="../node_modules/bootstrap_calendar/bootstrap_calendar/js/bootstrap_calendar.min.js"></script>
    <script src="../node_modules/jquery.animate-number/jquery.animateNumber.min.js"></script>
    <!-- page specific js -->
    <?php if (isset($loc) && $loc == "home") { ?>
        
        <!--<script src="../admin/js/custom_ini.js"></script>-->
        <script src="../admin/js/dashboard/index.js"></script>
        <!--<script src="../admin/js/form/form-elements.js"></script>-->
    <?php } ?>
    <?php if (isset($loc) && $loc == "overview") { ?>
        
        <script src="../admin/js/custom_ini.js"></script>
        <script src="../admin/js/dashboard/index.js"></script>
        <!--<script src="../admin/js/form/form-elements.js"></script>-->
    <?php } ?>
    <script src="../node_modules/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

    <?php if (isset($loc) && $loc == "dashboard") { ?>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "new") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../node_modules/messenger/docs/welcome/javascripts/location-sel.js"></script>
        <script src="../admin/js/custom_new.js"></script>
        <script src="../admin/js/components/ui-notifications.js"></script>
        <script src="../admin/js/xlsx/xlsx.full.min.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "exist") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../node_modules/messenger/docs/welcome/javascripts/location-sel.js"></script>
        <!-- page specific js -->
        <script src="../admin/js/tables/tables-dynamic.js"></script>
        <script src="../admin/js/custom_exist.js"></script>
        <script src="../admin/js/components/ui-notifications.js"></script>
        <script src="../admin/js/xlsx/xlsx.full.min.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "kpidata") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../admin/js/tables/tables-dynamic.js"></script>
        <!-- <script src="../admin/js/components/ui-notifications.js"></script> -->
        <script src="../admin/js/xlsx/xlsx.full.min.js"></script>
        <script src="../admin/js/kpidata.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "dealertarget") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../node_modules/messenger/docs/welcome/javascripts/location-sel.js"></script>
        <script src="../admin/js/components/ui-notifications.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <script src="../admin/js/tables/tables-dynamic.js"></script>
        <script src="../admin/js/custom_dealertarget.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "list") { ?>
        <script src="../admin/js/xlsx/xlsx.full.min.js"></script>
        <script src="../admin/js/custom_lista.js"></script>
        <script src="../admin/js/pdf/jspdf.umd.min.js"></script>
        <script src="../admin/js/pdf/jspdf.plugin.autotable.min.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "user") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../node_modules/messenger/docs/welcome/javascripts/location-sel.js"></script>
        <script src="../admin/js/components/ui-notifications.js"></script>
        <script src="../admin/js/custom_users.js"></script>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <!-- page specific js -->
        <script src="../admin/js/tables/tables-dynamic.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "dealer") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../node_modules/messenger/docs/welcome/javascripts/location-sel.js"></script>
        <script src="../admin/js/components/ui-notifications.js"></script>
        <script src="../admin/js/custom_dealers.js"></script>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <!-- page specific js -->
        <script src="../admin/js/tables/tables-dynamic.js"></script>
        <script src="https://unpkg.com/imask"></script>

    <?php } ?>

    <?php if (isset($loc) && $loc == "calendar") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../node_modules/messenger/docs/welcome/javascripts/location-sel.js"></script>
        <script src="../admin/js/components/ui-notifications.js"></script>
        <script src="../node_modules/moment/min/moment.min.js"></script>
        <script src="../node_modules/components-jqueryui/jquery-ui.js"></script>
        <script src="../node_modules/fullcalendar/dist/fullcalendar.min.js"></script>
        <script src="../admin/js/custom_calendar.js"></script>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <!-- page specific js -->
        <script src="../admin/js/tables/tables-dynamic.js"></script>
        <script src="https://unpkg.com/imask"></script>

    <?php } ?>

    <?php if (isset($loc) && $loc == "holiday") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../node_modules/messenger/docs/welcome/javascripts/location-sel.js"></script>
        <script src="../admin/js/components/ui-notifications.js"></script>
        <script src="../node_modules/moment/min/moment.min.js"></script>
        <script src="../node_modules/components-jqueryui/jquery-ui.js"></script>
        <script src="../node_modules/fullcalendar/dist/fullcalendar.min.js"></script>
        <script src="../admin/js/custom_holiday.js"></script>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <!-- page specific js -->
        <script src="../admin/js/tables/tables-dynamic.js"></script>
        <script src="https://unpkg.com/imask"></script>

    <?php } ?>

    <?php if (isset($loc) && $loc == "profile") { ?>
        <script src="../node_modules/messenger/build/js/messenger.js"></script>
        <script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
        <script src="../node_modules/messenger/docs/welcome/javascripts/location-sel.js"></script>
        <script src="../admin/js/components/ui-notifications.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "list") { ?>
        <script src="../node_modules/backbone/backbone.js"></script>
        <script src="../node_modules/backbone.paginator/lib/backbone.paginator.min.js"></script>
        <script src="../node_modules/backgrid/lib/backgrid.js"></script>
        <script src="../node_modules/backgrid-paginator/backgrid-paginator.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <!-- page specific js -->
        <script src="../admin/js/tables/tables-dynamic.js"></script>
    <?php } ?>
    <?php if (isset($loc) && $loc == "role_permissions") { ?>
        <script src="../admin/js/custom_role_permissions.js"></script>
    <?php } ?>

    <script src="../node_modules/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="../node_modules/jquery-autosize/jquery.autosize.min.js"></script>
    <script src="../node_modules/select2/dist/js/select2.min.js"></script>
    <script src="../node_modules/switchery/dist/switchery.min.js"></script>
    <script src="../node_modules/moment/min/moment.min.js"></script>
    <script src="../node_modules/tempusdominus-bootstrap-4/build/js/tempusdominus-bootstrap-4.min.js"></script>
    <script src="../node_modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js"></script>
    <script src="../node_modules/jasny-bootstrap/js/inputmask.js"></script>
    <script src="../node_modules/jasny-bootstrap/js/fileinput.js"></script>
    <script src="../node_modules/holderjs/holder.js"></script>
    <script src="../node_modules/dropzone/dist/min/dropzone.min.js"></script>
    <script src="../node_modules/markdown/lib/markdown.js"></script>
    <script src="../node_modules/bootstrap-markdown/js/bootstrap-markdown.js"></script>
    <script src="../node_modules/bootstrap-slider/dist/bootstrap-slider.min.js"></script>
    <script src="../node_modules/summernote/dist/summernote.js"></script>


          <?php if (isset($loc) && $loc == "dashboard") { ?>
        <script src="../admin/js/dashboard/index.js"></script>
        <!-- Filtros globales compartidos: debe cargarse antes de custom_dashboard.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/custom_dashboard.js"></script>
        <!--<script src="../admin/js/form/form-elements.js"></script>-->
    <?php } ?>

    <?php if (isset($loc) && $loc == "sales") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de dashboardSales.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <script src="../admin/js/dashboardSales.js"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "partssalesperformance") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de partSalesPerformance.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../node_modules/datatables/media/js/jquery.dataTables.js"></script>
        <script src="../admin/js/partSalesPerformance.js?v=<?php echo filemtime(__DIR__ . '/../js/partSalesPerformance.js'); ?>"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "unitsoperation") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de unitsoperation.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/unitsoperation.js?v=<?php echo filemtime(__DIR__ . '/../js/unitsoperation.js'); ?>"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "informationstatus") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de informationstatus.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/informationstatus.js?v=<?php echo filemtime(__DIR__ . '/../js/informationstatus.js'); ?>"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "indexreport") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de indexReport.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/indexReport.js?v=<?php echo filemtime(__DIR__ . '/../js/indexReport.js'); ?>"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "partssalesbychannel") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de partsSalesByChannel.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/partsSalesByChannel.js?v=<?php echo filemtime(__DIR__ . '/../js/partsSalesByChannel.js'); ?>"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "workshopreport") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de workshopReport.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/workshopReport.js?v=<?php echo filemtime(__DIR__ . '/../js/workshopReport.js'); ?>"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "inventoryreport") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de inventoryReport.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/inventoryReport.js?v=<?php echo filemtime(__DIR__ . '/../js/inventoryReport.js'); ?>"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "rankingreport") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de rankingReport.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/rankingReport.js?v=<?php echo filemtime(__DIR__ . '/../js/rankingReport.js'); ?>"></script>
    <?php } ?>

    <?php if (isset($loc) && $loc == "fillratebo") { ?>
        <!-- Filtros globales compartidos: debe cargarse antes de fillRateBO.js -->
        <script src="../admin/js/global_filters.js"></script>
        <script src="../admin/js/fillRateBO.js?v=<?php echo filemtime(__DIR__ . '/../js/fillRateBO.js'); ?>"></script>
    <?php } ?>