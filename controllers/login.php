<?php
ob_start();
@session_start();

if (isset($_POST['txtemail']) && isset($_POST['txtpassword'])) {
    include_once("../models/login.php");
    $login = Login::ingresarUsuario($_POST);

    if (isset($login['otp_sent']) && $login['otp_sent'] === true) {
        /* Credentials OK – OTP sent – go to verification page */
        $_SESSION['otp_masked_email'] = $login['masked_email'];
        header("location: ../admin/verify_otp.php");
        exit;
    } else {
        /* Credentials failed */
        $_SESSION['login_error_flash'] = $login['error'] ?? 'The username or password is incorrect, please check and try again.';
        header("location: ../admin/login.php?login_error=1");
        exit;
    }
}

if (isset($_GET['salir'])) {
    include_once("../models/login.php");
    Login::salirUsuario();
}
