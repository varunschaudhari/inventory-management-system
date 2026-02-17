<?php
/**
 * Delete Customer Handler
 * Handles AJAX requests to delete customers
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

if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
    exit();
}

try {
    // Check if customer has any sales
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $sales_count = $stmt->fetchColumn();
    
    if ($sales_count > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete customer. This customer has ' . $sales_count . ' sale(s) associated.'
        ]);
        exit();
    }
    
    // Delete customer
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
    }
} catch (PDOException $e) {
    error_log("Customer delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error deleting customer: ' . $e->getMessage()]);
}
