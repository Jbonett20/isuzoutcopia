<?php
include_once '../admin/src/seguridad.php';
include_once("../controllers/holidays.php");
?>
<section class="widget">
    <header>
        <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
            LIST - HOLIDAY
        </h6>
    </header>
    <div class="widget-body">
        <form class="form-vertical" role="form">
            <div class="row">
                <div class="col-lg-12">
                    <table id="TableListHolidays"
                        class="table table-bordered table-lg mt-lg mb-10 table-hover overflow-auto"
                        style="font-size: 11px;">
                        <thead>
                            <tr>
                                <th class="text-center" style="font-size:11px;">HOLIDAY</th>
                                <th class="text-center" style="font-size:11px;">DATE</th>
                                <th class="text-center" style="font-size:11px;">COUNTRY</th>
                                <th class="text-center" style="font-size:11px;">STATUS</th>
                                <th class="text-center no-sort" style="width: 5%;font-size:11px;">OPTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($listadoItems)) {
                                foreach ($listadoItems as $items) { ?>
                            <tr>
                                <td><?php echo ($items['nombre']); ?></td>
                                <td><?php echo ($items['fecha']); ?></td>
                                <td><?php echo ($items['pais']); ?></td>
                                <td class="<?php echo ($items['estadoid'] == '1') ? 'text-success' : 'text-danger'; ?>"
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
                            <?php }
                            } ?>
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
                    <i class="fa fa-plus"></i> NEW HOLIDAY
                </button>
            </div>
        </div>
    </div>
</section>