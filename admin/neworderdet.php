<?php 
include_once '../admin/src/seguridad.php';
//include_once("../controllers/new.php");
?>
<section class="widget">
    <header>
        <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
            ORDER CONFIGURATION DETAIL
        </h6>
    </header>
    <div class="widget-body">
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
                                <th class="text-center" style="width: 1%;font-size:12px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
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
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-1">
                        <button type="button" class="btn btn-danger btn-sm mb-xs" onclick="LimpiarTabla(); return false;"><i class="fa fa-eraser"></i> Clear Values</button>
                </div>
                <div class="col-lg-8">

                </div>
                <div class="col-lg-3" style="text-align: right;">
                    <button id="btn-SaveDraft" type="button" class="btn btn-default btn-sm mb-xs" onclick="ParaDraf();"><i class="fa fa fa-archive"></i> Save Draft</button>
                    <?php /*<!--<button type="button" class="btn btn-warning btn-sm mb-xs" onclick="exportarDetalleExcel();"><i class="fa fa-file-excel-o"></i> Export Order</button>-->*/?>
                    <button id="btn-SubmitOrder" type="button" class="btn btn-success btn-sm mb-xs" onclick="ParaNew();"><i class="fa fa-paper-plane"></i> Submit Order</button>
                </div>
            </div>
        </form>
    </div>
</section>