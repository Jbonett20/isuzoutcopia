<?php
$loc = "login";
@session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
include_once("../controllers/login.php");

$loginError = isset($_GET['login_error']) ? ($_SESSION['login_error_flash'] ?? null) : null;
unset($_SESSION['login_error_flash'], $_SESSION['error']);

$recoverySuccess = isset($_GET['recovery']) ? 'Your password has been updated successfully. You can now sign in.' : null;

/* Cancel OTP flow and return to fresh login */
if (isset($_GET['cancel_otp'])) {
    unset($_SESSION['otp_pending'], $_SESSION['otp_masked_email']);
}
/* If OTP was sent but not yet verified, redirect to verify page */
if (isset($_SESSION['otp_pending'])) {
    header("location: verify_otp.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Isuzu CV Order System 2.0 - CVS</title>
        <link rel="shortcut icon" href="../admin/img/favicon.ico">
        <link href="../admin/css/application.min.css" rel="stylesheet">
        <!-- as of IE9 cannot parse css files with more that 4K classes separating in two files -->
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
    .input-container {
      position: relative;
      width: 250px;
    }

    input[type="password"],
    input[type="text"] {
      width: 100%;
    padding-right: 40px; /* space for the icon */
      padding: 10px;
      font-size: 16px;
    }

    .toggle-password {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      cursor: pointer;
    }
  </style>
    </head>
    <body class="login-page" style="background-image: url('../admin/img/background.png');">
        <div class="container">
            <!-- main page content. the place to put widgets in. usually consists of .row > .col-lg-* > .widget.  -->
            <main id="content" class="widget-login-container" role="main">
                <!-- Page content -->
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-md-7 col-10">
                        <section class="widget widget-login animated fadeInUp">
                            <header>
                                <h4><img src="../admin/img/iconoisuzurojo.png" alt="Isuzu" class="logoSup" style="width: 150px;"></h4>
                            </header>
                            <div class="widget-body">
                                <p class="widget-login-info">
                                    <br>Use your email and password to sign in.
                                </p>
                                <form class="login-form mt-lg" id="login" name="login" action="login" method="post" autocomplete="off" data-show-preloader="true">
                                    <div role="group" class="form-group">
                                        <label for="txtemail" class="d-block">Email address</label>
                                        <div>
                                            <div role="group" class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">
                                                        <i class="la la-user text-white"></i>
                                                    </div>
                                                </div>
                                                <input id="txtemail" name="txtemail" type="email" required placeholder="email@domain.com" class="form-control input-transparent pl-3" autocomplete="username">
                                            </div>
                                        </div>
                                    </div>
                                    <div role="group" class="form-group">
                                        <label for="txtpassword" class="d-block">Password</label>
                                        <div>
                                            <div role="group" class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">
                                                        <i class="la la-lock text-white"></i>
                                                    </div>
                                                </div>
                                                <input id="txtpassword" name="txtpassword" type="password" required placeholder="Enter your password" class="form-control input-transparent pl-3" style="border-top-right-radius: .3rem; border-bottom-right-radius: .3rem;" autocomplete="current-password">
                                                <span class="toggle-password" onclick="togglePassword()">👁️</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="clearfix">
                                        <div class="btn-toolbar">
                                            <button class="btn btn-primary btn-block pull-right" type="submit" style="color:white;background-color:#FF0604;border-color:#FF0604;">
                                                <span class="auth-btn-circle">
                                                        <i class="la la-caret-right"></i>
                                                    </span>
                                                    Sign in
                                            </button>
                                        </div>
                                    </div>
                                    <?php if(!empty($loginError)){ ?>
                                        <div class="group">
                                        <div class="alert alert-danger" role="alert" id="loginErrorAlert">
                                            <h4 class="alert-heading text-white fw-bold">Error!</h4>
                                            <p class="text-white fw-bold mb-0 "><?php echo htmlspecialchars($loginError); ?></p>
                                        </div>
                                        </div>
                                    <?php } ?>
                                    <?php if(!empty($recoverySuccess)){ ?>
                                        <div class="group">
                                        <div class="alert alert-success" role="alert">
                                            <p class="text-white fw-bold mb-0"><?php echo htmlspecialchars($recoverySuccess); ?></p>
                                        </div>
                                        </div>
                                    <?php } ?>
                                    <div class="group">
                                        <div class="widget-login-info" style="font-size: 11px;">
                                            Don't have an account? Having trouble logging in? <br>
                                            Contact your system administrator.
                                        </div>
                                    </div>
                                    <div class="group text-center" style="margin-top:4px;">
                                        <a href="recoverpassword.php" style="font-size:12px;color:#FF0604;text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                            <strong>Forgot your password?</strong> Click here to recover it.
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </section>
                    </div>
                </div>

                <footer class="page-footer">
                    <?php echo(date("Y"));?> &copy; All rights reserved - <a href="https://www.isuzucv.com/"rel="nofollow" target="_blank">Isuzu Motors America, LLC</a> - Developed by Autotrain Colombia
                </footer>
            </main>
        </div>
        <!-- The Loader. Is shown when pjax happens -->
        <div class="loader-wrap hiding hide">
            <i class="fa fa-circle-o-notch fa-spin-fast"></i>
        </div>
        <!-- common libraries. required for every page-->
        <script src="../node_modules/jquery/dist/jquery.min.js"></script>
        <script src="../node_modules/jquery-pjax/jquery.pjax.js"></script>
        <script src='../node_modules/popper.js/dist/umd/popper.js'></script>
        <script src="../node_modules/bootstrap/dist/js/bootstrap.js"></script>
        <script src="../node_modules/bootstrap/js/dist/util.js"></script>
        <script src="../node_modules/widgster/widgster.js"></script>
        <script src="../node_modules/hammerjs/hammer.js"></script>
        <script src="../node_modules/jquery-hammerjs/jquery.hammer.js"></script>
        <!-- common app js -->
        <script src="../admin/js/settings.js"></script>
        <script src="../admin/js/app.js"></script>
        <!-- Page scripts -->

        <script>
            function togglePassword() {
            const passwordInput = document.getElementById("txtpassword");
            const toggleIcon = document.querySelector(".toggle-password");

            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";

            // Change the icon if desired
            toggleIcon.textContent = isPassword ? "🙈" : "👁️";
            }

            (function () {
                const navEntry = performance.getEntriesByType && performance.getEntriesByType('navigation').length
                    ? performance.getEntriesByType('navigation')[0]
                    : null;
                const isReload = navEntry && navEntry.type === 'reload';

                if (window.history.replaceState && window.location.search.indexOf('login_error=1') !== -1) {
                    window.history.replaceState({}, document.title, window.location.pathname.replace(/\.php$/, '').endsWith('/login') ? window.location.pathname : 'login');
                }

                window.addEventListener('pageshow', function (event) {
                    const alertBox = document.getElementById('loginErrorAlert');
                    if (alertBox && (event.persisted || isReload)) {
                        const wrapper = alertBox.closest('.group');
                        if (wrapper) {
                            wrapper.style.display = 'none';
                        } else {
                            alertBox.style.display = 'none';
                        }
                    }
                });
            })();
        </script>
        <?php require_once("src/preloader.php"); ?>
    </body>
</html>