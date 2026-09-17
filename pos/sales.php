<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/pos.php';

require_permission('sales.view');

$search = trim($_GET['q'] ?? '');
$detail_id = (int) ($_GET['id'] ?? 0);
$sale_detail = $detail_id > 0 ? get_sale_by_id($detail_id) : null;
$sales = get_sales_list($search);

$page_title = APP_NAME . ' — Sales History';
$success = get_flash('success');
$error = get_flash('error');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Sales History</h1>
            <p class="text-muted mb-0">Completed sales and item details.</p>
        </div>
        <?php if (user_has_permission('pos.view')): ?>
            <a href="<?php echo e(BASE_URL); ?>/pos/index.php" class="btn btn-primary">Point of Sale</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <?php if ($sale_detail): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Sale Details — <?php echo e($sale_detail['sale_no']); ?></span>
            <div class="d-flex gap-2">
                <a href="<?php echo e(BASE_URL); ?>/receipts/view.php?sale_id=<?php echo (int) $sale_detail['sale_id']; ?>" class="btn btn-sm btn-outline-primary">View Receipt</a>
                <a href="<?php echo e(BASE_URL); ?>/pos/sales.php" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Date/Time:</strong> <?php echo e(date('M j, Y g:i A', strtotime($sale_detail['sale_date']))); ?></p>
                    <p class="mb-1"><strong>Cashier:</strong> <?php echo e($sale_detail['cashier_name']); ?> (<?php echo e($sale_detail['cashier_username']); ?>)</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Subtotal:</strong> <?php echo e(format_money($sale_detail['subtotal'])); ?></p>
                    <p class="mb-1"><strong>Total:</strong> <?php echo e(format_money($sale_detail['total_amount'])); ?></p>
                    <p class="mb-1"><strong>Payment:</strong> <?php echo e(format_money($sale_detail['payment_amount'])); ?></p>
                    <p class="mb-0"><strong>Change:</strong> <?php echo e(format_money($sale_detail['change_amount'])); ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Product</th>
                            <th>Unit</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sale_detail['items'] as $item): ?>
                        <tr>
                            <td><?php echo e($item['product_code']); ?></td>
                            <td><?php echo e($item['product_name']); ?></td>
                            <td><?php echo e($item['unit_name']); ?></td>
                            <td><?php echo e(format_qty($item['quantity'])); ?></td>
                            <td><?php echo e(format_money($item['unit_price'])); ?></td>
                            <td><?php echo e(format_money($item['line_total'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="Search sale no. or cashier..." value="<?php echo e($search); ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Search</button>
            <?php if ($search !== ''): ?>
                <a href="<?php echo e(BASE_URL); ?>/pos/sales.php" class="btn btn-link">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sale No.</th>
                        <th>Date/Time</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Change</th>
                        <th>Cashier</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sales)): ?>
                    <tr><td colspan="7" class="text-muted">No sales found.</td></tr>
                <?php else: foreach ($sales as $sale): ?>
                    <tr>
                        <td><?php echo e($sale['sale_no']); ?></td>
                        <td><?php echo e(date('M j, Y g:i A', strtotime($sale['sale_date']))); ?></td>
                        <td><?php echo e(format_money($sale['total_amount'])); ?></td>
                        <td><?php echo e(format_money($sale['payment_amount'])); ?></td>
                        <td><?php echo e(format_money($sale['change_amount'])); ?></td>
                        <td><?php echo e($sale['cashier_name']); ?></td>
                        <td>
                            <a href="<?php echo e(BASE_URL); ?>/pos/sales.php?id=<?php echo (int) $sale['sale_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="<?php echo e(BASE_URL); ?>/receipts/view.php?sale_id=<?php echo (int) $sale['sale_id']; ?>" class="btn btn-sm btn-outline-secondary">Receipt</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
