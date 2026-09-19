<?php
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/inventory.php';

define('PRODUCT_IMAGE_MAX_SIZE', 5 * 1024 * 1024);

function get_all_products($search = '', $status = '', $category_id = 0)
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT p.product_id, p.product_code, p.barcode, p.product_name, p.image,
                   p.selling_price, p.status, p.category_id,
                   c.category_name, b.brand_name, u.unit_name, p.created_at,
                   COALESCE(i.quantity, 0) AS quantity,
                   COALESCE(i.reorder_level, 0) AS reorder_level
            FROM products p
            INNER JOIN categories c ON c.category_id = p.category_id
            LEFT JOIN brands b ON b.brand_id = p.brand_id
            INNER JOIN units u ON u.unit_id = p.unit_id
            LEFT JOIN inventory i ON i.product_id = p.product_id
            WHERE 1=1';
    $params = [];
    $types = '';

    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND (p.product_code LIKE ? OR p.barcode LIKE ? OR p.product_name LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }

    if ($status === 'active' || $status === 'inactive') {
        $sql .= ' AND p.status = ?';
        $params[] = $status;
        $types .= 's';
    }

    $category_id = (int) $category_id;
    if ($category_id > 0) {
        $sql .= ' AND p.category_id = ?';
        $params[] = $category_id;
        $types .= 'i';
    }

    $sql .= ' ORDER BY p.product_name ASC';

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['stock_status'] = get_stock_status($row['quantity'], $row['reorder_level']);
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function get_product_by_id($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT product_id, product_code, barcode, product_name, category_id, brand_id, unit_id,
                   description, image, selling_price, status, created_at, updated_at
            FROM products WHERE product_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function get_product_details($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT p.product_id, p.product_code, p.barcode, p.product_name, p.description, p.image,
                   p.selling_price, p.status, p.created_at, p.updated_at,
                   c.category_name, b.brand_name, u.unit_name, u.unit_code,
                   COALESCE(i.quantity, 0) AS quantity,
                   COALESCE(i.reorder_level, 0) AS reorder_level
            FROM products p
            INNER JOIN categories c ON c.category_id = p.category_id
            LEFT JOIN brands b ON b.brand_id = p.brand_id
            INNER JOIN units u ON u.unit_id = p.unit_id
            LEFT JOIN inventory i ON i.product_id = p.product_id
            WHERE p.product_id = ?
            LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }
    $row['stock_status'] = get_stock_status($row['quantity'], $row['reorder_level']);
    return $row;
}

