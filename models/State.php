<?php
class State
{
    public static function listar()
    {
        include_once('../config/init_db.php');
        $sql = "SELECT * FROM ots_estados";
        $lista = DB::query($sql);
        return $lista;
    }
}