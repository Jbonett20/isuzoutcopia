<?php
ob_start();
@session_start();
include_once("../controllers/verify_otp.php");

/* Guard: must have pending OTP session */
if (!isset($_SESSION['otp_pending'])) {
    header("location: login.php");
    exit;
}

$maskedEmail = $_SESSION['otp_masked_email'] ?? '***@***.***';
$otpError    = null;
$otpInfo     = null;

if (isset($_SESSION['otp_error'])) {
    $otpError = $_SESSION['otp_error'];
    unset($_SESSION['otp_error']);
}
if (isset($_SESSION['otp_info'])) {
    $otpInfo = $_SESSION['otp_info'];
    unset($_SESSION['otp_info']);
}

$otpRemainingSeconds = isset($_SESSION['otp_remaining_seconds'])
    ? max(0, (int)$_SESSION['otp_remaining_seconds'])
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Isuzu CV Order System 2.0 - CVS</title>
    <link rel="shortcut icon" href="../admin/img/favicon.ico">
    <link href="../admin/css/application.min.css" rel="stylesheet">
    <!--[if IE 9]>
        <link href="css/application-ie9-part2.css" rel="stylesheet">
    <![endif]-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="KPI (Key Performance Indicators) statistics and dashboard management, with real-time analysis of Isuzu dealer performance across Caribbean countries.">
    <meta name="author" content="Autotrain Colombia - Ludus Software Factory">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <script>
        /* yeah we need this empty stylesheet here. It's cool chrome & chromium fix
        chrome fix https://code.google.com/p/chromium/issues/detail?id=167083
        https://code.google.com/p/chromium/issues/detail?id=332189
        */
    </script>
    <script src="../admin/js/charts/d3-version-wrapper.js"></script>
    <style>
        .otp-wrapper {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 16px auto 8px;
            flex-wrap: nowrap;
            width: 100%;
            max-width: 320px;
            padding: 0;
            box-sizing: border-box;
        }

        .otp-digit {
            width: 44px;
            height: 48px;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            border-radius: .3rem;
            outline: none;
            caret-color: #FF0604;
            border: 1px solid rgba(145, 140, 140, 0.45);
            background-color: rgba(255, 255, 255, 0.2);
            color: #000;
            -webkit-text-fill-color: #000;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.06);
        }

        .otp-digit:focus {
            border-color: #FF0604;
            box-shadow: 0 0 0 0.2rem rgba(255, 6, 4, 0.25);
        }

        .otp-digit.filled {
            background-color: rgba(255, 255, 255, 0.22);
        }

        .otp-digit::-webkit-outer-spin-button,
        .otp-digit::-webkit-inner-spin-button { -webkit-appearance: none; }

        #btnVerify:disabled {
            background-color: #b0b0b0 !important;
            border-color: #b0b0b0 !important;
            cursor: not-allowed;
            opacity: .7;
        }
        #btnVerify:not(:disabled) {
            background-color: #FF0604 !important;
            border-color: #FF0604 !important;
            color: #fff !important;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #FF0604;
            font-size: 13px;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
        }
        .resend-btn:hover { color: #b50000; }

        #countdown { font-weight: 600; color: #FF0604; }

        @keyframes shake {
            0%,100%{transform:translateX(0)}
            20%{transform:translateX(-8px)}
            40%{transform:translateX(8px)}
            60%{transform:translateX(-6px)}
            80%{transform:translateX(6px)}
        }
        .shake { animation: shake .4s ease; }

        @media (max-width: 576px) {
            .otp-wrapper {
                gap: 6px;
                max-width: 100%;
            }
            .otp-digit {
                width: 38px;
                height: 44px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body class="login-page" style="background-image: url('../admin/img/background.png');">
    <div class="container">
        <main id="content" class="widget-login-container" role="main">
            <div class="row justify-content-center">
                <div class="col-xl-4 col-md-7 col-10">
                    <section class="widget widget-login animated fadeInUp">
                        <header>
                            <h4><img src="../admin/img/iconoisuzurojo.png" alt="Isuzu" class="logoSup" style="width: 150px;"></h4>
                        </header>
                        <div class="widget-body">
                            <p class="widget-login-info">
                                <br>Enter the 6-digit verification code sent to your email.
                            </p>

                            <div class="text-center mb-2">
                                <span class="badge badge-light" style="font-size: 13px; color: #333;">
                                    <i class="la la-envelope"></i>
                                    <?php echo htmlspecialchars($maskedEmail); ?>
                                </span>
                            </div>

                            <p class="text-center" style="font-size:12px;color:##000000;margin:8px 0 0;">
                                Code expires in <span id="countdown">02:00</span>
                            </p>

                            <?php if ($otpError): ?>
                            <div class="group mt-3">
                                <div class="alert alert-danger shake" role="alert" id="errAlert">
                                    <strong>Error!</strong> <?php echo htmlspecialchars($otpError); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($otpInfo): ?>
                            <div class="group mt-3">
                                <div class="alert alert-success" role="alert">
                                    <i class="la la-check-circle"></i> <?php echo htmlspecialchars($otpInfo); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <form id="otpForm" class="login-form mt-lg" method="POST" action="../controllers/verify_otp.php" autocomplete="off">
                                <div class="otp-wrapper" id="otpWrapper">
                                    <input class="otp-digit" type="text" name="d1" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" autofocus tabindex="1">
                                    <input class="otp-digit" type="text" name="d2" maxlength="1" inputmode="numeric" pattern="[0-9]" tabindex="2">
                                    <input class="otp-digit" type="text" name="d3" maxlength="1" inputmode="numeric" pattern="[0-9]" tabindex="3">
                                    <input class="otp-digit" type="text" name="d4" maxlength="1" inputmode="numeric" pattern="[0-9]" tabindex="4">
                                    <input class="otp-digit" type="text" name="d5" maxlength="1" inputmode="numeric" pattern="[0-9]" tabindex="5">
                                    <input class="otp-digit" type="text" name="d6" maxlength="1" inputmode="numeric" pattern="[0-9]" tabindex="6">
                                </div>

                                <input type="hidden" name="otp_code" id="otp_code_hidden">

                                <div class="btn-toolbar mt-4">
                                    <button id="btnVerify" type="submit" class="btn btn-primary btn-block pull-right" style="color:white;background-color:#FF0604;border-color:#FF0604;" disabled>
                                        <span class="auth-btn-circle">
                                            <i class="la la-caret-right"></i>
                                        </span>
                                        Verify code
                                    </button>
                                </div>
                            </form>

                            <div class="group mt-3 text-center" style="font-size: 13px;">
                                <span>Didn't receive the code? </span>
                                <form id="resendForm" method="POST" action="../controllers/verify_otp.php" style="display:inline;">
                                    <input type="hidden" name="resend_otp" value="1">
                                    <button type="submit" class="resend-btn" id="resendBtn">Resend</button>
                                </form>
                            </div>

                            <div class="group text-center" style="font-size: 12px; margin-top: 8px;">
                                <a href="login.php?cancel_otp=1" style="color:#aaa;text-decoration:none;">
                                    <i class="la la-arrow-left"></i> Back to login
                                </a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="page-footer">
                <?php echo date("Y"); ?> &copy; All rights reserved &mdash;
                <a href="https://www.isuzucv.com/" rel="nofollow" target="_blank">Isuzu Motors America, LLC</a>
                &mdash; Developed by Autotrain Colombia
            </footer>
        </main>
    </div>

    <!-- Scripts -->
    <script src="../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../node_modules/jquery-pjax/jquery.pjax.js"></script>
    <script src="../node_modules/popper.js/dist/umd/popper.js"></script>
    <script src="../node_modules/bootstrap/dist/js/bootstrap.js"></script>
    <script src="../node_modules/bootstrap/js/dist/util.js"></script>
    <script src="../node_modules/widgster/widgster.js"></script>
    <script src="../node_modules/hammerjs/hammer.js"></script>
    <script src="../node_modules/jquery-hammerjs/jquery.hammer.js"></script>
    <script src="../admin/js/settings.js"></script>
    <script src="../admin/js/app.js"></script>

    <script>
    (function () {
        const digits    = Array.from(document.querySelectorAll('.otp-digit'));
        const hidden    = document.getElementById('otp_code_hidden');
        const btnVerify = document.getElementById('btnVerify');
        const form      = document.getElementById('otpForm');

        function syncAndValidate() {
            const code = digits.map(function (d) { return d.value; }).join('');
            hidden.value = code;
            btnVerify.disabled = !/^\d{6}$/.test(code);
        }

        digits.forEach(function (inp, idx) {

            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace') {
                    if (this.value !== '') {
                        this.value = '';
                        this.classList.remove('filled');
                        syncAndValidate();
                        e.preventDefault();
                    } else if (idx > 0) {
                        digits[idx - 1].focus();
                        digits[idx - 1].value = '';
                        digits[idx - 1].classList.remove('filled');
                        syncAndValidate();
                        e.preventDefault();
                    }
                    return;
                }
                const allowed = ['Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'];
                if (!/^\d$/.test(e.key) && !allowed.includes(e.key)) {
                    e.preventDefault();
                }
            });

            inp.addEventListener('input', function () {
                let v = this.value.replace(/\D/g, '');
                if (v.length > 1) v = v.slice(-1);
                this.value = v;

                if (v !== '') {
                    this.classList.add('filled');
                    if (idx < digits.length - 1) digits[idx + 1].focus();
                } else {
                    this.classList.remove('filled');
                }
                syncAndValidate();
            });

            inp.addEventListener('paste', function (e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData)
                               .getData('text').replace(/\D/g, '').slice(0, 6);
                if (!pasted) return;

                const startIndex = pasted.length >= 6 ? 0 : idx;
                pasted.split('').forEach(function (ch, i) {
                    if (digits[startIndex + i]) {
                        digits[startIndex + i].value = ch;
                        digits[startIndex + i].classList.add('filled');
                    }
                });
                const nextEmpty = digits.findIndex(function (d) { return d.value === ''; });
                (nextEmpty !== -1 ? digits[nextEmpty] : digits[digits.length - 1]).focus();
                syncAndValidate();
            });
        });

        form.addEventListener('submit', function (e) {
            syncAndValidate();
            if (!/^\d{6}$/.test(hidden.value)) {
                e.preventDefault();
                return;
            }
            if (total < 0) {
                e.preventDefault();
                alert('The verification code has expired. Please request a new one.');
            }
        });

        let total = <?php echo $otpRemainingSeconds; ?>;
        const countdownEl = document.getElementById('countdown');

        function updateClock() {
            const m = String(Math.floor(total / 60)).padStart(2, '0');
            const s = String(total % 60).padStart(2, '0');
            countdownEl.textContent = m + ':' + s;

            if (total <= 0) {
                countdownEl.textContent = 'Expired';
                countdownEl.style.color = '#888';
                btnVerify.disabled = true;
                digits.forEach(function (d) { d.disabled = true; });
                clearInterval(timer);
            } else if (total <= 300) {
                countdownEl.style.color = '#e67e22';
            }
            total--;
        }

        updateClock();
        const timer = setInterval(updateClock, 1000);
    })();
    </script>
</body>
</html>
