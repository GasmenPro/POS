(function () {
    'use strict';

    var configElement = document.getElementById('offline-config');
    if (!configElement || !window.indexedDB) {
        return;
    }

    var config = JSON.parse(configElement.textContent);
    var DB_NAME = 'SariSariOfflinePOS';
    var DB_VERSION = 1;
    var dbPromise = null;
    var products = [];
    var cart = [];
    var syncing = false;
    var serverReachable = false;
    var connectionErrorType = null;

    var elements = {
        connection: document.getElementById('connection-status'),
        lastCache: document.getElementById('last-cache-time'),
        pendingCount: document.getElementById('pending-count'),
        updateCache: document.getElementById('update-cache-button'),
        sync: document.getElementById('sync-button'),
        search: document.getElementById('product-search'),
        productResults: document.getElementById('product-results'),
        productCount: document.getElementById('product-count'),
        cartItems: document.getElementById('cart-items'),
        cartTotal: document.getElementById('cart-total'),
        payment: document.getElementById('payment-amount'),
        change: document.getElementById('change-amount'),
        saveSale: document.getElementById('save-sale-button'),
        clearCart: document.getElementById('clear-cart-button'),
        pendingSales: document.getElementById('pending-sales'),
        message: document.getElementById('message-box')
    };

    function openDatabase() {
        if (dbPromise) {
            return dbPromise;
        }
        dbPromise = new Promise(function (resolve, reject) {
            var request = indexedDB.open(DB_NAME, DB_VERSION);
            request.onupgradeneeded = function () {
                var db = request.result;
                if (!db.objectStoreNames.contains('products')) {
                    var productStore = db.createObjectStore('products', { keyPath: 'product_id' });
                    productStore.createIndex('product_code', 'product_code', { unique: false });
                    productStore.createIndex('barcode', 'barcode', { unique: false });
                }
                if (!db.objectStoreNames.contains('pending_sales')) {
                    var saleStore = db.createObjectStore('pending_sales', { keyPath: 'offline_transaction_id' });
                    saleStore.createIndex('created_at', 'created_at', { unique: false });
                    saleStore.createIndex('cashier_user_id', 'cashier_user_id', { unique: false });
                }
                if (!db.objectStoreNames.contains('metadata')) {
                    db.createObjectStore('metadata', { keyPath: 'key' });
                }
            };
            request.onsuccess = function () { resolve(request.result); };
            request.onerror = function () { reject(request.error); };
        });
        return dbPromise;
    }

    function idbRequest(request) {
        return new Promise(function (resolve, reject) {
            request.onsuccess = function () { resolve(request.result); };
            request.onerror = function () { reject(request.error); };
        });
    }

    function transactionDone(transaction) {
        return new Promise(function (resolve, reject) {
            transaction.oncomplete = function () { resolve(); };
            transaction.onerror = function () { reject(transaction.error); };
            transaction.onabort = function () { reject(transaction.error || new Error('IndexedDB transaction aborted.')); };
        });
    }

    async function getAll(storeName) {
        var db = await openDatabase();
        var tx = db.transaction(storeName, 'readonly');
        return idbRequest(tx.objectStore(storeName).getAll());
    }

    async function putRecord(storeName, value) {
        var db = await openDatabase();
        var tx = db.transaction(storeName, 'readwrite');
        tx.objectStore(storeName).put(value);
        await transactionDone(tx);
    }

    async function deleteRecord(storeName, key) {
        var db = await openDatabase();
        var tx = db.transaction(storeName, 'readwrite');
        tx.objectStore(storeName).delete(key);
        await transactionDone(tx);
    }

    async function getMetadata(key) {
        var db = await openDatabase();
        var tx = db.transaction('metadata', 'readonly');
        return idbRequest(tx.objectStore('metadata').get(key));
    }

    function money(value) {
        return '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function quantity(value) {
        return Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 3 });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>'"]/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character];
        });
    }

    function positiveInteger(value) {
        var number = Number(value);
        return Number.isSafeInteger(number) && number > 0 ? number : null;
    }

    function showMessage(message, type) {
        elements.message.textContent = message;
        elements.message.className = 'message message-' + (type || 'info');
        elements.message.hidden = false;
    }

    function setConnectionState(state, label) {
        elements.connection.className = 'status-pill status-' + state;
        elements.connection.textContent = label;
    }

    function setButtonBusy(button, busy, busyLabel) {
        if (!button.dataset.defaultLabel) { button.dataset.defaultLabel = button.textContent; }
        button.disabled = busy;
        button.setAttribute('aria-busy', busy ? 'true' : 'false');
        button.textContent = busy ? busyLabel : button.dataset.defaultLabel;
    }

    function cartMetadataKey() {
        return 'cart_' + config.userId;
    }

    function cacheMetadataKey() {
        return 'last_cache_' + config.userId;
    }

    async function saveCart() {
        await putRecord('metadata', { key: cartMetadataKey(), value: cart, updated_at: new Date().toISOString() });
    }

    async function loadLocalData() {
        products = (await getAll('products')).filter(function (product) {
            return product && product.status === 'active' && positiveInteger(product.product_id) !== null;
        });
        var savedCart = await getMetadata(cartMetadataKey());
        cart = savedCart && Array.isArray(savedCart.value) ? savedCart.value.filter(function (item) {
            return item && positiveInteger(item.product_id) !== null;
        }) : [];
        var cacheInfo = await getMetadata(cacheMetadataKey());
        elements.lastCache.textContent = cacheInfo && cacheInfo.value ? new Date(cacheInfo.value).toLocaleString() : 'Never';
        renderProducts();
        renderCart();
        await renderPendingSales();
    }

    async function replaceProductCache(newProducts, cachedAt) {
        var db = await openDatabase();
        var tx = db.transaction(['products', 'metadata'], 'readwrite');
        var store = tx.objectStore('products');
        store.clear();
        newProducts.forEach(function (product) { store.put(product); });
        tx.objectStore('metadata').put({ key: cacheMetadataKey(), value: cachedAt });
        await transactionDone(tx);
    }

    async function updateProductCache() {
        setButtonBusy(elements.updateCache, true, 'Updating…');
        setConnectionState('syncing', 'Updating data…');
        try {
            var response = await fetch(config.productsUrl, { credentials: 'same-origin', cache: 'no-store' });
            var data = await response.json();
            if (!response.ok || !data.ok || !Array.isArray(data.products)) {
                throw new Error(data.message || 'Unable to update offline data.');
            }
            await replaceProductCache(data.products, data.cached_at);
            products = data.products;
            elements.lastCache.textContent = new Date(data.cached_at).toLocaleString();
            serverReachable = true;
            connectionErrorType = null;
            setConnectionState('online', 'ONLINE · READY');
            renderProducts();
            showMessage('Offline product data updated successfully.', 'success');
            cacheOfflineShell();
        } catch (error) {
            serverReachable = false;
            connectionErrorType = 'network';
            setConnectionState('offline', 'OFFLINE');
            showMessage('Update failed. The last successful product cache was kept.', 'error');
        } finally {
            setButtonBusy(elements.updateCache, false, '');
        }
    }

    async function checkServer() {
        if (!navigator.onLine) {
            serverReachable = false;
            connectionErrorType = 'network';
            setConnectionState('offline', 'OFFLINE');
            return false;
        }
        try {
            var response = await fetch(config.healthUrl, { credentials: 'same-origin', cache: 'no-store' });
            var data = await response.json();
            if (response.status === 401 || response.status === 403) {
                serverReachable = false;
                connectionErrorType = 'authentication';
                setConnectionState('failed', 'SIGN-IN REQUIRED');
                return false;
            }
            serverReachable = response.ok && data.ok === true;
            connectionErrorType = serverReachable ? null : 'server';
        } catch (error) {
            serverReachable = false;
            connectionErrorType = 'network';
        }
        setConnectionState(serverReachable ? 'online' : 'offline', serverReachable ? 'ONLINE · READY' : 'OFFLINE');
        return serverReachable;
    }

    async function reservedQuantities() {
        var sales = await getAll('pending_sales');
        var reserved = {};
        sales.filter(function (sale) {
            return sale.cashier_user_id === config.userId && sale.sync_status !== 'synced';
        }).forEach(function (sale) {
            sale.items.forEach(function (item) {
                reserved[item.product_id] = (reserved[item.product_id] || 0) + Number(item.quantity);
            });
        });
        cart.forEach(function (item) {
            reserved[item.product_id] = (reserved[item.product_id] || 0) + Number(item.quantity);
        });
        return reserved;
    }

    async function renderProducts() {
        var query = elements.search.value.trim().toLowerCase();
        var reserved = await reservedQuantities();
        var matches = products.filter(function (product) {
            if (!query) { return true; }
            return String(product.product_code).toLowerCase().includes(query)
                || String(product.barcode || '').toLowerCase().includes(query)
                || String(product.product_name).toLowerCase().includes(query);
        }).slice(0, 60);

        elements.productCount.textContent = products.length + ' products cached';
        if (!matches.length) {
            elements.productResults.innerHTML = '<tr><td colspan="5" class="empty">No cached products found.</td></tr>';
            return;
        }
        elements.productResults.innerHTML = matches.map(function (product) {
            var productId = positiveInteger(product.product_id);
            if (productId === null) { return ''; }
            var available = Math.max(0, Number(product.quantity) - Number(reserved[productId] || 0));
            return '<tr>'
                + '<td><strong>' + escapeHtml(product.product_code) + '</strong><div class="muted">' + escapeHtml(product.barcode || 'No barcode') + '</div></td>'
                + '<td>' + escapeHtml(product.product_name) + '<div class="muted">' + escapeHtml(product.unit_name) + '</div></td>'
                + '<td class="nowrap">' + money(product.selling_price) + '</td>'
                + '<td>' + quantity(available) + '</td>'
                + '<td><button type="button" class="button button-primary button-small" data-add-product="' + productId + '" aria-label="Add ' + escapeHtml(product.product_name) + ' to cart"' + (available <= 0 ? ' disabled' : '') + '>Add</button></td>'
                + '</tr>';
        }).join('');
    }

    function findProduct(productId) {
        return products.find(function (product) { return Number(product.product_id) === Number(productId); });
    }

    async function addProduct(productId) {
        var product = findProduct(productId);
        if (!product) { return; }
        var existing = cart.find(function (item) { return item.product_id === product.product_id; });
        if (existing) {
            existing.quantity = Number(existing.quantity) + 1;
        } else {
            cart.push({
                product_id: product.product_id,
                product_code: product.product_code,
                product_name: product.product_name,
                unit_name: product.unit_name,
                quantity: 1,
                cached_unit_price: Number(product.selling_price)
            });
        }
        await enforceCartStock(product.product_id);
        await saveCart();
        renderCart();
        renderProducts();
    }

    async function enforceCartStock(productId) {
        var product = findProduct(productId);
        var item = cart.find(function (entry) { return entry.product_id === Number(productId); });
        if (!product || !item) { return; }
        var sales = await getAll('pending_sales');
        var pendingQuantity = 0;
        sales.filter(function (sale) {
            return sale.cashier_user_id === config.userId && sale.sync_status !== 'synced';
        }).forEach(function (sale) {
            sale.items.forEach(function (saleItem) {
                if (saleItem.product_id === item.product_id) { pendingQuantity += Number(saleItem.quantity); }
            });
        });
        var maximum = Math.max(0, Number(product.quantity) - pendingQuantity);
        if (Number(item.quantity) > maximum) {
            item.quantity = maximum;
            showMessage('Cart quantity was limited by the cached stock estimate.', 'error');
        }
        if (item.quantity <= 0) {
            cart = cart.filter(function (entry) { return entry.product_id !== item.product_id; });
        }
    }

    async function changeCartQuantity(productId, value) {
        var item = cart.find(function (entry) { return entry.product_id === Number(productId); });
        if (!item) { return; }
        item.quantity = Math.round(Number(value) * 1000) / 1000;
        if (!Number.isFinite(item.quantity) || item.quantity <= 0) {
            cart = cart.filter(function (entry) { return entry.product_id !== Number(productId); });
        } else {
            await enforceCartStock(productId);
        }
        await saveCart();
        renderCart();
        renderProducts();
    }

    function cartSubtotal() {
        return Math.round(cart.reduce(function (total, item) {
            return total + Number(item.quantity) * Number(item.cached_unit_price);
        }, 0) * 100) / 100;
    }

    function renderCart() {
        if (!cart.length) {
            elements.cartItems.innerHTML = '<tr><td colspan="4" class="empty">Cart is empty.</td></tr>';
        } else {
            elements.cartItems.innerHTML = cart.map(function (item) {
                var itemId = positiveInteger(item.product_id);
                if (itemId === null) { return ''; }
                var lineTotal = Math.round(Number(item.quantity) * Number(item.cached_unit_price) * 100) / 100;
                return '<tr>'
                    + '<td><strong>' + escapeHtml(item.product_name) + '</strong><div class="muted">' + escapeHtml(item.product_code) + ' · ' + money(item.cached_unit_price) + '</div></td>'
                    + '<td><div class="quantity-controls"><button class="button button-outline button-small" data-cart-dec="' + itemId + '" aria-label="Decrease ' + escapeHtml(item.product_name) + ' quantity">−</button>'
                    + '<input type="number" min="0.001" step="0.001" value="' + escapeHtml(item.quantity) + '" data-cart-qty="' + itemId + '">'
                    + '<button class="button button-outline button-small" data-cart-inc="' + itemId + '" aria-label="Increase ' + escapeHtml(item.product_name) + ' quantity">+</button></div></td>'
                    + '<td class="nowrap">' + money(lineTotal) + '</td>'
                    + '<td><button class="button button-danger button-small" data-cart-remove="' + itemId + '" aria-label="Remove ' + escapeHtml(item.product_name) + ' from cart">×</button></td>'
                    + '</tr>';
            }).join('');
        }
        elements.cartTotal.textContent = money(cartSubtotal());
        updateChange();
    }

    function updateChange() {
        var payment = Number(elements.payment.value || 0);
        var total = cartSubtotal();
        elements.change.textContent = money(payment >= total ? payment - total : 0);
    }

    function generateOfflineId() {
        var bytes = new Uint8Array(8);
        crypto.getRandomValues(bytes);
        var random = Array.from(bytes).map(function (value) { return value.toString(16).padStart(2, '0'); }).join('').toUpperCase();
        return 'OFFLINE-' + Date.now() + '-' + random;
    }

    async function savePendingSale() {
        var total = cartSubtotal();
        var payment = Math.round(Number(elements.payment.value || 0) * 100) / 100;
        if (!cart.length) {
            showMessage('Add at least one item before saving a pending sale.', 'error');
            return;
        }
        if (!Number.isFinite(payment) || payment < total) {
            showMessage('Cash received must be at least ' + money(total) + '.', 'error');
            return;
        }

        var sale = {
            offline_transaction_id: generateOfflineId(),
            cashier_user_id: config.userId,
            cashier_username: config.username,
            items: cart.map(function (item) {
                return {
                    product_id: item.product_id,
                    product_code: item.product_code,
                    product_name: item.product_name,
                    unit_name: item.unit_name,
                    quantity: Number(item.quantity),
                    cached_unit_price: Number(item.cached_unit_price),
                    line_total: Math.round(Number(item.quantity) * Number(item.cached_unit_price) * 100) / 100
                };
            }),
            cached_total: total,
            payment_amount: payment,
            change_amount: Math.round((payment - total) * 100) / 100,
            created_at: new Date().toISOString(),
            sync_status: 'pending',
            error_type: null,
            error_message: null,
            official_sale_id: null,
            official_sale_no: null
        };
        await putRecord('pending_sales', sale);
        cart = [];
        elements.payment.value = '';
        await saveCart();
        renderCart();
        renderProducts();
        await renderPendingSales();
        showMessage('Pending Offline Sale saved as ' + sale.offline_transaction_id + '.', 'success');
    }

    async function userSales() {
        var sales = await getAll('pending_sales');
        return sales.filter(function (sale) { return sale.cashier_user_id === config.userId; })
            .sort(function (a, b) { return String(a.created_at).localeCompare(String(b.created_at)); });
    }

    async function renderPendingSales() {
        var sales = await userSales();
        var pending = sales.filter(function (sale) { return sale.sync_status !== 'synced'; });
        elements.pendingCount.textContent = pending.length;
        if (!sales.length) {
            elements.pendingSales.innerHTML = '<div class="empty padded">No offline sales stored.</div>';
            return;
        }
        elements.pendingSales.innerHTML = sales.map(function (sale) {
            var isSynced = sale.sync_status === 'synced';
            var statusClass = isSynced ? 'status-online' : (sale.sync_status === 'sync_failed' ? 'status-failed' : 'status-offline');
            var statusText = isSynced ? 'SYNCHRONIZED' : (sale.sync_status === 'sync_failed' ? 'SYNC FAILED' : 'PENDING');
            var saleItems = Array.isArray(sale.items) ? sale.items : [];
            var itemLines = saleItems.map(function (item) {
                return '<li>' + escapeHtml(item.product_name) + ' — ' + quantity(item.quantity) + ' × ' + money(item.cached_unit_price) + '</li>';
            }).join('');
            var result = isSynced
                ? '<div class="success-text">Official sale: ' + escapeHtml(sale.official_sale_no) + '</div>'
                : (sale.error_message ? '<div class="error-text">' + escapeHtml(sale.error_message) + '</div>' : '');
            var receipt = isSynced && sale.official_sale_id
                ? '<a class="button button-outline button-small" href="' + config.receiptBaseUrl + encodeURIComponent(sale.official_sale_id) + '">Receipt</a>' : '';
            return '<article class="pending-sale">'
                + '<div class="pending-summary"><div><div class="pending-id">' + escapeHtml(sale.offline_transaction_id) + '</div>'
                + '<div class="pending-meta">' + new Date(sale.created_at).toLocaleString() + ' · ' + money(sale.cached_total) + '</div>'
                + '<span class="status-pill ' + statusClass + '">' + statusText + '</span>' + result + '</div>'
                + '<div class="pending-actions">' + receipt
                + (!isSynced ? '<button class="button button-success button-small" data-sync-sale="' + escapeHtml(sale.offline_transaction_id) + '">Retry Sync</button>' : '')
                + '<button class="button button-danger button-small" data-remove-sale="' + escapeHtml(sale.offline_transaction_id) + '">' + (isSynced ? 'Remove Local Copy' : 'Cancel Pending Sale') + '</button></div></div>'
                + '<details class="pending-items"><summary>Review ' + saleItems.length + ' item(s)</summary><ul>' + itemLines + '</ul>'
                + '<div>Cash: ' + money(sale.payment_amount) + ' · Cached change: ' + money(sale.change_amount) + '</div></details></article>';
        }).join('');
    }

    async function syncOne(sale) {
        sale.sync_status = 'syncing';
        sale.error_message = null;
        await putRecord('pending_sales', sale);
        await renderPendingSales();
        try {
            var response = await fetch(config.syncUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': config.csrfToken },
                body: JSON.stringify(sale)
            });
            var data = await response.json();
            if (!response.ok || !data.ok) {
                sale.sync_status = 'sync_failed';
                sale.error_type = data.error_type || 'validation_error';
                sale.error_message = data.message || 'Synchronization failed. Review and retry.';
                await putRecord('pending_sales', sale);
                return false;
            }
            sale.sync_status = 'synced';
            sale.error_type = null;
            sale.error_message = null;
            sale.official_sale_id = Number(data.sale_id);
            sale.official_sale_no = data.sale_no;
            sale.official_total = Number(data.total_amount);
            sale.synced_at = new Date().toISOString();
            sale.already_synchronized = data.already_synchronized === true;
            await putRecord('pending_sales', sale);
            return true;
        } catch (error) {
            sale.sync_status = 'pending';
            sale.error_type = 'network';
            sale.error_message = 'Server unavailable. Sale remains pending.';
            await putRecord('pending_sales', sale);
            serverReachable = false;
            connectionErrorType = 'network';
            setConnectionState('offline', 'OFFLINE');
            return false;
        }
    }

    async function synchronizePending(onlyId) {
        if (syncing) { return; }
        syncing = true;
        setButtonBusy(elements.sync, true, 'Syncing…');
        setConnectionState('syncing', 'SYNCING');
        try {
            if (!(await checkServer())) {
                showMessage(
                    connectionErrorType === 'authentication'
                        ? 'Your server session expired. Sign in online, reload Offline POS, and retry synchronization.'
                        : 'Server unavailable. Pending sales remain stored locally.',
                    'error'
                );
                return;
            }
            var sales = (await userSales()).filter(function (sale) {
                return sale.sync_status !== 'synced' && (!onlyId || sale.offline_transaction_id === onlyId);
            });
            var successes = 0;
            var failures = 0;
            for (var i = 0; i < sales.length; i++) {
                if (await syncOne(sales[i])) { successes++; } else { failures++; }
            }
            await renderPendingSales();
            renderProducts();
            if (successes || failures) {
                showMessage(successes + ' sale(s) synchronized; ' + failures + ' sale(s) still need review.', failures ? 'error' : 'success');
            } else {
                showMessage('No pending sales to synchronize.', 'info');
            }
        } finally {
            syncing = false;
            setButtonBusy(elements.sync, false, '');
            await checkServer();
        }
    }

    async function removeLocalSale(id) {
        var sales = await userSales();
        var sale = sales.find(function (entry) { return entry.offline_transaction_id === id; });
        if (!sale) { return; }
        var warning = sale.sync_status === 'synced'
            ? 'Remove this synchronized sale from this browser only? The server sale will remain.'
            : 'Cancel and remove this pending sale from this browser? This cannot be undone.';
        if (!window.confirm(warning)) { return; }
        await deleteRecord('pending_sales', id);
        await renderPendingSales();
        renderProducts();
    }

    function cacheOfflineShell() {
        if (!('serviceWorker' in navigator)) { return; }
        navigator.serviceWorker.ready.then(function (registration) {
            var worker = registration.active || registration.waiting;
            if (worker) { worker.postMessage({ type: 'CACHE_OFFLINE_PAGE' }); }
        });
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator) || !window.isSecureContext) { return; }
        navigator.serviceWorker.register(config.baseUrl + '/service-worker.js', { scope: config.baseUrl + '/' })
            .then(function () { cacheOfflineShell(); })
            .catch(function () { showMessage('Offline page caching is unavailable in this browser.', 'error'); });
    }

    elements.updateCache.addEventListener('click', updateProductCache);
    elements.sync.addEventListener('click', function () { synchronizePending(); });
    elements.search.addEventListener('input', renderProducts);
    elements.payment.addEventListener('input', updateChange);
    elements.saveSale.addEventListener('click', savePendingSale);
    elements.clearCart.addEventListener('click', async function () {
        if (cart.length && !window.confirm('Clear the offline cart?')) { return; }
        cart = [];
        await saveCart();
        renderCart();
        renderProducts();
    });

    elements.productResults.addEventListener('click', function (event) {
        var button = event.target.closest('[data-add-product]');
        if (button) { addProduct(Number(button.dataset.addProduct)); }
    });
    elements.cartItems.addEventListener('click', function (event) {
        var increment = event.target.closest('[data-cart-inc]');
        var decrement = event.target.closest('[data-cart-dec]');
        var remove = event.target.closest('[data-cart-remove]');
        if (increment) {
            var incItem = cart.find(function (item) { return item.product_id === Number(increment.dataset.cartInc); });
            if (incItem) { changeCartQuantity(incItem.product_id, Number(incItem.quantity) + 1); }
        } else if (decrement) {
            var decItem = cart.find(function (item) { return item.product_id === Number(decrement.dataset.cartDec); });
            if (decItem) { changeCartQuantity(decItem.product_id, Number(decItem.quantity) - 1); }
        } else if (remove) {
            changeCartQuantity(Number(remove.dataset.cartRemove), 0);
        }
    });
    elements.cartItems.addEventListener('change', function (event) {
        if (event.target.matches('[data-cart-qty]')) {
            changeCartQuantity(Number(event.target.dataset.cartQty), Number(event.target.value));
        }
    });
    elements.pendingSales.addEventListener('click', function (event) {
        var syncButton = event.target.closest('[data-sync-sale]');
        var removeButton = event.target.closest('[data-remove-sale]');
        if (syncButton) { synchronizePending(syncButton.dataset.syncSale); }
        if (removeButton) { removeLocalSale(removeButton.dataset.removeSale); }
    });

    window.addEventListener('offline', function () {
        serverReachable = false;
        connectionErrorType = 'network';
        setConnectionState('offline', 'OFFLINE');
        showMessage('Connection lost. New sales will remain pending in this browser.', 'info');
    });
    window.addEventListener('online', function () {
        checkServer().then(function (available) {
            if (available) { synchronizePending(); }
        });
    });

    openDatabase()
        .then(loadLocalData)
        .then(function () { registerServiceWorker(); return checkServer(); })
        .then(function (available) {
            if (available) { return synchronizePending(); }
        })
        .catch(function () { showMessage('Browser offline storage could not be initialized.', 'error'); });
})();
