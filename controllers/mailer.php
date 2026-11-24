<?php
/**
 * mailer.php  — capa de compatibilidad
 * Mantiene sendOtpEmail() para el código existente,
 * delegando en models/Mailer.php + templatemail/templateotp.php.
 */

require_once __DIR__ . '/../models/Mailer.php';
require_once __DIR__ . '/../templatemail/templateotp.php';
require_once __DIR__ . '/../templatemail/templatepasswordchangeotp.php';
require_once __DIR__ . '/../templatemail/templaterecoverpassword.php';

function determineBrandByRoles($roleOrRoles)
{
    $kpiRoles = [5, 6];
    $roles = [];

    if (is_array($roleOrRoles)) {
        $roles = $roleOrRoles;
    } else {
        $roles = [$roleOrRoles];
    }

    foreach ($roles as $role) {
        if (in_array((int)$role, $kpiRoles, true)) {
            return 'kpi';
        }
    }

    return 'ots';
}

function sendOtpEmail(string $toEmail, string $toName, int $otpCode, $rolidOrRoles)
{
    $brand = determineBrandByRoles($rolidOrRoles);

    $subject = $brand === 'kpi'
        ? 'Verification Code - KPI System'
        : 'Verification Code - ILAC System';

    [$html, $text] = TemplateOtp::build($otpCode, $toName, $brand);

    return Mailer::send([
        'to_email' => $toEmail,
        'to_name'  => $toName,
        'subject'  => $subject,
        'html'     => $html,
        'text'     => $text,
        'brand'    => $brand,
        'images'   => [
            [
                'path' => __DIR__ . '/../admin/img/iconoisuzurojo.png',
                'cid'  => 'logo_isuzu',
                'name' => 'logo.png',
                'type' => 'image/png',
            ],
            [
                'path' => __DIR__ . '/../admin/img/camion.jpeg',
                'cid'  => 'truck_image',
                'name' => 'truck.jpeg',
                'type' => 'image/jpeg',
            ],
        ],
    ]);
}

function sendPasswordChangeOtpEmail(string $toEmail, string $toName, int $otpCode, $rolidOrRoles)
{
    $brand = determineBrandByRoles($rolidOrRoles);

    $subject = $brand === 'kpi'
        ? 'Password Change Verification - KPI System'
        : 'Password Change Verification - ILAC System';

    [$html, $text] = TemplatePasswordChangeOtp::build($otpCode, $toName, $brand);

    return Mailer::send([
        'to_email' => $toEmail,
        'to_name'  => $toName,
        'subject'  => $subject,
        'html'     => $html,
        'text'     => $text,
        'brand'    => $brand,
        'images'   => [
            [
                'path' => __DIR__ . '/../admin/img/iconoisuzurojo.png',
                'cid'  => 'logo_isuzu',
                'name' => 'logo.png',
                'type' => 'image/png',
            ],
            [
                'path' => __DIR__ . '/../admin/img/camion.jpeg',
                'cid'  => 'truck_image',
                'name' => 'truck.jpeg',
                'type' => 'image/jpeg',
            ],
        ],
    ]);
}

function sendPasswordRecoveryEmail(string $toEmail, string $toName, int $otpCode, $rolidOrRoles)
{
    $brand = determineBrandByRoles($rolidOrRoles);

    $subject = $brand === 'kpi'
        ? 'Password Recovery - KPI System'
        : 'Password Recovery - ILAC System';

    [$html, $text] = TemplateRecoverPassword::build($otpCode, $toName, $brand);

    return Mailer::send([
        'to_email' => $toEmail,
        'to_name'  => $toName,
        'subject'  => $subject,
        'html'     => $html,
        'text'     => $text,
        'brand'    => $brand,
        'images'   => [
            [
                'path' => __DIR__ . '/../admin/img/iconoisuzurojo.png',
                'cid'  => 'logo_isuzu',
                'name' => 'logo.png',
                'type' => 'image/png',
            ],
            [
                'path' => __DIR__ . '/../admin/img/camion.jpeg',
                'cid'  => 'truck_image',
                'name' => 'truck.jpeg',
                'type' => 'image/jpeg',
            ],
        ],
    ]);
}
