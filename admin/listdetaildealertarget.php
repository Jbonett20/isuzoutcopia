<?php
include_once '../admin/src/seguridad.php';
include_once("../controllers/dealertarget.php");
?>
<style>
    @media (min-width: 992px) {
        .table-responsive.table-responsive-mobile {
            overflow-x: visible;
        }

        .table-responsive.table-responsive-mobile .table {
            width: 100% !important;
        }
    }
</style>
<section class="widget">
    <header>
        <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
            LIST - TARGET X DEALER
        </h6>
    </header>
    <div class="widget-body">
        <form class="form-vertical" role="form">
            <div class="row">
                <div class="col-lg-12">
                    <div class="table-responsive table-responsive-mobile">
                    <table id="TableListDealerTarget"
                        class="table table-bordered mt-lg mb-10 table-hover"
                        style="font-size: 11px;">
                        <thead>
                            <tr>
                                <th class="text-center" style="font-size:11px; width: 30%;">DEALER</th>
                                <th class="text-center" style="font-size:11px; width: 10%;">YEAR</th>
                                <th class="text-center" style="font-size:11px; width: 15%;">TARGET (UNITS)</th>
                                <th class="text-center" style="font-size:11px; width: 15%;">TARGET ($ USD)</th>
                                <th class="text-center" style="font-size:11px; width: 15%;">CREATION</th>
                                <?php if ($canManage) { ?>
                                <th class="text-center no-sort" style="width: 15%;font-size:11px;">OPTIONS</th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($listadoItems)) {
                                foreach ($listadoItems as $items) { ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:flex-start; gap:5px;">
                                        <img src="../admin/img/country/<?php echo ($items['bandera']); ?>"
                                            style="width:20px;" alt="<?php echo ($items['pais']); ?>"
                                            title="<?php echo ($items['pais']); ?>" />
                                        <span style="white-space: normal; word-break: break-word; line-height: 1.2;">
                                            <?php echo ($items['dealer']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center"><?php echo ($items['year']); ?></td>
                                <td class="text-right"><?php echo number_format((int)$items['objetivo']); ?></td>
                                <td class="text-right"><?php echo number_format((int)$items['objetivovlr']); ?></td>
                                <td class="text-center"><?php echo ($items['creado']); ?></td>
                                <?php if ($canManage) { ?>
                                <td>
                                    <div style="display: flex;justify-content: center;align-items: center;">
                                        <button onclick="EditRegistro(this)"
                                            data-item='<?php echo htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8'); ?>'
                                            type="button" class="btn btn-warning btn-xs mb-xs">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                                <?php } ?>
                            </tr>
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php if ($canManage) { ?>
    <div class="widget-body">
        <div class="row">
            <div class="col-lg-12" style="text-align: right;">
                <button type="button" class="btn btn-success btn-sm mb-xs" onclick="NewRegistro();">
                    <i class="fa fa-plus"></i> NEW TARGET
                </button>
            </div>
        </div>
    </div>
    <?php } ?>
</section>
