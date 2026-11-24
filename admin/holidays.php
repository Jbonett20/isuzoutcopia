<?php
$loc = "holiday";
include_once '../admin/src/seguridad.php';
@session_start();
include_once("../controllers/holidays.php");

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
                <li class="breadcrumb-item active">Holidays</li>
            </ol>
            <div class="row">

                <div class="col-lg-12" id="TabItems">

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
                    <h5 class="modal-title" id="exampleModalLongTitle">Holiday</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ModalBody" style="text-align: center;">
                    <form id="frmReg" class="form-horizontal" role="form" autocomplete="off">
                        <input type="hidden" name="registroid" id="registroid">

                        <!-- Holiday -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="holiday">Holiday</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar-check-o"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control" id="holiday"
                                        name="holiday" placeholder="Holiday">
                                </div>
                            </div>
                        </div>

                        <!-- date -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="fecha">Date</label>
                            <div class="col-md-9">
                                <div class="input-group date" id="fechaPicker" data-target-input="nearest">
                                    <div class="input-group-prepend" data-target="#fechaPicker"
                                        data-toggle="datetimepicker">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                    </div>
                                    <input required autocomplete="off" type="text"
                                        class="form-control datetimepicker-input" data-toggle="datetimepicker"
                                        data-target="#fechaPicker" id="fecha" name="fecha" placeholder="Date" />
                                </div>
                            </div>
                        </div>

                        <!-- Country -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for=" dealer">Country</label>
                            <div class="col-md-9">
                                <select style="width: 100%;" class="form-control" id="country" name="country" required>
                                    <option value=""></option>
                                    <?php foreach ($listadoPaises as $country) {
                                        if (userHasAnyRole(['1', '4'])) { ?>
                                    <option title="<?php echo ($country['bandera']); ?>"
                                        value="<?php echo ($country['paisid']); ?>">
                                        <?php echo ($country['pais']); ?>
                                    </option>
                                    <?php } else {
                                            if ($_SESSION['UsrData']['paisid'] == $country['paisid']) { ?>
                                    <option selected title="<?php echo ($country['bandera']); ?>"
                                        value="<?php echo ($country['paisid']); ?>">
                                        <?php echo ($country['pais']); ?>
                                    </option>
                                    <?php }
                                        }
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <?php
                        if (userHasAnyRole(['1', '4'])) { ?>
                        <!-- Status -->
                        <div class="form-group row" style="display: none;" id="contentEstado">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="status">Status</label>
                            <div class="col-md-9">
                                <select style="width: 100%;" class="form-control" id="estado" name="estado" required>
                                    <option value=""></option>
                                    <?php foreach ($listadoEstados as $estado) { ?>
                                    <option title="<?php echo ($estado['estado']); ?>"
                                        value="<?php echo ($estado['estadoid']); ?>">
                                        <?php echo ($estado['estado']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <?php } ?>
                    </form>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                    <?php if (userHasAnyRole(['1', '4'])) { ?>
                    <button form="frmReg" type="submit" class="btn btn-success ml-3">Save</button>
                    <?php }else{?>
                    <button form="frmReg" type="submit" class="btn btn-success ml-3">Request</button>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php require_once("src/scripts.php"); ?>
</body>

</html>