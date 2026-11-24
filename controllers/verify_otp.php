<?php
ob_start();
@session_start();

if (!isset($_SESSION['otp_pending'])) {
    header("location: ../admin/login.php");
    exit;
}

include_once("../config/init_db.php");

$pendingUsuarioId = (int) $_SESSION['otp_pending']['usuarioid'];

$activeOtp = DB::queryFirstRow(
    "SELECT fechacreacion
     FROM adm_otp
     WHERE usuarioid = %i AND estadoid = 1
     ORDER BY otpid DESC
     LIMIT 1",
    $pendingUsuarioId
);

$otpRemainingSeconds = 0;

if ($activeOtp && !empty($activeOtp['fechacreacion'])) {

    $createdTs = strtotime($activeOtp['fechacreacion']);

    if ($createdTs !== false) {

        $elapsed = time() - $createdTs;

        $otpRemainingSeconds = max(0, 7200 - $elapsed);
    }
}

$_SESSION['otp_remaining_seconds'] = $otpRemainingSeconds;

if (isset($_POST['otp_code'])) {

    include_once("../models/login.php");
    include_once("../models/Permission.php");

    $otpInput = trim($_POST['otp_code']);

    $result = Login::verificarOtp($otpInput);

    if (isset($result['success']) && $result['success'] === true) {

      
        header("location: ../admin/home");

    } else {

        $_SESSION['otp_error'] =
            $result['error']
            ?? 'The verification code is incorrect.';

        header("location: ../admin/verify_otp.php");
        exit;
    }
}

if (isset($_POST['resend_otp'])) {

    include_once("../models/login.php");
    include_once(__DIR__ . "/mailer.php");
    include_once(__DIR__ . "/../models/Profile.php");

    $pending = $_SESSION['otp_pending'];

    DB::update(
        'adm_otp',
        ['estadoid' => 2],
        "usuarioid=%i AND estadoid=1",
        $pending['usuarioid']
    );

    $newOtp = random_int(100000, 999999);

    DB::insert('adm_otp', [
        'otp'           => $newOtp,
        'usuarioid'     => $pending['usuarioid'],
        'fechacreacion' => date('Y-m-d H:i:s'),
        'estadoid'      => 1
    ]);

    $userRoles = Profile::getRolesByUserId(
        (int) $pending['usuarioid']
    );

    $sendResult = sendOtpEmail(
        $pending['email'],
        $pending['nombres'],
        $newOtp,
        $userRoles
    );

    if ($sendResult === true) {

        $_SESSION['otp_info'] =
            'A new verification code has been sent to your email.';

    } else {

        $_SESSION['otp_error'] =
            'The verification code could not be resent. Please try again.';
    }

    $masked = Login::maskEmail($pending['email']);

    $_SESSION['otp_masked_email'] = $masked;

    header("location: ../admin/verify_otp.php");
    exit;
}