<?php
    $loc = "exist";
    include_once '../admin/src/seguridad.php';
    @session_start();
    include_once "../controllers/exist.php";
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once "src/header.php"; ?>
</head>

<body class="">
    <?php require_once "src/navbar.php"; ?>
<!--
  Main sidebar seen on the left. may be static or collapsing depending on selected state.

    * Collapsing - navigation automatically collapse when mouse leaves it and expand when enters.
    * Static - stays always open.
-->
<nav id="sidebar" class="sidebar" role="navigation">
    <!-- need this .js class to initiate slimscroll -->
    <div class="js-sidebar-content">
        <?php require_once "src/logo.php"; ?>
        <!-- seems like lots of recent admin template have this feature of user info in the sidebar.
             looks good, so adding it and enhancing with notifications -->
        <?php require_once "src/sidebar.php"; ?>
    </div>
</nav>


    <div class="content-wrap">
        <!-- main page content. the place to put widgets in. usually consists of .row > .col-lg-* > .widget.  -->
        <main id="content" class="content" role="main">
            <!-- Page content -->
<ol class="breadcrumb">
    <li class="breadcrumb-item">You are in</li>
    <?php if (isset($_GET['origen']) && $_GET['origen'] == "home") {?>
        <li class="breadcrumb-item"><a href="home">Home</a></li>
    <?php } else {?>
        <li class="breadcrumb-item"><a href="exist?month=<?php echo($_GET['month']); ?>&dealerid=<?php echo($_GET['dealerid']); ?>&typeorderid=<?php echo($_GET['typeorderid']); ?>&portid=<?php echo($_GET['portid']); ?>&status=<?php echo($_GET['status']); ?>">Exist Order</a></li>
    <?php }?>
    <li class="breadcrumb-item active">Edit Order</li>
</ol>
<h1 class="page-title">Exist Order
    <small>
        <small>in the period</small>
    </small>
