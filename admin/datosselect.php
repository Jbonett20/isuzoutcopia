<?php
//include_once("../models/home.php");
include_once '../admin/src/seguridad.php';
include_once( '../config/init_db.php' );
        
if(isset($_GET['ctrl'])){
    if($_GET['ctrl']=="tipoordenes"){
        $dealerid = $_GET['dealerid'];
        $sql = "SELECT tipoordenid, tipoorden FROM ots_tipoordenes WHERE estadoid=1 AND dealerid = ".$dealerid." ORDER BY tipoorden";
        $listadoCompleto = DB::query($sql);
        $data = [];
        foreach($listadoCompleto as $lista){ 
            $data[] = [
                "id" => $lista['tipoordenid'],
                "text" => $lista['tipoorden']
            ];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }
    if($_GET['ctrl']=="puertos"){
        $dealerid = $_GET['dealerid'];
        $sql = "SELECT puertoid, puerto FROM ots_puertos WHERE estadoid=1 AND dealerid = ".$dealerid." ORDER BY puerto";
        $listadoCompleto = DB::query($sql);
        $data = [];
        foreach($listadoCompleto as $lista){ 
            $data[] = [
                "id" => $lista['puertoid'],
                "text" => $lista['puerto']
            ];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }
    if($_GET['ctrl']=="codorden"){
        $mes = $_GET['month'];
        $dealer = $_GET['dealerid'];
        $NumOrder = DB::queryFirstRow("SELECT count(*) as cantidad FROM ots_ordenes WHERE dealerid = $dealer AND calendarioid = ".$mes);
        $Sigla = DB::queryFirstRow("SELECT siglaorden FROM ots_dealers WHERE dealerid = ".$dealer);
        $MesAno = DB::queryFirstRow("SELECT mesano FROM ots_calendarios WHERE calendarioid = ".$mes);
        if( isset($NumOrder['cantidad']) && isset($Sigla['siglaorden']) ){
            $num = $NumOrder['cantidad'] + 1;
            echo($Sigla['siglaorden'].'-'.$MesAno['mesano'].'-'.$num);
        }else{
            echo("NA");
        }
    }
    if($_GET['ctrl']=="busitem"){
        $calendarioid = $_GET['month'];
        $dealerid = $_GET['dealerid'];
        $tipoordenid = $_GET['tipoordenid'];
        $puertoid = $_GET['puertoid'];
        $busqueda = $_GET['q'];
        $sql = "SELECT i.*, c.color, c.rgb, c.colorid
                FROM ots_items i, ots_coloritem ci, ots_colores c
                WHERE i.dealerid = $dealerid AND i.calendarioid = $calendarioid AND i.tipoordenid = $tipoordenid AND i.puertoid = $puertoid AND i.itemid = ci.itemid AND ci.colorid = c.colorid
                AND (i.item like '%$busqueda%' OR i.modelname like '%$busqueda%' OR i.ezspeccode like '%$busqueda%' OR c.color like '%$busqueda%')
                ORDER BY i.item, i.modelname, i.ezspeccode";
        $listadoCompleto = DB::query($sql);
        $resultados = [];
        foreach($listadoCompleto as $lista){ 
            $resultados[] = [
                "id" => $lista['itemid'].'-'.$lista['color'],
                "text" => " &nbsp;".$lista['color']." ".$lista['item'],
                "imagen" => "<i class='fa fa-truck' style='color: ".$lista['rgb']." !important'> </i>",
                "color" => $lista['color'],
                "rgb" => $lista['rgb']
            ];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultados);
    }
    
}else if(isset($_POST['opcn'])){
    @session_start();

    if($_POST['opcn']=="detitem"){
        $item = explode("-",$_POST['item']);
        if(userHasRole('1')){
            $DetItem = DB::queryFirstRow("SELECT i.modelname, i.salesyear, i.ezspeccode, i.unitpriceusd, i.unitpricecif, i.remarks FROM ots_items i WHERE i.itemid = ".$item[0]);
            if(isset($DetItem['modelname'])){
                $resultado[] = [
                    "itemid"        => $item[0],
                    "color"         => $item[1],
                    "modelname"     => $DetItem['modelname'].' | <span style="color:#F40000;">'.$DetItem['ezspeccode'].'</span> | <span style="color:#2D8515;">'.$DetItem['salesyear'].'</span>',
                    "unitpriceusd"  => $DetItem['unitpriceusd'],
                    "unitpricecif"  => $DetItem['unitpricecif'],
                    "remarks"       => $DetItem['remarks']
                ];
            }else{
                $resultado[] = [
                    "itemid"        => $item[0],
                    "color"         => $item[1],
                    "modelname"     => "",
                    "unitpriceusd"  => "",
                    "unitpricecif"  => "",
                    "remarks"       => ""
                ];
            }
        }else{
            $DetItem = DB::queryFirstRow("SELECT i.modelname, i.salesyear, i.ezspeccode, i.unitpricecif, i.remarks FROM ots_items i WHERE i.itemid = ".$item[0]);
            if(isset($DetItem['modelname'])){
                $resultado[] = [
                    "itemid"        => $item[0],
                    "color"         => $item[1],
                    "modelname"     => $DetItem['modelname'].' | <span style="color:#F40000;">'.$DetItem['ezspeccode'].'</span> | <span style="color:#2D8515;">'.$DetItem['salesyear'].'</span>',
                    "unitpricecif"  => $DetItem['unitpricecif'],
                    "remarks"       => $DetItem['remarks']
                ];
            }else{
                $resultado[] = [
                    "itemid"        => $item[0],
                    "color"         => $item[1],
                    "modelname"     => "",
                    "unitpricecif"  => "",
                    "remarks"       => ""
                ];
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado);
    }
}