<?php

class TemplateRecoverPassword
{
    public static function build(int $otp, string $name, string $brand): array
    {
        return [
            self::html($otp, $name, $brand),
            self::text($otp, $name),
        ];
    }

    private static function html(int $otp, string $name, string $brand): string
    {
        $otpCode = str_pad((string)$otp, 6, '0', STR_PAD_LEFT);

        if ($brand === 'kpi') {
            $accentColor = '#f3eaea';
            $headerTitle = 'ISUZU KPI';
            $truckWidth  = 260;
            $truckHeight = 87;
        } else {
            $accentColor = '#f2e3e3';
            $headerTitle = 'ISUZU MOTORS AMERICA (ILAC)';
            $truckWidth  = 280;
            $truckHeight = 94;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Password Recovery</title>
  <style>
    @media only screen and (max-width: 620px) {
      .email-card { width: 100% !important; border-radius: 0 !important; }
      .pad { padding-left: 18px !important; padding-right: 18px !important; }
      .otp-code-block { width: 100% !important; font-size: 30px !important; letter-spacing: 6px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:#f0f0f0;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f0f0;padding:30px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" class="email-card"
          style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.10);max-width:600px;width:100%;">

          <tr><td height="6" style="background:{$accentColor};font-size:0;line-height:0;">&nbsp;</td></tr>

          <tr>
            <td class="pad" style="background:#fff;padding:28px 40px 8px 40px;text-align:center;">
              <img src="cid:logo_isuzu" alt="Isuzu" width="140" style="display:inline-block;border:0;width:140px;height:auto;">
            </td>
          </tr>

          <tr>
            <td class="pad" style="padding:20px 40px 0 40px;">
              <p style="margin:0;font-size:16px;color:#222;font-weight:600;">Hi {$name}</p>
              <p style="margin:12px 0 0 0;font-size:14px;color:#555;line-height:1.7;">
                We received a request to reset the password for your account. Use the verification code below to continue.<br>
                <strong>This code expires in 2 hours.</strong>
              </p>
            </td>
          </tr>

          <tr>
            <td class="pad" style="padding:30px 40px 24px 40px;" align="center">
              <p style="margin:0 0 16px 0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:1px;font-weight:600;">
                Password Recovery Verification Code
              </p>
              <div class="otp-code-block" style="display:inline-block;min-width:240px;padding:11px 20px;background:#f8f8f8;border:2px solid {$accentColor};border-radius:10px;font-size:30px;font-weight:600;letter-spacing:10px;color:#222;font-family:'Courier New',monospace;text-align:center;">
                {$otpCode}
              </div>
              <p style="margin:10px 0 0 0;font-size:12px;color:#666;">
                Copy this code and paste it in the password recovery form.
              </p>
              <p style="margin:18px 0 0 0;font-size:12px;color:#aaa;">
                If you did not request this reset, ignore this email.
              </p>
            </td>
          </tr>

          <tr><td style="padding:0 40px;"><hr style="border:none;border-top:1px solid #eee;margin:0;"></td></tr>

          <tr>
            <td class="pad" style="padding:22px 40px 16px 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                <tr>
                  <td style="padding:0;vertical-align:middle;">
                    <p style="margin:0;font-size:14px;line-height:20px;color:#555;font-weight:600;">Best regards,</p>
                    <p style="margin:6px 0 0 0;font-size:13px;line-height:18px;font-weight:700;color:#FF0604;letter-spacing:0.5px;">{$headerTitle}</p>
                    <p style="margin:10px 0 0 0;font-size:11px;line-height:16px;color:#888;">Automated notification — please do not reply to this email.</p>
                  </td>
                  <td style="padding:0 0 0 16px;vertical-align:middle;text-align:right;width:{$truckWidth}px;">
                    <img src="cid:truck_image" alt="Isuzu Truck" width="{$truckWidth}" height="{$truckHeight}" style="display:block;width:{$truckWidth}px;max-width:100%;height:auto;border:0;" translate="no">
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td height="6" style="background:{$accentColor};font-size:0;line-height:0;">&nbsp;</td></tr>
        </table>

        <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">
          <tr>
            <td style="padding:16px 0;text-align:center;font-size:11px;color:#aaa;">
              &copy; Isuzu Motors America, LLC &mdash; Developed by Autotrain Colombia
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private static function text(int $otp, string $name): string
    {
        $otpCode = str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
        $year    = date('Y');

        return <<<TEXT
Password Recovery - Isuzu CV Order System

Hello, {$name}.

We received a request to reset the password for your account.
Your verification code is: {$otpCode}

This code is valid for 2 hours.

If you did not request a password reset, please ignore this message.

© {$year} Isuzu Motors America, LLC — All rights reserved.
Developed by Autotrain Colombia.
TEXT;
    }
}
