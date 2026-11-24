<?php
$loc = "user";
include_once '../admin/src/seguridad.php';
@session_start();
include_once('../config/init_db.php');
include_once("../controllers/users.php");

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
                <li class="breadcrumb-item active">Users</li>
            </ol>
            <div class="row">

                <div class="col-lg-12" id="TabUsersItems">

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
                    <h5 class="modal-title" id="exampleModalLongTitle">User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ModalBody" style="text-align: center;">
                    <form id="frmReg" class="form-horizontal" role="form" autocomplete="off">
                        <input type="hidden" name="registroid" id="registroid">

                        <!-- Names -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="names">Names</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-user"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control" id="names"
                                        name="names" placeholder="Names">
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="email">Email</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-envelope"></i>
                                        </span>
                                    </span>
                                    <input required id="email" name="email" autocomplete="off" type="email"
                                        class="form-control" placeholder="Email">
                                </div>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left"
                                for="password-field">Password</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-lock"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="password" class="form-control"
                                        name="password" id="password-field" placeholder="Password">
                                    <span class="input-group-append">
                                        <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                                            <i class="fa fa-eye" id="toggleIcon"></i>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Roles (Multiple) -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="roles">Roles</label>
                            <div class="col-md-9">
                                <select style="width: 100%;" class="form-control" id="roles" name="roles[]" multiple="multiple" required>
                                    <?php
                                    $allowedRoleIds = array_column($listadoRoles, 'rolid');
                                    foreach ($listadoRoles as $rol) {
                                        if (in_array((string)$rol['rolid'], array_map('strval', $allowedRoleIds), true)) { ?>
                                    <option title="<?php echo ($rol['rol']); ?>" value="<?php echo ($rol['rolid']); ?>">
                                        <?php echo ($rol['rol']); ?>
                                    </option>
                                    <?php }
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Job Tittle -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for="cargodealer">Job
                                Tittle</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-briefcase"></i>
                                        </span>
                                    </span>
                                    <input required autocomplete="off" type="text" class="form-control"
                                        name="cargodealer" id="cargodealer" placeholder="Job Tittle">
                                </div>
                            </div>
                        </div>

                        <!-- Dealer -->
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-white text-md-left" for=" dealer">Dealer</label>
                            <div class="col-md-9">
                                <select style="width: 100%;" class="form-control" id="dealer" name="dealer" required>
                                    <option value=""></option>
                                    <?php foreach ($listadoDealers as $dealer) {
                                        if (userHasAnyRole(['1', '4'])) { ?>
                                    <option title="<?php echo ($dealer['bandera']); ?>"
                                        value="<?php echo ($dealer['dealerid']); ?>">
                                        <?php echo ($dealer['dealer']); ?>
                                    </option>
                                    <?php } else {
                                            if ($_SESSION['UsrData']['dealerid'] == $dealer['dealerid']) { ?>
                                    <option selected title="<?php echo ($dealer['bandera']); ?>"
                                        value="<?php echo ($dealer['dealerid']); ?>">
                                        <?php echo ($dealer['dealer']); ?>
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