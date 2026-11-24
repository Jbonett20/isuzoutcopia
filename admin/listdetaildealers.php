<?php
include_once '../admin/src/seguridad.php';
include_once("../controllers/dealers.php");
?>
<section class="widget">
    <header>
        <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
            LIST - DEALERS
        </h6>
    </header>
    <div class="widget-body">
        <form class="form-vertical" role="form">
            <div class="row">
                <div class="col-lg-12">
                    <table id="TableListUsers" name="TableListUsers"
                        class="table table-bordered table-lg mt-lg mb-10 table-hover overflow-auto"
                        style="font-size: 11px;">
                        <thead>
                            <tr>
                                <th class="text-center" style="font-size:11px; width: 10%;">DEALERS</th>
                                <th class="text-center" style="font-size:11px;">INITIALS</th>
                                <th class="text-center" style="font-size:11px;">CUSTOMER CODE</th>
                                <th class="text-center" style="font-size:11px;">COUNTRY</th>
                                <th class="text-center" style="font-size:11px;">FROM DATA</th>
                                <th class="text-center" style="font-size:11px;">TO DATA</th>
                                <th class="text-center" style="font-size:11px;">PAYMENT TERM </th>
                                <th class="text-center" style="font-size:11px;">PERSON IN CHARGE </th>
                                <th class="text-center" style="font-size:11px;">PHONE</th>
                                <th class="text-center" style="font-size:11px;">STATUS</th>
                                <th class="text-center no-sort" style="width: 5%;font-size:11px;">OPTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($listadoItems)) {
                                foreach ($listadoItems as $items) { ?>
                            <tr>
                                <td><?php echo ($items['dealer']. '<br>'. $items["intermediario"]); ?></td>
                                <td><?php echo ($items['siglaorden']); ?></td>
                                <td><?php echo ($items['customercode']); ?></td>
                                <td>
                                    <div style="display: flex;align-items: center;">
                                        <?php echo ($items['bandera']); ?>
                                    </div>
                                </td>
                                </td>
                                <td><?php echo ($items['fromdata']); ?></td>
                                <td><?php echo ($items['todata']); ?></td>
                                <td><?php echo ($items['paymentterm']); ?></td>
                                <td><?php echo ($items['personincharge']); ?></td>
                                <td><?php echo ($items['phone']); ?></td>
                                <td class="<?php echo ($items['estado'] === 'ACTIVE') ? 'text-success' : 'text-danger'; ?>"
                                    style="font-weight: bold;">
                                    <?php echo $items['estado']; ?>
                                </td>
                                <td>
                                    <div style="display: flex;justify-content: center;align-items: center;">
                                        <button onclick="EditRegistro(this)"
                                            data-item='<?php echo htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8'); ?>'
                                            type="button" class="btn btn-warning btn-xs mb-xs mr-3">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
    <div class="widget-body">
        <div class="row">
            <div class="col-lg-12" style="text-align: right;">
                <button type="button" class="btn btn-success btn-sm mb-xs" onclick="NewRegistro();">
                    <i class="fa fa-plus"></i> NEW DEALER
                </button>
            </div>
        </div>
    </div>
</section>