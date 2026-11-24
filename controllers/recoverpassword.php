<?php
/**
 * controllers/recoverpassword.php
 * Pure JSON API — called via fetch() from admin/recoverpassword.php (no redirects).
 *
 * Sessions used:
 *   $_SESSION['recovery_pending']      = ['userid','email','masked_email','rolid','name']
 *   $_SESSION['recovery_otp_verified'] = ['userid','email']
 */

@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/Profile.php';
require_once __DIR__ . '/../controllers/mailer.php';

Profile::ensureRecoveryOtpTable();

function rpJsonOut(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rpJsonOut(['ok' => false, 'msg' => 'Method not allowed.']);
}

$action = trim($_POST['action'] ?? '');

// ─────────────────────────────────────────────────────────────────
// Step 1 – submit email
// ─────────────────────────────────────────────────────────────────
if ($action === 'request_recovery_otp') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        rpJsonOut(['ok' => false, 'msg' => 'Please enter a valid email address.']);
    }

    $user = Profile::findUserByEmail($email);

    if (!$user) {
        rpJsonOut(['ok' => false, 'msg' => 'No account found with that email address.']);
    }

    $otp = random_int(100000, 999999);
    Profile::invalidatePendingRecoveryOtps((int)$user['usuarioid']);
    Profile::createRecoveryOtp((int)$user['usuarioid'], $otp);

    $userRoles = Profile::getRolesByUserId((int)$user['usuarioid']);
    if (empty($userRoles)) {
        $userRoles = [(int)$user['rolid']];
    }

    $result = sendPasswordRecoveryEmail(
        (string)$user['email'],
        (string)$user['nombres'],
        $otp,
        $userRoles
    );

    if ($result !== true) {
        rpJsonOut(['ok' => false, 'msg' => 'Could not send the recovery email. Please try again later.']);
    }

    $parts   = explode('@', (string)$user['email'], 2);
    $local   = $parts[0];
    $domain  = $parts[1] ?? '';
    $visible = substr($local, 0, min(2, strlen($local)));
    $masked  = $visible . str_repeat('*', max(0, strlen($local) - 2)) . '@' . $domain;

    $_SESSION['recovery_pending'] = [
        'userid'       => (int)$user['usuarioid'],
        'email'        => (string)$user['email'],
        'masked_email' => $masked,
        'roles'        => $userRoles,
        'name'         => (string)$user['nombres'],
    ];

    $remaining = Profile::getActiveRecoveryOtpRemainingSeconds((int)$user['usuarioid']);

    rpJsonOut([
        'ok'           => true,
        'masked_email' => $masked,
        'remaining'    => $remaining,
        'msg'          => 'A verification code has been sent to ' . $masked . '.',
    ]);
}

// ─────────────────────────────────────────────────────────────────
// Resend OTP
// ─────────────────────────────────────────────────────────────────
if ($action === 'resend_recovery_otp') {
    if (empty($_SESSION['recovery_pending'])) {
        rpJsonOut(['ok' => false, 'msg' => 'Session expired. Please start over.', 'restart' => true]);
    }

    $pending = $_SESSION['recovery_pending'];
    $otp     = random_int(100000, 999999);
    Profile::invalidatePendingRecoveryOtps((int)$pending['userid']);
    Profile::createRecoveryOtp((int)$pending['userid'], $otp);

    $roles = $pending['roles'] ?? null;
    if (empty($roles) || !is_array($roles)) {
        $roles = [(int)$pending['rolid']];
    }

    $result = sendPasswordRecoveryEmail(
        (string)$pending['email'],
        (string)$pending['name'],
        $otp,
        $roles
    );

    if ($result !== true) {
        rpJsonOut(['ok' => false, 'msg' => 'Could not resend the email. Please try again.']);
    }

    $remaining = Profile::getActiveRecoveryOtpRemainingSeconds((int)$pending['userid']);

    rpJsonOut([
        'ok'        => true,
        'remaining' => $remaining,
        'msg'       => 'A new code has been sent to ' . $pending['masked_email'] . '.',
    ]);
}

// ─────────────────────────────────────────────────────────────────
// Step 2 – verify OTP
// ─────────────────────────────────────────────────────────────────
if ($action === 'verify_recovery_otp') {
    if (empty($_SESSION['recovery_pending'])) {
        rpJsonOut(['ok' => false, 'msg' => 'Session expired. Please start over.', 'restart' => true]);
    }

    $pending = $_SESSION['recovery_pending'];
    $otpIn   = (int)trim($_POST['otp'] ?? '0');
    $record  = Profile::getValidRecoveryOtp((int)$pending['userid'], $otpIn);

    if (!$record) {
        rpJsonOut(['ok' => false, 'msg' => 'Invalid or expired verification code. Please try again.']);
    }

    Profile::markRecoveryOtpUsed((int)$record['otpid']);
    unset($_SESSION['recovery_pending']);

    $_SESSION['recovery_otp_verified'] = [
        'userid' => (int)$pending['userid'],
        'email'  => (string)$pending['email'],
    ];

    rpJsonOut(['ok' => true, 'msg' => 'Code verified. Please set your new password.']);
}

// ─────────────────────────────────────────────────────────────────
// Step 3 – set new password
// ─────────────────────────────────────────────────────────────────
if ($action === 'set_new_password') {
    if (empty($_SESSION['recovery_otp_verified'])) {
        rpJsonOut(['ok' => false, 'msg' => 'Session expired. Please start over.', 'restart' => true]);
    }

    $verified = $_SESSION['recovery_otp_verified'];
    $pass1    = $_POST['newpassword']     ?? '';
    $pass2    = $_POST['confirmpassword'] ?? '';

    $hasMinLength = strlen($pass1) >= 10;
    $hasUppercase = (bool)preg_match('/[A-Z]/', $pass1);
    $hasLowercase = (bool)preg_match('/[a-z]/', $pass1);
    $hasNumber = (bool)preg_match('/\d/', $pass1);
    $hasSymbol = (bool)preg_match('/[^A-Za-z0-9]/', $pass1);

    if (!($hasMinLength && $hasUppercase && $hasLowercase && $hasNumber && $hasSymbol)) {
        rpJsonOut(['ok' => false, 'msg' => 'Password must be at least 10 characters and include uppercase, lowercase, number, and symbol.']);
    }

    if ($pass1 !== $pass2) {
        rpJsonOut(['ok' => false, 'msg' => 'Passwords do not match. Please try again.']);
    }

    $hash = password_hash($pass1, PASSWORD_DEFAULT);
    $ok   = Profile::updatePasswordHash((int)$verified['userid'], $hash);
    unset($_SESSION['recovery_otp_verified']);

    if (!$ok) {
        rpJsonOut(['ok' => false, 'msg' => 'Could not update the password. Please contact your administrator.']);
    }

    rpJsonOut(['ok' => true, 'msg' => 'Your password has been updated successfully.', 'redirect' => 'login.php?recovery=1']);
}

rpJsonOut(['ok' => false, 'msg' => 'Unknown action.']);
