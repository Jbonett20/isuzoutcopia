<?php
@session_start();
class ListaItems {
    public static function consultarItems($dealer,$periodo){
        include_once( '../config/init_db.php' );
        $sql = "SELECT i.dealerid, i.item, i.modelname, i.ezspeccode, i.salesyear, i.remarks, i.unitpriceusd, i.unitpricecif, i.minqtyord, o.color, o.rgb, CONCAT(c.ano,'-',c.mes) as mescal, t.tipoorden, s.puerto
                    FROM ots_items i, ots_dealers d, ots_calendarios c, ots_puertos p, ots_tipoordenes t, ots_coloritem ci, ots_colores o, ots_puertos s
                    WHERE i.dealerid = $dealer AND i.calendarioid = $periodo AND i.dealerid = d.dealerid AND i.calendarioid = c.calendarioid AND i.puertoid = p.puertoid AND i.tipoordenid = t.tipoordenid AND i.itemid = ci.itemid AND ci.colorid = o.colorid AND i.puertoid = s.puertoid 
                    ORDER BY i.item, i.modelname, o.color";
        $listadoItems = DB::query($sql);
        return $listadoItems;
    }
    public static function consultarExistOrdes($dealerid,$calendarioid,$typeorderid,$portid,$status){
        include_once( '../config/init_db.php' );
        $sql = "SELECT o.ordenid, o.codigo, o.unidades, o.total, o.creacion, o.actualizacion, u.nombres, c.ano, c.mes, c.mesano, d.dealer, a.pais, a.bandera, e.estadoorden, e.color, t.tipoorden, p.puerto
                    FROM ots_ordenes o, ots_calendarios c, ots_dealers d, ots_estadosorden e, ots_tipoordenes t, ots_puertos p, ots_paises a, ots_usuarios u
                    WHERE o.estadoordenid<8 AND o.dealerid = d.dealerid AND o.calendarioid = c.calendarioid AND d.paisid = a.paisid AND o.estadoordenid = e.estadoordenid AND o.tipoordenid = t.tipoordenid AND o.puertoid = p.puertoid AND o.usuarioid = u.usuarioid ";
        if($dealerid!="0"){
            $sql .= " AND o.dealerid = $dealerid ";
        }
        if($calendarioid!="0"){
            $sql .= " AND o.calendarioid = $calendarioid ";
        }
        if($typeorderid!="0"){
            $sql .= " AND o.tipoordenid = $typeorderid ";
        }
        if($portid!="0"){
            $sql .= " AND o.puertoid = $portid ";
        }
        if($status!="0"){
            $sql .= " AND o.estadoordenid = $status ";
        }
        $sql .= "ORDER BY o.codigo";
        $listadoItems = DB::query($sql);
        return $listadoItems;
    }
    public static function ConsultaMensajesPend(){
        include_once( '../config/init_db.php' );
        if(userHasAnyRole(['1', '4'])){
            $pendientes = DB::query("SELECT n.*, o.ordenid, o.codigo FROM ots_ordennotas n, ots_ordenes o WHERE n.leido = 0 AND o.ordenid = n.ordenid AND n.usuarioid NOT IN (SELECT usuarioid FROM adm_usuarioroles WHERE rolid IN (1,4))");
        }else{
            $dealerid = $_SESSION['UsrData']['dealerid'];
            $pendientes = DB::query("SELECT n.*, o.ordenid, o.codigo FROM ots_ordennotas n, ots_ordenes o WHERE n.leido = 0 AND n.visible = 1 AND o.ordenid = n.ordenid AND o.dealerid = ".$_SESSION['UsrData']['dealerid']." AND n.usuarioid IN (SELECT aur.usuarioid FROM adm_usuarioroles aur WHERE aur.rolid IN (1,4));");
        }
        return $pendientes;
    }
}
