<?php
@session_start();
class Order{
    public static function guardarOrden($items,$prodmonthhome,$orderhome,$dealerhome,$estadoOrden,$typeorderhome,$porthome){
        include_once( '../config/init_db.php' );
        try {
            $NumOrder = DB::queryFirstRow("SELECT count(*) as cantidad FROM ots_ordenes WHERE dealerid = $dealerhome AND calendarioid = ".$prodmonthhome);
            $Sigla = DB::queryFirstRow("SELECT siglaorden FROM ots_dealers WHERE dealerid = ".$dealerhome);
            $MesAno = DB::queryFirstRow("SELECT mesano FROM ots_calendarios WHERE calendarioid = ".$prodmonthhome);
            if( isset($NumOrder['cantidad']) && isset($Sigla['siglaorden']) ){
                $num = $NumOrder['cantidad'] + 1;
                $orderhome = $Sigla['siglaorden'].'-'.$MesAno['mesano'].'-'.$num;
            }else{
                $orderhome = "NA";
            }
            $totUnidades = 0;
            $totValores = 0;
            DB::insert('ots_ordenes', array(
                'dealerid'      => $dealerhome,
                'calendarioid'  => $prodmonthhome,
                'estadoordenid' => $estadoOrden,
                'tipoordenid'   => $typeorderhome,
                'puertoid'      => $porthome,
                'codigo'        => $orderhome,
                'unidades'      => $totUnidades,
                'total'         => $totValores,
                'creacion'      => DB::sqleval("NOW()"),
                'actualizacion' => DB::sqleval("NOW()"),
                'usuarioid'     => $_SESSION['UsrData']['usuarioid']
            ));
            $ordenId = DB::insertId();

            $items = json_decode($_POST['items'], true);
            foreach ($items as $item) {
                /*Consultar los datos del item x completo*/
                $partes = explode('-', $item['itemselect']);
                $itemid = $partes[0];
                $color = $partes[1];
                $itemData = DB::queryFirstRow("SELECT * FROM ots_items WHERE itemid = ".$itemid);
                $colorData = DB::queryFirstRow("SELECT colorid FROM ots_colores WHERE color = ".$color);
                $colorid = $colorData['colorid'];
                $totUnidades = $totUnidades + $item['qtyoptadd'];
                $totValores = $totValores + ($item['qtyoptadd']*$itemData['unitpricecif']);
                DB::insert('ots_ordendetalles', array(
                    'ordenid'       => $ordenId,
                    'itemid'        => $itemid,
                    'item'          => $itemData['item'],
                    'opcionbody'    => $item['bodyoptadd'],
                    'modelo'        => $itemData['modelname'],
                    'ezspeccode'    => $itemData['ezspeccode'],
                    'anomodelo'     => $itemData['salesyear'],
                    'color'         => $colorid,
                    'preciodolar'   => $itemData['unitpriceusd'],
                    'preciocif'     => $itemData['unitpricecif'],
                    'cantidad'      => $item['qtyoptadd'],
                    'noorden'       => $item['ordernoadd'],
                    'asignacion'    => $item['allocationadd'],
                    'respuesta'     => $item['backgroundadd'],
                    'precio'        => $item['qtyoptadd']*$itemData['unitpricecif']
                ));
                /*Consultar los datos del item x completo*/
            }
                DB::insert('ots_trazabilidades', array(
                    'ordenid'       => $ordenId,
                    'usuarioid'     => $_SESSION['UsrData']['usuarioid'],
                    'accion'        => "CREATE THE ORDER ".$orderhome,
                    'icono'         => "fa-plus",
                    'estadoordenid' => $estadoOrden,
                    'creacion'      => DB::sqleval("NOW()")
                ));
            DB::update('ots_ordenes', array(
                    'unidades' => $totUnidades,
                    'total' => $totValores
                ), "ordenid=%s", $ordenId);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Allready']);
            exit;
        } catch (MeekroDBException $e) {
            //error_log('Error en guardar_items.php: ' . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Error detail: ' . $e->getMessage()]);
            exit;
        }
    }
    public static function actualizarOrden($items,$prodmonthhome,$orderhome,$dealerhome,$estadoOrden,$typeorderhome,$porthome,$ordenid){
        include_once( '../config/init_db.php' );
         try {
            
            $totUnidades = 0;
            $totValores = 0;
            DB::delete('ots_ordendetalles', 'ordenid=%s', $ordenid);

            $items = json_decode($_POST['items'], true);
            foreach ($items as $item) {
                /*Consultar los datos del item x completo*/
                $partes = explode('-', $item['itemselect']);
                $itemid = $partes[0];
                $color = $partes[1];
                $itemData = DB::queryFirstRow("SELECT * FROM ots_items WHERE itemid = ".$itemid);
                $colorData = DB::queryFirstRow("SELECT colorid FROM ots_colores WHERE color = ".$color);
                $colorid = $colorData['colorid'];
                $totUnidades = $totUnidades + $item['qtyoptadd'];
                $totValores = $totValores + ($item['qtyoptadd']*$itemData['unitpricecif']);
                DB::insert('ots_ordendetalles', array(
                    'ordenid'       => $ordenid,
                    'itemid'        => $itemid,
                    'item'          => $itemData['item'],
                    'opcionbody'    => $item['bodyoptadd'],
                    'modelo'        => $itemData['modelname'],
                    'ezspeccode'    => $itemData['ezspeccode'],
                    'anomodelo'     => $itemData['salesyear'],
                    'color'         => $colorid,
                    'preciodolar'   => $itemData['unitpriceusd'],
                    'preciocif'     => $itemData['unitpricecif'],
                    'cantidad'      => $item['qtyoptadd'],
                    'noorden'       => $item['ordernoadd'],
                    'asignacion'    => $item['allocationadd'],
                    'respuesta'     => $item['backgroundadd'],
                    'precio'        => $item['qtyoptadd']*$itemData['unitpricecif']
                ));
                /*Consultar los datos del item x completo*/
            }
            DB::insert('ots_trazabilidades', array(
                    'ordenid'       => $ordenid,
                    'usuarioid'     => $_SESSION['UsrData']['usuarioid'],
                    'accion'        => "UPDATE THE ORDER ".$orderhome,
                    'icono'         => "fa-pencil",
                    'estadoordenid' => $estadoOrden,
                    'creacion'      => DB::sqleval("NOW()")
            ));
            DB::update('ots_ordenes', array(
                    'unidades' => $totUnidades,
                    'total' => $totValores,
                    'estadoordenid' => $estadoOrden,
                    'actualizacion' => DB::sqleval("NOW()")
                ), "ordenid=%s", $ordenid);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Allready']);
            exit;
        } catch (MeekroDBException $e) {
            //error_log('Error en guardar_items.php: ' . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Error detail: ' . $e->getMessage()]);
            exit;
        }
    }
    public static function enviaMensaje($statusid,$note,$visible,$ordenid,$orderhome){
        include_once( '../config/init_db.php' );
         try {
            DB::insert('ots_ordennotas', array(
                    'ordenid'       => $ordenid,
                    'usuarioid'     => $_SESSION['UsrData']['usuarioid'],
                    'nota'          => $note,
                    'visible'       => $visible,
                    'estadoordenid' => $statusid,
                    'creacion'      => DB::sqleval("NOW()")
            ));
            DB::insert('ots_trazabilidades', array(
                    'ordenid'       => $ordenid,
                    'usuarioid'     => $_SESSION['UsrData']['usuarioid'],
                    'accion'        => "UPDATE THE ORDER WHIT MESSAGE ".$orderhome,
                    'icono'         => "fa-pencil",
                    'estadoordenid' => $statusid,
                    'creacion'      => DB::sqleval("NOW()")
            ));
            DB::update('ots_ordenes', array(
                    'estadoordenid' => $statusid,
                    'actualizacion' => DB::sqleval("NOW()")
                ), "ordenid=%s", $ordenid);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Allready']);
            exit;
        } catch (MeekroDBException $e) {
            //error_log('Error en guardar_items.php: ' . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Error detail: ' . $e->getMessage()]);
            exit;
        }
    }
    public static function borrarOrden($ordenid){
        include_once( '../config/init_db.php' );
         try {
            DB::insert('ots_trazabilidades', array(
                    'ordenid'       => $ordenid,
                    'usuarioid'     => $_SESSION['UsrData']['usuarioid'],
                    'accion'        => "DELETE THE ORDER",
                    'icono'         => "fa-erraser",
                    'estadoordenid' => 8,
                    'creacion'      => DB::sqleval("NOW()")
            ));
            DB::update('ots_ordenes', array(
                    'estadoordenid' => 8,
                    'actualizacion' => DB::sqleval("NOW()")
                ), "ordenid=%s", $ordenid);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Allready']);
            exit;
        } catch (MeekroDBException $e) {
            //error_log('Error en guardar_items.php: ' . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Error detail: ' . $e->getMessage()]);
            exit;
        }
    }
    public static function traerMensajes($orderid){
        include_once( '../config/init_db.php' );    
        $sql = "SELECT n.ordennotaid, n.nota, n.visible, n.creacion, e.estadoorden, e.color, u.nombres, n.leido, n.lectorid, n.usuarioid
                FROM ots_ordennotas n, ots_usuarios u, ots_estadosorden e
                WHERE n.ordenid = $orderid AND e.estadoordenid<8 AND n.estadoordenid = e.estadoordenid AND n.usuarioid = u.usuarioid
                ORDER BY n.creacion DESC";
        $listadoEstados = DB::query($sql);
        return $listadoEstados;
    }
    public static function marcarMensaje($ordennotaid){
        include_once( '../config/init_db.php' );
         try {
            DB::update('ots_ordennotas', array(
                    'leido'     => "1",
                    'lectorid'  => $_SESSION['UsrData']['usuarioid'],
                    'fechaleido'=> DB::sqleval("NOW()")
            ), "ordennotaid=%s", $ordennotaid);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Allready']);
            exit;
        } catch (MeekroDBException $e) {
            //error_log('Error en guardar_items.php: ' . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Error detail: ' . $e->getMessage()]);
            exit;
        }
    }
}