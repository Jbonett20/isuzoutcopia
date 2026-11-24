<?php 
include_once '../admin/src/seguridad.php';
include_once("../controllers/exist.php");
?>
<form class="form-vertical" role="form" id="FormCreaItem">
    <div class="row">
        <div class="col-lg-12">
            <table id="tablaItems" class="tablaItems table table-bordered table-lg mt-lg mb-10" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 4%;font-size:12px;">PROD MONTH</th>
                        <th class="text-center" style="width: 10%;font-size:12px;">ITEM#</th>
                        <th class="text-center" style="width: 6%;font-size:12px;">MODEL NAME | <br><span style="color:#F40000;">EZSPEC</span> | <span style="color:#2D8515;">YEAR</span></th>
                        <?php if( userHasRole('1') ){?>
                            <th class="text-center" style="width: 6%;font-size:12px;">UNIT PRICE USD (ISZJ-ILAC)</th>
                        <?php }?>
                        <th class="text-center" style="width: 6%;font-size:12px;">UNIT PRICE USD (ILAC-DLR)</th>
                        <th class="text-center" style="width: 7%;font-size:12px;">BODY/OPT</th>
                        <th class="text-center" style="width: 3%;font-size:12px;">QTY</th>
                        <th class="text-center" style="width: 7%;font-size:12px;">ORDER No</th>
                        <th class="text-center" style="width: 5%;font-size:12px;">PRICE</th>
                        <th class="text-center" style="width: 10%;font-size:12px;">PRODUCTION ALLOCATION REQ (WEEK)</th>
                        <th class="text-center" style="width: 10%;font-size:12px;">BACKGROUND REQUEST</th>
                        <?php if(isset($DatosOrden) && ($DatosOrden['estadoordenid']=="7" || $DatosOrden['estadoordenid']=="1") ){?>
                            <th class="text-center" style="width: 1%;font-size:12px;"></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($DatosOrden) && ($DatosOrden['estadoordenid']=="7" || $DatosOrden['estadoordenid']=="1") ){?>
                        <tr style="background-color: #FFF;">
                            <td id="iditemadd"><?php echo(str_replace('_',' ',$_GET['month'])); ?></td>
                            <td>
                                <select id="itemselect" data-placeholder="write to search" class="select2 form-control itemselect" tabindex="-1" name="itemselect" onchange="ExecuteCalculeData();">
                                </select>
                            </td>
                            <input type="hidden" id="unitpriceilacmod" name="unitpriceilacmod" value="0">
                            <td style="text-align: center;" id="modeladd"></td>
                            <?php if( userHasRole('1') ){?>
                                <td style="text-align: right;" id="unitpriceilacadd"></td>
                            <?php }?>
                            <td style="text-align: right;" id="unitpricedlradd" name="unitpricedlradd[]"></td>
                            <td><input type="text" id="bodyoptadd" name="bodyoptadd" class="form-control" placeholder="Write here" value=""></td>
                            <td><input type="number" id="qtyoptadd" name="qtyoptadd" class="form-control" min="0" max="100" placeholder="0" value="0" onchange="RecalculatePrice();"></td>
                            <td><input type="text" id="ordernoadd" name="ordernoadd" class="form-control" placeholder="Write here" value="" maxlength="15"></td>
                            <td style="text-align: right;" id="priceadd">$ 0</td>
                            <td><input type="text" id="allocationadd" name="allocationadd" class="form-control" placeholder="Write here" value=""></td>
                            <td><input type="text" id="backgroundadd" name="backgroundadd" class="form-control" placeholder="Write here" value=""></td>
                            <td style="text-align: center;padding-top: 8px;">
                                <button type="button" class="btn btn-success btn-xs mb-xs" onclick="AddItem();"><i class="fa fa-plus"></i></button>
                            </td>
                        </tr>
                    <?php }?>
                    <?php if(isset($DetalleOrden)){ foreach($DetalleOrden as $items){ ?>
                        <tr style="background-color: #FFF;">
                            <td id="iditemadd"><?php echo($items['ano'].'-'.$items['mes']); ?></td>
                            <td style="font-size:13px;font-weight: bold;background-color: rgba(0,0,0,0.24);color:<?php echo($items['rgb']);?>"><i class="fa fa-truck" style="color: <?php echo($items['rgb']);?> !important"> </i> <?php echo($items['colord'].' '.$items['item']);?></td>
                            <input type="hidden" class="cantUnidades" name="cantUnidades[]" value="<?php echo($items['cantidad']);?>">
                            <input type="hidden" class="vlrUnidades" name="vlrUnidades[]" value="<?php echo($items['precio']);?>">
                            <input type="hidden" class="itemId" name="itemId[]" value="<?php echo($items['itemid'].'-'.$items['colord']);?>">
                            <input type="hidden" class="bodyopt" name="bodyopt[]" value="<?php echo($items['opcionbody']);?>">
                            <input type="hidden" class="orderno" name="orderno[]" value="<?php echo($items['noorden']);?>">
                            <input type="hidden" class="prodalloca" name="prodalloca[]" value="<?php echo($items['asignacion']);?>">
                            <input type="hidden" class="background" name="background[]" value="<?php echo($items['respuesta']);?>">
                            <td style="text-align: center;"><?php echo($items['modelo']);?> | <span style="color:#F40000;"><?php echo($items['ezspeccode']);?></span> | <span style="color:#2D8515;"><?php echo($items['anomodelo']);?></span></td>
                            <?php if( userHasRole('1') ){?>
                                <td style="text-align: right;">$ <?php echo(number_format($items['preciodolar'],0));?></td>
                            <?php } ?>
                            <td style="text-align: right;">$ <?php echo(number_format($items['preciocif'],0));?></td>
                            <td><?php echo($items['opcionbody']);?></td>
                            <td style="text-align: center;font-size:13px;font-weight: bold;" class="cantidadmod"><?php echo($items['cantidad']);?></td>
                            <td><?php echo($items['noorden']);?></td>
                            <td style="text-align: right;font-size:13px;font-weight: bold;" class="vlrTotal">$ <?php echo(number_format($items['precio'],0));?></td>
                            <td><?php echo($items['asignacion']);?></td>
                            <td><?php echo($items['respuesta']);?></td>
                            <?php if(isset($DatosOrden) && ($DatosOrden['estadoordenid']=="7" || $DatosOrden['estadoordenid']=="1") ){?>
                                <td style="text-align: center;">
                                    <button type="button" class="btn btn-danger btn-xs mb-xs btnEliminar" onclick="DelItem(this);"><i class="fa fa-minus"></i></button>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-2">
            <?php if(isset($DatosOrden) && ($DatosOrden['estadoordenid']=="7" || $DatosOrden['estadoordenid']=="1") ){?>
                <button type="button" class="btn btn-warning btn-sm mb-xs" onclick="CargaDetOrden(); return false;"><i class="fa fa-refresh"></i> Undo</button>
            <?php } ?>
            <?php if(isset($DatosOrden) && (userHasAnyRole(['1', '4'])) && ( $DatosOrden['estadoordenid']=="7" || $DatosOrden['estadoordenid']=="1" ) ){?>
                <button type="button" class="btn btn-danger btn-sm mb-xs" onclick="BorrarOrdenMsg();"><i class="fa fa fa-eraser"></i> Delete Order</button>
            <?php }?>
            <?php if(isset($DatosOrden) && (userHasRole('2')) && ( $DatosOrden['estadoordenid']=="7" ) ){?>
                <button type="button" class="btn btn-danger btn-sm mb-xs" onclick="BorrarOrdenMsg();"><i class="fa fa fa-eraser"></i> Delete Order</button>
            <?php }?>
        </div>
        <div class="col-lg-7" style="text-align: center;">
            
        </div>
        <div class="col-lg-3" style="text-align: right;">
            <?php if(isset($DatosOrden) && $DatosOrden['estadoordenid']=="7"){?>
                <button type="button" class="btn btn-default btn-sm mb-xs" onclick="ParaDraf();"><i class="fa fa fa-archive"></i> Save Draft</button>
            <?php }?>
            <!--<button type="button" class="btn btn-warning btn-sm mb-xs" onclick="exportarDetalleExcel();"><i class="fa fa-file-excel-o"></i> Export Order</button>-->
            <?php if( userHasAnyRole(['1', '4']) ){ ?>
                <a href="descargarMasterReport.php?orderid=<?php echo($_GET['orderid']); ?>&source=2025MLA" target="_blank" class="btn btn-warning btn-sm mb-xs"><i class="fa fa-file-excel-o"></i> Export Order</a>
            <?php } ?>
            <?php if( userHasRole('2') ){ ?>
                <a href="descargarMasterReport.php?orderid=<?php echo($_GET['orderid']); ?>&source=2025MLD" target="_blank" class="btn btn-warning btn-sm mb-xs"><i class="fa fa-file-excel-o"></i> Export Order</a>
            <?php } ?>
            <?php if(isset($DatosOrden) && ($DatosOrden['estadoordenid']=="7" || $DatosOrden['estadoordenid']=="1") ){?>
                <button type="button" class="btn btn-success btn-sm mb-xs" onclick="ParaNew();"><i class="fa fa-paper-plane"></i> Submit Order</button>
            <?php }?>
        </div>
    </div>
</form>