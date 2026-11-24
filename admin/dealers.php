<?php
$loc = "dealer";
include_once '../admin/src/seguridad.php';
@session_start();
include_once("../controllers/dealers.php");

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
                <li class="breadcrumb-item active">Dealers</li>
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
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Dealer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ModalBody" style="text-align: center;">
                    <form id="frmReg" class="form-horizontal" role="form" autocomplete="off">
                        <input type="hidden" name="registroid" id="registroid">

                        <!-- dealer -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="dealer">Dealer</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-building"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control" id="dealer"
                                        name="dealer" placeholder="Dealer">
                                </div>
                            </div>
                        </div>

                        <!-- Intermediario -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="tc">TC</label>
                            <div class="col-md-9">
                                <select style="width: 100%;" class="form-control" id="tc" name="tc" required>
                                    <option value=""></option>
                                    <?php foreach ($listadoIntermediario as $item) {
                                    ?>
                                    <option title="<?php echo ($item['intermediario']); ?>"
                                        value="<?php echo ($item['intermediarioid']); ?>">
                                        <?php echo ($item['intermediario']); ?>
                                    </option>
                                    <?php
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <!-- siglaorden -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left"
                                for="siglaorden">Initials</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-file-text-o"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control" id="siglaorden"
                                        name="siglaorden" placeholder="Initials">
                                </div>
                            </div>
                        </div>

                        <!-- customercode -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="customercode">Customer
                                Code</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-hashtag"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" pattern="^\d{4,5}$" inputmode="numeric"
                                        type="text" class="form-control" id="customercode" name="customercode"
                                        placeholder="Customer Code">
                                </div>
                            </div>
                        </div>

                        <!-- fromdata -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="fromdata">From
                                Data</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-info"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control" id="fromdata"
                                        name="fromdata" placeholder="From Data">
                                </div>
                            </div>
                        </div>

                        <!-- todata -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="todata">To Data</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-info"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control" id="todata"
                                        name="todata" placeholder="To Data">
                                </div>
                            </div>
                        </div>

                        <!-- paymentterm -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="paymentterm">Payment
                                Term</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-money"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control" id="paymentterm"
                                        name="paymentterm" placeholder="Payment Term">
                                </div>
                            </div>
                        </div>

                        <!-- personincharge -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="personincharge">Person
                                In Charge</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-user"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control"
                                        id="personincharge" name="personincharge" placeholder="Person In Charge">
                                </div>
                            </div>
                        </div>

                        <!-- phone -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="phone">Phone</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-phone"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control" id="phone"
                                        name="phone" placeholder="Phone">
                                </div>
                            </div>
                        </div>

                        <!-- country -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="country">Country</label>
                            <div class="col-md-9">
                                <select style="width: 100%;" class="form-control" id="country" name="country" required>
                                    <option value=""></option>
                                    <?php foreach ($listadoPaises as $country) {
                                    ?>
                                    <option title="<?php echo ($country['bandera']); ?>"
                                        value="<?php echo ($country['paisid']); ?>"><?php echo ($country['pais']); ?>
                                    </option>
                                    <?php
                                    } ?>
                                </select>
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