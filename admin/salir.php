<?php
@session_start();
include_once("../controllers/login.php");
unset($_SESSION['UsrData']);
header("location: ../");