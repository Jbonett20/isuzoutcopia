<?php
$loc = "overview";
   
include_once '../admin/src/seguridad.php';
include_once("../controllers/home.php");
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
    <li class="breadcrumb-item active">Home</li>
</ol>
<h1 class="page-title">Orders
    <small>
        <small>in the period</small>
    </small>
</h1>
<div class="row">
    <div class="col-lg-12">
        <section class="widget">
            <div class="widget-body">
                <div class="row">

                    <div class="col-lg-3">
                        <label for="prodmonthhome" class="col-md-12" style="color:black;">
                            Production Month:
                        </label>

                        <?php
                        $entro = 0;
                        $mesAnt = "";
                        $momento = new DateTime(date('Y-m-d H:i:s'));

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
                        }
                        ?>

                        <div class="col-md-12 pr-0">
                            <select
                                id="prodmonthhome"
                                data-placeholder="All Dates"
                                class="select2 form-control"
                                tabindex="-1"
                                name="prodmonthhome"
                            >
                                <option value="0">
                                    ALL PRODUCTION MONTHS
                                </option>

                                <?php foreach ($listadoMeses as $month) { ?>

                                    <option
                                        value="<?php echo $month['calendarioid']; ?>"
                                        <?php if ($mesAnt == $month['mes']) { ?>
                                            selected="selected"
                                        <?php } ?>
                                    >
                                        <?php echo $month['mes']; ?>
                                    </option>

                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3">

                        <label for="dealerhome" class="col-md-12" style="color:black;">
                            Dealer:
                        </label>

                        <div class="col-md-12 pr-0">

                            <select
                                id="dealerhome"
                                data-placeholder="All Dealers"
                                class="select2 form-control"
                                tabindex="-1"
                                name="dealerhome"
                            >

                                <?php
                                $dealerSesion = (int) ($_SESSION['UsrData']['dealerid'] ?? 0);

                                if (userHasRole('1')) {
                                ?>
                                    <option title="worldsa.svg" value="0">
                                        ALL DEALERS
                                    </option>
                                <?php
                                }

                                foreach ($listadoDealers as $dealer) {

                                    $dealerId = (int) $dealer['dealerid'];

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Admin ve todos
                                    |--------------------------------------------------------------------------
                                    */

                                    if (userHasRole('1')) {
                                ?>

                                        <option
                                            title="<?php echo $dealer['bandera']; ?>"
                                            value="<?php echo $dealerId; ?>"
                                        >
                                            <?php echo $dealer['dealer']; ?>
                                        </option>

                                <?php
                                        continue;
                                    }

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Usuarios normales solo su dealer
                                    |--------------------------------------------------------------------------
                                    */

                                    if ($dealerSesion === $dealerId) {
                                ?>

                                        <option
                                            title="<?php echo $dealer['bandera']; ?>"
                                            value="<?php echo $dealerId; ?>"
                                            selected="selected"
                                        >
                                            <?php echo $dealer['dealer']; ?>
                                        </option>

                                <?php
                                    }
                                }
                                ?>

                            </select>

                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>
</div>
<div class="row" id="OrdersList">
    
</div>

            <footer class="content-footer hidden-print">
                <?php require_once("src/footer.php"); ?>
            </footer>
        </main>
    </div>
    <?php require_once("src/scripts.php"); ?>
</body>
</html>