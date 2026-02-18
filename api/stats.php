<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/auth.php';

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please login.']);
    exit;
}

$conn = getDBConnection();

$stats = [];

// Total products
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
$stats['total_products'] = $result->fetch_assoc()['total'];

// Low stock products
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active' AND quantity <= min_stock_level");
$stats['low_stock_products'] = $result->fetch_assoc()['total'];

// Total customers
$result = $conn->query("SELECT COUNT(*) as total FROM customers");
$stats['total_customers'] = $result->fetch_assoc()['total'];

// Total invoices
$result = $conn->query("SELECT COUNT(*) as total FROM invoices");
$stats['total_invoices'] = $result->fetch_assoc()['total'];

// Total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE payment_status = 'paid'");
$stats['total_revenue'] = floatval($result->fetch_assoc()['total'] ?? 0);

// Pending invoices
$result = $conn->query("SELECT COUNT(*) as total FROM invoices WHERE payment_status = 'pending'");
$stats['pending_invoices'] = $result->fetch_assoc()['total'];

// Today's sales
$result = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE DATE(invoice_date) = CURDATE()");
$stats['today_sales'] = floatval($result->fetch_assoc()['total'] ?? 0);

// This month's sales
$result = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())");
$stats['month_sales'] = floatval($result->fetch_assoc()['total'] ?? 0);

echo json_encode($stats);

closeDBConnection($conn);
?>
