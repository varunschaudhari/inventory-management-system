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
            // Get single invoice with items and customer
            $id = intval($_GET['id']);
            
            $stmt = $conn->prepare("SELECT i.*, c.customer_name, c.phone, c.email, c.address, c.gstin, c.city, c.state, c.pincode 
                                    FROM invoices i 
                                    LEFT JOIN customers c ON i.customer_id = c.id 
                                    WHERE i.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $invoice = $result->fetch_assoc();
            
            if ($invoice) {
                // Get invoice items with product description
                $stmt2 = $conn->prepare("SELECT ii.*, p.description as product_description 
                                        FROM invoice_items ii 
                                        LEFT JOIN products p ON ii.product_id = p.id 
                                        WHERE ii.invoice_id = ?");
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $items = [];
                while ($row = $result2->fetch_assoc()) {
                    $items[] = $row;
                }
                $invoice['items'] = $items;
            }
            
            echo json_encode($invoice ?: ['error' => 'Invoice not found']);
        } else {
            // Get all invoices
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            
            $query = "SELECT i.*, c.customer_name FROM invoices i 
                     LEFT JOIN customers c ON i.customer_id = c.id 
                     WHERE 1=1";
            $params = [];
            $types = '';
            
            if ($search) {
                $query .= " AND (i.invoice_number LIKE ? OR c.customer_name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "ss";
            }
            
            if ($status) {
                $query .= " AND i.payment_status = ?";
                $params[] = $status;
                $types .= "s";
            }
            
            $query .= " ORDER BY i.invoice_date DESC, i.id DESC";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $invoices = [];
            while ($row = $result->fetch_assoc()) {
                $invoices[] = $row;
            }
            
            echo json_encode($invoices);
        }
        break;
        
    case 'POST':
        // Create new invoice
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Generate invoice number
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'invoice_prefix'");
        $stmt->execute();
        $result = $stmt->get_result();
        $prefix = $result->fetch_assoc()['setting_value'] ?? 'INV-';
        
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(invoice_number, LENGTH(?) + 1) AS UNSIGNED)) as max_num FROM invoices WHERE invoice_number LIKE ?");
        $likePattern = $prefix . '%';
        $stmt->bind_param("ss", $prefix, $likePattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nextNum = ($row['max_num'] ?? 0) + 1;
        $invoice_number = $prefix . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        
        $customer_id = intval($data['customer_id'] ?? 0) ?: null;
        $invoice_date = $data['invoice_date'] ?? date('Y-m-d');
        $due_date = $data['due_date'] ?? null;
        $subtotal = floatval($data['subtotal'] ?? 0);
        $tax_rate = floatval($data['tax_rate'] ?? 0);
        $tax_amount = floatval($data['tax_amount'] ?? 0);
        $discount = floatval($data['discount'] ?? 0);
        $total_amount = floatval($data['total_amount'] ?? 0);
        $payment_status = $data['payment_status'] ?? 'pending';
        $payment_method = $data['payment_method'] ?? '';
        $notes = $data['notes'] ?? '';
        $items = $data['items'] ?? [];
        
        // Start transaction
        $conn->autocommit(FALSE);
        
        try {
            // Insert invoice
            $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, customer_id, invoice_date, due_date, subtotal, tax_rate, tax_amount, discount, total_amount, payment_status, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissddddssss", $invoice_number, $customer_id, $invoice_date, $due_date, $subtotal, $tax_rate, $tax_amount, $discount, $total_amount, $payment_status, $payment_method, $notes);
            $stmt->execute();
            $invoice_id = $conn->insert_id;
            
            // Insert invoice items and update product quantities
            foreach ($items as $item) {
                $product_id = intval($item['product_id']);
                $product_name = $item['product_name'];
                $quantity = intval($item['quantity']);
                $unit_price = floatval($item['unit_price']);
                $total_price = floatval($item['total_price']);
                
                // Insert invoice item
                $stmt2 = $conn->prepare("INSERT INTO invoice_items (invoice_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("iisidd", $invoice_id, $product_id, $product_name, $quantity, $unit_price, $total_price);
                $stmt2->execute();
                
                // Update product quantity
                $stmt3 = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt3->bind_param("ii", $quantity, $product_id);
                $stmt3->execute();
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'id' => $invoice_id, 'invoice_number' => $invoice_number]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        
        $conn->autocommit(TRUE);
        break;
        
    case 'PUT':
        // Update invoice with items
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        $customer_id = intval($data['customer_id'] ?? 0) ?: null;
        $invoice_date = $data['invoice_date'] ?? date('Y-m-d');
        $due_date = $data['due_date'] ?? null;
        $subtotal = floatval($data['subtotal'] ?? 0);
        $tax_rate = floatval($data['tax_rate'] ?? 0);
        $tax_amount = floatval($data['tax_amount'] ?? 0);
        $discount = floatval($data['discount'] ?? 0);
        $total_amount = floatval($data['total_amount'] ?? 0);
        $payment_status = $data['payment_status'] ?? 'pending';
        $payment_method = $data['payment_method'] ?? '';
        $notes = $data['notes'] ?? '';
        $items = $data['items'] ?? [];
        
        // Start transaction
        $conn->autocommit(FALSE);
        
        try {
            // Update invoice header
            $stmt = $conn->prepare("UPDATE invoices SET customer_id = ?, invoice_date = ?, due_date = ?, subtotal = ?, tax_rate = ?, tax_amount = ?, discount = ?, total_amount = ?, payment_status = ?, payment_method = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("issddddssssi", $customer_id, $invoice_date, $due_date, $subtotal, $tax_rate, $tax_amount, $discount, $total_amount, $payment_status, $payment_method, $notes, $id);
            $stmt->execute();
            
            // Get old invoice items to restore quantities
            $stmt = $conn->prepare("SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $oldItems = [];
            while ($row = $result->fetch_assoc()) {
                $oldItems[$row['product_id']] = $row['quantity'];
            }
            
            // Restore old quantities
            foreach ($oldItems as $product_id => $quantity) {
                $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
            }
            
            // Delete old invoice items
            $stmt = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Insert new invoice items and update product quantities
            foreach ($items as $item) {
                $product_id = intval($item['product_id']);
                $product_name = $item['product_name'];
                $quantity = intval($item['quantity']);
                $unit_price = floatval($item['unit_price']);
                $total_price = floatval($item['total_price']);
                
                // Insert invoice item
                $stmt2 = $conn->prepare("INSERT INTO invoice_items (invoice_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("iisidd", $id, $product_id, $product_name, $quantity, $unit_price, $total_price);
                $stmt2->execute();
                
                // Update product quantity (deduct new quantity)
                $stmt3 = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt3->bind_param("ii", $quantity, $product_id);
                $stmt3->execute();
            }
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        
        $conn->autocommit(TRUE);
        break;
        
    case 'DELETE':
        // Delete invoice (restore product quantities)
        $id = intval($_GET['id'] ?? 0);
        
        $conn->autocommit(FALSE);
        
        try {
            // Get invoice items to restore quantities
            $stmt = $conn->prepare("SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                // Restore product quantity
                $stmt2 = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $stmt2->bind_param("ii", $row['quantity'], $row['product_id']);
                $stmt2->execute();
            }
            
            // Delete invoice (cascade will delete items)
            $stmt3 = $conn->prepare("DELETE FROM invoices WHERE id = ?");
            $stmt3->bind_param("i", $id);
            $stmt3->execute();
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        
        $conn->autocommit(TRUE);
        break;
}

closeDBConnection($conn);
?>
