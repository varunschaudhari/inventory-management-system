<?php
/**
 * Product Search API
 * Returns JSON array of products matching search query
 */

// Set content type to JSON
header('Content-Type: application/json');

// Include required files
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
redirect_if_not_logged();

// Get search query
$q = trim($_GET['q'] ?? '');

// Validate minimum length
if (strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

try {
    // Search products by name or code
    $stmt = $pdo->prepare("SELECT id, name, code, sale_price, stock, cgst_rate, sgst_rate, igst_rate 
                           FROM products 
                           WHERE name LIKE ? OR code LIKE ? 
                           LIMIT 10");
    $stmt->execute(["%$q%", "%$q%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (PDOException $e) {
    // Return empty array on error
    error_log("Product search error: " . $e->getMessage());
    echo json_encode([]);
}
