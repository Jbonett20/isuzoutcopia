<?php
class Rol
{
    public static function listar()
    {
        include_once('../config/init_db.php');
        $sql = "SELECT * FROM ots_roles";
        $lista = DB::query($sql);
        return $lista;
    }
}