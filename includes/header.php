<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title><?php echo e($page_title ?? APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo e(BASE_URL); ?>/assets/css/custom.css?v=<?php echo e((string) filemtime(BASE_PATH . '/assets/css/custom.css')); ?>" rel="stylesheet">
</head>
<body class="app-body">
<a class="visually-hidden-focusable skip-link" href="#main-content">Skip to main content</a>
<?php require_once BASE_PATH . '/includes/navbar.php'; ?>
<div class="container-fluid app-shell px-0">
    <div class="row g-0 flex-nowrap">
