<?php
/**
 * Delete Product Handler
 * Handles AJAX requests to delete products
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

// Get product ID
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

try {
    // Delete product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} catch (PDOException $e) {
    error_log("Product delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Unexpected error in delete_product.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
