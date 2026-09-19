<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_permission('pos.manage');

$user = get_logged_in_user();
$page_title = APP_NAME . ' — Offline POS';
$offline_config = [
    'baseUrl' => BASE_URL,
    'userId' => (int) $user['id'],
    'username' => $user['username'],
    'csrfToken' => csrf_token(),
    'productsUrl' => BASE_URL . '/pos/offline_products.php',
    'healthUrl' => BASE_URL . '/pos/offline_health.php',
    'syncUrl' => BASE_URL . '/pos/offline_sync.php',
    'receiptBaseUrl' => BASE_URL . '/receipts/view.php?sale_id=',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#b45309">
    <title><?php echo e($page_title); ?></title>
    <link rel="manifest" href="<?php echo e(BASE_URL); ?>/manifest.webmanifest">
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/offline-pos.css?v=<?php echo e((string) filemtime(BASE_PATH . '/assets/css/offline-pos.css')); ?>">
</head>
<body>
<header class="offline-header">
    <div>
        <div class="offline-kicker">OFFLINE MODE</div>
        <h1><?php echo e(APP_NAME); ?> — Offline POS</h1>
        <p>Sales created here remain pending until the server verifies and synchronizes them.</p>
    </div>
    <div class="offline-header-actions">
        <span id="connection-status" class="status-pill status-checking" role="status" aria-live="polite">Checking server…</span>
        <a href="<?php echo e(BASE_URL); ?>/pos/index.php" class="button button-light">Online POS</a>
    </div>
</header>

<main class="offline-container">
    <section id="message-box" class="message" role="status" aria-live="polite" hidden></section>

    <section class="status-grid">
        <div class="status-card"><span>Cashier</span><strong><?php echo e($user['username']); ?></strong></div>
        <div class="status-card"><span>Last Offline Data Update</span><strong id="last-cache-time" aria-live="polite">Never</strong></div>
        <div class="status-card"><span>Pending Sales</span><strong id="pending-count" aria-live="polite">0</strong></div>
        <div class="status-actions">
            <button type="button" id="update-cache-button" class="button button-primary">↻ Update Offline Data</button>
            <button type="button" id="sync-button" class="button button-success">⇅ Sync Pending Sales</button>
        </div>
    </section>

    <div class="offline-warning">
        <strong>Cached inventory is an estimate.</strong> Final price, product status, and stock are verified by the server during synchronization.
    </div>

    <div class="pos-grid">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Cached Products</h2>
                    <small id="product-count">0 products cached</small>
                </div>
            </div>
            <div class="panel-body">
                <label for="product-search">Product code, barcode, or name</label>
                <input type="search" id="product-search" placeholder="Scan barcode or search cached products…" autocomplete="off" autofocus>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Code</th><th>Product</th><th>Price</th><th>Cached Stock</th><th></th></tr></thead>
                        <tbody id="product-results"><tr><td colspan="5" class="empty">Update offline data while online to begin.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Offline Cart</h2>
                <button type="button" id="clear-cart-button" class="button button-danger button-small">Clear</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Item</th><th>Qty</th><th>Total</th><th></th></tr></thead>
                    <tbody id="cart-items"><tr><td colspan="4" class="empty">Cart is empty.</td></tr></tbody>
                </table>
            </div>
            <div class="checkout-box">
                <div class="total-row"><span>Pending Total</span><strong id="cart-total">₱0.00</strong></div>
                <label for="payment-amount">Cash Received</label>
                <input type="number" id="payment-amount" min="0" step="0.01" inputmode="decimal">
                <div class="total-row"><span>Change</span><strong id="change-amount">₱0.00</strong></div>
                <button type="button" id="save-sale-button" class="button button-warning button-block">Save Pending Offline Sale</button>
                <small>This does not create an official sale until synchronization succeeds.</small>
            </div>
        </section>
    </div>

    <section class="panel pending-panel">
        <div class="panel-heading">
            <div><h2>Pending Offline Sales</h2><small>Older pending sales synchronize first; one conflict does not block later sales.</small></div>
        </div>
        <div id="pending-sales" class="pending-list" aria-live="polite"><div class="empty padded">No offline sales stored.</div></div>
    </section>
</main>

<script id="offline-config" type="application/json"><?php echo json_encode($offline_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?></script>
<script src="<?php echo e(BASE_URL); ?>/assets/js/offline-pos.js?v=<?php echo e((string) filemtime(BASE_PATH . '/assets/js/offline-pos.js')); ?>"></script>
</body>
</html>
