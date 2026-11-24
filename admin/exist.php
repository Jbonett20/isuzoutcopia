<?php
$loc = "exist";
include_once '../admin/src/seguridad.php';
@session_start();
include_once("../controllers/exist.php");
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once("src/header.php"); ?>
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
    <li class="breadcrumb-item active">Existing Orders</li>
</ol>
<h1 class="page-title">Existing orders
    <small>
        <small>in the system</small>
    </small>
</h1>
<div class="row">   
    <div class="col-lg-12">
        <section class="widget">
            <header>
                <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
                    CONFIG FILTERS (<?php if(isset($_SESSION['DatLoc']['dealer'])){ ?> <img src = "../admin/img/country/<?php echo($_SESSION['DatLoc']['bandera']); ?>" style="width:20px;" alt="<?php echo($_SESSION['DatLoc']['pais']); ?>"/> | <?php echo(strtoupper($_SESSION['DatLoc']['dealer'])); ?><?php }else{?> <img src = "../admin/img/country/worldsa.svg" style="width:20px;" alt="All World"/> | GENERAL ADMIN <?php }?>)
                </h6>
            </header>
            <div class="widget-body">
                 <input type="hidden"  id="ctrlR" value="<?php echo userHasRole('1') ? '1' : '0'; ?>">
                <form class="form-vertical" role="form">
                    <div id="CompletoDesactivar" style="z-index: 1000;width: 100%;height: 130px;position: absolute;"></div>
                    <div class="row">
                        <div class="col-lg-3">
                            <label for="statusorderhome" class="col-md-12" style="color:black;">Order Status:</label>
                            <div class="col-md-12 pr-0">
                                <select id="statusorderhome" data-placeholder="Select type of order" class="select2 form-control" tabindex="-1" name="statusorderhome">
                                    <option value="0">ALL STATUS</option>
                                    <?php foreach($listadoEstados as $statusorder){ ?>
                                        <option value="<?php echo($statusorder['estadoordenid']); ?>" <?php if(isset($_GET['status'])&&$_GET['status']==$statusorder['estadoordenid']){?>selected="selected"<?php }?> ><?php echo($statusorder['estadoorden']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label for="prodmonthhome" class="col-md-12" style="color:black;">Production Month:
                            <?php 
                            $entro = 0;
                            $mesAnt = "";
                            $momento    = new DateTime(date('Y-m-d h:i:s'));
                            foreach($listadoMeses as $month){ 
                                $inicio     = new DateTime($month['inicia']);
                                if( $entro==0 && $momento>$inicio ){
                                    $mesAnt     = $month['mes'];
                                }
                                if($momento<=$inicio && $entro == 0){
                                    $entro++;
                                    $Actual = $month['mes'];
                                    if($momento>$inicio){
                                        $mesAnt     = $month['mes'];
                                    }
                                }
                            }?></label>
                            <div class="col-md-12 pr-0">
                                <select id="prodmonthhome" data-placeholder="All Dates" class="select2 form-control" tabindex="-1" name="prodmonthhome" readonly>
                                    <option value="0">ALL DATES</option>
                                    <?php foreach($listadoMeses as $month){ ?>
                                        <option value="<?php echo($month['calendarioid']); ?>" <?php if(isset($_GET['month'])&&$_GET['month']==$month['calendarioid']){?>selected="selected"<?php }?> ><?php echo($month['mes']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label for="dealerhome" class="col-md-12" style="color:black;">Dealer:</label>
                            <div class="col-md-12 pr-0">
                                <?php if(isset($_GET['typeorderid']) && $_GET['typeorderid']>0){ ?>
                                    <input type="hidden" name="typeorderidsel" id="typeorderidsel" value="<?php echo($_GET['typeorderid']); ?>">
                                <?php }?>
                                <?php if(isset($_GET['portid']) && $_GET['portid']>0){ ?>
                                    <input type="hidden" name="portidsel" id="portidsel" value="<?php echo($_GET['portid']); ?>">
                                <?php }?>
                                <select id="dealerhome" data-placeholder="All Dealers" class="select2 form-control" tabindex="-1" name="dealerhome">
                                    <?php if(userHasRole('1')){?>    
                                        <option title="worldsa.svg" value="0">ALL DEALERS</option>
                                    <?php } ?>
                                    <?php foreach($listadoDealers as $dealer){ 
                                        if(userHasRole('1')){?>
                                        <option title="<?php echo($dealer['bandera']); ?>" value="<?php echo($dealer['dealerid']); ?>" <?php if(isset($_GET['dealerid'])&&$_GET['dealerid']==$dealer['dealerid']){?>selected="selected"<?php }?> ><?php echo($dealer['dealer']); ?></option>
                                        <?php }else{ 
                                            if($_SESSION['UsrData']['dealerid']==$dealer['dealerid']) { ?>
                                            <option title="<?php echo($dealer['bandera']); ?>" value="<?php echo($dealer['dealerid']); ?>" ><?php echo($dealer['dealer']); ?></option>                                    
                                    <?php } } }?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label for="typeorderhome" class="col-md-12" style="color:black;">Type of Order:</label>
                            <div class="col-md-12 pr-0">
                                <select id="typeorderhome" data-placeholder="Select type of order" class="select2 form-control" tabindex="-1" name="typeorderhome">
                                    <option value="0">ALL TYPES</option>
                                    <?php foreach($listadoTiposOrdenes as $typeorder){ ?>
                                        <option value="<?php echo($typeorder['tipoordenid']); ?>"><?php echo($typeorder['tipoorden']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3" style="padding-top: 15px;">
                            <label for="porthome" class="col-md-12" style="color:black;">Port of Discharge:</label>
                            <div class="col-md-12 pr-0">
                                <select id="porthome" data-placeholder="Select port" class="select2 form-control" tabindex="-1" name="porthome">
                                    <option value="0">ALL PORTS</option>
                                    <?php foreach($listadoPuertos as $ports){ ?>
                                        <option value="<?php echo($ports['puertoid']); ?>"><?php echo($ports['puerto']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3" style="padding-top: 15px;">
                            <label for="countryhome" class="col-md-12" style="color:black;">Country:</label>
                            <div class="col-md-12 pr-0">
                                <input type="text" id="countryhome" class="form-control" placeholder="" name="countryhome" disabled>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
    <div class="col-lg-12" id="TabSearchOrder">
        
    </div>
</div>

            <footer class="content-footer hidden-print">
                <?php require_once("src/footer.php"); ?>
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
    <?php require_once("src/scripts.php"); ?>
</body>
</html>