<?php
$loc = "master";
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
    <li class="breadcrumb-item active">Master Report</li>
</ol>
<h1 class="page-title">Master List
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
                <form id="ExportMasterReport" class="form-vertical" role="form" action="exportMasterR" method="post">
                    <div class="row">
                        <div class="col-lg-1">
                            <label for="txtstartdate" class="col-md-12" style="color:black;">Start Date:</label>
                        </div>
                        <div class="col-lg-2">
                            <div class="col-md-12 pr-0">
                                <div role="group" class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="la la-calendar"></i>
                                        </div>
                                    </div>
                                    <input id="txtstartdate" name="txtstartdate" type="date" required placeholder="Start Date" class="form-control input-transparent pl-3" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <label for="txtenddate" class="col-md-12" style="color:black;">End Date:</label>
                        </div>
                        <div class="col-lg-2">
                            <div class="col-md-12 pr-0">
                                <div role="group" class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="la la-calendar"></i>
                                        </div>
                                    </div>
                                    <input id="txtenddate" name="txtenddate" type="date" required placeholder="End Date" class="form-control input-transparent pl-3" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <button type="submit" form="ExportMasterReport" class="btn btn-warning btn-sm mb-xs"><i class="fa fa-file-excel-o"></i> Export Master Report</button>
                        </div>
                        <div class="col-lg-5">
                        </div>
                    </div>
                </form>

            </div>
        </section>
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