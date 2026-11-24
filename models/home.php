<?php
@session_start();
class Home{
    public static function consultarEstados(){
        include_once( '../config/init_db.php' );
        $sql = "SELECT * FROM ots_estadosorden WHERE estadoid=1 AND estadoordenid<8 ORDER BY orden";
        $listadoEstados = DB::query($sql);
        return $listadoEstados;
    }
    public static function consultarEstadosAll(){
        include_once( '../config/init_db.php' );
        $sql = "SELECT * FROM ots_estadosorden WHERE estadoordenid<8 ORDER BY orden";
        $listadoEstados = DB::query($sql);
        return $listadoEstados;
    }
    public static function consultarMeses(){
        include_once( '../config/init_db.php' );
        $sql = "SELECT calendarioid, CONCAT(ano,' ',mes) as mes,  inicia, finaliza
                FROM ots_calendarios WHERE estadoid = 1 ORDER BY ano,mesnum";
        $listadoEstados = DB::query($sql);
        return $listadoEstados;
    }
    public static function consultarDealers(){
        include_once( '../config/init_db.php' );
        $sql = "SELECT d.dealerid, CONCAT(d.dealer, ' (', p.pais,')') AS dealer, p.bandera
                    FROM ots_dealers d, ots_paises p
                    WHERE d.paisid = p.paisid AND d.estadoid = 1
                    ORDER BY d.dealer, p.pais;";
        $listadoEstados = DB::query($sql);
        return $listadoEstados;
    }
    public static function consultarOrdenes($dealer,$periodo){
        include_once( '../config/init_db.php' );
        $sql = "SELECT o.ordenid, o.codigo, o.unidades, o.total, o.creacion, o.actualizacion, o.estadoordenid, u.nombres, d.dealer, c.ano, c.mes, c.inicia, c.finaliza, p.puerto, t.tipoorden, pa.pais, pa.bandera
                    FROM ots_ordenes o, ots_dealers d, ots_calendarios c, ots_puertos p, ots_tipoordenes t, ots_usuarios u, ots_paises pa
                    WHERE o.dealerid = d.dealerid AND 
                    o.calendarioid = c.calendarioid AND 
                    o.puertoid = p.puertoid AND 
                    o.tipoordenid = t.tipoordenid AND 
                    o.usuarioid = u.usuarioid AND 
                    d.paisid = pa.paisid";
        if($dealer <> 0 ){
            $sql .= " AND o.dealerid=".$dealer;
        }
        if($periodo <> 0 ){
            $sql .= " AND o.calendarioid=".$periodo;
        }
        $sql .= " ORDER BY c.ano, c.mes, o.codigo";
        $listadoEstados = DB::query($sql);
        return $listadoEstados;
    }
    public static function consultaDetalle($ordenid){
        include_once( '../config/init_db.php' );
        $detalleorden = DB::query("SELECT od.*, c.ano, c.mes, r.color as colord, r.rgb FROM ots_ordendetalles od, ots_ordenes o, ots_calendarios c, ots_colores r WHERE od.ordenid = $ordenid AND od.ordenid = o.ordenid AND o.calendarioid = c.calendarioid AND od.color = r.colorid ORDER BY od.detalleid");
        return $detalleorden;
    }
    public static function consultarTiposOrdenes($dealerid){
        if($dealerid==0){
            if(isset($_SESSION['UsrData']) && $_SESSION['UsrData']['dealerid']>0){
                $dealerid = $_SESSION['UsrData']['dealerid'];
            }
        }
        include_once( '../config/init_db.php' );
        $sql = "SELECT * FROM ots_tipoordenes WHERE dealerid = ".$dealerid." AND estadoid=1 ORDER BY tipoorden";
        $listadoEstados = DB::query($sql);
        return $listadoEstados;
    }
    public static function consultarPuertos($dealerid){
        if($dealerid==0){
            if(isset($_SESSION['UsrData']) && $_SESSION['UsrData']['dealerid']>0){
                $dealerid = $_SESSION['UsrData']['dealerid'];
            }
        }
        include_once( '../config/init_db.php' );
        $sql = "SELECT * FROM ots_puertos WHERE dealerid = ".$dealerid." AND estadoid=1 ORDER BY puerto";
        $listadoEstados = DB::query($sql);
        return $listadoEstados;
    }
    public static function consultarOrden($ordenid){
        include_once( '../config/init_db.php' );
        $orden = DB::queryFirstRow("SELECT o.*, e.estadoorden, e.color
                                    FROM ots_ordenes o, ots_estadosorden e
                                    WHERE o.ordenid = $ordenid AND o.estadoordenid = e.estadoordenid");
        return $orden;
    }
    public static function ConsultaCantInactivos(){
        include_once( '../config/init_db.php' );
        $orden = DB::queryFirstRow("SELECT count(*) as cant FROM `ots_usuarios` WHERE estadoid = 2 AND ultimaconexion IS NULL");
        return $orden;
    }
    public static function ConsultaMensajesPend(){
        include_once( '../config/init_db.php' );
        if(userHasAnyRole(['1', '4'])){
            $pendientes = DB::query("SELECT n.*, o.ordenid, o.codigo FROM ots_ordennotas n, ots_ordenes o  WHERE o.estadoordenid <8 AND n.leido = 0 AND o.ordenid = n.ordenid AND n.usuarioid NOT IN (SELECT usuarioid FROM adm_usuarioroles WHERE rolid IN (1,4))");
        }else{
            $dealerid = $_SESSION['UsrData']['dealerid'];
            $pendientes = DB::query("SELECT n.*, o.ordenid, o.codigo FROM ots_ordennotas n, ots_ordenes o WHERE o.estadoordenid <8 AND n.leido = 0 AND n.visible = 1 AND o.ordenid = n.ordenid AND o.dealerid = ".$_SESSION['UsrData']['dealerid']." AND n.usuarioid IN (SELECT aur.usuarioid FROM adm_usuarioroles aur WHERE aur.rolid IN (1,4));");
        }
        return $pendientes;
    }
}
