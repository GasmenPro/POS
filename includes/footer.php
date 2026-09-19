    </div>
</div>
<footer class="app-footer border-top py-3 bg-white">
    <div class="container-fluid text-center text-muted small">
        &copy; <?php echo date('Y'); ?> <?php echo e(APP_NAME); ?> <span class="mx-1">·</span> v<?php echo e(APP_VERSION); ?>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo e(BASE_URL); ?>/assets/js/app.js?v=<?php echo e((string) filemtime(BASE_PATH . '/assets/js/app.js')); ?>"></script>
</body>
</html>
