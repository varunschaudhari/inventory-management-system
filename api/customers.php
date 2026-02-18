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

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single customer
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            echo json_encode($customer ?: ['error' => 'Customer not found']);
        } else {
            // Get all customers
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            
            $query = "SELECT * FROM customers WHERE 1=1";
            $params = [];
            $types = '';
            
            if ($search) {
                $query .= " AND (customer_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }
            
            $query .= " ORDER BY customer_name ASC";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $customers = [];
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
            
            echo json_encode($customers);
        }
        break;
        
    case 'POST':
        // Create new customer
        $data = json_decode(file_get_contents('php://input'), true);
        
        $customer_name = $data['customer_name'] ?? '';
        $phone = $data['phone'] ?? '';
        $email = $data['email'] ?? '';
        $address = $data['address'] ?? '';
        $gstin = $data['gstin'] ?? '';
        $city = $data['city'] ?? '';
        $state = $data['state'] ?? '';
        $pincode = $data['pincode'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO customers (customer_name, phone, email, address, gstin, city, state, pincode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $customer_name, $phone, $email, $address, $gstin, $city, $state, $pincode);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    case 'PUT':
        // Update customer
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        $customer_name = $data['customer_name'] ?? '';
        $phone = $data['phone'] ?? '';
        $email = $data['email'] ?? '';
        $address = $data['address'] ?? '';
        $gstin = $data['gstin'] ?? '';
        $city = $data['city'] ?? '';
        $state = $data['state'] ?? '';
        $pincode = $data['pincode'] ?? '';
        
        $stmt = $conn->prepare("UPDATE customers SET customer_name = ?, phone = ?, email = ?, address = ?, gstin = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $customer_name, $phone, $email, $address, $gstin, $city, $state, $pincode, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    case 'DELETE':
        // Delete customer
        $id = intval($_GET['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
}

closeDBConnection($conn);
?>
