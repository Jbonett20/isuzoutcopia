<?php
@session_start();

class Dealertarget
{

    public static function esAdmin()
    {
        $sessionRoleIds = array_map(
            'intval',
            $_SESSION['UsrData']['rolids'] ?? []
        );

        return in_array(1, $sessionRoleIds, true);
    }

    public static function dealerSesion()
    {
        return (int) ($_SESSION['UsrData']['dealerid'] ?? 0);
    }

    /**
     * Usuario global: sin dealer asignado (dealerid = 0). Ve/gestiona toda la red.
     */
    public static function esGlobal()
    {
        return self::dealerSesion() === 0;
    }

    /**
     * Puede gestionar (crear/editar) targets.
     * - Usuario con dealer asignado (>0): gestiona SOLO su dealer (aunque sea administrador).
     * - Usuario global sin dealer (0): gestiona toda la red (debe ser administrador).
     */
    public static function puedeGestionar()
    {
        if (self::dealerSesion() > 0) {
            return true;
        }
        return self::esAdmin();
    }

    public static function listarDealersFiltro()
    {
        include_once '../config/init_db.php';

        $sql = "SELECT
                    d.dealerid,
                    CONCAT(d.dealer, ' (', p.pais, ')') AS dealer,
                    p.bandera
                FROM ots_dealers d
                INNER JOIN ots_paises p ON p.paisid = d.paisid
                WHERE d.estadoid = 1";

        $params = [];

        if (self::dealerSesion() > 0) {
            $sql .= " AND d.dealerid = %i";
            $params[] = self::dealerSesion();
        }

        $sql .= " ORDER BY d.dealer, p.pais";

        return count($params) > 0
            ? DB::query($sql, ...$params)
            : DB::query($sql);
    }

    public static function listar($dealerid = 0)
    {
        include_once '../config/init_db.php';

        $dealerid = (int) $dealerid;

        $sql = "SELECT
                dt.dealertargetid,
                dt.dealerid,
                dt.year,
                dt.objetivo,
                dt.objetivovlr,
                dt.creado,
                dt.usuarioid,
                d.dealer,
                p.pais,
                p.bandera,
                u.nombres
            FROM kpi_dealertarget dt
            INNER JOIN ots_dealers d ON d.dealerid = dt.dealerid
            INNER JOIN ots_paises p ON p.paisid = d.paisid
            INNER JOIN ots_usuarios u ON u.usuarioid = dt.usuarioid
            WHERE 1 = 1";

        $params = [];

        if (self::dealerSesion() > 0) {
            // Usuario con dealer asignado: solo su dealer (aunque sea administrador).
            $sql .= " AND dt.dealerid = %i";
            $params[] = self::dealerSesion();
        } elseif ($dealerid > 0) {
            // Usuario global: puede filtrar por cualquier dealer.
            $sql .= " AND dt.dealerid = %i";
            $params[] = $dealerid;
        }

        $sql .= " ORDER BY dt.year DESC, d.dealer ASC, dt.creado DESC";

        if (count($params) > 0) {
            return DB::query($sql, ...$params);
        }

        return DB::query($sql);
    }

