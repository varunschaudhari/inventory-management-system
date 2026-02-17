<?php
/**
 * Save Customer Handler
 * Handles AJAX requests to create or update customers
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
$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$email = trim($data['email'] ?? '');
$address = trim($data['address'] ?? '');
$state_code = trim($data['state_code'] ?? '29');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit();
}

try {
    if ($customer_id > 0) {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=?, address=?, state_code=? WHERE id=?");
        $stmt->execute([$name, $phone, $email, $address, $state_code, $customer_id]);
        $msg = 'Customer updated';
    } else {
        $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address, state_code) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $phone, $email, $address, $state_code]);
        $msg = 'Customer added';
    }
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