function product_code_exists($code, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT product_id FROM products WHERE product_code = ? AND product_id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $code, $exclude_id);
    } else {
        $sql = 'SELECT product_id FROM products WHERE product_code = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $code);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function product_barcode_exists($barcode, $exclude_id = null)
{
    if ($barcode === '' || $barcode === null) {
        return false;
    }

    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT product_id FROM products WHERE barcode = ? AND product_id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $barcode, $exclude_id);
    } else {
        $sql = 'SELECT product_id FROM products WHERE barcode = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $barcode);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_active_categories_for_select()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $result = $db->query("SELECT category_id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC");
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_categories_for_filter()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare('SELECT category_id, category_name FROM categories ORDER BY category_name ASC');
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function get_active_brands_for_select()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $result = $db->query("SELECT brand_id, brand_name FROM brands WHERE status = 'active' ORDER BY brand_name ASC");
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_active_units_for_select()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $result = $db->query("SELECT unit_id, unit_name, unit_code FROM units WHERE status = 'active' ORDER BY unit_name ASC");
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_category_by_id_raw($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT category_id, category_name, status FROM categories WHERE category_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_brand_by_id_raw($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT brand_id, brand_name, status FROM brands WHERE brand_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_unit_by_id_raw($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT unit_id, unit_name, unit_code, status FROM units WHERE unit_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function create_product($data)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'INSERT INTO products (product_code, barcode, product_name, category_id, brand_id, unit_id, description, image, selling_price, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);

    $brand_id = $data['brand_id'];
    $stmt->bind_param(
        'sssiiissds',
        $data['product_code'],
        $data['barcode'],
        $data['product_name'],
        $data['category_id'],
        $brand_id,
        $data['unit_id'],
        $data['description'],
        $data['image'],
        $data['selling_price'],
        $data['status']
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

function update_product($id, $data)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE products SET product_code = ?, barcode = ?, product_name = ?, category_id = ?, brand_id = ?,
            unit_id = ?, description = ?, image = ?, selling_price = ?, status = ? WHERE product_id = ?';
    $stmt = $db->prepare($sql);

    $brand_id = $data['brand_id'];
    $stmt->bind_param(
        'sssiiissdsi',
        $data['product_code'],
        $data['barcode'],
        $data['product_name'],
        $data['category_id'],
        $brand_id,
        $data['unit_id'],
        $data['description'],
        $data['image'],
        $data['selling_price'],
        $data['status'],
        $id
    );

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_product_status($id, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE products SET status = ? WHERE product_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('si', $status, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_product_image($id, $image)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE products SET image = ? WHERE product_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('si', $image, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function product_image_directory()
{
    return PRODUCT_UPLOAD_PATH;
}

function is_safe_product_image_path($image)
{
    return is_string($image)
        && preg_match('/^products\/[a-f0-9]{32}\.(jpg|png|webp)$/', $image) === 1;
}

function product_image_url($image)
{
    if (!is_safe_product_image_path($image)) {
        return null;
    }
    return PRODUCT_UPLOAD_URL . '/' . rawurlencode(basename($image));
}

function product_image_absolute_path($image)
{
    if (!is_safe_product_image_path($image)) {
        return null;
    }
    return product_image_directory() . DIRECTORY_SEPARATOR . basename($image);
}

function validate_product_image_file($tmp_path, $original_name, $size, $require_uploaded_file = true)
{
    if (!is_string($tmp_path) || $tmp_path === '' || !is_file($tmp_path)) {
        return [false, 'Uploaded image could not be read.', null];
    }
    if ($require_uploaded_file && !is_uploaded_file($tmp_path)) {
        return [false, 'Invalid image upload.', null];
    }
    if ((int) $size <= 0 || (int) $size > PRODUCT_IMAGE_MAX_SIZE) {
        return [false, 'Product image must be 5 MB or smaller.', null];
    }

    $extension = strtolower(pathinfo((string) $original_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowed_extensions, true)) {
        return [false, 'Product image must be JPG, PNG, or WEBP.', null];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp_path);
    $mime_extensions = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];
    if (!isset($mime_extensions[$mime]) || !in_array($extension, $mime_extensions[$mime], true)) {
        return [false, 'Image file type does not match its extension.', null];
    }
    if (@getimagesize($tmp_path) === false) {
        return [false, 'Uploaded file is not a valid image.', null];
    }

    $safe_extension = $mime === 'image/jpeg' ? 'jpg' : $extension;
    return [true, '', $safe_extension];
}

function store_product_image_upload($file)
{
    if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [true, '', null];
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Product image upload failed. Please try again.', null];
    }

    list($valid, $message, $extension) = validate_product_image_file(
        $file['tmp_name'] ?? '',
        $file['name'] ?? '',
        $file['size'] ?? 0,
        true
    );
    if (!$valid) {
        return [false, $message, null];
    }

    $directory = product_image_directory();
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        return [false, 'Product image directory is unavailable.', null];
    }

    try {
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    } catch (Exception $e) {
        return [false, 'Unable to generate a safe image filename.', null];
    }
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [false, 'Unable to save product image.', null];
    }

    return [true, '', 'products/' . $filename];
}

function delete_product_image_file($image)
{
    $path = product_image_absolute_path($image);
    if ($path !== null) {
        clearstatcache(true, $path);
    }
    if ($path === null || !is_file($path)) {
        return $path !== null;
    }
    return unlink($path);
}

function format_price($amount)
{
    return number_format((float) $amount, 2);
}
