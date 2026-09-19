<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/pos.php';

require_permission('pos.view');

$search = trim($_GET['q'] ?? '');
$products = search_pos_products($search);
$cart = get_pos_cart();
refresh_cart_prices();
$cart = get_pos_cart();
$subtotal = get_cart_subtotal($cart);
$can_manage = user_has_permission('pos.manage');

$page_title = APP_NAME . ' — Point of Sale';
$success = get_flash('success');
$error = get_flash('error');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4 pos-page">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Point of Sale</h1>
            <p class="text-muted mb-0">Search products, build a cart, and checkout with cash payment.</p>
        </div>
        <?php if (user_has_permission('sales.view')): ?>
            <a href="<?php echo e(BASE_URL); ?>/pos/sales.php" class="btn btn-outline-secondary"><i class="bi bi-clock-history" aria-hidden="true"></i> Sales History</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-3 pos-products-card">
                <div class="card-header"><i class="bi bi-search" aria-hidden="true"></i> Product Search</div>
                <div class="card-body">
                    <form method="get" class="row g-2 mb-2 filter-bar">
                        <div class="col-md-8">
                            <label for="pos-search" class="visually-hidden">Search products</label>
                            <input type="search" name="q" id="pos-search" class="form-control form-control-lg pos-search-input" placeholder="Scan barcode or search code/name…" value="<?php echo e($search); ?>" autocomplete="off" data-pos-search autofocus>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
                            <?php if ($search !== ''): ?>
                                <a href="<?php echo e(BASE_URL); ?>/pos/index.php" class="btn btn-link">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <p class="form-text mb-3"><kbd>F2</kbd> focuses product search.</p>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Unit</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <?php if ($can_manage): ?><th></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="<?php echo $can_manage ? 6 : 5; ?>" class="text-muted">No active products with stock found.</td></tr>
                            <?php else: foreach ($products as $p): ?>
                                <tr>
                                    <td><?php echo e($p['product_code']); ?></td>
                                    <td class="fw-semibold"><?php echo e($p['product_name']); ?></td>
                                    <td><?php echo e($p['unit_name']); ?></td>
                                    <td class="money"><?php echo e(format_money($p['selling_price'])); ?></td>
                                    <td class="stock-value"><?php echo e(format_qty($p['stock'])); ?></td>
                                    <?php if ($can_manage): ?>
                                    <td>
                                        <form method="post" action="<?php echo e(BASE_URL); ?>/pos/process.php" class="d-inline">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?php echo (int) $p['product_id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="q" value="<?php echo e($search); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-cart-plus" aria-hidden="true"></i> Add</button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 pos-checkout-column">
            <div class="card mb-3 pos-cart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cart3" aria-hidden="true"></i> Cart <span class="badge text-bg-primary ms-1"><?php echo count($cart); ?></span></span>
                    <?php if ($can_manage && !empty($cart)): ?>
                    <form method="post" action="<?php echo e(BASE_URL); ?>/pos/process.php" class="d-inline">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear the cart?');">Clear Cart</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <?php if ($can_manage): ?><th></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($cart)): ?>
                                <tr><td colspan="<?php echo $can_manage ? 5 : 4; ?>" class="text-muted p-3">Cart is empty.</td></tr>
                            <?php else: foreach ($cart as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($item['product_name']); ?></div>
                                        <small class="text-muted"><?php echo e($item['product_code']); ?> · <?php echo e($item['unit_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($can_manage): ?>
                                        <form method="post" action="<?php echo e(BASE_URL); ?>/pos/process.php" class="d-flex gap-1 align-items-center">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="set_qty">
                                            <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                            <label class="visually-hidden" for="cart-qty-<?php echo (int) $item['product_id']; ?>">Quantity for <?php echo e($item['product_name']); ?></label>
                                            <input type="number" name="quantity" id="cart-qty-<?php echo (int) $item['product_id']; ?>" class="form-control form-control-sm quantity-input" min="0.001" step="any" value="<?php echo e(format_qty($item['quantity'])); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Update quantity">Set</button>
                                        </form>
                                        <div class="btn-group btn-group-sm mt-1">
                                            <form method="post" action="<?php echo e(BASE_URL); ?>/pos/process.php" class="d-inline">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="dec">
                                                <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                                <button type="submit" class="btn btn-outline-secondary" aria-label="Decrease <?php echo e($item['product_name']); ?> quantity">−</button>
                                            </form>
                                            <form method="post" action="<?php echo e(BASE_URL); ?>/pos/process.php" class="d-inline">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="inc">
                                                <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                                <button type="submit" class="btn btn-outline-secondary" aria-label="Increase <?php echo e($item['product_name']); ?> quantity">+</button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                            <?php echo e(format_qty($item['quantity'])); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="money"><?php echo e(format_money($item['unit_price'])); ?></td>
                                    <td class="money fw-semibold"><?php echo e(format_money($item['line_total'])); ?></td>
                                    <?php if ($can_manage): ?>
                                    <td>
                                        <form method="post" action="<?php echo e(BASE_URL); ?>/pos/process.php">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Remove <?php echo e($item['product_name']); ?> from cart" title="Remove item"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($can_manage): ?>
            <div class="card pos-total-panel">
                <div class="card-header"><i class="bi bi-cash-coin" aria-hidden="true"></i> Checkout</div>
                <div class="card-body">
                    <dl class="row mb-3 pos-totals">
                        <dt class="col-6">Subtotal</dt>
                        <dd class="col-6 text-end money" id="cart-subtotal"><?php echo e(format_money($subtotal)); ?></dd>
                        <dt class="col-6">Total Amount</dt>
                        <dd class="col-6 text-end pos-grand-total" id="cart-total"><?php echo e(format_money($subtotal)); ?></dd>
                    </dl>

                    <?php if (!empty($cart)): ?>
                    <form method="post" action="<?php echo e(BASE_URL); ?>/pos/process.php" id="checkout-form">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="checkout">
                        <div class="mb-3">
                            <label for="payment_amount" class="form-label">Payment Amount (Cash)</label>
                            <input type="number" name="payment_amount" id="payment_amount" class="form-control form-control-lg" min="0" step="0.01" inputmode="decimal" data-pos-payment required>
                            <div class="form-text"><kbd>F4</kbd> focuses cash payment.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Change</label>
                            <div class="form-control form-control-lg bg-light pos-change" id="change-display" role="status" aria-live="polite"><?php echo e(format_money(0)); ?></div>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100"><i class="bi bi-check2-circle" aria-hidden="true"></i> Complete Sale</button>
                    </form>
                    <?php else: ?>
                        <p class="text-muted mb-0">Add products to the cart before checkout.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php if ($can_manage && !empty($cart)): ?>
<script>
(function () {
    var total = <?php echo json_encode((float) $subtotal); ?>;
    var paymentInput = document.getElementById('payment_amount');
    var changeDisplay = document.getElementById('change-display');

    function updateChange() {
        var payment = parseFloat(paymentInput.value) || 0;
        var change = payment >= total ? payment - total : 0;
        changeDisplay.textContent = '₱' + change.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    paymentInput.addEventListener('input', updateChange);
    updateChange();
})();
</script>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
