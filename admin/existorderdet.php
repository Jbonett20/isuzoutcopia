<?php 
include_once '../admin/src/seguridad.php';
include_once("../controllers/listexist.php");
$ordenesConMensajes = array_column($mensajesPend, 'ordenid');
?>
<section class="widget">
    <header>
        <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
            RESULTS FOUND
        </h6>
    </header>
    <div class="widget-body">
        <form class="form-vertical" role="form">
            <div class="row">
                <div class="col-lg-12">
                    <table id="TableListItems" name="TableListItems" class="table table-bordered table-lg mt-lg mb-10 table-hover overflow-auto" style="font-size: 11px;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 5%;font-size:11px;">PO#</th>
                                <th class="text-center" style="width: 5%;font-size:11px;">STATUS</th>
                                <th class="text-center" style="width: 5%;font-size:11px;">PROD MONTH</th>
                                <th class="text-center" style="width: 6%;font-size:11px;">CREATION</th>
                                <th class="text-center" style="width: 6%;font-size:11px;">UPDATE</th>
                                <th class="text-center" style="width: 4%;font-size:11px;">UNITS</th>
                                <th class="text-center" style="width: 4%;font-size:11px;">PRICE</th>
                                <th class="text-center" style="width: 8%;font-size:11px;">COUNTRY</th>
                                <th class="text-center" style="width: 8%;font-size:11px;">DEALER</th>
                                <th class="text-center" style="width: 8%;font-size:11px;">PORT</th>
                                <th class="text-center" style="width: 5%;font-size:11px;">ORDER TYPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(isset($listadoItems)){ foreach($listadoItems as $items){ ?>
                                <tr>
                                    <td class="text-center">
                                        <a style="font-weight: bold;" href="editorder?orderid=<?php echo($items['ordenid']); ?>&month=<?php echo($_GET['month']); ?>&dealerid=<?php echo($_GET['dealerid']); ?>&typeorderid=<?php echo($_GET['typeorderid']); ?>&portid=<?php echo($_GET['portid']); ?>&status=<?php echo($_GET['status']); ?>">
                                            <?php echo($items['codigo']); ?>
                                            <?php if (in_array($items['ordenid'], $ordenesConMensajes)) {?><i class="fa fa-comments text-danger"></i><?php }?>
                                        </a>
                                    </td>
                                    <td class="text-center" style="font-weight: bold;color:<?php echo($items['color']); ?>"><?php echo($items['estadoorden']); ?></td>
                                    <td class="text-center"><?php echo($items['ano'].'-'.$items['mes']); ?></td>
                                    <td class="text-center"><?php echo($items['creacion']); ?></td>
                                    <td class="text-center"><?php echo($items['actualizacion']); ?></td>
                                    <td class="text-center"><?php echo($items['unidades']); ?></td>
                                    <td class="text-center">$ <?php echo(number_format($items['total'],0)); ?></td>
                                    <td class="text-left"><img src = "../admin/img/country/<?php echo($items['bandera']); ?>" style="width:20px;" alt="<?php echo($items['pais']); ?>"/> <?php echo($items['pais']); ?></td>
                                    <td class="text-left"><?php echo($items['dealer']); ?></td>
                                    <td class="text-left"><?php echo($items['puerto']); ?></td>
                                    <td class="text-center"><?php echo($items['tipoorden']); ?></td>
                                </tr>
                            <?php }}?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row" style="padding-top: 15px;">
                <div class="col-lg-9">
                </div>
                <div class="col-lg-3" style="text-align: right;">
                    <button type="button" class="btn btn-warning btn-sm mb-xs" onclick="exportarTablaAExcel();"><i class="fa fa-file-excel-o"></i> Export Summary</button>
                </div>
            </div>
        </form>
    </div>
</section>