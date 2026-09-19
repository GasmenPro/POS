<?php
require_once BASE_PATH . '/includes/products.php';

define('PRODUCT_IMPORT_MAX_SIZE', 2 * 1024 * 1024);
define('PRODUCT_IMPORT_MAX_ROWS', 5000);

function product_import_headers()
{
    return [
        'product_code',
        'barcode',
        'product_name',
        'category',
        'brand',
        'unit',
        'description',
        'selling_price',
        'status',
    ];
}

function product_import_text_length($value)
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function product_import_key($value)
{
    $value = trim((string) $value);
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function product_import_safe_filename($name)
{
    $name = basename((string) $name);
    $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);
    return substr($name ?: 'products.csv', 0, 255);
}

function product_import_base_result($filename = '')
{
    return [
        'ok' => false,
        'filename' => product_import_safe_filename($filename),
        'total_rows' => 0,
        'imported' => 0,
        'failed' => 0,
        'errors' => [],
        'fatal_error' => '',
    ];
}

function product_import_add_error(&$result, $row_number, $message)
{
    $result['failed']++;
    $result['errors'][] = [
        'row' => (int) $row_number,
        'message' => (string) $message,
    ];
}

function validate_product_import_upload($file, $require_uploaded_file = true)
{
    if (!is_array($file) || !isset($file['error'])) {
        return [false, 'Select a CSV file to import.'];
    }

    $error = (int) $file['error'];
    if ($error === UPLOAD_ERR_NO_FILE) {
        return [false, 'Select a CSV file to import.'];
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return [false, 'The CSV file exceeds the 2 MB import limit.'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return [false, 'The CSV upload failed. Please try again.'];
    }

    $tmp_path = $file['tmp_name'] ?? '';
    $size = (int) ($file['size'] ?? 0);
    $name = (string) ($file['name'] ?? '');
    if (!is_string($tmp_path) || $tmp_path === '' || !is_file($tmp_path)) {
        return [false, 'The uploaded CSV file could not be read.'];
    }
    if ($require_uploaded_file && !is_uploaded_file($tmp_path)) {
        return [false, 'Invalid CSV upload.'];
    }
    if ($size <= 0) {
        return [false, 'The CSV file is empty.'];
    }
    if ($size > PRODUCT_IMPORT_MAX_SIZE) {
        return [false, 'The CSV file must be 2 MB or smaller.'];
    }
    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'csv') {
        return [false, 'Only .csv files are accepted.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp_path);
    $allowed_mimes = [
        'text/csv',
        'text/plain',
        'text/x-csv',
        'application/csv',
        'application/vnd.ms-excel',
        'application/octet-stream',
    ];
    if (!in_array($mime, $allowed_mimes, true)) {
        return [false, 'The uploaded file does not appear to be a valid CSV file.'];
    }

    return [true, ''];
}

function cleanup_product_import_upload($file)
{
    $tmp_path = is_array($file) ? ($file['tmp_name'] ?? '') : '';
    if (is_string($tmp_path) && $tmp_path !== '' && is_uploaded_file($tmp_path)) {
        @unlink($tmp_path);
    }
}

function product_import_reference_maps()
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $maps = ['categories' => [], 'brands' => [], 'units' => []];
    $queries = [
        'categories' => 'SELECT category_id AS id, category_name AS name, status FROM categories',
        'brands' => 'SELECT brand_id AS id, brand_name AS name, status FROM brands',
        'units' => 'SELECT unit_id AS id, unit_name AS name, unit_code AS code, status FROM units',
    ];

    foreach ($queries as $type => $sql) {
        $stmt = $db->prepare($sql);
        if (!$stmt || !$stmt->execute()) {
            if ($stmt) {
                $stmt->close();
            }
            return null;
        }
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $keys = [product_import_key($row['name'])];
            if ($type === 'units') {
                $keys[] = product_import_key($row['code']);
            }
            foreach (array_unique($keys) as $key) {
                if ($key === '') {
                    continue;
                }
                if (isset($maps[$type][$key]) && (!empty($maps[$type][$key]['ambiguous']) || (int) $maps[$type][$key]['id'] !== (int) $row['id'])) {
                    $maps[$type][$key] = ['ambiguous' => true];
                } else {
                    $maps[$type][$key] = $row;
                }
            }
        }
        $stmt->close();
    }

    return $maps;
}

function product_import_resolve_reference($map, $value, $label)
{
    $key = product_import_key($value);
    if ($key === '' || !isset($map[$key])) {
        return [null, $label . ' "' . $value . '" does not exist.'];
    }
    $record = $map[$key];
    if (!empty($record['ambiguous'])) {
        return [null, $label . ' "' . $value . '" is ambiguous. Use a unique name or unit code.'];
    }
    if ($record['status'] !== 'active') {
        return [null, $label . ' "' . $value . '" is inactive.'];
    }
    return [(int) $record['id'], ''];
}

