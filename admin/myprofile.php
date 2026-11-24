<?php
$loc = 'profile';
include_once '../admin/src/seguridad.php';
@session_start();
include_once '../controllers/profile.php';

$clearPasswordDraft = false;
if (!empty($profileFlash) && ($profileFlash['type'] ?? '') === 'success') {
    $flashMsg = strtolower((string)($profileFlash['message'] ?? ''));
    $clearPasswordDraft = strpos($flashMsg, 'password was updated successfully') !== false;
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('src/header.php'); ?>
    <style>
        .profile-card {
            background: #ffffff;
            border: 1px solid #d9d9d9;
            border-radius: 12px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .profile-title {
            font-size: 16px;
            font-weight: 700;
            color: #111111;
            margin-bottom: 16px;
            padding-left: 10px;
            border-left: 4px solid #ff0604;
        }
        .profile-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px dashed #d8d8d8;
            padding: 10px 0;
        }
        .profile-row:last-child { border-bottom: none; }
        .profile-label {
            color: #333333;
            font-size: 13px;
            font-weight: 600;
        }
        .profile-value {
            color: #111111;
            font-size: 13px;
            text-align: right;
            max-width: 65%;
            word-break: break-word;
        }
        .profile-card label {
            color: #222222;
            font-weight: 600;
        }
        .profile-card .form-control {
            color: #111111;
            background: #ffffff;
            border: 1px solid #cfcfcf;
        }
        .profile-card .form-control:focus {
            border-color: #ff0604;
            box-shadow: 0 0 0 0.2rem rgba(255, 6, 4, 0.15);
        }
        .password-toggle {
            cursor: pointer;
            user-select: none;
            min-width: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .password-toggle i {
            color: #444444;
        }
        .otp-panel {
            margin-top: 16px;
            padding: 14px;
            border-radius: 10px;
            background: #f8f8f8;
            border: 1px solid #f2b8b8;
        }
        .otp-helper {
            font-size: 12px;
            color: #444444;
            margin-bottom: 10px;
        }
        .password-rules {
            margin-top: 8px;
            font-size: 12px;
            color: #555555;
            line-height: 1.45;
        }
        @media (max-width: 767px) {
            .profile-row { flex-direction: column; }
            .profile-value { max-width: 100%; text-align: left; }
        }
    </style>
</head>
<body data-clear-password-draft="<?php echo $clearPasswordDraft ? '1' : '0'; ?>">
    <?php require_once('src/navbar.php'); ?>

    <nav id="sidebar" class="sidebar" role="navigation">
        <div class="js-sidebar-content">
            <?php require_once('src/logo.php'); ?>
            <?php require_once('src/sidebar.php'); ?>
        </div>
    </nav>

    <div class="content-wrap">
        <main id="content" class="content" role="main">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">You are in</li>
                <li class="breadcrumb-item active">My Profile</li>
            </ol>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <section class="profile-card">
                        <h2 class="profile-title">My Profile</h2>

                        <div class="profile-row">
                            <span class="profile-label">Name</span>
                            <span class="profile-value"><?php echo htmlspecialchars((string)($profileData['nombres'] ?? '')); ?></span>
                        </div>

                        <div class="profile-row">
                            <span class="profile-label">Email</span>
                            <span class="profile-value"><?php echo htmlspecialchars((string)($profileData['email'] ?? '')); ?></span>
                        </div>

                        <div class="profile-row">
                            <span class="profile-label">Roles</span>
                            <span class="profile-value">
                                <?php
                                $roles = $_SESSION['UsrData']['roles'] ?? null;
                                if (!empty($roles) && is_array($roles)) {
                                    foreach ($roles as $role) {
                                        echo '<div style="text-transform: uppercase; margin-bottom: 5px;">' . htmlspecialchars((string)($role['rol'] ?? '')) . '</div>';
                                    }
                                } else {
                                    echo '<div style="text-transform: uppercase;">' . htmlspecialchars((string)($profileData['rol'] ?? '')) . '</div>';
                                }
                                ?>
                            </span>
                        </div>

                        <div class="profile-row">
                            <span class="profile-label">Last Connection</span>
                            <span class="profile-value"><?php echo htmlspecialchars((string)($profileData['ultimaconexion'] ?? 'N/A')); ?></span>
                        </div>

                        <div class="profile-row">
                            <span class="profile-label">Dealer Job Title</span>
                            <span class="profile-value"><?php echo htmlspecialchars((string)($profileData['cargodealer'] ?? '')); ?></span>
                        </div>

                        <?php if (!empty($profileData['dealer'])): ?>
                            <div class="profile-row">
                                <span class="profile-label">Dealer</span>
                                <span class="profile-value"><?php echo htmlspecialchars((string)$profileData['dealer']); ?></span>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="col-lg-6 mb-4">
                    <section class="profile-card">
                        <h2 class="profile-title">Change Password</h2>

                        <form method="post" autocomplete="off" data-show-preloader="true" id="password-request-form">
                            <input type="hidden" name="action" value="request_password_otp">

                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text password-toggle" data-target="current_password" title="Show / Hide">
                                            <i class="fa fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text password-toggle" data-target="new_password" title="Show / Hide">
                                            <i class="fa fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="password-rules">
                                    Password rules: minimum 10 characters, including at least one uppercase letter, one lowercase letter, one number, and one symbol.
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text password-toggle" data-target="confirm_password" title="Show / Hide">
                                            <i class="fa fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger">Send Verification Code</button>
                        </form>

                        <?php if (!empty($hasPendingPwdChange)): ?>
                            <div class="otp-panel">
                                <p class="otp-helper mb-2">
                                    A verification code was sent to <?php echo htmlspecialchars($maskedEmail); ?>.
                                </p>
                                <p class="otp-helper">
                                    Enter it below. The code expires in 2 hours.
                                    <?php if ($otpRemainingSeconds > 0): ?>
                                        Remaining: <?php echo (int)$otpRemainingSeconds; ?> seconds.
                                    <?php endif; ?>
                                </p>

                                <form method="post" class="mb-2" autocomplete="off">
                                    <input type="hidden" name="action" value="confirm_password_otp">
                                    <div class="form-group mb-2">
                                        <label for="verification_code">Verification Code</label>
                                        <input type="text" maxlength="6" pattern="\d{6}" class="form-control" id="verification_code" name="verification_code" placeholder="Enter 6-digit code" required>
                                    </div>
                                    <button type="submit" class="btn btn-danger">Update Password</button>
                                </form>

                                <form method="post" autocomplete="off">
                                    <input type="hidden" name="action" value="resend_password_otp">
                                    <button type="submit" class="btn btn-outline-danger">Resend Code</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>

            <footer class="content-footer hidden-print">
                <?php require_once('src/footer.php'); ?>
            </footer>
        </main>
    </div>

    <?php require_once('src/preloader.php'); ?>
    <?php
        $flashTypeRaw = (string)($profileFlash['type'] ?? '');
        $flashMessage = (string)($profileFlash['message'] ?? '');
        $flashType = 'info';
        if ($flashTypeRaw === 'success') {
            $flashType = 'success';
        } elseif ($flashTypeRaw === 'danger') {
            $flashType = 'error';
        } elseif ($flashTypeRaw === 'warning') {
            $flashType = 'warning';
        }
    ?>
    <script>
        (function () {
            const toggles = document.querySelectorAll('.password-toggle');
            toggles.forEach((toggle) => {
                toggle.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    if (!input || !icon) return;

                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';

                    icon.classList.remove('fa-eye', 'fa-eye-slash');
                    icon.classList.add(isPassword ? 'fa-eye-slash' : 'fa-eye');
                });
            });
        })();
    </script>
    <script>
        (function () {
            const form = document.getElementById('password-request-form');
            const currentInput = document.getElementById('current_password');
            const newInput = document.getElementById('new_password');
            const confirmInput = document.getElementById('confirm_password');
            const storageKey = 'myprofile_password_request_draft';

            if (!form || !currentInput || !newInput || !confirmInput) {
                return;
            }

            const shouldClear = document.body && document.body.getAttribute('data-clear-password-draft') === '1';
            const navEntries = (window.performance && typeof window.performance.getEntriesByType === 'function')
                ? window.performance.getEntriesByType('navigation')
                : [];
            const isReload = navEntries.length > 0 && navEntries[0].type === 'reload';

            if (isReload) {
                sessionStorage.removeItem(storageKey);
                currentInput.value = '';
                newInput.value = '';
                confirmInput.value = '';
            }

            if (shouldClear) {
                sessionStorage.removeItem(storageKey);
            }

            try {
                const raw = sessionStorage.getItem(storageKey);
                if (raw) {
                    const draft = JSON.parse(raw);
                    currentInput.value = draft.current_password || '';
                    newInput.value = draft.new_password || '';
                    confirmInput.value = draft.confirm_password || '';
                }
            } catch (e) {
                sessionStorage.removeItem(storageKey);
            }

            form.addEventListener('submit', function (e) {
                const actionField = form.querySelector('input[name="action"]');
                if (!actionField || actionField.value !== 'request_password_otp') {
                    return;
                }

                const draft = {
                    current_password: currentInput.value,
                    new_password: newInput.value,
                    confirm_password: confirmInput.value
                };

                sessionStorage.setItem(storageKey, JSON.stringify(draft));

                const password = newInput.value || '';
                const hasMinLength = password.length >= 10;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSymbol = /[^A-Za-z0-9]/.test(password);

                if (!(hasMinLength && hasUppercase && hasLowercase && hasNumber && hasSymbol)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    if (window.ProjectPreloader && typeof window.ProjectPreloader.hide === 'function') {
                        window.ProjectPreloader.hide();
                    }

                    const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                    submitButtons.forEach((btn) => {
                        btn.disabled = false;
                    });

                    if (typeof window.Messenger === 'function') {
                        window.Messenger().post({
                            message: 'Password must be at least 10 characters and include uppercase, lowercase, number, and symbol.',
                            type: 'error',
                            showCloseButton: true
                        });
                    }
                    newInput.focus();
                    return;
                }
            }, true);
        })();
    </script>
    <?php require_once('src/scripts.php'); ?>
    <script>
        (function () {
            const flashMessage = <?php echo json_encode($flashMessage, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const flashType = <?php echo json_encode($flashType, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            if (!flashMessage || typeof window.Messenger !== 'function') {
                return;
            }

            window.Messenger().post({
                message: flashMessage,
                type: flashType,
                showCloseButton: true
            });
        })();
    </script>
</body>
</html>
