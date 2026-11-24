<?php
@session_start();
class Dealer
{
    public static function listar()
    {
        include_once('../config/init_db.php');
        $sql = "SELECT
                    otd.*,
                    ote.estado,
                    otp.pais,
                    CONCAT(
                        '<div style=\"display: flex; align-items: center;\">',
                        '<img src=\"../admin/img/country/', otp.bandera, '\" ',
                        'style=\"width:20px; margin-right:5px; cursor: pointer;\" ',
                        'title=\"', otp.pais, '\" ',
                        'alt=\"', otp.pais, '\"/>',
                        otp.pais,
                        '</div>'
                    ) AS bandera,
                     COALESCE(concat('( ',oi.intermediario, ' )'), '') intermediario
                FROM
                    ots_dealers otd
                INNER JOIN ots_paises otp ON
                    otp.paisid = otd.paisid
                INNER JOIN ots_estados ote ON
                    ote.estadoid = otd.estadoid
                LEFT JOIN ots_intermediario oi ON 
                	oi.intermediarioid = otd.intermediarioid
                ORDER BY
                    otd.dealerid DESC;";


        $lista = DB::query($sql);
        return $lista;
    }

    public static function crear($data)
    {
        include_once('../config/init_db.php');
        if (!userHasAnyRole([1, 2])) {
            return [
                "success" => false,
                "message" => "You are not authorized to use this role.",
                "calendarioid" => null
            ];
        }
        $dealerName = strtoupper($data['dealer']);
        $existeDealer = DB::queryFirstRow(
            "SELECT * FROM ots_dealers WHERE UPPER(dealer) = %s",
            $dealerName
        );

        if ($existeDealer) {
            return [
                "success"    => false,
                "message"    => "Dealer already exists",
                "dealerid"  => null
            ];
        }

        $customercode = $data['customercode'];

        $existeCustomerCode = DB::queryFirstRow(
            "SELECT * FROM ots_dealers WHERE customercode = %s",
            $customercode
        );

        if ($existeCustomerCode) {
            return [
                "success"    => false,
                "message"    => "Customer code already exists",
                "dealerid"  => null
            ];
        }

        $result = DB::insert('ots_dealers', [
            'dealer'         => $dealerName,
            'siglaorden'     => strtoupper($data['siglaorden']),
            'customercode'   => $data['customercode'],
            'paisid'         => $data['country'],
            'intermediarioid' => $data['tc'],
            'estadoid'       => 1,
            'fromdata'       => $data['fromdata'],
            'todata'         => $data['todata'],
            'paymentterm'    => strtoupper($data['paymentterm']),
            'personincharge' => strtoupper($data['personincharge']),
            'phone'          => $data['phone']
        ]);

        if ($result) {
            return [
                "success"    => true,
                "message"    => "The dealer has been created.!",
                "dealerid"  => DB::insertId()
            ];
        } else {
            return [
                "success"    => false,
                "message"    => "Failed to create dealer",
                "dealerid"  => null
            ];
        }
    }

    public static function editar($data)
    {
        include_once('../config/init_db.php');
        if (!userHasAnyRole([1, 2])) {
            return [
                "success" => false,
                "message" => "You are not authorized to use this role.",
                "calendarioid" => null
            ];
        }
        $dealer = DB::queryFirstRow(
            "SELECT * FROM ots_dealers WHERE dealerid = %i",
            $data['registroid']
        );

        if (!$dealer) {
            return [
                "success"    => false,
                "message"    => "Dealer not found",
                "dealerid"  => null
            ];
        }

        if ($dealer['dealer'] !== $data['dealer']) {

            $dealerName = strtoupper($data['dealer']);

            $existeDealer = DB::queryFirstRow(
                "SELECT * FROM ots_dealers WHERE UPPER(dealer) = %s AND dealerid != %i",
                $dealerName,
                $data['registroid']
            );

            if ($existeDealer) {
                return [
                    "success"    => false,
                    "message"    => "Dealer already exists",
                    "dealer"  => null
                ];
            }
        }

        $customercode = $data['customercode'];



        if ($dealer['customercode'] !== $customercode) {
            $existeCustomerCode = DB::queryFirstRow(
                "SELECT * FROM ots_dealers WHERE customercode = %s AND dealerid != %i",
                $customercode,
                $data['registroid']
            );

            if ($existeCustomerCode) {
                return [
                    "success"    => false,
                    "message"    => "Customer code already exists",
                    "dealerid"  => null
                ];
            }
        }

        $updateData = [
            'dealer'         => strtoupper($data['dealer']),
            'siglaorden'     => strtoupper($data['siglaorden']),
            'customercode'   => strtoupper($data['customercode']),
            'paisid'         => $data['country'],
            'intermediarioid' => $data['tc'],
            'estadoid'       => $data['estado'],
            'fromdata'       => $data['fromdata'],
            'todata'         => $data['todata'],
            'paymentterm'    => strtoupper($data['paymentterm']),
            'personincharge' => strtoupper($data['personincharge']),
            'phone'          => $data['phone']
        ];


        $result = DB::update('ots_dealers', $updateData, "dealerid = %i", $data['registroid']);

        if ($result !== false) {
            return [
                "success"    => true,
                "message"    => "The dealer has been updated.!",
                "dealerid"  => $data['registroid']
            ];
        } else {
            return [
                "success"    => false,
                "message"    => "Failed to update dealer",
                "dealerid"  => null
            ];
        }
    }
}