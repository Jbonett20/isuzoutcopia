<?php
    $loc = "role_permissions";

    include_once '../admin/src/seguridad.php';
    include_once "../controllers/role_permissions.php";
?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once "src/header.php"; ?>

    <style>
.toast-flatlogic{
    width: 320px !important;
    min-height: auto !important;
    height: auto !important;
    padding: 0 !important;
    background: #1f2240 !important;
    border: none !important;

}

.toast-flatlogic .toast-header{
    min-height: auto !important;
    height: auto !important;
    background: #1f2240 !important;
    border: none !important;
    color: #fff !important;
}
.toast-flatlogic.show{
    display: block !important;
}

.toast-flatlogic .toast-body{
    display: none !important;
    height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
}

.toast-content{
    display: flex;
    align-items: center;

}

.toast-icon{
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #52c41a;
    margin-right: 12px;
    flex-shrink: 0;
}

.toast-message{
    flex: 1;
    color: #ffffff;
    font-size: 13px;
    line-height: 1.3;
}

.toast-close{
    color: #ffffff !important;
    font-size: 18px;
    cursor: pointer;
    margin-left: 12px;

}
.close{
    color: #ffffff !important;
}

.toast-close:hover{
    opacity: 1;
}


        .custom-checkbox{
            background: rgba(192, 192, 192, 0.3) !important;
        }

       .permission-inline{
            position: relative;
            background: #f8fafc;
            border: 1px solid #e6ecf2;
            border-radius: 10px;
            padding: 10px 12px 10px 38px;
            transition: .15s ease;
            height: 58px;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        .permission-inline:hover{
            background: #f1f5f9;
            border-color: #d8e1ea;
        }

        .permission-inline .custom-control-label{
            width: 100%;
            cursor: pointer;
            font-size: 13px;
            line-height: 1.25;
            color: #2a2a2a;
            margin: 0;
            white-space: normal;
            word-break: break-word;
        }


        .permission-inline .custom-control-label::before,
        .permission-inline .custom-control-label::after{
            top: 50%;
            left: -24px;
            transform: translateY(-50%);
             box-shadow: none !important;
        }
         .permission-inline .custom-control-input:checked ~ .custom-control-label::before{
            background-color: #ff0604 !important;
            border-color: #ff0604 !important;
        }

        .permission-inline .custom-control-input:focus ~ .custom-control-label::before{
            box-shadow: 0 0 0 0.1rem rgba(255, 6, 4, 0.25) !important;
        }

        .permission-inline .custom-control-input:active ~ .custom-control-label::before{
            background-color: #ff0604 !important;
            border-color: #ff0604 !important;
        }

        @media (max-width: 768px){

            .permission-inline{
                min-height: auto;
            }

            .permission-inline .custom-control-label{
                font-size: 12px;
            }

        }

        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple{
            background-color: #f7f7f7;
            border: 1px solid #dde2e8;
            color: #2a2a2a;
            height: 30px;
            min-height: 30px;
            border-radius: 4px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered,
        .select2-container--default .select2-selection--multiple .select2-selection__rendered{
            color: #2a2a2a;
            line-height: 28px;
            font-size: 13px;
            padding-left: 10px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow{
            height: 30px;
        }

        .card{
            background: #ffffff !important;
            border: 1px solid #dde2e8;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
        }

        .card-header{
            background-color: #ffffff;
            border-bottom: 1px solid #dde2e8;
            color: #2a2a2a;
        }

        .card-body{
            background-color: #ffffff;
        }

        .nav-tabs{
            border-bottom: 1px solid #dde2e8;
        }

        .nav-tabs .nav-link{
            background-color: #f5f6f8;
            border: 1px solid #dde2e8;
            border-bottom-color: transparent;
            color: #2a2a2a;
            margin-right: 0.25rem;
            border-radius: 0.35rem 0.35rem 0 0;
        }

        .nav-tabs .nav-link.active,
        .nav-tabs .nav-link:hover{
            background-color: #ffffff;
            border-color: #dde2e8;
        }

        .tab-pane{
            background-color: #ffffff;
            border: 1px solid #dde2e8;
            border-top: none;
            padding: 1rem;
        }

        .btn-success{
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
        }

        .btn-success:hover,
        .btn-success:focus{
            background-color: #218838;
            border-color: #1e7e34;
        }



    </style>
</head>

<body class="">

    <?php require_once "src/navbar.php"; ?>

    <nav id="sidebar" class="sidebar" role="navigation">
        <div class="js-sidebar-content">
            <?php require_once "src/logo.php"; ?>
            <?php require_once "src/sidebar.php"; ?>
        </div>
    </nav>

    <div class="content-wrap">

        <main id="content" class="content" role="main">

            <ol class="breadcrumb">
                <li class="breadcrumb-item">You are in</li>
                <li class="breadcrumb-item active">Role Permissions</li>
            </ol>

            <?php if (! empty($message)) {?>

             <div aria-live="polite" aria-atomic="true">

                <div
                    id="rolePermissionsToast"
                    class="toast toast-flatlogic"
                    data-delay="4000"
                    role="alert"
                    aria-live="assertive"
                    aria-atomic="true"
                >

                    <div class="toast-header">

                        <span class="toast-icon"></span>

                        <div class="toast-message">
                            <?php echo htmlspecialchars($message); ?>
                        </div>

                        <button
                            type="button"
                            class="close"
                            data-dismiss="toast"
                            aria-label="Close"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>

                    </div>

                </div>

            </div>

                <script>

                    document.addEventListener('DOMContentLoaded', function () {

                        var toastEl = document.getElementById('rolePermissionsToast');

                        if (toastEl) {

                            $(toastEl).toast('show');

                            /*
                            |--------------------------------------------------------------------------
                            | Limpiar URL después de mostrar el toast
                            |--------------------------------------------------------------------------
                            */

                            const cleanUrl =
                                window.location.origin +
                                window.location.pathname +
                                '?rolid=<?php echo intval($selectedRoleId); ?>';

                            window.history.replaceState(
                                {},
                                document.title,
                                cleanUrl
                            );
                        }

                    });

                </script>

            <?php }?>

            <div class="row">

                <div class="col-lg-4">

                    <div class="card mb-4">

                        <div class="card-header">
                            <h5 class="card-title">Select Role</h5>
                            <p class="card-subtitle">
                                Choose a role to manage its permissions.
                            </p>
                        </div>

                        <div class="card-body">

                            <form method="get" action="role_permissions.php">

                                <div class="form-group">

                                    <label for="rolid">Role</label>

                                    <select
                                        class="select2 form-control"
                                        id="rolid"
                                        name="rolid"
                                        onchange="this.form.submit()"
                                    >
                                    <option value="all">SELECCIONE UN ROL</option>

                                        <?php foreach ($roles as $rol) {?>

                                            <option
                                                value="<?php echo intval($rol['rolid']); ?>"
                                                <?php echo($selectedRoleId === intval($rol['rolid']))
                                                            ? 'selected'
                                                        : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($rol['rol']); ?>
                                            </option>

                                        <?php }?>

                                    </select>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

                <div class="col-lg-8">

                    <div class="card mb-4">

                        <div class="card-header">
                            <h5 class="card-title">Permissions</h5>
                            <p class="card-subtitle">
                                Enable the menu permissions available for this role.
                            </p>
                        </div>

                        <div class="card-body">

                            <form
                                method="post"
                                action="role_permissions.php?rolid=<?php echo intval($selectedRoleId); ?>"
                            >

                                <input type="hidden" name="opc" value="guardarPermisos">
                                <input type="hidden" name="rolid" value="<?php echo intval($selectedRoleId); ?>">

                                <ul class="nav nav-tabs " role="tablist">

                                    <?php $first = true; ?>

                                    <?php foreach ($permissionsBySection as $section => $items) {?>

                                        <li class="nav-item">

                                            <a
                                                class="nav-link <?php echo $first ? 'active' : ''; ?>"
                                                data-toggle="tab"
                                                href="#pane-<?php echo htmlspecialchars($section); ?>"
                                            >
                                                <?php echo htmlspecialchars(
                                                        ucwords(str_replace('_', ' ', $section))
                                                ); ?>
                                            </a>

                                        </li>

                                        <?php $first = false; ?>

                                    <?php }?>

                                </ul>

                                <div class="tab-content mb-4">

                                    <?php $first = true; ?>

                                    <?php foreach ($permissionsBySection as $section => $items) {?>

                                        <div
                                            class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>"
                                            id="pane-<?php echo htmlspecialchars($section); ?>"
                                        >

                                            <div class="row">

                                            <?php
                                                $chunks = array_chunk($items, 4);

                                                    foreach ($chunks as $grupo) {
                                                    ?>

    <div class="col-xl-4 col-lg-6 mb-3">

        <div class="row" style="margin-right:-5px;">

            <?php foreach ($grupo as $permission) {?>

                <div class="col-6 mb-3 pr-1">

                    <div class="custom-control custom-checkbox permission-inline">

                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="perm_<?php echo intval($permission['permisoid']); ?>"
                            name="permisos[]"
                            value="<?php echo htmlspecialchars($permission['codigo']); ?>"
                            <?php echo in_array(
                                            $permission['codigo'],
                                            $selectedCodes,
                                            true
                                    ) ? 'checked' : ''; ?>
                        >

                        <label
                            class="custom-control-label"
                            for="perm_<?php echo intval($permission['permisoid']); ?>"
                        >
                            <?php echo htmlspecialchars($permission['nombre']); ?>
                        </label>

                    </div>

                </div>

            <?php }?>

        </div>

    </div>

<?php }?>

                                            </div>

                                        </div>

                                        <?php $first = false; ?>

                                    <?php }?>

                                </div>
                                <?php
                                    $propieddad = "disabled";
                                    if (isset($_GET['rolid']) && $_GET['rolid'] != "all") {
                                        $propieddad = "";
                                    }
                                ?>
                                <button type="submit" class="btn btn-success" <?php echo $propieddad; ?>>
                                    Save permissions
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

            <footer class="content-footer hidden-print">
                <?php require_once "src/footer.php"; ?>
            </footer>

        </main>

    </div>

    <?php require_once "src/scripts.php"; ?>

</body>
</html>