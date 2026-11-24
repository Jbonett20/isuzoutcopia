<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer
{
    private static array $transports = [
        ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS, 'auto_tls' => true,  'host_key' => 'primary'],
        ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS,    'auto_tls' => true,  'host_key' => 'primary'],
        ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS, 'auto_tls' => true,  'host_key' => 'secondary'],
        ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS,    'auto_tls' => true,  'host_key' => 'secondary'],
        ['port' => 25,  'secure' => '',                              'auto_tls' => false, 'host_key' => 'primary'],
    ];

    public static function send(array $params)
    {
        [$primaryHost, $secondaryHost, $user, $pass, $fromEmail, $fromName] =
            self::credentials($params['brand'] ?? 'ots');

        $lastError = 'Unknown mailer error';

        foreach (self::$transports as $transport) {
            $host = $transport['host_key'] === 'primary' ? $primaryHost : $secondaryHost;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host        = $host;
                $mail->SMTPAuth    = true;
                $mail->Username    = $user;
                $mail->Password    = $pass;
                $mail->Port        = $transport['port'];
                $mail->CharSet     = 'UTF-8';
                $mail->Timeout     = 10;
                $mail->SMTPAutoTLS = $transport['auto_tls'];

                if (!empty($transport['secure'])) {
                    $mail->SMTPSecure = $transport['secure'];
                }

                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ];

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($params['to_email'], $params['to_name']);
                $mail->addReplyTo($fromEmail, $fromName);

                $mail->isHTML(true);
                $mail->Subject = $params['subject'];
                $mail->Body    = $params['html'];
                $mail->AltBody = $params['text'] ?? strip_tags($params['html']);

                foreach ($params['images'] ?? [] as $img) {
                    if (!empty($img['path']) && file_exists($img['path'])) {
                        $mail->addEmbeddedImage(
                            $img['path'],
                            $img['cid'],
                            $img['name']  ?? basename($img['path']),
                            'base64',
                            $img['type']  ?? 'image/png'
                        );
                    }
                }

                $mail->send();
                return true;

            } catch (Exception $e) {
                $lastError = "Host:{$host} Port:{$transport['port']} Error:{$mail->ErrorInfo}";
            }
        }

        return $lastError;
    }

    private static function credentials(string $brand): array
    {
        $prefix = strtoupper($brand);

        $primaryHost   = self::env("SMTP_{$prefix}_HOST", 'smtpout.secureserver.net');
        $secondaryHost = 'smtp.secureserver.net';
        $user          = self::env("SMTP_{$prefix}_USER");
        $pass          = self::env("SMTP_{$prefix}_PASS");
        $fromEmail     = $user;
        $fromName      = self::env("SMTP_{$prefix}_FROM_NAME", "ISUZU {$prefix}");

        return [$primaryHost, $secondaryHost, $user, $pass, $fromEmail, $fromName];
    }

    private static function env(string $key, string $default = ''): string
    {
        $val = getenv($key);
        return ($val !== false && $val !== '') ? $val : $default;
    }
}
