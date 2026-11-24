<?php

class Profile
{
    public static function getByUserId(int $userId): ?array
    {
        include_once __DIR__ . '/../config/init_db.php';

        $row = DB::queryFirstRow(
            "SELECT
                u.usuarioid,
                u.dealerid,
                u.nombres,
                u.email,
                u.ultimaconexion,
                COALESCE(u.cargodealer, '') AS cargodealer,
                COALESCE(d.dealer, '') AS dealer
             FROM ots_usuarios u
             LEFT JOIN ots_dealers d ON d.dealerid = u.dealerid
             WHERE u.usuarioid = %i
             LIMIT 1",
            $userId
        );

        if ($row) {
            $row['roles'] = self::getRolesByUserId($userId);
        }

        return $row ?: null;
    }

    public static function getRolesByUserId(int $userId): array
    {
        include_once __DIR__ . '/../config/init_db.php';

        $rows = DB::query(
            "SELECT aur.rolid
             FROM adm_usuarioroles aur
             WHERE aur.usuarioid = %i",
            $userId
        );

        return array_map(static function ($row) {
            return (int)($row['rolid'] ?? 0);
        }, $rows ?: []);
    }

    public static function verifyCurrentPassword(int $userId, string $plainPassword): bool
    {
        include_once __DIR__ . '/../config/init_db.php';

        $row = DB::queryFirstRow(
            "SELECT contrasena FROM ots_usuarios WHERE usuarioid = %i LIMIT 1",
            $userId
        );

        if (!$row || empty($row['contrasena'])) {
            return false;
        }

        return password_verify($plainPassword, $row['contrasena']);
    }

    public static function updatePasswordHash(int $userId, string $passwordHash): bool
    {
        include_once __DIR__ . '/../config/init_db.php';

        try {
            $result = DB::update(
                'ots_usuarios',
                ['contrasena' => $passwordHash],
                'usuarioid=%i',
                $userId
            );

            return $result !== false;
        } catch (Exception $e) {
            $message = strtolower($e->getMessage());

            if (strpos($message, 'data too long') === false && strpos($message, 'too long') === false) {
                return false;
            }

            try {
                DB::query("ALTER TABLE ots_usuarios MODIFY contrasena VARCHAR(255) NOT NULL");

                $retry = DB::update(
                    'ots_usuarios',
                    ['contrasena' => $passwordHash],
                    'usuarioid=%i',
                    $userId
                );

                return $retry !== false;
            } catch (Exception $inner) {
                return false;
            }
        }
    }

