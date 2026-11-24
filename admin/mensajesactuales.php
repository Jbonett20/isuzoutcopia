<?php 
include_once '../admin/src/seguridad.php';
include_once("../controllers/mensajes.php");
?>
<section class="widget widget-email">
    <header id="widget-email-header">
        <div class="row">
            <div class="col-sm-12" style="color:#000;font-weight:bold;">
                Messages about the ORDER:
            </div>
        </div>
    </header>
    <div class="widget-body" id="mailbox-content">
        <table id="folder-view" class="table table-emails table-hover">
            <thead>
                <tr>
                    <th style="color:#2477FF;font-weight:bold;width:20%;text-align:left;">USER</th>
                    <th style="color:#2477FF;font-weight:bold;width:44%;text-align:left;">NOTE</th>
                    <th style="color:#2477FF;font-weight:bold;width:18%;text-align:center;">ORDER STATUS</th>
                    <th style="color:#2477FF;font-weight:bold;width:18%;text-align:center;">DATE</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($detalleMensajes as $items){ 
                    if(userHasRole('1')){ ?>
                        <tr>
                            <td style="color:#000000;"><i class="fa fa-user"></i> <?php echo($items['nombres']); ?>
                            <?php if($items['visible']=="1"){?>
                                <br><span style="color:#2D8515;"><i class="fa fa-star" style="color:#2D8515;"></i> Visible to dealer</span>
                            <?php } ?>
                            </td>
                            <?php if($_SESSION['UsrData']['usuarioid']==$items['usuarioid']){?>
                                <td style="color:#000000;<?php if($items['leido']=="0"){?>font-weight: bold;<?php }?>"><?php echo($items['nota']); ?></td>
                            <?php }else{ ?>
                                <td style="color:#000000;<?php if($items['leido']=="0"){?>font-weight: bold;<?php }?>"><?php if($items['leido']=="0"){?><a href="#" style="text-decoration:none" onclick="MarcarLeido(<?php echo($items['ordennotaid']); ?>);return false;"><?php echo($items['nota']); ?></a></td><?php }else{?><?php echo($items['nota']); ?><?php }?>
                            <?php }?>
                            <td style="color:<?php echo($items['color']); ?>;text-align:center;"><?php echo($items['estadoorden']); ?></td>
                            <td style="color:#000000;text-align:center;"><?php echo($items['creacion']); ?></td>
                        </tr>
                <?php }else{
                    if($items['visible']=="1"){?>
                        <tr>
                            <td style="color:#000000;"><i class="fa fa-user"></i> <?php echo($items['nombres']); ?></td>

                            <?php if($_SESSION['UsrData']['usuarioid']==$items['usuarioid']){?>
                                <td style="color:#000000;<?php if($items['leido']=="0"){?>font-weight: bold;<?php }?>"><?php echo($items['nota']); ?></td>
                            <?php }else{ ?>
                                <td style="color:#000000;<?php if($items['leido']=="0"){?>font-weight: bold;<?php }?>"><?php if($items['leido']=="0"){?><a href="#" style="text-decoration:none" onclick="MarcarLeido(<?php echo($items['ordennotaid']); ?>);return false;"><?php echo($items['nota']); ?></a></td><?php }else{?><?php echo($items['nota']); ?><?php }?>
                            <?php }?>

                            <td style="color:<?php echo($items['color']); ?>;text-align:center;"><?php echo($items['estadoorden']); ?></td>
                            <td style="color:#000000;text-align:center;"><?php echo($items['creacion']); ?></td>
                        </tr>
                <?php } } }?>
            </tbody>
        </table>
    </div>
</section>