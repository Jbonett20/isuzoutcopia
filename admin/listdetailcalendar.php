<?php
include_once '../admin/src/seguridad.php';
include_once("../controllers/calendar.php");
?>
<section class="widget">
    <header>
        <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
            Ordering Calendar
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
                                <th class="text-center" style="font-size:11px; width: 10%;">YEAR</th>
                                <th class="text-center" style="font-size:11px; width: 10%;">PROD MONTH</th>
                                <th class="text-center" style="font-size:11px;">OPEN</th>
                                <th class="text-center" style="font-size:11px;">CLOSE</th>
                                <th class="text-center" style="font-size:11px;">STATUS</th>
                                <?php if (userHasRole('1')) { ?>
                                <th class="text-center no-sort" style="width: 5%;font-size:11px;">OPTIONS</th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($listadoItems)) {
                                foreach ($listadoItems as $items) { ?>
                            <tr>
                                <td><?php echo ($items['ano']); ?></td>
                                <td><?php echo ($items['mes']); ?></td>
                                <td><?php echo ($items['inicia']); ?></td>
                                <td><?php echo ($items['finaliza']); ?></td>
                                <td class="<?php echo ($items['estado'] === 'ACTIVE') ? 'text-success' : 'text-danger'; ?>"
                                    style="font-weight: bold;">
                                    <?php echo $items['estado']; ?>
                                </td>
                                <?php if (userHasRole('1')) { ?>
                                <td>
                                    <div style="display: flex;justify-content: center;align-items: center;">
                                        <button onclick="EditRegistro(this)"
                                            data-item='<?php echo htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8'); ?>'
                                            type="button" class="btn btn-warning btn-xs mb-xs mr-3">
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
        </form>
    </div>
    <?php if (userHasRole('1')) { ?>
    <div class="widget-body">
        <div class="row">
            <div class="col-lg-12" style="text-align: right;">
                <button type="button" class="btn btn-success btn-sm mb-xs" onclick="NewRegistro();">
                    <i class="fa fa-plus"></i> NEW CALENDAR
                </button>
            </div>
        </div>
    </div>
    <?php } ?>
</section>