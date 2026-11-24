<?php
@session_start();
class Login
{

    /**
     * Step 1 – Validate credentials and send OTP.
     * Returns:
     *   ['otp_sent' => true,  'masked_email' => '...']  → redirect to verify page
     *   ['error'    => '...']                            → show error on login
     */
    public static function ingresarUsuario($p)
    {
        include_once '../config/init_db.php';
        include_once __DIR__ . '/../controllers/mailer.php';
        include_once __DIR__ . '/Profile.php';

        $txtemail = trim($p['txtemail']);
        $password = $p['txtpassword'];

        $account = DB::queryFirstRow(
            "SELECT usuarioid, dealerid, nombres, contrasena, email
             FROM ots_usuarios
             WHERE email = %s AND estadoid = 1",
            $txtemail
        );

        if (! $account || ! isset($account['usuarioid']) || $account['usuarioid'] < 1) {
            return ['error' => 'The username or password is incorrect, please check and try again.'];
        }

        if (! password_verify($password, $account['contrasena'])) {
            DB::insert('log_general', [
                'datos'    => 'Intento fallido de inicio de sesión | Email: ' . $txtemail,
                'ip'       => $_SERVER['REMOTE_ADDR'],
                'creacion' => date('Y-m-d H:i:s'),
            ]);
            return ['error' => 'The username or password is incorrect, please check and try again.'];
        }

        /* ── Credentials OK → generate 6-digit OTP ── */
        $otpCode = random_int(100000, 999999);

        /* Invalidate any previous pending OTPs for this user */
        DB::update('adm_otp', ['estadoid' => 2], "usuarioid=%i AND estadoid=1", $account['usuarioid']);

        /* Insert new OTP */
        DB::insert('adm_otp', [
            'otp'           => $otpCode,
            'usuarioid'     => $account['usuarioid'],
            'fechacreacion' => date('Y-m-d H:i:s'),
            'estadoid'      => 1,
        ]);

        /* ── Send OTP email ── */
        $userRoles = Profile::getRolesByUserId((int) $account['usuarioid']);
        if (empty($userRoles)) {
            $userRoles = [(int) $account['rolid']];
        }

        $sendResult = sendOtpEmail(
            $account['email'],
            $account['nombres'],
            $otpCode,
            $userRoles
        );

        if ($sendResult !== true) {
            /* Log mail error but don't expose details to user */
            DB::insert('log_general', [
                'datos'    => 'OTP mail error | User: ' . $account['usuarioid'] . ' | ' . $sendResult,
                'ip'       => $_SERVER['REMOTE_ADDR'],
                'creacion' => date('Y-m-d H:i:s'),
            ]);
            return ['error' => 'There was a problem sending your verification email. Please try again later.'];
        }

        /* ── Store pending session (NOT a full authenticated session) ── */
        $_SESSION['otp_pending'] = [
            'usuarioid' => $account['usuarioid'],
            'dealerid'  => $account['dealerid'],
            'nombres'   => $account['nombres'],
            'rolid'     => $account['rolid'],
            'email'     => $account['email'],
        ];

        return [
            'otp_sent'     => true,
            'masked_email' => self::maskEmail($account['email']),
        ];
    }

    /**
     * Step 2 – Verify OTP entered by the user and complete login.
     * Returns:
     *   ['success' => true]  → login complete, session set
     *   ['error'   => '...'] → invalid / expired code
     */
    public static function verificarOtp($otpInput)
    {
        include_once '../config/init_db.php';

        if (! isset($_SESSION['otp_pending'])) {
            return ['error' => 'Your session has expired. Please sign in again.'];
        }

        $pending   = $_SESSION['otp_pending'];
        $usuarioid = (int) $pending['usuarioid'];
        $otpInput  = (int) $otpInput;

        /* Find a valid, unused OTP created within the last 2 hours */
        $record = DB::queryFirstRow(
            "SELECT otpid, otp, fechacreacion
             FROM adm_otp
             WHERE usuarioid = %i
               AND estadoid  = 1
               AND otp       = %i
               AND fechacreacion >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
             ORDER BY otpid DESC
             LIMIT 1",
            $usuarioid, $otpInput
        );

        if (! $record) {
            return ['error' => 'The verification code is invalid or has expired. Please try again.'];
        }

        /* Mark OTP as used */
        DB::update('adm_otp', ['estadoid' => 2], "otpid=%i", $record['otpid']);

        /* ── Build full authenticated session ── */
        $account_resp = DB::queryFirstRow(
            "SELECT usuarioid, dealerid, nombres
             FROM ots_usuarios
             WHERE usuarioid = %i AND estadoid = 1",
            $usuarioid
        );

        // Get all roles for the user
        $rolesData = DB::query(
            "SELECT aur.rolid, otr.rol
             FROM adm_usuarioroles aur
             INNER JOIN ots_roles otr ON otr.rolid = aur.rolid
             WHERE aur.usuarioid = %i",
            $usuarioid
        );

        $rolesArray = [];
        foreach ($rolesData as $rol) {
            $rolesArray[] = [
                'rolid' => $rol['rolid'],
                'rol'   => $rol['rol'],
            ];
        }

        $Daloc = DB::queryFirstRow(
            "SELECT p.pais, p.paisid, d.dealer, p.bandera
             FROM ots_dealers d, ots_paises p
             WHERE d.dealerid = %i AND d.paisid = p.paisid",
            $account_resp['dealerid']
        );
        $_SESSION['UsrData'] = $account_resp;
        $_SESSION['UsrData']['roles'] = $rolesArray;
        $_SESSION['UsrData']['rolids'] = array_column($rolesArray, 'rolid');
        $_SESSION['DatLoc'] = $Daloc;

        unset($_SESSION['otp_pending']);
        /* Update last connection & log entry */
        DB::update('ots_usuarios', ['ultimaconexion' => date('Y-m-d H:i:s')], "usuarioid=%i", $usuarioid);
        DB::insert('ots_logusuarios', [
            'usuarioid' => $usuarioid,
            'tipo'      => 'Entrada',
            'ip'        => $_SERVER['REMOTE_ADDR'],
            'fecha'     => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Logout the current user.
     */
    public static function salirUsuario()
    {
        include_once '../config/init_db.php';
        DB::insert('ots_logusuarios', [
            'usuarioid' => $_SESSION['UsrData']['usuarioid'],
            'tipo'      => 'Salida',
            'ip'        => $_SERVER['REMOTE_ADDR'],
            'fecha'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mask an email: j***n@e***.com
     */
    public static function maskEmail(string $email): string
    {
        $parts  = explode('@', $email);
        $local  = $parts[0];
        $domain = $parts[1] ?? '';

        $maskedLocal  = (strlen($local) > 2) ? $local[0] . str_repeat('*', strlen($local) - 2) . $local[-1] : $local[0] . '*';
        $domainParts  = explode('.', $domain);
        $ext          = array_pop($domainParts);
        $domainName   = implode('.', $domainParts);
        $maskedDomain = (strlen($domainName) > 2) ? $domainName[0] . str_repeat('*', strlen($domainName) - 2) . $domainName[-1] : $domainName[0] . '*';

        return $maskedLocal . '@' . $maskedDomain . '.' . $ext;
    }
}
