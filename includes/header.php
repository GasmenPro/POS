<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title ?? APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo e(BASE_URL); ?>/assets/css/custom.css" rel="stylesheet">
</head>
<body>
<?php require_once BASE_PATH . '/includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
