<?php
/**
 * Save Product Handler
 * Handles AJAX requests to create or update products
 */

// Set content type to JSON
header('Content-Type: application/json');

// Include required files
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
redirect_if_not_logged();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Read JSON from php://input
$data = json_decode(file_get_contents('php://input'), true);

// Check if JSON decode was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;

// Prepare fields (use filter_var or type cast as needed)
$name = trim($data['name'] ?? '');
$code = trim($data['code'] ?? '');
$hsn = trim($data['hsn'] ?? '');
$purchase_price = (float)($data['purchase_price'] ?? 0);
$sale_price = (float)($data['sale_price'] ?? 0);
$stock = (int)($data['stock'] ?? 0);
$low_stock_alert = (int)($data['low_stock_alert'] ?? 10);
$cgst_rate = (float)($data['cgst_rate'] ?? 0);
$sgst_rate = (float)($data['sgst_rate'] ?? 0);
$igst_rate = (float)($data['igst_rate'] ?? 0);

if (empty($name) || empty($data['sale_price'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit();
}

try {
    if ($product_id > 0) {
        // UPDATE
        $stmt = $pdo->prepare("UPDATE products SET name=?, code=?, hsn=?, purchase_price=?, sale_price=?, stock=?, low_stock_alert=?, cgst_rate=?, sgst_rate=?, igst_rate=? WHERE id=?");
        $stmt->execute([$name, $code, $hsn, $purchase_price, $sale_price, $stock, $low_stock_alert, $cgst_rate, $sgst_rate, $igst_rate, $product_id]);
        $message = 'Product updated successfully';
    } else {
        // INSERT (add UNIQUE check on code if needed)
        $stmt = $pdo->prepare("INSERT INTO products (name, code, hsn, purchase_price, sale_price, stock, low_stock_alert, cgst_rate, sgst_rate, igst_rate) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $code, $hsn, $purchase_price, $sale_price, $stock, $low_stock_alert, $cgst_rate, $sgst_rate, $igst_rate]);
        $message = 'Product added successfully';
    }
    echo json_encode(['success' => true, 'message' => $message]);
} catch (PDOException $e) {
    // Handle duplicate code constraint or other database errors
    $error_code = $e->getCode();
    $error_message = $e->getMessage();
    
    // Check for duplicate entry error (MySQL error code 1062 or 23000)
    if ($error_code == 23000 || strpos($error_message, 'Duplicate entry') !== false) {
        echo json_encode(['success' => false, 'message' => 'Product code already exists. Please use a unique code.']);
    } else {
        error_log("Product save error: " . $error_message);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    error_log("Unexpected error in save_product.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
