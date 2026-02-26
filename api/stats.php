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

// Last month's sales
$result = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE MONTH(invoice_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(invoice_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$stats['last_month_sales'] = floatval($result->fetch_assoc()['total'] ?? 0);

// Payment status breakdown
$result = $conn->query("SELECT payment_status, COUNT(*) as count FROM invoices GROUP BY payment_status");
$payment_breakdown = [];
while ($row = $result->fetch_assoc()) {
    $payment_breakdown[$row['payment_status']] = intval($row['count']);
}
$stats['payment_breakdown'] = $payment_breakdown;

// Top selling products (last 30 days)
$result = $conn->query("
    SELECT 
        ii.product_name,
        SUM(ii.quantity) as total_quantity,
        SUM(ii.total_price) as total_revenue
    FROM invoice_items ii
    INNER JOIN invoices i ON ii.invoice_id = i.id
    WHERE i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY ii.product_name
    ORDER BY total_quantity DESC
    LIMIT 5
");
$top_products = [];
while ($row = $result->fetch_assoc()) {
    $top_products[] = [
        'name' => $row['product_name'],
        'quantity' => intval($row['total_quantity']),
        'revenue' => floatval($row['total_revenue'])
    ];
}
$stats['top_products'] = $top_products;

// Sales by day (last 7 days)
$result = $conn->query("
    SELECT 
        DATE(invoice_date) as date,
        COUNT(*) as invoice_count,
        SUM(total_amount) as total_amount
    FROM invoices
    WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(invoice_date)
    ORDER BY date ASC
");
$sales_trend = [];
while ($row = $result->fetch_assoc()) {
    $sales_trend[] = [
        'date' => $row['date'],
        'count' => intval($row['invoice_count']),
        'amount' => floatval($row['total_amount'])
    ];
}
$stats['sales_trend'] = $sales_trend;

// Total paid invoices
$result = $conn->query("SELECT COUNT(*) as total FROM invoices WHERE payment_status = 'paid'");
$stats['paid_invoices'] = $result->fetch_assoc()['total'];

// Total partial invoices
$result = $conn->query("SELECT COUNT(*) as total FROM invoices WHERE payment_status = 'partial'");
$stats['partial_invoices'] = $result->fetch_assoc()['total'];

// Average invoice value
$result = $conn->query("SELECT AVG(total_amount) as avg FROM invoices");
$stats['avg_invoice_value'] = floatval($result->fetch_assoc()['avg'] ?? 0);

// Out of stock products
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active' AND quantity = 0");
$stats['out_of_stock'] = $result->fetch_assoc()['total'];

echo json_encode($stats);

closeDBConnection($conn);
?>
