<?php 
include_once("../controllers/sidebar.php");
?>
<nav class="page-controls navbar navbar-dashboard">
    <div class="container-fluid">
        <!-- .navbar-header contains links seen on xs & sm screens -->
        <div class="navbar-header mr-md-3">
            <ul class="nav navbar-nav navbar-right d-md-none">
                <li class="nav-item d-none d-xl-block">
                    <!-- toggles chat -->
                    <a class="nav-link" href="#" data-toggle="chat-sidebar">
                        <span class="square square-lg"><i class="la la-globe"></i></span>
                    </a>
                </li>
            </ul>
        </div>
<?php //print_r($mensajesPend); ?>
        <!-- this part is hidden for xs screens -->
        <div class="navbar-header mobile-hidden">
            <?php if(isset($mensajesPend) && count($mensajesPend)>0){?>
                <div class="alert alert-info alert-rounded animated bounceIn delay-2s mx-3 d-none d-xl-block">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <i class="fa fa-comments text-danger"></i> Welcome to the system, if you are seeing this notification, it is because there are orders that require your attention.
                </div>
            <?php } ?>
            <ul class="nav navbar-nav float-right">
                <li class="dropdown nav-item">
                        <a target="_self" href="../admin/myprofile.php" class="nav-link p-0">
                            <span class="badge badge-primary xs-1"> 
                                <i class="glyphicon glyphicon-user text-warning"></i>
                                <?php echo(strtoupper($_SESSION['UsrData']['nombres'])); ?><?php if(isset($_SESSION['DatLoc']['dealer'])){ ?> | <img src = "../admin/img/country/<?php echo($_SESSION['DatLoc']['bandera']); ?>" style="width:20px;" alt="<?php echo($_SESSION['DatLoc']['pais']); ?>"/> <?php echo(strtoupper($_SESSION['DatLoc']['dealer'])); ?><?php }else{?> <img src = "../admin/img/country/worldsa.svg" style="width:20px;" alt="All World"/> | GENERAL ADMIN <?php }?>
                            </span>
                        </a>
                </li>
                <li class="nav-item divider d-none d-xl-block"></li>
                <li class="nav-item d-none d-xl-block">
                    <a target="_self" href="../admin/salir?salir=<?php echo(md5($_SESSION['UsrData']['nombres'])); ?>" class="nav-link">
                        <i class="la la-power-off px-2"></i>
                    </a>
                </li>
                <li class="nav-item d-md-none">
                    <a target="_self" class="nav-link collapsed" data-toggle="collapse" href="#sidebar" role="button" aria-expanded="false" aria-controls="collapseExample">
                        <i class="la la-navicon"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>