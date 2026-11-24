<?php
include_once '../admin/src/seguridad.php';
include_once("../controllers/users.php");
?>
<section class="widget">
    <header>
        <h6 style="color:black;font-weight:bold;text-align:left;" id="config">
            LIST - USERS
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
                                <th class="text-center" style="font-size:11px;">NAMES</th>
                                <th class="text-center" style="font-size:11px;">EMAIL</th>
                                <th class="text-center" style="font-size:11px; min-width: 130px;">DEALERS</th>
                                <th class="text-center" style="font-size:11px;">ROL</th>
                                <th class="text-center" style="font-size:11px;">JOB TITTLE</th>
                                <th class="text-center" style="font-size:11px;">CREATION AT</th>
                                <th class="text-center" style="font-size:11px;">LAST CONNECTION </th>
                                <th class="text-center" style="font-size:11px;">STATUS</th>
                                <th class="text-center no-sort" style="width: 5%;font-size:11px;">OPTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($listadoItems)) {
                                foreach ($listadoItems as $items) { ?>
                            <tr>
                                <td>
                                    <?php if($items['estadoid'] == "2" && $items['ultimaconexion']==""){?>
                                    <?php if(userHasRole('1')){?>
                                    <i class="fa fa-star text-info"></i><i class="fa fa-star text-info"></i>
                                    <?php }?>
                                    <?php }?>
                                    <?php echo ($items['nombres']); ?>
                                </td>
                                <td><?php echo ($items['email']); ?></td>
                                <td><?php echo ($items['dealer']); ?></td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <?php 
                                        if (isset($items['roles']) && !empty($items['roles'])) {
                                            foreach ($items['roles'] as $role) { 
                        ?>
                                                <span style="padding: 5px 10px; background-color: #5a7180; color: white; border-radius: 5px; font-size: 11px; display: inline-block; width: fit-content; font-weight: 600;">
                                                    <?php echo htmlspecialchars($role['rol']); ?>
                                                </span>
                                        <?php }
                                        } else {
                                            echo '<span style="color: #999;">N/A</span>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td><?php echo ($items['cargodealer']); ?></td>
                                </td>
                                <td><?php echo ($items['creacion']); ?></td>
                                <td><?php echo ($items['ultimaconexion']); ?></td>
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
                    <i class="fa fa-plus"></i> NEW USER
                </button>
            </div>
        </div>
    </div>
</section>