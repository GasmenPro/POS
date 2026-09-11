<?php
/**
 * Login page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('/account.php');
}

$page_title = APP_NAME . ' — Login';
$error = get_flash('error');
$success = get_flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo e(BASE_URL); ?>/assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-1"><?php echo e(APP_NAME); ?></h1>
                    <p class="text-center text-muted small mb-4">Sign in to your account</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success py-2"><?php echo e($success); ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo e(BASE_URL); ?>/auth/login_process.php" autocomplete="off">
                        <?php csrf_field(); ?>
                        <div class="mb-3">
                            <label for="login" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="login" name="login" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
            <p class="text-center mt-3 small">
                <a href="<?php echo e(BASE_URL); ?>/">Back to home</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
