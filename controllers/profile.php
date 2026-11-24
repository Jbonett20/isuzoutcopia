<?php
include_once __DIR__ . '/../admin/src/seguridad.php';
include_once __DIR__ . '/../models/Profile.php';
include_once __DIR__ . '/../models/login.php';
include_once __DIR__ . '/mailer.php';

@session_start();

$currentUserId = (int)($_SESSION['UsrData']['usuarioid'] ?? 0);

if ($currentUserId < 1) {
    header('location: ../admin/login.php');
    exit;
}

function profileSetFlash(string $type, string $message): void
{
    $_SESSION['profile_flash'] = ['type' => $type, 'message' => $message];
}

function profileRedirect(): void
{
    header('location: ../admin/myprofile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        Profile::ensurePasswordOtpTable();
    } catch (Throwable $e) {
        profileSetFlash('danger', 'Password OTP storage is not available. Please contact support.');
        profileRedirect();
    }

    if ($action === 'request_password_otp') {
        $currentPassword = trim((string)($_POST['current_password'] ?? ''));
        $newPassword     = trim((string)($_POST['new_password'] ?? ''));
        $confirmPassword = trim((string)($_POST['confirm_password'] ?? ''));

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            profileSetFlash('danger', 'All password fields are required.');
            profileRedirect();
        }

        if ($newPassword !== $confirmPassword) {
            profileSetFlash('danger', 'New password and confirmation do not match.');
            profileRedirect();
        }

        $hasMinLength = strlen($newPassword) >= 10;
        $hasUppercase = (bool)preg_match('/[A-Z]/', $newPassword);
        $hasLowercase = (bool)preg_match('/[a-z]/', $newPassword);
        $hasNumber = (bool)preg_match('/\d/', $newPassword);
        $hasSymbol = (bool)preg_match('/[^A-Za-z0-9]/', $newPassword);

        if (!($hasMinLength && $hasUppercase && $hasLowercase && $hasNumber && $hasSymbol)) {
            profileSetFlash('danger', 'New password must be at least 10 characters and include uppercase, lowercase, number, and symbol.');
            profileRedirect();
        }

        if (!Profile::verifyCurrentPassword($currentUserId, $currentPassword)) {
            profileSetFlash('danger', 'Current password is incorrect.');
            profileRedirect();
        }

        $profileData = Profile::getByUserId($currentUserId);
        if (!$profileData) {
            profileSetFlash('danger', 'User profile not found.');
            profileRedirect();
        }

        $otpCode = random_int(100000, 999999);
        Profile::invalidatePendingPasswordOtps($currentUserId);
        Profile::createPasswordOtp($currentUserId, $otpCode);

        $userRoles = Profile::getRolesByUserId($currentUserId);
        if (empty($userRoles)) {
            $userRoles = $profileData['roles'] ?? [];
        }

        $sendResult = sendPasswordChangeOtpEmail(
            (string)$profileData['email'],
            (string)$profileData['nombres'],
            $otpCode,
            $userRoles
        );

        if ($sendResult !== true) {
            profileSetFlash('danger', 'Failed to send verification code. Please try again.');
            profileRedirect();
        }

        $_SESSION['pwd_change_pending'] = [
            'usuarioid'          => $currentUserId,
            'new_password_hash'  => password_hash($newPassword, PASSWORD_DEFAULT),
            'masked_email'       => Login::maskEmail((string)$profileData['email']),
        ];

        profileSetFlash('info', 'A verification code was sent to your email address.');
        profileRedirect();
    }

    if ($action === 'resend_password_otp') {
        $pending = $_SESSION['pwd_change_pending'] ?? null;

        if (!$pending || (int)$pending['usuarioid'] !== $currentUserId) {
            profileSetFlash('danger', 'No pending password change request found.');
            profileRedirect();
        }

        $profileData = Profile::getByUserId($currentUserId);
        if (!$profileData) {
            profileSetFlash('danger', 'User profile not found.');
            profileRedirect();
        }

        $otpCode = random_int(100000, 999999);
        Profile::invalidatePendingPasswordOtps($currentUserId);
        Profile::createPasswordOtp($currentUserId, $otpCode);

        $userRoles = Profile::getRolesByUserId($currentUserId);
        if (empty($userRoles)) {
            $userRoles = $profileData['roles'] ?? [];
        }

        $sendResult = sendPasswordChangeOtpEmail(
            (string)$profileData['email'],
            (string)$profileData['nombres'],
            $otpCode,
            $userRoles
        );

        if ($sendResult !== true) {
            profileSetFlash('danger', 'Failed to resend verification code. Please try again.');
            profileRedirect();
        }

        profileSetFlash('success', 'A new verification code was sent.');
        profileRedirect();
    }

    if ($action === 'confirm_password_otp') {
        $pending = $_SESSION['pwd_change_pending'] ?? null;
        $otpRaw  = trim((string)($_POST['verification_code'] ?? ''));

        if (!$pending || (int)$pending['usuarioid'] !== $currentUserId) {
            profileSetFlash('danger', 'No pending password change request found.');
            profileRedirect();
        }

        if (!preg_match('/^\d{6}$/', $otpRaw)) {
            profileSetFlash('danger', 'Enter a valid 6-digit verification code.');
            profileRedirect();
        }

        $otpRecord = Profile::getValidPasswordOtp($currentUserId, (int)$otpRaw);
        if (!$otpRecord) {
            profileSetFlash('danger', 'The verification code is invalid or expired.');
            profileRedirect();
        }

        $updated = Profile::updatePasswordHash($currentUserId, (string)$pending['new_password_hash']);
        if (!$updated) {
            profileSetFlash('danger', 'Password could not be updated. Please try again.');
            profileRedirect();
        }

        Profile::markPasswordOtpUsed((int)$otpRecord['otpid']);
        Profile::invalidatePendingPasswordOtps($currentUserId);
        unset($_SESSION['pwd_change_pending']);

        profileSetFlash('success', 'Your password was updated successfully.');
        profileRedirect();
    }
}

$profileData = Profile::getByUserId($currentUserId);

$profileFlash = $_SESSION['profile_flash'] ?? null;
unset($_SESSION['profile_flash']);

$pendingPwdChange = $_SESSION['pwd_change_pending'] ?? null;
$hasPendingPwdChange = is_array($pendingPwdChange)
    && isset($pendingPwdChange['usuarioid'])
    && (int)$pendingPwdChange['usuarioid'] === $currentUserId;

$maskedEmail = $hasPendingPwdChange
    ? (string)($pendingPwdChange['masked_email'] ?? '***@***.***')
    : '';

$otpRemainingSeconds = $hasPendingPwdChange
    ? Profile::getActivePasswordOtpRemainingSeconds($currentUserId)
    : 0;