    public static function ensurePasswordOtpTable(): void
    {
        include_once __DIR__ . '/../config/init_db.php';

        DB::query(
            "CREATE TABLE IF NOT EXISTS adm_otp_password (
                otpid INT AUTO_INCREMENT PRIMARY KEY,
                otp INT NOT NULL,
                usuarioid INT NOT NULL,
                fechacreacion DATETIME NOT NULL,
                estadoid INT NOT NULL DEFAULT 1,
                INDEX idx_usuario_estado (usuarioid, estadoid),
                INDEX idx_fecha (fechacreacion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function invalidatePendingPasswordOtps(int $userId): void
    {
        include_once __DIR__ . '/../config/init_db.php';

        DB::update('adm_otp_password', ['estadoid' => 2], 'usuarioid=%i AND estadoid=1', $userId);
    }

    public static function createPasswordOtp(int $userId, int $otp): void
    {
        include_once __DIR__ . '/../config/init_db.php';

        DB::insert('adm_otp_password', [
            'otp'           => $otp,
            'usuarioid'     => $userId,
            'fechacreacion' => date('Y-m-d H:i:s'),
            'estadoid'      => 1,
        ]);
    }

    public static function getValidPasswordOtp(int $userId, int $otp): ?array
    {
        include_once __DIR__ . '/../config/init_db.php';

        $row = DB::queryFirstRow(
            "SELECT otpid, otp, fechacreacion
             FROM adm_otp_password
             WHERE usuarioid = %i
               AND estadoid = 1
               AND otp = %i
               AND fechacreacion >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
             ORDER BY otpid DESC
             LIMIT 1",
            $userId,
            $otp
        );

        return $row ?: null;
    }

    public static function markPasswordOtpUsed(int $otpId): void
    {
        include_once __DIR__ . '/../config/init_db.php';

        DB::update('adm_otp_password', ['estadoid' => 2], 'otpid=%i', $otpId);
    }

    public static function getActivePasswordOtpRemainingSeconds(int $userId): int
    {
        include_once __DIR__ . '/../config/init_db.php';

        $row = DB::queryFirstRow(
            "SELECT fechacreacion
             FROM adm_otp_password
             WHERE usuarioid = %i
               AND estadoid = 1
             ORDER BY otpid DESC
             LIMIT 1",
            $userId
        );

        if (!$row || empty($row['fechacreacion'])) {
            return 0;
        }

        $createdTs = strtotime((string)$row['fechacreacion']);
        if ($createdTs === false) {
            return 0;
        }

        $elapsed = time() - $createdTs;
        return max(0, 7200 - $elapsed);
    }

    // ─── Password Recovery OTP (adm_otp_recovery) ───────────────────────────

    public static function ensureRecoveryOtpTable(): void
    {
        include_once __DIR__ . '/../config/init_db.php';

        DB::query(
            "CREATE TABLE IF NOT EXISTS adm_otp_recovery (
                otpid INT AUTO_INCREMENT PRIMARY KEY,
                otp INT NOT NULL,
                usuarioid INT NOT NULL,
                fechacreacion DATETIME NOT NULL,
                estadoid INT NOT NULL DEFAULT 1,
                INDEX idx_usuario_estado (usuarioid, estadoid),
                INDEX idx_fecha (fechacreacion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function findUserByEmail(string $email): ?array
    {
        include_once __DIR__ . '/../config/init_db.php';

        $row = DB::queryFirstRow(
            "SELECT u.usuarioid, u.nombres, u.email
             FROM ots_usuarios u
             WHERE u.email = %s
               AND u.estadoid = 1
             LIMIT 1",
            $email
        );

        return $row ?: null;
    }

    public static function invalidatePendingRecoveryOtps(int $userId): void
    {
        include_once __DIR__ . '/../config/init_db.php';

        DB::update('adm_otp_recovery', ['estadoid' => 2], 'usuarioid=%i AND estadoid=1', $userId);
    }

    public static function createRecoveryOtp(int $userId, int $otp): void
    {
        include_once __DIR__ . '/../config/init_db.php';

        DB::insert('adm_otp_recovery', [
            'otp'           => $otp,
            'usuarioid'     => $userId,
            'fechacreacion' => date('Y-m-d H:i:s'),
            'estadoid'      => 1,
        ]);
    }

    public static function getValidRecoveryOtp(int $userId, int $otp): ?array
    {
        include_once __DIR__ . '/../config/init_db.php';

        $row = DB::queryFirstRow(
            "SELECT otpid, otp, fechacreacion
             FROM adm_otp_recovery
             WHERE usuarioid = %i
               AND estadoid = 1
               AND otp = %i
               AND fechacreacion >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
             ORDER BY otpid DESC
             LIMIT 1",
            $userId,
            $otp
        );

        return $row ?: null;
    }

    public static function markRecoveryOtpUsed(int $otpId): void
    {
        include_once __DIR__ . '/../config/init_db.php';

        DB::update('adm_otp_recovery', ['estadoid' => 2], 'otpid=%i', $otpId);
    }

    public static function getActiveRecoveryOtpRemainingSeconds(int $userId): int
    {
        include_once __DIR__ . '/../config/init_db.php';

        $row = DB::queryFirstRow(
            "SELECT fechacreacion
             FROM adm_otp_recovery
             WHERE usuarioid = %i
               AND estadoid = 1
             ORDER BY otpid DESC
             LIMIT 1",
            $userId
        );

        if (!$row || empty($row['fechacreacion'])) {
            return 0;
        }

        $createdTs = strtotime((string)$row['fechacreacion']);
        if ($createdTs === false) {
            return 0;
        }

        $elapsed = time() - $createdTs;
        return max(0, 7200 - $elapsed);
    }
}
