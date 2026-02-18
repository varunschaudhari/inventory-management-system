<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single product
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            echo json_encode($product ?: ['error' => 'Product not found']);
        } else {
            // Get all products
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            
            $query = "SELECT * FROM products WHERE status = 'active'";
            $params = [];
            $types = '';
            
            if ($search) {
                $query .= " AND (product_name LIKE ? OR product_code LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "ss";
            }
            
            if ($category) {
                $query .= " AND category = ?";
                $params[] = $category;
                $types .= "s";
            }
            
            $query .= " ORDER BY product_name ASC";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            echo json_encode($products);
        }
        break;
        
    case 'POST':
        // Create new product
        $data = json_decode(file_get_contents('php://input'), true);
        
        $product_name = $data['product_name'] ?? '';
        $product_code = $data['product_code'] ?? '';
        $category = $data['category'] ?? '';
        $description = $data['description'] ?? '';
        $quantity = intval($data['quantity'] ?? 0);
        $unit_price = floatval($data['unit_price'] ?? 0);
        $cost_price = floatval($data['cost_price'] ?? 0);
        $unit = $data['unit'] ?? 'pcs';
        $min_stock_level = intval($data['min_stock_level'] ?? 0);
        
        $stmt = $conn->prepare("INSERT INTO products (product_name, product_code, category, description, quantity, unit_price, cost_price, unit, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiddsi", $product_name, $product_code, $category, $description, $quantity, $unit_price, $cost_price, $unit, $min_stock_level);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    case 'PUT':
        // Update product
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        $product_name = $data['product_name'] ?? '';
        $product_code = $data['product_code'] ?? '';
        $category = $data['category'] ?? '';
        $description = $data['description'] ?? '';
        $quantity = intval($data['quantity'] ?? 0);
        $unit_price = floatval($data['unit_price'] ?? 0);
        $cost_price = floatval($data['cost_price'] ?? 0);
        $unit = $data['unit'] ?? 'pcs';
        $min_stock_level = intval($data['min_stock_level'] ?? 0);
        $status = $data['status'] ?? 'active';
        
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_code = ?, category = ?, description = ?, quantity = ?, unit_price = ?, cost_price = ?, unit = ?, min_stock_level = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssiddssisi", $product_name, $product_code, $category, $description, $quantity, $unit_price, $cost_price, $unit, $min_stock_level, $status, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    case 'DELETE':
        // Delete product (soft delete by setting status to inactive)
        $id = intval($_GET['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
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