    public static function crear($data)
    {
        include_once '../config/init_db.php';

        if (! self::puedeGestionar()) {
            return [
                'success'        => false,
                'message'        => 'You are not authorized to create targets.',
                'dealertargetid' => null,
            ];
        }

        // Usuario con dealer asignado (>0): solo puede crear targets de SU dealer (se fuerza, no se confía en el POST).
        // Usuario global (dealer 0): usa el dealer que envía el formulario.
        $dealerid     = self::dealerSesion() > 0
            ? self::dealerSesion()
            : (int) ($data['dealerid'] ?? 0);
        $year         = (int) ($data['year'] ?? 0);
        $objetivo     = (int) ($data['objetivo'] ?? -1);
        $objetivovlr  = (int) ($data['objetivovlr'] ?? -1);
        $usuarioid    = (int) ($_SESSION['UsrData']['usuarioid'] ?? 0);

        if ($dealerid <= 0 || $year < 1900 || $year > 2100 || $objetivo < 0 || $objetivovlr < 0) {
            return [
                'success'        => false,
                'message'        => 'Invalid data. Check dealer, year, target (Units) and target ($ USD).',
                'dealertargetid' => null,
            ];
        }

        $dealerExiste = DB::queryFirstRow(
            "SELECT dealerid FROM ots_dealers WHERE dealerid = %i",
            $dealerid
        );

        if (! $dealerExiste) {
            return [
                'success'        => false,
                'message'        => 'Dealer not found.',
                'dealertargetid' => null,
            ];
        }

        $duplicado = DB::queryFirstRow(
            "SELECT dealertargetid FROM kpi_dealertarget WHERE dealerid = %i AND year = %i",
            $dealerid,
            $year
        );

        if ($duplicado) {
            return [
                'success'        => false,
                'message'        => 'A dealer can only have one target per year.',
                'dealertargetid' => null,
            ];
        }

        try {
            DB::insert('kpi_dealertarget', [
                'dealerid'     => $dealerid,
                'year'         => $year,
                'objetivo'     => $objetivo,
                'objetivovlr'  => $objetivovlr,
                'creado'       => DB::sqleval('NOW()'),
                'usuarioid'    => $usuarioid,
            ]);

            return [
                'success'        => true,
                'message'        => 'Target created successfully.',
                'dealertargetid' => DB::insertId(),
            ];
        } catch (Exception $e) {
            if (stripos($e->getMessage(), 'Duplicate entry') !== false) {
                return [
                    'success'        => false,
                    'message'        => 'A dealer can only have one target per year.',
                    'dealertargetid' => null,
                ];
            }

            return [
                'success'        => false,
                'message'        => 'Failed to create target.',
                'dealertargetid' => null,
            ];
        }
    }

    public static function editar($data)
    {
        include_once '../config/init_db.php';

        if (! self::puedeGestionar()) {
            return [
                'success'        => false,
                'message'        => 'You are not authorized to edit targets.',
                'dealertargetid' => null,
            ];
        }

        $dealertargetid = (int) ($data['registroid'] ?? 0);
        // Usuario con dealer asignado (>0): solo puede editar targets de SU dealer (se fuerza, no se confía en el POST).
        // Usuario global (dealer 0): usa el dealer que envía el formulario.
        $dealerid       = self::dealerSesion() > 0
            ? self::dealerSesion()
            : (int) ($data['dealerid'] ?? 0);
        $year           = (int) ($data['year'] ?? 0);
        $objetivo       = (int) ($data['objetivo'] ?? -1);
        $objetivovlr    = (int) ($data['objetivovlr'] ?? -1);
        $usuarioid      = (int) ($_SESSION['UsrData']['usuarioid'] ?? 0);

        if ($dealertargetid <= 0 || $dealerid <= 0 || $year < 1900 || $year > 2100 || $objetivo < 0 || $objetivovlr < 0) {
            return [
                'success'        => false,
                'message'        => 'Invalid data. Check dealer, year, target (Units) and target ($ USD).',
                'dealertargetid' => null,
            ];
        }

        $registro = DB::queryFirstRow(
            "SELECT dealertargetid, dealerid FROM kpi_dealertarget WHERE dealertargetid = %i",
            $dealertargetid
        );

        if (! $registro) {
            return [
                'success'        => false,
                'message'        => 'Target not found.',
                'dealertargetid' => null,
            ];
        }

        // Usuario con dealer asignado solo puede editar registros de su propio dealer.
        if (self::dealerSesion() > 0 && (int) $registro['dealerid'] !== self::dealerSesion()) {
            return [
                'success'        => false,
                'message'        => 'You are not authorized to edit this target.',
                'dealertargetid' => null,
            ];
        }

        $duplicado = DB::queryFirstRow(
            "SELECT dealertargetid
             FROM kpi_dealertarget
             WHERE dealerid = %i AND year = %i AND dealertargetid <> %i",
            $dealerid,
            $year,
            $dealertargetid
        );

        if ($duplicado) {
            return [
                'success'        => false,
                'message'        => 'A dealer can only have one target per year.',
                'dealertargetid' => null,
            ];
        }

        $result = DB::update('kpi_dealertarget', [
            'dealerid'     => $dealerid,
            'year'         => $year,
            'objetivo'     => $objetivo,
            'objetivovlr'  => $objetivovlr,
            'usuarioid'    => $usuarioid,
        ], "dealertargetid = %i", $dealertargetid);

        if ($result !== false) {
            return [
                'success'        => true,
                'message'        => 'Target updated successfully.',
                'dealertargetid' => $dealertargetid,
            ];
        }

        return [
            'success'        => false,
            'message'        => 'Failed to update target.',
            'dealertargetid' => null,
        ];
    }
}
