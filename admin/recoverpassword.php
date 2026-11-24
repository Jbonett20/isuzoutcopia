<?php
@session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: text/html; charset=utf-8');

if (isset($_SESSION['UsrData'])) {
    header("Location: admin/home");
    exit;
}

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin'));
$controllerUrl = preg_replace('#/admin$#', '/controllers', rtrim($scriptDir, '/')) . '/recoverpassword.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password – Isuzu CV Order System</title>
    <link rel="shortcut icon" href="../admin/img/favicon.ico">
    <link href="../admin/css/application.min.css" rel="stylesheet">
    <link href="../node_modules/messenger/build/css/messenger.css" rel="stylesheet">
    <link href="../node_modules/messenger/build/css/messenger-theme-flat.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <script src="../admin/js/charts/d3-version-wrapper.js"></script>
    <style>
        .rp-panel { display: none; }
        .rp-panel.rp-active { display: block; }

        .rp-step-row {
            display: flex;
            align-items: center;
           /* gap: 14px; */
            margin-bottom: 18px;
            font-size: 13px;
            color: #888;
            padding: 0;
        }

        .rp-step-row .step { display: flex; align-items: center; }

        .rp-step-row .sep {
            width: 30px;
            height: 2px;
            background: #dee2e6;
            border-radius: 2px;
            transition: background .3s;
        }

        .rp-step-row .sep.done { background: #28a745; }

        .rp-step-badge {
            display: inline-block;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            line-height: 26px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            margin-right: 6px;
            vertical-align: middle;
            transition: background .3s, color .3s;
        }

        .rp-step-badge.active { background: #FF0604; color: #fff; }
        .rp-step-badge.done { background: #28a745; color: #fff; }
        .rp-step-badge.next { background: #dee2e6; color: #666; }

        .rp-eye-wrap { position: relative; }
        .rp-eye-wrap input { padding-right: 40px; }

        .rp-eye-btn {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: #aaa;
            line-height: 1;
        }

        .rp-eye-btn:hover { color: #555; }

        .rp-back-link {
            font-size: 12px;
            color: #888;
            text-decoration: none;
            display: inline-block;
        }

        .rp-back-link:hover { color: #FF0604; text-decoration: underline; }

        .rp-spin {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: rp-rotate .7s linear infinite;
            vertical-align: middle;
        }

        @keyframes rp-rotate { to { transform: rotate(360deg); } }

        #rp-alert {
            display: none;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .rp-password-rules {
            margin-top: 8px;
            font-size: 12px;
            color: #888;
            line-height: 1.45;
        }
    </style>
</head>
<body class="login-page" style="background-image: url('../admin/img/background.png');">
<div class="container">
    <main id="content" class="widget-login-container" role="main">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-md-8 col-12">
                <section class="widget widget-login animated fadeInUp">
                    <header>
                        <h4><img src="../admin/img/iconoisuzurojo.png" alt="Isuzu" class="logoSup" style="width:150px;"></h4>
                    </header>
                    <div class="widget-body">
                        <div class="rp-step-row">
                            <div class="step">
                                <span class="rp-step-badge active" id="badge-1">1</span>
                                <span>Email</span>
                            </div>
                            <div class="sep" id="sep-1"></div>
                            <div class="step">
                                <span class="rp-step-badge next" id="badge-2">2</span>
                                <span>Verify Code</span>
                            </div>
                            <div class="sep" id="sep-2"></div>
                            <div class="step">
                                <span class="rp-step-badge next" id="badge-3">3</span>
                                <span>New Password</span>
                            </div>
                        </div>

                        <div id="rp-alert" class="alert" role="alert"></div>

                        <div class="rp-panel rp-active" id="panel-1">
                            <p class="widget-login-info">
                                <br>Forgot your password? Enter your account email address and we'll send you a verification code.
                            </p>
                            <form id="form-email" autocomplete="off">
                                <div class="form-group">
                                    <label for="rp_email">Email address</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text"><i class="la la-envelope text-white"></i></div>
                                        </div>
                                        <input id="rp_email" name="email" type="email" required
                                               placeholder="Enter your account email"
                                               class="form-control input-transparent pl-3" autocomplete="email">
                                    </div>
                                </div>
                                <div class="btn-toolbar">
                                    <button type="submit" id="btn-send" class="btn btn-block btn-primary" style="color:#fff;background-color:#FF0604;border-color:#FF0604;">
                                        <i class="la la-paper-plane"></i> Send Verification Code
                                    </button>
                                </div>
                                <div class="text-center mt-2">
                                    <a href="login.php" class="rp-back-link"><i class="la la-arrow-left"></i> Back to Sign In</a>
                                </div>
                            </form>
                        </div>

                        <div class="rp-panel" id="panel-2">
                            <p class="widget-login-info">
                                <br>A 6-digit code was sent to <strong id="rp-masked-email"></strong>.<br>
                                Enter it below to continue.
                            </p>
                            <p class="text-center text-muted" style="font-size:12px;display:none;" id="rp-countdown-wrap">
                                Code expires in: <strong><span id="rp-countdown"></span></strong>
                            </p>
                            <form id="form-otp" autocomplete="off">
                                <div class="form-group">
                                    <label for="rp_otp">Verification Code</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text"><i class="la la-key text-white"></i></div>
                                        </div>
                                        <input id="rp_otp" name="otp" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" required
                                               placeholder="6-digit code"
                                               class="form-control input-transparent pl-3"
                                               style="letter-spacing:6px;font-size:18px;font-weight:bold;">
                                    </div>
                                </div>
                                <div class="btn-toolbar">
                                    <button type="submit" id="btn-verify" class="btn btn-block btn-primary" style="color:#fff;background-color:#FF0604;border-color:#FF0604;">
                                        <i class="la la-check"></i> Verify Code
                                    </button>
                                </div>
                            </form>
                            <div class="text-center mt-2">
                                <button type="button" id="btn-resend" class="btn btn-link" style="font-size:12px;color:#888;">
                                    <i class="la la-refresh"></i> Resend code
                                </button>
                                &nbsp;|&nbsp;
                                <button type="button" id="btn-cancel" class="rp-back-link btn btn-link p-0" style="font-size:12px;">
                                    <i class="la la-times"></i> Cancel
                                </button>
                            </div>
                        </div>

                        <div class="rp-panel" id="panel-3">
                            <p class="widget-login-info">
                                <br>Identity verified. Please enter your new password below.
                            </p>
                            <form id="form-newpwd" autocomplete="off">
                                <div class="form-group">
                                    <label for="rp_newpwd">New Password</label>
                                    <div class="input-group rp-eye-wrap">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text"><i class="la la-lock text-white"></i></div>
                                        </div>
                                        <input id="rp_newpwd" name="newpassword" type="password" required
                                                 minlength="10"
                                               placeholder="Minimum 10 characters"
                                               class="form-control input-transparent pl-3"
                                               style="border-top-right-radius:.3rem;border-bottom-right-radius:.3rem;">
                                        <button type="button" class="rp-eye-btn" data-target="rp_newpwd" tabindex="-1" aria-label="Show password">
                                            <span class="rp-eye-glyph" aria-hidden="true">👁</span>
                                        </button>
                                    </div>
                                    <div class="rp-password-rules">
                                        Password rules: minimum 10 characters, including at least one uppercase letter, one lowercase letter, one number, and one symbol.
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="rp_confpwd">Confirm New Password</label>
                                    <div class="input-group rp-eye-wrap">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text"><i class="la la-lock text-white"></i></div>
                                        </div>
                                        <input id="rp_confpwd" name="confirmpassword" type="password" required
                                                                                                 minlength="10"
                                               placeholder="Repeat new password"
                                               class="form-control input-transparent pl-3"
                                               style="border-top-right-radius:.3rem;border-bottom-right-radius:.3rem;">
                                        <button type="button" class="rp-eye-btn" data-target="rp_confpwd" tabindex="-1" aria-label="Show password">
                                            <span class="rp-eye-glyph" aria-hidden="true">👁</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="btn-toolbar">
                                    <button type="submit" id="btn-update" class="btn btn-block btn-primary" style="color:#fff;background-color:#FF0604;border-color:#FF0604;">
                                        <i class="la la-save"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <footer class="page-footer">
            <?php echo date('Y'); ?> &copy; All rights reserved &mdash;
            <a href="https://www.isuzucv.com/" rel="nofollow" target="_blank">Isuzu Motors America, LLC</a>
            &mdash; Developed by Autotrain Colombia
        </footer>
    </main>
</div>

<div class="loader-wrap hiding hide"><i class="fa fa-circle-o-notch fa-spin-fast"></i></div>

<script src="../node_modules/jquery/dist/jquery.min.js"></script>
<script src="../node_modules/messenger/build/js/messenger.js"></script>
<script src="../node_modules/messenger/build/js/messenger-theme-flat.js"></script>
<script>
(function () {
    'use strict';

    var API = <?php echo json_encode($controllerUrl, JSON_UNESCAPED_SLASHES); ?>;
    var countdownTimer = null;

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function fmtTime(s) {
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        var sec = s % 60;
        return pad(h) + ':' + pad(m) + ':' + pad(sec);
    }

    function showAlert(type, msg) {
        var normalizedType = type;
        if (type === 'danger') {
            normalizedType = 'error';
        }

        if (msg && typeof window.Messenger === 'function') {
            try {
                window.Messenger().post({
                    message: msg,
                    type: normalizedType || 'info',
                    showCloseButton: true
                });
                return;
            } catch (e) {
                // Fallback to inline alert below when push notification fails.
            }
        }

        var el = document.getElementById('rp-alert');
        el.className = 'alert alert-' + type + ' animated fadeIn';
        el.textContent = msg;
        el.style.display = 'block';
    }

    function hideAlert() {
        document.getElementById('rp-alert').style.display = 'none';
    }

    (function initPushNotifications() {
        if (typeof window.Messenger !== 'function') {
            return;
        }

        var options = {
            theme: 'flat',
            extraClasses: 'messenger-fixed messenger-on-bottom messenger-on-right'
        };

        if (window.jQuery && typeof window.jQuery.globalMessenger === 'function') {
            window.jQuery.globalMessenger(options);
        }
        window.Messenger.options = options;
    })();

    function setLoading(btn, loading) {
        if (!btn) {
            return;
        }

        if (loading) {
            btn.disabled = true;
            btn.dataset.orig = btn.innerHTML;
            btn.innerHTML = '<span class="rp-spin"></span> Please wait...';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.orig || btn.innerHTML;
        }
    }

    function goToPanel(active) {
        document.querySelectorAll('.rp-panel').forEach(function (panel) {
            panel.classList.remove('rp-active');
        });

        document.getElementById('panel-' + active).classList.add('rp-active');
        hideAlert();

        for (var i = 1; i <= 3; i++) {
            var badge = document.getElementById('badge-' + i);
            if (i < active) {
                badge.className = 'rp-step-badge done';
                badge.textContent = '✓';
            } else if (i === active) {
                badge.className = 'rp-step-badge active';
                badge.textContent = String(i);
            } else {
                badge.className = 'rp-step-badge next';
                badge.textContent = String(i);
            }
        }

        for (var j = 1; j <= 2; j++) {
            document.getElementById('sep-' + j).className = 'sep' + (j < active ? ' done' : '');
        }
    }

    function startCountdown(seconds) {
        clearInterval(countdownTimer);
        var el = document.getElementById('rp-countdown');
        var wrap = document.getElementById('rp-countdown-wrap');

        if (!el || !wrap) {
            return;
        }

        wrap.style.display = 'block';
        el.style.color = '';
        el.textContent = fmtTime(seconds);

        countdownTimer = setInterval(function () {
            seconds--;
            if (seconds <= 0) {
                clearInterval(countdownTimer);
                el.textContent = 'Expired';
                el.style.color = '#dc3545';
            } else {
                el.textContent = fmtTime(seconds);
            }
        }, 1000);
    }

    function apiPost(data, btn, onSuccess) {
        setLoading(btn, true);

        var body = new FormData();
        Object.keys(data).forEach(function (key) {
            body.append(key, data[key]);
        });

        fetch(API, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.text().then(function (text) {
                    var parsed;
                    try {
                        parsed = JSON.parse(text);
                    } catch (e) {
                        throw new Error(text || 'Invalid server response');
                    }

                    if (!response.ok) {
                        throw new Error(parsed.msg || 'Request failed');
                    }

                    return parsed;
                });
            })
            .then(function (res) {
                setLoading(btn, false);

                if (res.restart) {
                    goToPanel(1);
                    showAlert('warning', res.msg || 'Please start over.');
                    return;
                }

                if (!res.ok) {
                    showAlert('danger', res.msg || 'Request failed.');
                    return;
                }

                onSuccess(res);
            })
            .catch(function (error) {
                setLoading(btn, false);
                var message = (error && error.message) ? error.message : 'Network error. Please try again.';
                showAlert('danger', message);
            });
    }

    document.getElementById('form-email').addEventListener('submit', function (e) {
        e.preventDefault();
        var email = document.getElementById('rp_email').value.trim();
        var btn = document.getElementById('btn-send');

        if (!email) {
            showAlert('danger', 'Please enter your email address.');
            return;
        }

        apiPost({ action: 'request_recovery_otp', email: email }, btn, function (res) {
            document.getElementById('rp-masked-email').textContent = res.masked_email || '';
            startCountdown(parseInt(res.remaining || 7200, 10));
            goToPanel(2);
            showAlert('success', res.msg || 'Verification code sent.');
        });
    });

    document.getElementById('form-otp').addEventListener('submit', function (e) {
        e.preventDefault();
        var otp = document.getElementById('rp_otp').value.trim();
        var btn = document.getElementById('btn-verify');

        if (!/^\d{6}$/.test(otp)) {
            showAlert('danger', 'Please enter a valid 6-digit code.');
            return;
        }

        apiPost({ action: 'verify_recovery_otp', otp: otp }, btn, function (res) {
            clearInterval(countdownTimer);
            goToPanel(3);
            showAlert('success', res.msg || 'Code verified.');
        });
    });

    document.getElementById('btn-resend').addEventListener('click', function () {
        var btn = this;
        apiPost({ action: 'resend_recovery_otp' }, btn, function (res) {
            startCountdown(parseInt(res.remaining || 7200, 10));
            showAlert('info', res.msg || 'A new code has been sent.');
        });
    });

    document.getElementById('btn-cancel').addEventListener('click', function () {
        clearInterval(countdownTimer);
        document.getElementById('rp_otp').value = '';
        document.getElementById('rp_email').focus();
        goToPanel(1);
    });

    document.getElementById('form-newpwd').addEventListener('submit', function (e) {
        e.preventDefault();
        var p1 = document.getElementById('rp_newpwd').value;
        var p2 = document.getElementById('rp_confpwd').value;
        var btn = document.getElementById('btn-update');
        var hasMinLength = p1.length >= 10;
        var hasUppercase = /[A-Z]/.test(p1);
        var hasLowercase = /[a-z]/.test(p1);
        var hasNumber = /\d/.test(p1);
        var hasSymbol = /[^A-Za-z0-9]/.test(p1);

        if (!(hasMinLength && hasUppercase && hasLowercase && hasNumber && hasSymbol)) {
            showAlert('danger', 'Password must be at least 10 characters and include uppercase, lowercase, number, and symbol.');
            return;
        }

        if (p1 !== p2) {
            showAlert('danger', 'Passwords do not match.');
            return;
        }

        apiPost({ action: 'set_new_password', newpassword: p1, confirmpassword: p2 }, btn, function (res) {
            showAlert('success', (res.msg || 'Password updated successfully.') + ' Redirecting to sign in...');
            setTimeout(function () {
                window.location.href = res.redirect || 'login.php?recovery=1';
            }, 1500);
        });
    });

    document.querySelectorAll('.rp-eye-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) {
                return;
            }

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';

            var icon = btn.querySelector('.rp-eye-glyph');
            if (icon) {
                icon.textContent = show ? '🙈' : '👁';
            }

            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    window.addEventListener('error', function () {
        showAlert('danger', 'Unexpected error. Please try again.');
    });

    window.addEventListener('unhandledrejection', function () {
        showAlert('danger', 'Unexpected error. Please try again.');
    });
})();
</script>
</body>
</html>
