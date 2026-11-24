<?php
$loc = "list";
include_once '../admin/src/seguridad.php';
@session_start();
include_once("../controllers/new.php");
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
    <li class="breadcrumb-item active">Order List</li>
</ol>
<h1 class="page-title">Price List
    <small>
        <small>items</small>
    </small>
</h1>
<div class="row">
    <div class="col-lg-12">
        <section class="widget">
            <header>
                <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
                    GENERAL FILTERS (<?php if(isset($_SESSION['DatLoc']['dealer'])){ ?> | <img src = "../admin/img/country/<?php echo($_SESSION['DatLoc']['bandera']); ?>" style="width:20px;" alt="<?php echo($_SESSION['DatLoc']['pais']); ?>"/> <?php echo(strtoupper($_SESSION['DatLoc']['dealer'])); ?><?php }else{?> <img src = "../admin/img/country/worldsa.svg" style="width:20px;" alt="All World"/> | GENERAL ADMIN <?php }?>)
                </h6>
            </header>
            <div class="widget-body">
                <form class="form-vertical" role="form">
                    <div class="row">
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
                                <select id="prodmonthhome" data-placeholder="All Dates" class="select2 form-control" tabindex="-1" name="prodmonthhome">
                                    <?php foreach($listadoMeses as $month){ ?>
                                        <option value="<?php echo($month['calendarioid']); ?>" <?php if($mesAnt==$month['mes']){ ?>selected=selected<?php }?>><?php echo($month['mes']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label for="dealerhome" class="col-md-12" style="color:black;">Dealer:</label>
                            <div class="col-md-12 pr-0">
                                <select id="dealerhome" data-placeholder="All Dealers" class="select2 form-control" tabindex="-1" name="dealerhome">
                                    <?php foreach($listadoDealers as $dealer){ 
                                        if(userHasRole('1')){?>
                                        <option title="<?php echo($dealer['bandera']); ?>" value="<?php echo($dealer['dealerid']); ?>"><?php echo($dealer['dealer']); ?></option>
                                        <?php }else{ 
                                            if($_SESSION['UsrData']['dealerid']==$dealer['dealerid']) { ?>
                                            <option title="<?php echo($dealer['bandera']); ?>" value="<?php echo($dealer['dealerid']); ?>" ><?php echo($dealer['dealer']); ?></option>                                    
                                    <?php } } }?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </section>
    </div>
    <div class="col-lg-12" id="TabListItems">
        
    </div>
</div>

            <footer class="content-footer hidden-print">
                <?php require_once("src/footer.php"); ?>
            </footer>
        </main>
    </div>
    <?php require_once("src/scripts.php"); ?>
</body>
</html>