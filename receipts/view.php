<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/pos.php';

require_permission('sales.view');

$sale_id = filter_input(INPUT_GET, 'sale_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$sale_id) {
    set_flash('error', 'Invalid sale.');
    redirect('/pos/sales.php');
}

$sale = get_sale_by_id((int) $sale_id);
if (!$sale) {
    set_flash('error', 'Sale not found.');
    redirect('/pos/sales.php');
}

$store = get_receipt_store_info();
$page_title = 'Receipt ' . $sale['sale_no'] . ' — ' . APP_NAME;

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4 receipt-page">
    <div class="receipt-actions d-flex justify-content-center gap-2 mb-3">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
        <a href="<?php echo e(BASE_URL); ?>/pos/sales.php?id=<?php echo (int) $sale['sale_id']; ?>" class="btn btn-outline-secondary">Back to Sale</a>
        <?php if (user_has_permission('pos.view')): ?>
            <a href="<?php echo e(BASE_URL); ?>/pos/index.php" class="btn btn-outline-secondary">Point of Sale</a>
        <?php endif; ?>
    </div>

    <article class="receipt-card mx-auto bg-white p-3" aria-label="Receipt <?php echo e($sale['sale_no']); ?>">
        <header class="text-center mb-3">
            <h1 class="receipt-store-name h5 mb-1"><?php echo e($store['name']); ?></h1>
            <?php if ($store['address'] !== ''): ?>
                <div class="receipt-muted"><?php echo nl2br(e($store['address'])); ?></div>
            <?php endif; ?>
            <?php if ($store['contact'] !== ''): ?>
                <div class="receipt-muted"><?php echo e($store['contact']); ?></div>
            <?php endif; ?>
        </header>

        <div class="receipt-divider"></div>
        <dl class="row g-0 receipt-meta mb-2">
            <dt class="col-4">Sale No.</dt>
            <dd class="col-8 text-end mb-1"><?php echo e($sale['sale_no']); ?></dd>
            <dt class="col-4">Date</dt>
            <dd class="col-8 text-end mb-1"><?php echo e(date('M j, Y g:i A', strtotime($sale['sale_date']))); ?></dd>
            <dt class="col-4">Cashier</dt>
            <dd class="col-8 text-end mb-1"><?php echo e($sale['cashier_name']); ?></dd>
        </dl>
        <div class="receipt-divider"></div>

        <div class="table-responsive receipt-items">
            <table class="table table-sm table-borderless mb-2">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sale['items'] as $item): ?>
                    <tr>
                        <td>
                            <span class="d-block"><?php echo e($item['product_name']); ?></span>
                            <small class="receipt-muted"><?php echo e($item['product_code'] . ' · ' . $item['unit_name']); ?></small>
                        </td>
                        <td class="text-end"><?php echo e(format_qty($item['quantity'])); ?></td>
                        <td class="text-end"><?php echo e(format_money($item['unit_price'])); ?></td>
                        <td class="text-end"><?php echo e(format_money($item['line_total'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="receipt-divider"></div>
        <dl class="row g-0 receipt-totals mb-3">
            <dt class="col-6">Subtotal</dt>
            <dd class="col-6 text-end mb-1"><?php echo e(format_money($sale['subtotal'])); ?></dd>
            <dt class="col-6 receipt-total-label">Total</dt>
            <dd class="col-6 text-end mb-1 receipt-total-value"><?php echo e(format_money($sale['total_amount'])); ?></dd>
            <dt class="col-6">Cash Payment</dt>
            <dd class="col-6 text-end mb-1"><?php echo e(format_money($sale['payment_amount'])); ?></dd>
            <dt class="col-6">Change</dt>
            <dd class="col-6 text-end mb-1"><?php echo e(format_money($sale['change_amount'])); ?></dd>
        </dl>

        <footer class="receipt-footer text-center">
            Thank you for your purchase!
        </footer>
    </article>
</main>

<style>
.receipt-card {
    width: min(100%, 80mm);
    border: 1px solid #dee2e6;
    border-radius: .375rem;
    font-family: "Courier New", Courier, monospace;
    font-size: 12px;
    line-height: 1.3;
}

.receipt-store-name {
    font-family: inherit;
    font-weight: 700;
}

.receipt-muted {
    color: #6c757d;
}

.receipt-divider {
    border-top: 1px dashed #495057;
    margin: .6rem 0;
}

.receipt-card .table {
    font-size: inherit;
}

.receipt-card .table > :not(caption) > * > * {
    padding: .25rem .1rem;
}

.receipt-total-label,
.receipt-total-value {
    font-size: 1.08em;
    font-weight: 700;
}

.receipt-footer {
    border-top: 1px dashed #495057;
    padding-top: .75rem;
}

@media print {
    @page {
        margin: 4mm;
    }

    body {
        display: block;
        background: #fff !important;
    }

    .navbar,
    .sidebar,
    body > footer,
    .receipt-actions {
        display: none !important;
    }

    .container-fluid,
    .container-fluid > .row {
        display: block;
        width: 100%;
        margin: 0;
        padding: 0;
    }

    .receipt-page {
        display: block;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .receipt-card {
        width: 80mm;
        max-width: 100%;
        margin: 0 auto !important;
        padding: 0 !important;
        border: 0;
        border-radius: 0;
        box-shadow: none !important;
        color: #000;
    }

    .receipt-muted {
        color: #000;
    }
}
</style>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
