<?php
$loc = "calendar";
include_once '../admin/src/seguridad.php';
@session_start();
include_once("../controllers/calendar.php");

?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once("src/header.php"); ?>
    <style>
    .select2.select2-container.select2-container--default .select2-selection.select2-selection--single .select2-selection__rendered,
    .select2.select2-container.select2-container--default .select2-selection.select2-selection--multiple .select2-selection__rendered {

        color: #ffffff;
        text-align: left;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #ffffff;
    }

    .form-control {
        color: #ebdfdf;
    }

    .fc-today {
        background-color: #e1e2e8 !important;
    }
    </style>
</head>

<body class="">
    <?php require_once("src/navbar.php"); ?>

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
                <li class="breadcrumb-item active">Ordering Calendar</li>
            </ol>
            <div class="row">

                <div class="col-lg-5" id="TabItems">

                </div>

                <div class="col-lg-7">
                    <section class="widget">
                        <div class="widget-body" style="color: #000000;">
                            <div id="calendar"></div>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="content-footer hidden-print">
                <?php require_once("src/footer.php"); ?>
            </footer>
        </main>
    </div>
    <div class="modal fade" id="modalAdd" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Calendar</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ModalBody" style="text-align: center;">
                    <form id="frmReg" class="form-horizontal" role="form" autocomplete="off">
                        <input type="hidden" name="registroid" id="registroid">

                        <!-- year  -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="year">Year</label>
                            <div class="col-md-9">
                                <div class="input-group date" id="yearPicker" data-target-input="nearest">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text"
                                        class="form-control datetimepicker-input" data-target="#yearPicker" id="year"
                                        name="year" placeholder="Year" />
                                </div>
                            </div>
                        </div>


                        <!-- Status -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="month">Month</label>
                            <div class="col-md-9">
                                <select style="width: 100%;" class="form-control" id="month" name="month" required>
                                    <option value=""></option>
                                    <option value="1">JAN</option>
                                    <option value="2">FEB</option>
                                    <option value="3">MAR</option>
                                    <option value="4">APR</option>
                                    <option value="5">MAY</option>
                                    <option value="6">JUN</option>
                                    <option value="7">JUL</option>
                                    <option value="8">AUG</option>
                                    <option value="9">SEP</option>
                                    <option value="10">OCT</option>
                                    <option value="11">NOV</option>
                                    <option value="12">DEC</option>
                                </select>
                            </div>
                        </div>

                        <!--  start date  -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="inicia">Open
                            </label>
                            <div class="col-md-9">
                                <div class="input-group date" id="startDate" data-target-input="nearest">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text"
                                        class="form-control datetimepicker-input" data-target="#startDate" id="inicia"
                                        name="inicia" placeholder="Start date" />
                                </div>
                            </div>
                        </div>

                        <!--  end date  -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="finaliza">Close
                            </label>
                            <div class="col-md-9">
                                <div class="input-group date" id="endDate" data-target-input="nearest">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text"
                                        class="form-control datetimepicker-input" data-target="#endDate" id="finaliza"
                                        name="finaliza" placeholder="End date" />
                                </div>
                            </div>
                        </div>


                        <!-- Status -->
                        <div class="form-group row" style="display: none;" id="contentEstado">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="status">Status</label>
                            <div class="col-md-9">
                                <select style="width: 100%;" class="form-control" id="estado" name="estado" required>
                                    <option value=""></option>
                                    <?php foreach ($listadoEstados as $estado) { ?>
                                    <option title="<?php echo ($estado['estado']); ?>"
                                        value="<?php echo ($estado['estadoid']); ?>"><?php echo ($estado['estado']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                    </form>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                    <button form="frmReg" type="submit" class="btn btn-success ml-3">Save</button>
                </div>
            </div>
        </div>
    </div>
    <?php require_once("src/scripts.php"); ?>
</body>

</html>