</h1>
<div class="row">
    <div class="col-lg-12">
        <section class="widget">
            <header>
                <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
                    CONFIG FILTERS (<?php if (isset($_SESSION['DatLoc']['dealer'])) {?> | <img src = "../admin/img/country/<?php echo($_SESSION['DatLoc']['bandera']); ?>" style="width:20px;" alt="<?php echo($_SESSION['DatLoc']['pais']); ?>"/> <?php echo(strtoupper($_SESSION['DatLoc']['dealer'])); ?><?php } else {?> <img src = "../admin/img/country/worldsa.svg" style="width:20px;" alt="All World"/> | GENERAL ADMIN <?php }?>)
                </h6>
            </header>
            <div class="widget-body">
                <input type="hidden"  id="ctrlR" value="<?php echo userHasRole('1') ? '1' : '0'; ?>">
                <form class="form-vertical" role="form">
                    <div id="CompletoDesactivar" style="z-index: 1000;width: 100%;height: 130px;position: absolute;"></div>
                    <div class="row">
                        <div class="col-lg-3">
                            <label for="orderhome" class="col-md-12" style="color:black;">PO #:</label>
                            <div class="col-md-12 pr-0">
                                <input type="text" id="orderhome" name="orderhome" class="form-control" placeholder="Order code here" value="<?php if (isset($DatosOrden['codigo'])) {echo($DatosOrden['codigo']);}?>" disabled>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label for="prodmonthhome" class="col-md-12" style="color:black;">Production Month:
                            <?php
                                $entro   = 0;
                                $mesAnt  = "";
                                $momento = new DateTime(date('Y-m-d h:i:s'));
                                foreach ($listadoMeses as $month) {
                                    $inicio = new DateTime($month['inicia']);
                                    if ($entro == 0 && $momento > $inicio) {
                                        $mesAnt = $month['mes'];
                                    }
                                    if ($momento <= $inicio && $entro == 0) {
                                        $entro++;
                                        $Actual = $month['mes'];
                                        if ($momento > $inicio) {
                                            $mesAnt = $month['mes'];
                                        }
                                }
                            }?></label>
                            <div class="col-md-12 pr-0">
                                <select id="prodmonthhome" data-placeholder="All Dates" class="select2 form-control" tabindex="-1" name="prodmonthhome" disabled>
                                    <?php foreach ($listadoMeses as $month) {?>
                                        <option value="<?php echo($month['calendarioid']); ?>" <?php if (isset($DatosOrden['calendarioid']) && ($month['calendarioid'] == $DatosOrden['calendarioid'])) {?>selected=selected<?php }?>><?php echo($month['mes']); ?></option>
                                    <?php }?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label for="dealerhome" class="col-md-12" style="color:black;">Dealer:</label>
                            <div class="col-md-12 pr-0">
                                <select id="dealerhome" data-placeholder="All Dealers" class="select2 form-control" tabindex="-1" name="dealerhome" disabled>
                                    <?php foreach ($listadoDealers as $dealer) {
                                        if (userHasRole('1')) {?>
                                        <option title="<?php echo($dealer['bandera']); ?>" value="<?php echo($dealer['dealerid']); ?>" <?php if (isset($DatosOrden['dealerid']) && ($dealer['dealerid'] == $DatosOrden['dealerid'])) {?>selected=selected<?php }?> ><?php echo($dealer['dealer']); ?></option>
                                        <?php } else {
                                                if ($_SESSION['UsrData']['dealerid'] == $dealer['dealerid']) {?>
                                            <option title="<?php echo($dealer['bandera']); ?>" value="<?php echo($dealer['dealerid']); ?>" ><?php echo($dealer['dealer']); ?></option>
                                    <?php }
                                            }
                                    }?>
                                </select>
                            </div>
                        </div>
                        <?php if (isset($DatosOrden['tipoordenid']) && $DatosOrden['tipoordenid'] > 0) {?>
                            <input type="hidden" name="typeorderidsel" id="typeorderidsel" value="<?php echo($DatosOrden['tipoordenid']); ?>">
                        <?php }?>
                        <?php if (isset($DatosOrden['puertoid']) && $DatosOrden['puertoid'] > 0) {?>
                            <input type="hidden" name="portidsel" id="portidsel" value="<?php echo($DatosOrden['puertoid']); ?>">
                        <?php }?>
                        <?php if (isset($DatosOrden['ordenid']) && $DatosOrden['ordenid'] > 0) {?>
                            <input type="hidden" name="ordenidsel" id="ordenidsel" value="<?php echo($DatosOrden['ordenid']); ?>">
                        <?php }?>
                        <div class="col-lg-3">
                            <label for="typeorderhome" class="col-md-12" style="color:black;">Type of Order:</label>
                            <div class="col-md-12 pr-0">
                                <select id="typeorderhome" data-placeholder="Select type of order" class="select2 form-control" tabindex="-1" name="typeorderhome" disabled>
                                    <?php foreach ($listadoTiposOrdenes as $typeorder) {?>
                                        <option value="<?php echo($typeorder['tipoordenid']); ?>"><?php echo($typeorder['tipoorden']); ?></option>
                                    <?php }?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3" style="padding-top: 15px;">
                            <label for="porthome" class="col-md-12" style="color:black;">Port of Discharge:</label>
                            <div class="col-md-12 pr-0">
                                <select id="porthome" data-placeholder="Select port" class="select2 form-control" tabindex="-1" name="porthome" disabled>
                                    <?php foreach ($listadoPuertos as $ports) {?>
                                        <option value="<?php echo($ports['puertoid']); ?>"><?php echo($ports['puerto']); ?></option>
                                    <?php }?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3" style="padding-top: 15px;">
                            <label for="countryhome" class="col-md-12" style="color:black;">Country:</label>
                            <div class="col-md-12 pr-0">
                                <input type="text" id="countryhome" class="form-control" placeholder="Write the country here" name="countryhome" disabled>
                            </div>
                        </div>
                        <div class="col-lg-3" style="padding-top: 15px;">
                            <label for="countryhome" class="col-md-12" style="color:black;">Total Units:</label>
                            <div class="col-md-12 pr-0" style="text-align: center;">
                                <span id="CantNewOrder" style="font-weight: bold; font-size:14px;"><?php if (isset($DatosOrden['unidades'])) {echo($DatosOrden['unidades']);}?></span>
                                <i class="fa fa-truck" style="color: #F40000 !important"></i>
                            </div>
                        </div>
                        <div class="col-lg-3" style="padding-top: 15px;">
                            <label for="countryhome" class="col-md-12" style="color:black;">Total Order:</label>
                            <div class="col-md-12 pr-0" style="text-align: center;">
                                <span id="ValNewOrder" style="font-weight: bold; font-size:14px;">$ <?php if (isset($DatosOrden['total'])) {echo(number_format($DatosOrden['total'], 0));}?></span>
                                <i class="fa fa-shopping-cart" style="color: #F40000 !important"></i>
                            </div>
                        </div>
                        <div class="col-lg-3" style="padding-top: 15px;">
                            <label for="countryhome" class="col-md-12" style="color:black;">Actual Status Order:</label>
                            <div class="col-md-12 pr-0" style="text-align: center; color:<?php echo($DatosOrden['color']); ?>;font-weight: bold;font-size: 20px;">
                                <?php echo($DatosOrden['estadoorden']); ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
    <div class="col-lg-12">
        <section class="widget" style="padding: 10px 0px;margin-bottom: 5px !important;color:#000000;">
            <div class="widget-body">
                <div class="col-lg-12" style="padding-left: 10px;padding-right: 10px;">
                    <div class="clearfix">
                        <ul class="nav nav-tabs float-left" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="home-tab" data-toggle="tab" href="#orderdetailtab" role="tab" aria-controls="basic" aria-expanded="true">
                                    <i class="fa fa-book"></i> ORDER DETAIL
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="profile-tab" data-toggle="tab" href="#processtab" role="tab" aria-controls="assumtion" aria-expanded="false">
                                    <i class="fa fa-comments"></i> PROCESS
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content mb-lg" id="myTabContent" style="border-bottom-left-radius: .625rem;border-bottom-right-radius: .625rem;border-top-right-radius: .625rem; margin-bottom: 0px;">
                        <div role="tabpanel" class="tab-pane active in clearfix" id="orderdetailtab" aria-labelledby="basic-tab" aria-expanded="true" style="padding: 0px;padding-bottom: 12px;">
                            <div class="col-lg-12" id="TabDetailOrder"></div>
                        </div>
                        <div class="tab-pane" id="processtab" role="tabpanel" aria-labelledby="assumtion-tab" aria-expanded="false" style="padding: 0px;padding-bottom: 12px;padding-top: 20px;">
                            <div class="row">
                                <div class="col-md-1"></div>
                                <div class="col-md-4">
                                        <?php if (userHasRole('1')) {?>
                                            <div class="form-group">
                                                <label for="changestatus" class="col-md-12" style="color:black;">New Status:</label>
                                                <div class="col-md-12 pr-0">
                                                    <select id="changestatus" data-placeholder="Select type of order" class="select2 form-control" tabindex="-1" name="changestatus" style="width: 100%;">
                                                        <?php foreach ($listadoEstados as $statusorder) {if ($statusorder['estadoordenid'] != "3") {?>
                                                            <option value="<?php echo($statusorder['estadoordenid']); ?>" <?php if (isset($DatosOrden['estadoordenid']) && $DatosOrden['estadoordenid'] == $statusorder['estadoordenid']) {?>selected="selected"<?php }?> ><?php echo($statusorder['estadoorden']); ?></option>
                                                        <?php }}?>
                                                    </select>
                                                </div>
                                            </div>
                                        <?php } else {?>
                                            <input type="hidden" name="changestatus" id="changestatus" value="<?php echo($DatosOrden['estadoordenid']); ?>">
                                        <?php }?>
                                        <div class="form-group">
                                            <label for="changestatus" class="col-md-12" style="color:black;">Push Order Note:</label>
                                            <div class="col-md-12 pr-0">
                                                <textarea name="changenote" id="changenote" rows="3" class="autogrow form-control transition-height" id="elastic-textarea" placeholder="Enter order note.." style="overflow: hidden; overflow-wrap: break-word; resize: none; height: 76px;"></textarea>
                                            </div>
                                        </div>
                                        <?php if (userHasRole('1')) {?>
                                            <div class="form-group">
                                                <label for="changevisible" class="col-md-6" style="color:black;">Visible to Dealer:</label>
                                                <div class="col-md-6 pr-0">
                                                    <input class="form-check-label" id="changevisible" name="changevisible" type="checkbox">
                                                </div>
                                            </div>
                                        <?php } else {?>
                                            <input type="hidden" name="changevisible" id="changevisible" value="1">
                                        <?php }?>
                                        <div class="clearfix">
                                            <div class="btn-toolbar float-right">
                                                <button type="button" id="compose-send-button" class="btn btn-success" onclick="EnviarNota();">SEND</button>
                                            </div>
                                        </div>
                                </div>
                                <div class="col-md-6" id="MensajesActuales">
                                </div>
                                <div class="col-md-1"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

            <footer class="content-footer hidden-print">
                <?php require_once "src/footer.php"; ?>
            </footer>
        </main>
    </div>
    <div class="modal fade" id="ConfirmModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ModalBody" style="text-align: center;">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                    <button id="btnAccion" type="button" class="btn btn-success ml-3" onclick="GuardarBorrador();">Save</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ConfirmModalCenterDelete" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitledelete" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Delete Order</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ModalBody" style="text-align: center;">
                    Are you sure you want to delete this order? This process has no return.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                    <button id="btnAccion" type="button" class="btn btn-success ml-3" onclick="BorrarOrden();">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <?php require_once "src/scripts.php"; ?>
</body>
</html>