function product_import_has_formula_prefix($value)
{
    $value = ltrim((string) $value, " \t\r\n");
    return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true);
}

function product_import_validate_row($row, $row_number, $maps, &$seen_codes, &$seen_barcodes)
{
    foreach ($row as $value) {
        if (strpos($value, "\0") !== false || preg_match('//u', $value) !== 1) {
            return [false, 'The row contains invalid text encoding.', null];
        }
    }

    $code = trim($row['product_code']);
    $barcode = trim($row['barcode']);
    $name = trim($row['product_name']);
    $category_name = trim($row['category']);
    $brand_name = trim($row['brand']);
    $unit_name = trim($row['unit']);
    $description = trim($row['description']);
    $price_raw = trim($row['selling_price']);
    $status = strtolower(trim($row['status']));
    if ($status === '') {
        $status = 'active';
    }

    if ($code === '') {
        return [false, 'Product code is required.', null];
    }
    if ($name === '') {
        return [false, 'Product name is required.', null];
    }
    if ($category_name === '') {
        return [false, 'Category is required.', null];
    }
    if ($unit_name === '') {
        return [false, 'Unit is required.', null];
    }
    if ($price_raw === '') {
        return [false, 'Selling price is required.', null];
    }

    if (product_import_text_length($code) > 100) {
        return [false, 'Product code must not exceed 100 characters.', null];
    }
    if (product_import_text_length($barcode) > 100) {
        return [false, 'Barcode must not exceed 100 characters.', null];
    }
    if (product_import_text_length($name) > 255) {
        return [false, 'Product name must not exceed 255 characters.', null];
    }
    if (strlen($description) > 65535) {
        return [false, 'Description is too long for the product description field.', null];
    }
    if (product_import_text_length($category_name) > 100 || product_import_text_length($brand_name) > 100 || product_import_text_length($unit_name) > 100) {
        return [false, 'Category, brand, and unit values must not exceed 100 characters.', null];
    }

    foreach ([$code, $barcode, $name, $description] as $text_value) {
        if ($text_value !== '' && product_import_has_formula_prefix($text_value)) {
            return [false, 'Text fields must not begin with spreadsheet formula characters (=, +, -, @).', null];
        }
    }

    if (!is_numeric($price_raw)) {
        return [false, 'Selling price must be numeric and greater than or equal to 0.', null];
    }
    $price = (float) $price_raw;
    if (!is_finite($price) || $price < 0 || $price > 9999999999.99) {
        return [false, 'Selling price must be between 0 and 9,999,999,999.99.', null];
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        return [false, 'Status must be active or inactive.', null];
    }

    $code_key = product_import_key($code);
    if (isset($seen_codes[$code_key])) {
        return [false, 'Duplicate product code within CSV: ' . $code . '.', null];
    }
    $seen_codes[$code_key] = $row_number;

    if ($barcode !== '') {
        $barcode_key = product_import_key($barcode);
        if (isset($seen_barcodes[$barcode_key])) {
            return [false, 'Duplicate barcode within CSV: ' . $barcode . '.', null];
        }
        $seen_barcodes[$barcode_key] = $row_number;
    }

    if (product_code_exists($code)) {
        return [false, 'Product code already exists: ' . $code . '.', null];
    }
    if ($barcode !== '' && product_barcode_exists($barcode)) {
        return [false, 'Barcode already exists: ' . $barcode . '.', null];
    }

    list($category_id, $reference_error) = product_import_resolve_reference($maps['categories'], $category_name, 'Category');
    if ($reference_error !== '') {
        return [false, $reference_error, null];
    }

    $brand_id = null;
    if ($brand_name !== '') {
        list($brand_id, $reference_error) = product_import_resolve_reference($maps['brands'], $brand_name, 'Brand');
        if ($reference_error !== '') {
            return [false, $reference_error, null];
        }
    }

    list($unit_id, $reference_error) = product_import_resolve_reference($maps['units'], $unit_name, 'Unit');
    if ($reference_error !== '') {
        return [false, $reference_error, null];
    }

    return [true, '', [
        'product_code' => $code,
        'barcode' => $barcode !== '' ? $barcode : null,
        'product_name' => $name,
        'category_id' => $category_id,
        'brand_id' => $brand_id,
        'unit_id' => $unit_id,
        'description' => $description !== '' ? $description : null,
        'image' => null,
        'selling_price' => round($price, 2),
        'status' => $status,
    ]];
}

function product_import_create_with_inventory($data)
{
    $db = get_db_connection();
    if (!$db) {
        return [false, 'Database connection is unavailable.', 0];
    }

    $db->begin_transaction();
    try {
        $product_id = create_product($data);
        if (!$product_id) {
            throw new RuntimeException('Unable to create the product. Its code or barcode may already exist.');
        }
        if (!ensure_inventory_record($product_id, $db)) {
            throw new RuntimeException('Unable to initialize inventory for the product.');
        }
        $db->commit();
        return [true, '', $product_id];
    } catch (Throwable $e) {
        $db->rollback();
        return [false, $e->getMessage(), 0];
    }
}

