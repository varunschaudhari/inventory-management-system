<?php
/**
 * Save Invoice Handler
 * Handles AJAX requests to create invoices/sales
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
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Read JSON from php://input
$data = json_decode(file_get_contents('php://input'), true);

// Check if JSON decode was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$customer_id = (int)($data['customer_id'] ?? 0);
$cart = $data['cart'] ?? [];
$sale_date = $data['sale_date'] ?? date('Y-m-d');
$reference_no = $data['reference_no'] ?? '';
$order_tax = (float)($data['order_tax'] ?? 0);
$shipping = (float)($data['shipping'] ?? 0);
$order_discount = (float)($data['order_discount'] ?? 0);
$discount_type = $data['discount_type'] ?? 'fixed';
$sale_status = $data['sale_status'] ?? 'pending';
$payment_status = $data['payment_status'] ?? 'pending';
$sale_note = $data['sale_note'] ?? '';

if ($customer_id <= 0 || empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Use reference_no if provided, otherwise generate invoice_no
    if (!empty($reference_no)) {
        $invoice_no = $reference_no;
    } else {
        // Generate unique invoice_no e.g. INV-YYYY-MM-XXXX
        $year = date('Y', strtotime($sale_date));
        $month = date('m', strtotime($sale_date));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE invoice_no LIKE ?");
        $stmt->execute(["INV-$year-$month%"]);
        $count = $stmt->fetchColumn() + 1;
        $invoice_no = sprintf("INV-%s-%s-%04d", $year, $month, $count);
    }

    // Calculate totals
    $subtotal = 0;
    $totalCgst = 0;
    $totalSgst = 0;
    $totalIgst = 0;

    foreach ($cart as $item) {
        $lineSubtotal = $item['price'] * $item['qty'];
        $subtotal += $lineSubtotal;
        
        // Calculate taxes per item
        $cgstRate = $item['cgst_rate'] ?? 0;
        $sgstRate = $item['sgst_rate'] ?? 0;
        $igstRate = $item['igst_rate'] ?? 0;
        
        $totalCgst += ($lineSubtotal * $cgstRate) / 100;
        $totalSgst += ($lineSubtotal * $sgstRate) / 100;
        $totalIgst += ($lineSubtotal * $igstRate) / 100;
    }

    $tax = $totalCgst + $totalSgst + $totalIgst;
    $grand_total = $subtotal + $tax;

    // Insert sale
    $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, customer_id, sale_date, subtotal, tax_amount, grand_total, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$invoice_no, $customer_id, $sale_date, $subtotal, $tax, $grand_total, $sale_status]);
    $sale_id = $pdo->lastInsertId();

    // Insert items & deduct stock
    foreach ($cart as $item) {
        $lineSubtotal = $item['price'] * $item['qty'];
        $cgstRate = $item['cgst_rate'] ?? 0;
        $sgstRate = $item['sgst_rate'] ?? 0;
        $igstRate = $item['igst_rate'] ?? 0;
        
        $lineCgst = ($lineSubtotal * $cgstRate) / 100;
        $lineSgst = ($lineSubtotal * $sgstRate) / 100;
        $lineIgst = ($lineSubtotal * $igstRate) / 100;
        $lineTotal = $lineSubtotal + $lineCgst + $lineSgst + $lineIgst;

        // Insert sale item
        $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, cgst, sgst, igst, total) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_item->execute([
            $sale_id, 
            $item['id'], 
            $item['qty'], 
            $item['price'], 
            $lineCgst, 
            $lineSgst, 
            $lineIgst, 
            $lineTotal
        ]);

        // Deduct stock
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$item['qty'], $item['id']]);
    }

    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Invoice saved successfully', 
        'invoice_no' => $invoice_no, 
        'sale_id' => $sale_id
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Invoice save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
