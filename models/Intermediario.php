<?php
class Intermediario
{
    public static function listar()
    {
        include_once('../config/init_db.php');
        $sql = "SELECT * FROM ots_intermediario";
        $lista = DB::query($sql);
        return $lista;
    }
}