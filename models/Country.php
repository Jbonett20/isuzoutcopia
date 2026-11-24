<?php
class Country
{
    public static function listar()
    {
        include_once('../config/init_db.php');
        $sql = "SELECT * FROM ots_paises where estadoid = 1";
        $lista = DB::query($sql);
        return $lista;
    }
}