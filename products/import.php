<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/product_import.php';

require_permission('products.manage');

if (isset($_GET['download']) && $_GET['download'] === 'template') {
    output_product_import_template();
}

$result = $_SESSION['product_import_result'] ?? null;
unset($_SESSION['product_import_result']);
$error = get_flash('error');
$page_title = APP_NAME . ' — Import Products';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Import Products</h1>
            <p class="text-muted mb-0">Create product records from a validated CSV file.</p>
        </div>
        <div>
            <a href="<?php echo e(BASE_URL); ?>/products/import.php?download=template" class="btn btn-outline-success"><i class="bi bi-download" aria-hidden="true"></i> Download CSV Template</a>
            <a href="<?php echo e(BASE_URL); ?>/products/index.php" class="btn btn-outline-secondary"><i class="bi bi-box-seam" aria-hidden="true"></i> Products</a>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <?php if (is_array($result)): ?>
        <?php if ($result['fatal_error'] !== ''): ?>
            <div class="alert alert-danger"><strong>Import failed.</strong> <?php echo e($result['fatal_error']); ?></div>
        <?php else: ?>
            <div class="alert alert-<?php echo $result['failed'] > 0 ? 'warning' : 'success'; ?>">
                <strong>Import completed.</strong>
                Processed <?php echo e($result['total_rows']); ?> row(s):
                <?php echo e($result['imported']); ?> imported and <?php echo e($result['failed']); ?> rejected.
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Import Result</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">File</dt><dd class="col-sm-8"><?php echo e($result['filename']); ?></dd>
                    <dt class="col-sm-4">Rows processed</dt><dd class="col-sm-8"><?php echo e($result['total_rows']); ?></dd>
                    <dt class="col-sm-4">Successfully imported</dt><dd class="col-sm-8"><?php echo e($result['imported']); ?></dd>
                    <dt class="col-sm-4">Failed / skipped</dt><dd class="col-sm-8"><?php echo e($result['failed']); ?></dd>
                </dl>
            </div>
        </div>

        <?php if (!empty($result['errors'])): ?>
            <div class="card mb-4">
                <div class="card-header">Row Validation Errors</div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>CSV Row</th><th>Reason</th></tr></thead>
                        <tbody>
                        <?php foreach ($result['errors'] as $row_error): ?>
                            <tr><td><?php echo e($row_error['row']); ?></td><td><?php echo e($row_error['message']); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card import-dropzone">
                <div class="card-header"><i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Upload CSV</div>
                <div class="card-body">
                    <form method="post" action="<?php echo e(BASE_URL); ?>/products/import_process.php" enctype="multipart/form-data">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo e(PRODUCT_IMPORT_MAX_SIZE); ?>">
                        <div class="mb-3">
                            <label for="csv-file" class="form-label">CSV file</label>
                            <input type="file" class="form-control" id="csv-file" name="csv_file" accept=".csv,text/csv" required>
                            <div class="form-text">Maximum 2 MB and 5,000 product rows. Uploaded files are parsed from PHP temporary storage and are not saved publicly.</div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload" aria-hidden="true"></i> Import Products</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">CSV Requirements</div>
                <div class="card-body">
                    <p class="mb-2"><strong>Required headers, in any order:</strong></p>
                    <code class="d-block text-wrap mb-3">product_code,barcode,product_name,category,brand,unit,description,selling_price,status</code>
                    <ul class="mb-3">
                        <li>Required values: product code, product name, active category, active unit, and a nonnegative selling price.</li>
                        <li>Optional values: barcode, active brand, description, and status. Blank status defaults to <code>active</code>.</li>
                        <li>Unit may contain its active unit name or unit code.</li>
                        <li>Status must be <code>active</code> or <code>inactive</code>.</li>
                        <li>Existing or repeated product codes and nonblank barcodes are rejected; existing products are never overwritten.</li>
                        <li>Product images and opening stock are not imported. Every imported product starts with zero inventory and zero reorder level.</li>
                    </ul>
                    <p class="small text-muted mb-0">Replace or remove the example rows in the template before importing it.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
