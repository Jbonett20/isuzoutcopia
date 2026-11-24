<?php 
include_once '../admin/src/seguridad.php';
include_once("../controllers/listdetail.php");
?>
<section class="widget">
    <header>
        <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
            LIST ITEMS - PRICE LIST
        </h6>
    </header>
    <div class="widget-body">
        <form class="form-vertical" role="form">
            <div class="row">
                <div class="col-lg-12">
                    <table id="TableListItems" name="TableListItems" class="table table-bordered table-lg mt-lg mb-10 table-hover overflow-auto" style="font-size: 11px;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 4%;font-size:11px;">PROD MONTH</th>
                                <th class="text-center" style="width: 6%;font-size:11px;">ITEM#</th>
                                <th class="text-center" style="width: 6%;font-size:11px;">MODEL NAME</th>
                                <th class="text-center" style="width: 5%;font-size:11px;">EZSPEC</th>
                                <th class="text-center" style="width: 3%;font-size:11px;">MY</th>
                                <th class="text-center" style="width: 5%;font-size:11px;">COLOR</th>
                                <?php if( userHasRole('1') ){?>
                                    <th class="text-center" style="width: 6%;font-size:11px;">UNIT PRICE USD (ISZJ-ILAC)</th>
                                <?php }?>
                                <th class="text-center" style="width: 6%;font-size:11px;">UNIT PRICE USD (ILAC-DLR)</th>
                                <th class="text-center" style="width: 8%;font-size:11px;">REMARKS</th>
                                <th class="text-center" style="width: 5%;font-size:11px;">PORT</th>
                                <th class="text-center" style="width: 5%;font-size:11px;">ORDER TYPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(isset($listadoItems)){ foreach($listadoItems as $items){ ?>
                                <tr>
                                    <td><?php echo($items['mescal']); ?></td>
                                    <td><?php echo($items['item']); ?></td>
                                    <td><?php echo($items['modelname']); ?></td>
                                    <td><?php echo($items['ezspeccode']); ?></td>
                                    <td class="text-center"><?php echo($items['salesyear']); ?></td>
                                    <td style="background-color: #C0C0C0;text-align:center;"><i class="fa fa-truck" style="color: <?php echo($items['rgb']); ?> !important"></i> <?php echo($items['color']); ?></td>
                                    <?php if( userHasRole('1') ){?>
                                        <td class="text-right">$ <?php echo(number_format($items['unitpriceusd'])); ?></td>
                                    <?php }?>
                                    <td class="text-right">$ <?php echo(number_format($items['unitpricecif'])); ?></td>
                                    <td><?php echo($items['remarks']); ?></td>
                                    <td><?php echo($items['puerto']); ?></td>
                                    <td><?php echo($items['tipoorden']); ?></td>
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
                    <?php if( userHasAnyRole(['1', '4']) ){ ?>
                        <button type="button" class="btn btn-warning btn-sm mb-xs" onclick="exportarTablaAExcel();"><i class="fa fa-file-excel-o"></i> Export List</button>
                    <?php } ?>
                    <?php if( (userHasAnyRole(['1', '4', '2']) || userHasAnyRole(['1', '4', '2']) || userHasAnyRole(['1', '4', '2'])) ){ ?>
                        <button type="button" class="btn btn-info btn-sm mb-xs" onclick="exportarTablaAPDF();"><i class="fa fa-file-pdf-o"></i> Export List</button>
                    <?php } ?>
                </div>
            </div>
        </form>
    </div>
</section>