function process_product_csv_import($file, $require_uploaded_file = true)
{
    $result = product_import_base_result($file['name'] ?? '');
    list($upload_ok, $upload_error) = validate_product_import_upload($file, $require_uploaded_file);
    if (!$upload_ok) {
        $result['fatal_error'] = $upload_error;
        return $result;
    }

    $maps = product_import_reference_maps();
    if ($maps === null) {
        $result['fatal_error'] = 'Unable to load product reference data.';
        return $result;
    }

    $handle = @fopen($file['tmp_name'], 'rb');
    if (!$handle) {
        $result['fatal_error'] = 'Unable to open the CSV file.';
        return $result;
    }

    $header = fgetcsv($handle, 0, ',', '"', '\\');
    if (!is_array($header)) {
        fclose($handle);
        $result['fatal_error'] = 'The CSV file is empty or has no readable header row.';
        return $result;
    }

    $normalized_headers = [];
    foreach ($header as $index => $column) {
        $column = (string) $column;
        if ($index === 0) {
            $column = preg_replace('/^\xEF\xBB\xBF/', '', $column);
        }
        $normalized_headers[] = strtolower(trim($column));
    }

    if (count($normalized_headers) !== count(array_unique($normalized_headers))) {
        fclose($handle);
        $result['fatal_error'] = 'The CSV header contains duplicate column names.';
        return $result;
    }

    $missing = array_values(array_diff(product_import_headers(), $normalized_headers));
    if ($missing) {
        fclose($handle);
        $result['fatal_error'] = 'Missing required CSV column(s): ' . implode(', ', $missing) . '.';
        return $result;
    }

    $valid_rows = [];
    $seen_codes = [];
    $seen_barcodes = [];
    $row_number = 1;
    while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        $row_number++;
        if (count($values) === 1 && ($values[0] === null || trim((string) $values[0]) === '')) {
            continue;
        }
        $result['total_rows']++;
        if ($result['total_rows'] > PRODUCT_IMPORT_MAX_ROWS) {
            fclose($handle);
            $result['fatal_error'] = 'The CSV file exceeds the 5,000-row import limit.';
            $result['imported'] = 0;
            $result['failed'] = 0;
            $result['errors'] = [];
            return $result;
        }
        if (count($values) !== count($normalized_headers)) {
            product_import_add_error($result, $row_number, 'Column count does not match the CSV header.');
            continue;
        }

        $all_values = array_combine($normalized_headers, array_map(static function ($value) {
            return (string) $value;
        }, $values));
        $row = [];
        foreach (product_import_headers() as $required_header) {
            $row[$required_header] = $all_values[$required_header];
        }

        list($valid, $message, $data) = product_import_validate_row(
            $row,
            $row_number,
            $maps,
            $seen_codes,
            $seen_barcodes
        );
        if (!$valid) {
            product_import_add_error($result, $row_number, $message);
            continue;
        }
        $valid_rows[] = ['row' => $row_number, 'data' => $data];
    }
    fclose($handle);

    if ($result['total_rows'] === 0) {
        $result['fatal_error'] = 'The CSV file contains no product rows.';
        return $result;
    }

    foreach ($valid_rows as $valid_row) {
        $data = $valid_row['data'];
        if (product_code_exists($data['product_code'])) {
            product_import_add_error($result, $valid_row['row'], 'Product code already exists: ' . $data['product_code'] . '.');
            continue;
        }
        if ($data['barcode'] !== null && product_barcode_exists($data['barcode'])) {
            product_import_add_error($result, $valid_row['row'], 'Barcode already exists: ' . $data['barcode'] . '.');
            continue;
        }

        list($created, $create_error) = product_import_create_with_inventory($data);
        if (!$created) {
            product_import_add_error($result, $valid_row['row'], $create_error);
            continue;
        }
        $result['imported']++;
    }

    $result['ok'] = true;
    return $result;
}

function output_product_import_template()
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="product-import-template.csv"');
    header('X-Content-Type-Options: nosniff');
    $stream = fopen('php://output', 'w');
    fputcsv($stream, product_import_headers());
    fputcsv($stream, ['SAMPLE-REPLACE-001', '100000000001', 'Replace With Product Name', 'Replace With Active Category', '', 'Replace With Active Unit', 'Replace this sample row before importing', '25.00', 'active']);
    fputcsv($stream, ['SAMPLE-REPLACE-002', '', 'Second Sample Product', 'Replace With Active Category', 'Replace With Active Brand Or Leave Blank', 'Replace With Active Unit', '', '10.50', 'inactive']);
    fclose($stream);
    exit;
}
