<?php 
include_once '../admin/src/seguridad.php';
include_once("../controllers/home.php");
?>
<?php 
$ordenesConMensajes = array_column($mensajesPend, 'ordenid');
//print_r($ordenesConMensajes);
for($i=0;$i<count($listadoEstados);$i++) {
    $cant = 0;
    $valorTotal = 0;
    $cantItems = 0;
    if(isset($listadoOrdenes)){ 
        foreach($listadoOrdenes as $orden){ 
            if($orden['estadoordenid']==$listadoEstados[$i]['estadoordenid']){
                $cant++;
                $valorTotal = $valorTotal + $orden['total'];
                $cantItems = $cantItems + $orden['unidades'];
            }
        }
    }
    ?>
    <div class="col-lg-2">
        <section class="widget" style="min-height: 380px;">
            <header>
                <h6 style="color:#353a63;font-weight:bold;text-align:center;">
                    <?php echo($listadoEstados[$i]['estadoorden']); ?><br><span style="font-weight:bold;">(Orders: <?php echo($cant);?>)</span>
                </h6>
                <div class="widget-controls">
                    <a href="#" data-widgster="close"><i class="la la-remove text-danger"></i></a>
                </div>
            </header>
            <div class="widget-body" style="margin-bottom: 40px;">
                <div class="stats-row" style="text-align:center">
                    <div class="stat-item">
                        <?php if(isset($listadoOrdenes)){ 
                            foreach($listadoOrdenes as $orden){ 
                                if($orden['estadoordenid']==$listadoEstados[$i]['estadoordenid']){?>
                            <h6 class="name fs-sm" style="color:#000000;font-weight:bold;font-size:15px;">
                                <a style="color:#000;" href="editorder?orderid=<?php echo($orden['ordenid']);?>&month=0&dealerid=14&typeorderid=0&portid=0&status=0&origen=home">
                                    <img src = "../admin/img/country/<?php echo($orden['bandera']);?>" style="width:20px;" alt="<?php echo($orden['pais']);?>" title="<?php echo($orden['pais']);?>"/> <?php echo($orden['codigo']);?>
                                    <?php if (in_array($orden['ordenid'], $ordenesConMensajes)) {?><i class="fa fa-comments text-info"></i><?php }?>
                                </a>
                            </h6>
                        <?php } } }?>
                    </div>
                </div>
                <div style="position: absolute;bottom: 0;left: 10px;right: 10px; text-align:center;">
                    <div class=" progress progress-xs" style="background-color:<?php echo($listadoEstados[$i]['color']); ?>">
                        <div class="progress-bar" style="width: 100%;background-color:<?php echo($listadoEstados[$i]['color']); ?>"></div>
                    </div>
                    <p>
                        <i class="fa fa-shopping-cart" style="color: <?php echo($listadoEstados[$i]['color']); ?> !important"></i>
                        <span class="fw-semi-bold text-inverse">$ <?php echo(number_format($valorTotal)); ?></span> - 
                        <i class="fa fa-truck" style="color: <?php echo($listadoEstados[$i]['color']); ?> !important"></i>
                        <span class="fw-semi-bold text-inverse"><?php echo(number_format($cantItems)); ?></span>
                    </p>
                </div>
            </div>
        </section>
    </div>
<?php }?>