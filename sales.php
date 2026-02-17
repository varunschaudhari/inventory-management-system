<?php
session_start();

// Check if user is logged in
require_once 'includes/functions.php';
redirect_if_not_logged();

// Include database connection
require_once 'includes/db.php';

// Fetch all customers for dropdown
$customers = [];
try {
    $stmt = $pdo->query("SELECT id, name, phone, email, address, state_code FROM customers ORDER BY name ASC");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $customers = [];
}

// Fetch all products for search
$products = [];
try {
    $stmt = $pdo->query("SELECT id, name, code, sale_price, cgst_rate, sgst_rate, igst_rate, stock FROM products ORDER BY name ASC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Get username from session
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Sale - My Business</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- DataTables Bootstrap 5 CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar posdash-sidebar" id="sidebar">
        <div class="sidebar-brand posdash-brand">
            <div class="brand-logo">
                <span class="logo-p">P</span><span class="logo-d">D</span>
            </div>
            <span class="brand-text">POSDash</span>
        </div>
        <ul class="sidebar-menu posdash-menu">
            <li>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboards</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <i class="bi bi-cart"></i>
                    <span>Products</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </li>
            <li>
                <a href="#" class="">
                    <i class="bi bi-folder"></i>
                    <span>Categories</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </li>
            <li>
                <a href="#" class="sale-menu-item">
                    <i class="bi bi-clock-history"></i>
                    <span>Sale</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
                <ul class="sale-submenu" style="list-style: none; padding-left: 20px; margin-top: 5px;">
                    <li>
                        <a href="list_sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'list_sales.php' ? 'active' : ''; ?>">
                            <i class="bi bi-dash"></i>
                            <span>List Sale</span>
                        </a>
                    </li>
                    <li>
                        <a href="sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">
                            <i class="bi bi-plus-circle"></i>
                            <span>Add Sale</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="#" class="">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Purchases</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </li>
            <li>
                <a href="#" class="">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Returns</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </li>
            <li>
                <a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Customers</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </li>
            <li>
                <a href="#" class="">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Reports</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </li>
            <li>
                <a href="#" class="">
                    <i class="bi bi-file-earmark"></i>
                    <span>Other Page</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Enhanced Top Bar -->
        <div class="top-bar-posdash">
            <div class="top-bar-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div class="top-bar-logo">
                    <span class="logo-p">P</span><span class="logo-d">D</span>
                    <span class="logo-text">POSDash</span>
                </div>
                <div class="search-box-posdash">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search here..." class="form-control">
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn-lang">
                    <span>En</span>
                    <i class="bi bi-flag"></i>
                </button>
                <a href="sales.php" class="btn-new-order">
                    <i class="bi bi-plus-circle"></i>New Order
                </a>
                <div class="notification-icon-posdash">
                    <i class="bi bi-envelope"></i>
                </div>
                <div class="notification-icon-posdash">
                    <i class="bi bi-bell"></i>
                </div>
                <div class="user-profile-posdash dropdown">
                    <div class="profile-avatar-posdash dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                        <div class="avatar-circle"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" style="margin-top: 10px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; padding: 12px;">
                        <li class="px-3 py-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-circle" style="width: 44px; height: 44px; font-size: 20px;">
                                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #1e293b; font-size: 15px;"><?php echo htmlspecialchars($username); ?></div>
                                    <div style="font-size: 12px; color: #64748b;">Administrator</div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php" style="padding: 12px 16px; border-radius: 8px; color: #ef4444; font-weight: 600;">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4">
            <!-- Page Header -->
            <div class="page-header-posdash fade-in mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h1 class="greeting-title" style="font-size: 28px; margin-bottom: 6px;">Create New Sale</h1>
                        <p class="greeting-subtitle" style="margin: 0; font-size: 15px;">Fill in the details below to create a new sale invoice</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="resetFormBtn" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset
                        </button>
                        <a href="list_sales.php" class="btn btn-outline-primary">
                            <i class="bi bi-list-ul me-2"></i>View Sales
                        </a>
                    </div>
                </div>
            </div>

            <!-- ① Tax Invoice Information Card -->
            <div class="card shadow-sm fade-in mb-4" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="card-header-modern" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 24px 30px; border: none;">
                    <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">
                        <i class="bi bi-receipt-cutoff me-2"></i>Tax Invoice Information
                    </h5>
                </div>
                <div class="card-body" style="padding: 30px;">
                    <div class="row g-4">
                        <!-- Company Details Column -->
                        <div class="col-lg-6">
                            <h6 class="section-title mb-3">
                                <i class="bi bi-building me-2"></i>Company Details
                            </h6>
                            
                            <div class="form-group-modern mb-3">
                                <label for="company_name" class="form-label-modern">
                                    Company Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="bi bi-building input-icon"></i>
                                    <input type="text" class="form-control-modern" id="company_name" name="company_name" placeholder="Enter company name" value="Your Company Name">
                                </div>
                            </div>

                            <div class="form-group-modern mb-3">
                                <label for="company_gstin" class="form-label-modern">
                                    Company GSTIN
                                </label>
                                <div class="input-wrapper">
                                    <i class="bi bi-hash input-icon"></i>
                                    <input type="text" class="form-control-modern" id="company_gstin" name="company_gstin" placeholder="29ABCDE1234F1Z5" value="29ABCDE1234F1Z5" maxlength="15">
                                </div>
                            </div>

                            <div class="form-group-modern mb-3">
                                <label for="company_address" class="form-label-modern">
                                    Company Address
                                </label>
                                <div class="input-wrapper">
                                    <i class="bi bi-geo-alt input-icon"></i>
                                    <textarea class="form-control-modern" id="company_address" name="company_address" rows="2" placeholder="123 Business Street, City - 560001">123 Business Street, City - 560001</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Details Column -->
                        <div class="col-lg-6">
                            <h6 class="section-title mb-3">
                                <i class="bi bi-telephone me-2"></i>Contact Details
                            </h6>
                            
                            <div class="form-group-modern mb-3">
                                <label for="company_phone" class="form-label-modern">
                                    Phone Number
                                </label>
                                <div class="input-wrapper">
                                    <i class="bi bi-phone input-icon"></i>
                                    <input type="tel" class="form-control-modern" id="company_phone" name="company_phone" placeholder="+91 98765 43210" value="+91 98765 43210">
                                </div>
                            </div>

                            <div class="form-group-modern mb-3">
                                <label for="company_email" class="form-label-modern">
                                    Email Address
                                </label>
                                <div class="input-wrapper">
                                    <i class="bi bi-envelope input-icon"></i>
                                    <input type="email" class="form-control-modern" id="company_email" name="company_email" placeholder="info@company.com" value="info@company.com">
                                </div>
                            </div>

                            <div class="form-group-modern mb-3">
                                <label for="company_state_code" class="form-label-modern">
                                    State Code
                                </label>
                                <div class="input-wrapper">
                                    <i class="bi bi-map input-icon"></i>
                                    <input type="text" class="form-control-modern" id="company_state_code" name="company_state_code" placeholder="29" value="29" maxlength="2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ② Products Card (full width) -->
            <div class="card shadow-sm fade-in mb-4" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="card-header-modern" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 24px 30px; border: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">
                            <i class="bi bi-box-seam me-2"></i>Products
                        </h5>
                        <span class="badge bg-white text-success" id="cartCount" style="font-size: 13px; padding: 6px 14px; font-weight: 600;">0 items</span>
                    </div>
                </div>
                <div class="card-body" style="padding: 30px;">
                    <!-- Product Search -->
                    <div class="product-search-wrapper mb-4">
                        <div class="input-wrapper position-relative">
                            <i class="bi bi-search input-icon"></i>
                            <input type="text" id="productSearch" class="form-control-modern" placeholder="Search products by name or code..." autocomplete="off">
                            <div id="productResults" class="product-dropdown-modern"></div>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="table-wrapper-modern">
                        <table class="table-modern" id="productsTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Product Name</th>
                                    <th style="width: 120px;">Price</th>
                                    <th style="width: 110px;">Quantity</th>
                                    <th style="width: 100px;">Tax</th>
                                    <th style="width: 130px;">Total</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="bi bi-inbox"></i>
                                            <p>No products added yet</p>
                                            <small>Search and add products above or click "Add Product Row"</small>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Quick Add Button -->
                    <div class="mt-3">
                        <button type="button" class="btn-add-row" id="addProductRowBtn">
                            <i class="bi bi-plus-circle me-2"></i>Add Product Row
                        </button>
                    </div>
                </div>
            </div>

            <!-- ③ Sale Information Card -->
            <div class="card shadow-sm fade-in mb-4" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="card-header-modern" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px 30px; border: none;">
                    <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">
                        <i class="bi bi-receipt me-2"></i>Sale Information
                    </h5>
                </div>
                <div class="card-body" style="padding: 30px;">
                    <form id="saleForm">
                        <div class="row g-4">
                            <!-- Left Column -->
                            <div class="col-lg-6">
                                <div class="form-section">
                                    <h6 class="section-title">
                                        <i class="bi bi-calendar3 me-2"></i>Basic Details
                                    </h6>
                                    
                                    <!-- Date -->
                                    <div class="form-group-modern mb-4">
                                        <label for="sale_date" class="form-label-modern">
                                            Date <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-calendar3 input-icon"></i>
                                            <input type="date" class="form-control-modern" id="sale_date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>

                                    <!-- Reference No -->
                                    <div class="form-group-modern mb-4">
                                        <label for="reference_no" class="form-label-modern">
                                            Reference No <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-hash input-icon"></i>
                                            <input type="text" class="form-control-modern" id="reference_no" name="reference_no" placeholder="Enter reference number" required>
                                        </div>
                                    </div>

                                    <!-- Customer -->
                                    <div class="form-group-modern mb-4">
                                        <label for="customer_search" class="form-label-modern">
                                            Customer <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-wrapper position-relative">
                                            <i class="bi bi-person input-icon"></i>
                                            <input type="text" class="form-control-modern" id="customer_search" name="customer_search" placeholder="Search customer by name..." autocomplete="off">
                                            <input type="hidden" id="customer_id" name="customer_id">
                                            <div id="customerResults" class="customer-dropdown"></div>
                                        </div>
                                    </div>

                                    <!-- Biller -->
                                    <div class="form-group-modern mb-4">
                                        <label for="biller" class="form-label-modern">
                                            Biller
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-person-badge input-icon"></i>
                                            <select class="form-control-modern" id="biller" name="biller">
                                                <option value="default">Default Biller</option>
                                                <option value="test" selected>Test Biller</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-lg-6">
                                <div class="form-section">
                                    <h6 class="section-title">
                                        <i class="bi bi-calculator me-2"></i>Pricing & Status
                                    </h6>

                                    <!-- Order Tax -->
                                    <div class="form-group-modern mb-4">
                                        <label for="order_tax" class="form-label-modern">
                                            Order Tax <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-receipt-cutoff input-icon"></i>
                                            <select class="form-control-modern" id="order_tax" name="order_tax" required>
                                                <option value="0" selected>No Tax</option>
                                                <option value="5">5%</option>
                                                <option value="9">9%</option>
                                                <option value="12">12%</option>
                                                <option value="18">18%</option>
                                                <option value="28">28%</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Shipping -->
                                    <div class="form-group-modern mb-4">
                                        <label for="shipping" class="form-label-modern">
                                            Shipping Charges
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-truck input-icon"></i>
                                            <input type="number" class="form-control-modern" id="shipping" name="shipping" placeholder="0.00" min="0" step="0.01" value="0">
                                        </div>
                                    </div>

                                    <!-- Payment Status -->
                                    <div class="form-group-modern mb-4">
                                        <label for="payment_status" class="form-label-modern">
                                            Payment Status <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-credit-card input-icon"></i>
                                            <select class="form-control-modern" id="payment_status" name="payment_status" required>
                                                <option value="pending" selected>Pending</option>
                                                <option value="partial">Partial</option>
                                                <option value="paid">Paid</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Sale Status -->
                                    <div class="form-group-modern mb-4">
                                        <label for="sale_status" class="form-label-modern">
                                            Sale Status <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-check-circle input-icon"></i>
                                            <select class="form-control-modern" id="sale_status" name="sale_status" required>
                                                <option value="pending">Pending</option>
                                                <option value="completed" selected>Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details Row -->
                        <div class="row g-4 mt-2">
                            <div class="col-lg-6">
                                <!-- Attach Document -->
                                <div class="form-group-modern mb-4">
                                    <label for="document" class="form-label-modern">
                                        <i class="bi bi-paperclip me-1"></i>Attach Document
                                    </label>
                                    <div class="file-upload-wrapper">
                                        <input type="file" class="form-control-modern file-input" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png">
                                        <label for="document" class="file-upload-label">
                                            <i class="bi bi-cloud-upload me-2"></i>
                                            <span class="file-text">Choose file or drag & drop</span>
                                        </label>
                                        <small class="file-hint">PDF, JPG, PNG up to 10MB</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <!-- Sale Note -->
                                <div class="form-group-modern mb-4">
                                    <label for="sale_note" class="form-label-modern">
                                        <i class="bi bi-sticky me-1"></i>Sale Note
                                    </label>
                                    <div class="rich-text-editor-modern">
                                        <div class="editor-toolbar-modern">
                                            <button type="button" class="toolbar-btn" onclick="formatText('bold')" title="Bold">
                                                <i class="bi bi-type-bold"></i>
                                            </button>
                                            <button type="button" class="toolbar-btn" onclick="formatText('italic')" title="Italic">
                                                <i class="bi bi-type-italic"></i>
                                            </button>
                                            <button type="button" class="toolbar-btn" onclick="formatText('underline')" title="Underline">
                                                <i class="bi bi-type-underline"></i>
                                            </button>
                                            <div class="toolbar-divider"></div>
                                            <button type="button" class="toolbar-btn" onclick="insertImage()" title="Insert Image">
                                                <i class="bi bi-image"></i>
                                            </button>
                                            <button type="button" class="toolbar-btn" onclick="insertLink()" title="Insert Link">
                                                <i class="bi bi-link-45deg"></i>
                                            </button>
                                        </div>
                                        <textarea class="editor-content" id="sale_note" name="sale_note" rows="4" placeholder="Add any additional notes or comments..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ④ Action Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="action-buttons-bar">
                        <div class="d-flex gap-3 justify-content-end flex-wrap">
                            <button type="button" id="resetFormBtnBottom" class="btn-action-secondary">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Form
                            </button>
                            <button type="button" id="previewInvoiceBtnMain" class="btn-action-primary">
                                <i class="bi bi-eye me-2"></i>Preview Invoice
                            </button>
                            <button type="button" id="submitOrderBtn" class="btn btn-success" disabled style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; color: white; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; opacity: 0.5; cursor: not-allowed;">
                                <i class="bi bi-check-circle me-2"></i>Submit Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <!-- Tax Invoice Preview Modal -->
    <div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-labelledby="invoicePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 12px; border: none;">
                <!-- Modal Header -->
                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 12px 12px 0 0; padding: 18px 25px;">
                    <h5 class="modal-title" id="invoicePreviewModalLabel" style="font-size: 18px; font-weight: 600;">
                        <i class="bi bi-file-earmark-text me-2"></i>Tax Invoice Preview
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body — Invoice Paper -->
                <div class="modal-body" style="padding: 32px; background: #f1f5f9;">
                    <div id="invoicePrintArea" style="max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 36px;">

                        <!-- Company Header -->
                        <div class="text-center mb-4 pb-4 border-bottom">
                            <h3 class="mb-1 fw-bold text-primary" id="previewCompanyName">Your Company Name</h3>
                            <p class="mb-1 text-muted small">GSTIN: <span id="previewCompanyGstin">29ABCDE1234F1Z5</span></p>
                            <p class="mb-1 text-muted small" id="previewCompanyAddress">123 Business Street, City - 560001</p>
                            <p class="mb-0 text-muted small">
                                Phone: <span id="previewCompanyPhone">+91 98765 43210</span>
                                &nbsp;|&nbsp;
                                Email: <span id="previewCompanyEmail">info@company.com</span>
                            </p>
                        </div>

                        <!-- Invoice Title + Number/Date -->
                        <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
                            <h4 class="mb-0 fw-bold text-primary" style="letter-spacing: 1px;">TAX INVOICE</h4>
                            <div class="text-end">
                                <p class="mb-1 small"><strong>Invoice No:</strong> <span id="previewInvoiceNo">-</span></p>
                                <p class="mb-0 small"><strong>Date:</strong> <span id="previewInvoiceDate"><?php echo date('d/m/Y'); ?></span></p>
                            </div>
                        </div>

                        <!-- Bill To -->
                        <div class="mb-4 pb-3 border-bottom">
                            <h6 class="fw-bold mb-2 text-uppercase text-muted" style="font-size: 11px; letter-spacing: 1.5px;">Bill To</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1" id="previewCustomerName"><strong>Customer Name:</strong> -</p>
                                    <p class="mb-1 small" id="previewCustomerPhone"><strong>Phone:</strong> -</p>
                                    <p class="mb-1 small" id="previewCustomerAddress"><strong>Address:</strong> -</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 small" id="previewCustomerState"><strong>State Code:</strong> -</p>
                                    <p class="mb-0 small" id="previewCustomerGstin"><strong>GSTIN:</strong> -</p>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm mb-0" id="invoicePreviewTable" style="font-size: 13px;">
                                <thead style="background: #f0f4ff;">
                                    <tr>
                                        <th style="width: 36px;">#</th>
                                        <th>Description</th>
                                        <th class="text-end" style="width: 60px;">Qty</th>
                                        <th class="text-end" style="width: 90px;">Rate (₹)</th>
                                        <th class="text-end" style="width: 100px;">Amount (₹)</th>
                                        <th class="text-end" style="width: 95px;">CGST</th>
                                        <th class="text-end" style="width: 95px;">SGST</th>
                                        <th class="text-end" style="width: 95px;">IGST</th>
                                        <th class="text-end" style="width: 105px;">Total (₹)</th>
                                    </tr>
                                </thead>
                                <tbody id="invoicePreviewBody">
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No items added yet</td>
                                    </tr>
                                </tbody>
                                <tfoot style="background: #f8fafc; font-size: 13px;">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                        <td class="text-end fw-bold" id="previewSubtotal">₹0.00</td>
                                        <td colspan="4"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end text-muted">CGST Total:</td>
                                        <td class="text-end text-muted" id="previewCgstTotal">₹0.00</td>
                                        <td colspan="4"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end text-muted">SGST Total:</td>
                                        <td class="text-end text-muted" id="previewSgstTotal">₹0.00</td>
                                        <td colspan="4"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end text-muted">IGST Total:</td>
                                        <td class="text-end text-muted" id="previewIgstTotal">₹0.00</td>
                                        <td colspan="4"></td>
                                    </tr>
                                    <tr style="background: #dbeafe;">
                                        <td colspan="4" class="text-end fw-bold fs-6">Grand Total:</td>
                                        <td class="text-end fw-bold fs-6 text-primary" id="previewGrandTotal">₹0.00</td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Amount in Words + Grand Total -->
                        <div class="d-flex justify-content-between align-items-end pt-3 border-top">
                            <div>
                                <p class="mb-1 text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Amount in Words:</p>
                                <p class="mb-0 fst-italic text-dark" id="previewAmountWords">Zero Rupees Only</p>
                            </div>
                            <div class="text-end">
                                <p class="mb-1 text-muted small">Grand Total</p>
                                <h3 class="mb-0 fw-bold text-primary" id="previewGrandTotalDisplay">₹0.00</h3>
                            </div>
                        </div>

                    </div><!-- end invoice paper -->
                </div><!-- end modal-body -->

                <!-- Modal Footer -->
                <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 16px 24px; gap: 10px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 10px 20px; border-radius: 8px;">
                        <i class="bi bi-x-lg me-1"></i> Close
                    </button>
                    <button type="button" id="printInvoiceBtn" class="btn btn-outline-primary" style="padding: 10px 20px; border-radius: 8px;" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <button type="button" id="saveInvoiceBtn" class="btn btn-success" style="padding: 10px 24px; border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-check-circle me-1"></i> Save & Submit Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery 3.7 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Global cart array
        let cart = [];

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

            $(document).ready(function() {
            // Generate reference number on page load
            const refNo = 'REF-' + Date.now();
            $('#reference_no').val(refNo);
            
            // Initial form completion check
            setTimeout(function() {
                checkFormCompletion();
            }, 100);

            // Customer search functionality
            const searchCustomers = debounce(function(value) {
                if (value.length < 1) {
                    $('#customerResults').hide().empty();
                    return;
                }

                const customers = <?php echo json_encode($customers); ?>;
                const filtered = customers.filter(c => 
                    c.name.toLowerCase().includes(value.toLowerCase())
                );

                let html = '';
                if (filtered.length === 0) {
                    html = '<div class="list-group-item text-muted">No customers found</div>';
                } else {
                    filtered.forEach(c => {
                        html += `<a href="#" class="list-group-item list-group-item-action select-customer" 
                                     data-id="${c.id}" 
                                     data-name="${c.name}"
                                     data-phone="${c.phone || ''}"
                                     data-email="${c.email || ''}"
                                     data-address="${c.address || ''}"
                                     data-state_code="${c.state_code || ''}">
                                     <strong>${c.name}</strong>
                                     ${c.phone ? '<br><small class="text-muted">' + c.phone + '</small>' : ''}
                                   </a>`;
                    });
                }
                $('#customerResults').html(html).show();
            }, 300);

            $('#customer_search').on('keyup', function() {
                searchCustomers($(this).val());
            });

            $(document).on('click', '.select-customer', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const name = $(this).data('name');
                const phone = $(this).data('phone') || '-';
                const email = $(this).data('email') || '-';
                const address = $(this).data('address') || '-';
                const stateCode = $(this).data('state_code') || '-';
                
                $('#customer_id').val(id);
                $('#customer_search').val(name);
                $('#customerResults').hide();
                checkFormCompletion();
                
                // Update preview customer details
                $('#previewCustomerName').html('<strong>Customer Name:</strong> ' + name);
                $('#previewCustomerPhone').html('<strong>Phone:</strong> ' + phone);
                $('#previewCustomerAddress').html('<strong>Address:</strong> ' + address);
                $('#previewCustomerState').html('<strong>State Code:</strong> ' + stateCode);
                $('#previewCustomerGstin').html('<strong>GSTIN:</strong> -');
                
                renderInvoicePreview();
            });

            // Close customer results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#customer_search, #customerResults').length) {
                    $('#customerResults').hide();
                }
            });

            // Rich text editor functions
            window.formatText = function(command) {
                document.execCommand(command, false, null);
            };

            window.insertImage = function() {
                const url = prompt('Enter image URL:');
                if (url) {
                    document.execCommand('insertImage', false, url);
                }
            };

            window.insertLink = function() {
                const url = prompt('Enter link URL:');
                if (url) {
                    document.execCommand('createLink', false, url);
                }
            };

            // Reset form button (both top and bottom)
            $('#resetFormBtn, #resetFormBtnBottom').on('click', function() {
                Swal.fire({
                    title: 'Reset Form?',
                    text: 'Are you sure you want to reset all fields?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reset',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#saleForm')[0].reset();
                        cart = [];
                        $('#sale_date').val('<?php echo date('Y-m-d'); ?>');
                        $('#reference_no').val('REF-' + Date.now());
                        $('#customer_search').val('');
                        $('#customer_id').val('');
                        $('#productSearch').val('');
                        $('#productResults').hide();
                        $('#cartCount').text('0 items');
                        cart = [];
                        renderProductsTable();
                        checkFormCompletion();
                        Swal.fire('Reset!', 'Form has been reset.', 'success');
                    }
                });
            });

            // Initialize cart count on page load
            $('#cartCount').text('0 items');
            
            // Update company details in preview when changed
            $('#company_name, #company_gstin, #company_address, #company_phone, #company_email').on('input change', function() {
                updateCompanyPreview();
            });
            
            function updateCompanyPreview() {
                $('#previewCompanyName').text($('#company_name').val() || 'Your Company Name');
                $('#previewCompanyGstin').text($('#company_gstin').val() || '-');
                $('#previewCompanyAddress').text($('#company_address').val() || '-');
                $('#previewCompanyPhone').text($('#company_phone').val() || '-');
                $('#previewCompanyEmail').text($('#company_email').val() || '-');
            }
            
            // Initialize preview
            renderInvoicePreview();
            updateCompanyPreview();
            
            // Set initial invoice date
            const today = new Date();
            $('#previewInvoiceDate').text(today.toLocaleDateString('en-GB'));

            // All products from PHP
            const allProducts = <?php echo json_encode($products); ?>;

            // Live search with debounce
            const searchProducts = debounce(function(value) {
                if (value.length < 1) {
                    $('#productResults').hide().empty();
                    return;
                }

                fetch('search_products.php?q=' + encodeURIComponent(value))
                    .then(res => res.json())
                    .then(products => {
                        let html = '';
                        if (products.length === 0) {
                            html = '<div class="list-group-item text-muted" style="font-size: 14px; padding: 12px 15px;">No products found</div>';
                        } else {
                            products.forEach(p => {
                                const inCart = cart.find(item => item.id == p.id);
                                const stockStatus = (p.stock || 0) > 0 ? 'text-success' : 'text-danger';
                                const stockText = (p.stock || 0) > 0 ? `Stock: ${p.stock}` : 'Out of Stock';
                                const cartBadge = inCart ? `<span class="badge bg-primary ms-2" style="font-size: 11px;">In Cart (${inCart.qty})</span>` : '';
                                
                                html += `<a href="#" class="list-group-item list-group-item-action add-product ${(p.stock || 0) < 1 ? 'disabled' : ''}" 
                                             data-id="${p.id}" 
                                             data-name="${p.name}" 
                                             data-code="${p.code || ''}"
                                             data-price="${p.sale_price}" 
                                             data-cgst="${p.cgst_rate || 0}" 
                                             data-sgst="${p.sgst_rate || 0}" 
                                             data-igst="${p.igst_rate || 0}" 
                                             data-stock="${p.stock || 0}"
                                             style="font-size: 14px; padding: 12px 15px;">
                                             <div class="d-flex justify-content-between align-items-center">
                                                 <div>
                                                     <strong style="font-size: 14px;">${p.name}</strong>
                                                     ${p.code ? `<br><small class="text-muted" style="font-size: 12px;">Code: ${p.code}</small>` : ''}
                                                     <br>
                                                     <small class="text-muted" style="font-size: 13px;">₹${parseFloat(p.sale_price).toFixed(2)}</small>
                                                     <span class="badge ${stockStatus} ms-2" style="font-size: 11px;">${stockText}</span>
                                                     ${cartBadge}
                                                 </div>
                                                 <i class="bi bi-plus-circle text-primary" style="font-size: 20px;"></i>
                                             </div>
                                           </a>`;
                            });
                        }
                        $('#productResults').html(html).show();
                        $('#productResults').addClass('product-dropdown-modern');
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        $('#productResults').html('<div class="list-group-item text-danger" style="font-size: 14px; padding: 12px 15px;">Error searching products</div>').show();
                    });
            }, 300);

            $('#productSearch').on('keyup', function() {
                searchProducts($(this).val());
            });

            // Add product to cart
            $(document).on('click', '.add-product', function(e) {
                e.preventDefault();
                
                const id = $(this).data('id');
                const name = $(this).data('name');
                const code = $(this).data('code') || '';
                const price = parseFloat($(this).data('price'));
                const cgst = parseFloat($(this).data('cgst'));
                const sgst = parseFloat($(this).data('sgst'));
                const igst = parseFloat($(this).data('igst'));
                const stock = parseInt($(this).data('stock'));

                if (stock < 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Out of stock!',
                        text: 'This product is currently out of stock.'
                    });
                    return;
                }

                // Check if product already exists in cart
                const existingIndex = cart.findIndex(item => item.id === id);
                
                if (existingIndex !== -1) {
                    // Product already in cart - increment quantity if stock allows
                    const existingItem = cart[existingIndex];
                    if (existingItem.qty < existingItem.stock) {
                        cart[existingIndex].qty += 1;
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock Limit Reached',
                            text: `Maximum available stock (${existingItem.stock}) already in cart.`
                        });
                        return;
                    }
                } else {
                    // New product - add to cart
                    let item = {
                        id: id,
                        name: name,
                        code: code,
                        price: price,
                        cgst_rate: cgst,
                        sgst_rate: sgst,
                        igst_rate: igst,
                        stock: stock,
                        qty: 1
                    };

                    cart.push(item);
                }

                renderProductsTable();
                $('#productSearch').val('');
                $('#productResults').empty().hide();
                checkFormCompletion();
            });

            // Add Product Row Button
            $('#addProductRowBtn').on('click', function() {
                addProductRow();
            });

            // Add empty product row
            function addProductRow() {
                const rowIndex = cart.length;
                const rowHtml = `
                    <tr class="product-row" data-index="${rowIndex}">
                        <td style="font-weight: 600; color: #64748b;">${rowIndex + 1}</td>
                        <td>
                            <div class="position-relative">
                                <input type="text" class="form-control-modern product-search-input" placeholder="Search product..." style="font-size: 14px; padding: 8px 12px; height: 38px; border: 1px solid #e2e8f0;" autocomplete="off">
                                <input type="hidden" class="product-id-input">
                                <div class="product-dropdown" style="display: none; position: absolute; z-index: 1000; background: white; border: 2px solid #e2e8f0; border-radius: 10px; max-height: 200px; overflow-y: auto; width: 100%; box-shadow: 0 8px 24px rgba(0,0,0,0.12); top: 100%; margin-top: 4px;"></div>
                            </div>
                        </td>
                        <td>
                            <input type="number" class="form-control-modern product-price" placeholder="0.00" step="0.01" min="0" style="font-size: 14px; padding: 8px 12px; height: 38px; border: 1px solid #e2e8f0; text-align: right;" readonly>
                        </td>
                        <td>
                            <input type="number" class="form-control-modern product-qty" placeholder="1" min="1" value="1" style="font-size: 14px; padding: 8px 12px; height: 38px; width: 100px; border: 1px solid #e2e8f0; text-align: center;">
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">Stock: <span class="product-stock">0</span></small>
                        </td>
                        <td>
                            <input type="text" class="form-control-modern product-tax" placeholder="0%" style="font-size: 14px; padding: 8px 12px; height: 38px; border: 1px solid #e2e8f0; text-align: center;" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control-modern product-total" placeholder="₹0.00" style="font-size: 14px; padding: 8px 12px; height: 38px; font-weight: 600; border: 1px solid #e2e8f0; text-align: right; color: #1e293b;" readonly>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn btn-sm btn-danger remove-product-row" style="padding: 6px 10px; border-radius: 6px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                
                if ($('#productsTableBody tr:first-child').hasClass('product-row') || $('#productsTableBody tr:first-child td').attr('colspan')) {
                    $('#productsTableBody').html(rowHtml);
                } else {
                    $('#productsTableBody').append(rowHtml);
                }
                
                initializeProductRow($('#productsTableBody tr:last-child'));
            }

            // Initialize product row functionality
            function initializeProductRow($row) {
                const $searchInput = $row.find('.product-search-input');
                const $productId = $row.find('.product-id-input');
                const $priceInput = $row.find('.product-price');
                const $qtyInput = $row.find('.product-qty');
                const $taxInput = $row.find('.product-tax');
                const $totalInput = $row.find('.product-total');
                const $stockSpan = $row.find('.product-stock');
                const $dropdown = $row.find('.product-dropdown');

                // Product search for this row
                const searchProductRow = debounce(function(value) {
                    if (value.length < 2) {
                        $dropdown.hide().empty();
                        return;
                    }

                    const filtered = allProducts.filter(p => 
                        p.name.toLowerCase().includes(value.toLowerCase()) || 
                        (p.code && p.code.toLowerCase().includes(value.toLowerCase()))
                    );

                    let html = '';
                    if (filtered.length === 0) {
                        html = '<div class="list-group-item text-muted" style="font-size: 13px; padding: 10px;">No products found</div>';
                    } else {
                        filtered.forEach(p => {
                            html += `<div class="list-group-item list-group-item-action select-product-row" 
                                         data-id="${p.id}" 
                                         data-name="${p.name}"
                                         data-code="${p.code || ''}"
                                         data-price="${p.sale_price}" 
                                         data-cgst="${p.cgst_rate || 0}" 
                                         data-sgst="${p.sgst_rate || 0}" 
                                         data-igst="${p.igst_rate || 0}" 
                                         data-stock="${p.stock || 0}"
                                         style="font-size: 13px; padding: 10px; cursor: pointer;">
                                         <strong>${p.name}</strong>
                                         ${p.code ? `<br><small class="text-muted">Code: ${p.code}</small>` : ''}
                                         <br><small class="text-muted">₹${parseFloat(p.sale_price).toFixed(2)} | Stock: ${p.stock || 0}</small>
                                       </div>`;
                        });
                    }
                    $dropdown.html(html).show();
                }, 300);

                $searchInput.on('keyup', function() {
                    searchProductRow($(this).val());
                });

                // Select product from dropdown
                $(document).on('click', '.select-product-row', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    const code = $(this).data('code') || '';
                    const price = parseFloat($(this).data('price'));
                    const cgst = parseFloat($(this).data('cgst'));
                    const sgst = parseFloat($(this).data('sgst'));
                    const igst = parseFloat($(this).data('igst'));
                    const stock = parseInt($(this).data('stock'));

                    $productId.val(id);
                    $searchInput.val(name + (code ? ` (${code})` : ''));
                    $priceInput.val(price.toFixed(2));
                    $stockSpan.text(stock);
                    $qtyInput.attr('max', stock);
                    
                    const taxRate = cgst + sgst + igst;
                    $taxInput.val(taxRate > 0 ? `${taxRate.toFixed(2)}%` : '0%');
                    
                    $dropdown.hide();
                    calculateRowTotal($row);
                    updateCartFromTable();
                    checkFormCompletion();
                });

                // Calculate row total
                function calculateRowTotal($row) {
                    const price = parseFloat($row.find('.product-price').val()) || 0;
                    const qty = parseInt($row.find('.product-qty').val()) || 0;
                    const productId = $row.find('.product-id-input').val();
                    
                    if (productId) {
                        const product = allProducts.find(p => p.id == productId);
                        if (product) {
                            const cgst = parseFloat(product.cgst_rate || 0);
                            const sgst = parseFloat(product.sgst_rate || 0);
                            const igst = parseFloat(product.igst_rate || 0);
                            const subtotal = price * qty;
                            const tax = subtotal * (cgst + sgst + igst) / 100;
                            const total = subtotal + tax;
                            $row.find('.product-total').val(total.toFixed(2));
                        }
                    } else {
                        $row.find('.product-total').val('0.00');
                    }
                }

                // Quantity change
                $qtyInput.on('change', function() {
                    const qty = parseInt($(this).val()) || 1;
                    const stock = parseInt($stockSpan.text()) || 0;
                    
                    if (qty > stock) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Exceeds Stock',
                            text: `Available stock: ${stock}`
                        });
                        $(this).val(Math.min(qty, stock));
                    }
                    if (qty < 1) {
                        $(this).val(1);
                    }
                    calculateRowTotal($row);
                    updateCartFromTable();
                    checkFormCompletion();
                });

                // Remove row
                $row.find('.remove-product-row').on('click', function() {
                    $row.remove();
                    updateCartFromTable();
                    renderProductsTable();
                    checkFormCompletion();
                });
            }

            // Update cart from table
            function updateCartFromTable() {
                cart = [];
                $('#productsTableBody .product-row').each(function() {
                    const productId = $(this).find('.product-id-input').val();
                    if (productId) {
                        const product = allProducts.find(p => p.id == productId);
                        if (product) {
                            const qty = parseInt($(this).find('.product-qty').val()) || 1;
                            cart.push({
                                id: product.id,
                                name: product.name,
                                code: product.code || '',
                                price: parseFloat(product.sale_price),
                                cgst_rate: parseFloat(product.cgst_rate || 0),
                                sgst_rate: parseFloat(product.sgst_rate || 0),
                                igst_rate: parseFloat(product.igst_rate || 0),
                                stock: parseInt(product.stock || 0),
                                qty: qty
                            });
                        }
                    }
                });
                renderProductsTable();
            }

            // Render products table
            function renderProductsTable() {
                if (cart.length === 0) {
                    $('#productsTableBody').html(`
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div class="empty-state-content">
                                    <i class="bi bi-inbox"></i>
                                    <p>No products added yet</p>
                                    <small>Search and add products above or click "Add Product Row"</small>
                                </div>
                            </td>
                        </tr>
                    `);
                    $('#cartCount').text('0 items');
                    $('#previewButtonContainer').hide();
                    $('#previewInvoiceBtnMain').hide();
                    // Keep submit button visible but disabled
                    $('#submitOrderBtn').prop('disabled', true).css({
                        'opacity': '0.5',
                        'cursor': 'not-allowed'
                    });
                    calculateTotals();
                    checkFormCompletion();
                    return;
                }

                let html = '';
                cart.forEach((item, index) => {
                    const lineSub = item.price * item.qty;
                    const lineCgst = lineSub * (item.cgst_rate / 100);
                    const lineSgst = lineSub * (item.sgst_rate / 100);
                    const lineIgst = lineSub * (item.igst_rate / 100);
                    const taxRate = item.cgst_rate + item.sgst_rate + item.igst_rate;
                    const lineTotal = lineSub + lineCgst + lineSgst + lineIgst;

                    html += `
                        <tr class="product-row" data-index="${index}">
                            <td style="padding: 12px; vertical-align: middle; font-size: 14px;">${index + 1}</td>
                            <td style="padding: 12px; vertical-align: middle;">
                                <input type="text" class="form-control product-search-input" value="${item.name}${item.code ? ' (' + item.code + ')' : ''}" style="font-size: 14px; padding: 8px 12px; height: 38px;" readonly>
                                <input type="hidden" class="product-id-input" value="${item.id}">
                            </td>
                            <td style="padding: 12px; vertical-align: middle;">
                                <input type="number" class="form-control product-price" value="${item.price.toFixed(2)}" step="0.01" style="font-size: 14px; padding: 8px 12px; height: 38px;" readonly>
                            </td>
                            <td style="padding: 12px; vertical-align: middle;">
                                <input type="number" class="form-control product-qty" value="${item.qty}" min="1" max="${item.stock}" style="font-size: 14px; padding: 8px 12px; height: 38px; width: 100px;">
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Stock: <span class="product-stock">${item.stock}</span></small>
                            </td>
                            <td style="padding: 12px; vertical-align: middle;">
                                <input type="text" class="form-control product-tax" value="${taxRate > 0 ? taxRate.toFixed(2) + '%' : '0%'}" style="font-size: 14px; padding: 8px 12px; height: 38px;" readonly>
                            </td>
                            <td style="padding: 12px; vertical-align: middle;">
                                <input type="text" class="form-control product-total" value="${lineTotal.toFixed(2)}" style="font-size: 14px; padding: 8px 12px; height: 38px; font-weight: 600;" readonly>
                            </td>
                            <td style="padding: 12px; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-sm btn-danger remove-product-row" style="font-size: 13px; padding: 6px 12px;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#productsTableBody').html(html);
                
                // Re-initialize rows
                $('#productsTableBody .product-row').each(function() {
                    initializeProductRow($(this));
                });

                $('#cartCount').text(`${cart.length} ${cart.length === 1 ? 'item' : 'items'}`);
                calculateTotals();
                // Trigger form completion check after rendering
                setTimeout(checkFormCompletion, 50);
                // Render invoice preview
                renderInvoicePreview();
            }

            // Calculate totals function
            function calculateTotals() {
                let subtotal = 0;
                let cgstTotal = 0;
                let sgstTotal = 0;
                let igstTotal = 0;

                cart.forEach((item) => {
                    let lineSub = item.price * item.qty;
                    subtotal += lineSub;
                    cgstTotal += lineSub * (item.cgst_rate / 100);
                    sgstTotal += lineSub * (item.sgst_rate / 100);
                    igstTotal += lineSub * (item.igst_rate / 100);
                });

                // Calculate discount
                const discount = parseFloat($('#order_discount').val()) || 0;
                const discountType = $('#discount_type').val();
                const shipping = parseFloat($('#shipping').val()) || 0;
                const orderTax = parseFloat($('#order_tax').val()) || 0;
                
                let discountAmount = 0;
                if (discountType === 'percent') {
                    discountAmount = (subtotal * discount) / 100;
                } else {
                    discountAmount = discount;
                }
                
                // Apply order tax on subtotal after discount
                const orderTaxAmount = ((subtotal - discountAmount) * orderTax) / 100;
                
                const finalSubtotal = subtotal - discountAmount;
                const totalTax = cgstTotal + sgstTotal + igstTotal + orderTaxAmount;
                const grandTotal = finalSubtotal + totalTax + shipping;

                $('#subtotal').text('₹' + subtotal.toFixed(2));
                $('#discountAmount').text('-₹' + discountAmount.toFixed(2));
                $('#shippingAmount').text('₹' + shipping.toFixed(2));
                $('#cgst').text('₹' + cgstTotal.toFixed(2));
                $('#sgst').text('₹' + sgstTotal.toFixed(2));
                $('#igst').text('₹' + igstTotal.toFixed(2));
                $('#grandTotal').text('₹' + grandTotal.toFixed(2));
                
                // Show preview and submit buttons if cart has items
                if (cart.length > 0) {
                    checkFormCompletion();
                } else {
                    $('#previewButtonContainer').hide();
                    $('#previewInvoiceBtnMain').hide();
                    // Keep submit button visible but disabled
                    $('#submitOrderBtn').prop('disabled', true).css({
                        'opacity': '0.5',
                        'cursor': 'not-allowed'
                    });
                }
            }

            // Check if form is complete to show preview and submit buttons
            function checkFormCompletion() {
                const hasDate = $('#sale_date').val() !== '';
                const hasCustomer = $('#customer_id').val() !== '';
                const hasReference = $('#reference_no').val() !== '';
                const hasProducts = cart.length > 0;
                
                const isComplete = hasDate && hasCustomer && hasReference && hasProducts;
                
                if (isComplete) {
                    $('#previewButtonContainer').show();
                    $('#previewInvoiceBtnMain').show();
                    // Enable and show submit button
                    $('#submitOrderBtn').prop('disabled', false).css({
                        'opacity': '1',
                        'cursor': 'pointer'
                    });
                } else {
                    $('#previewButtonContainer').hide();
                    $('#previewInvoiceBtnMain').hide();
                    // Disable submit button but keep it visible
                    $('#submitOrderBtn').prop('disabled', true).css({
                        'opacity': '0.5',
                        'cursor': 'not-allowed'
                    });
                }
            }

            // Preview button from main form — render fresh data then open modal
            $('#previewInvoiceBtnMain').on('click', function() {
                renderInvoicePreview();
                const modal = new bootstrap.Modal(document.getElementById('invoicePreviewModal'));
                modal.show();
            });

            // Watch form fields for changes
            $('#sale_date, #customer_id, #reference_no').on('change input', function() {
                checkFormCompletion();
                renderInvoicePreview();
            });
            
            // Update invoice date and number in preview
            $('#sale_date').on('change', function() {
                const date = new Date($(this).val());
                const formattedDate = date.toLocaleDateString('en-GB');
                $('#previewInvoiceDate').text(formattedDate);
            });
            
            $('#reference_no').on('input', function() {
                $('#previewInvoiceNo').text($(this).val() || '-');
            });

            // Watch customer search input for changes
            $('#customer_search').on('input', function() {
                // Trigger check after a short delay to allow customer selection
                setTimeout(checkFormCompletion, 100);
            });

            // Direct submit order button (without preview)
            $('#submitOrderBtn').on('click', function() {
                // Validate first
                if (!$('#sale_date').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Required',
                        text: 'Please select a date.'
                    });
                    return;
                }

                if (!$('#customer_id').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Customer Required',
                        text: 'Please select a customer first.'
                    });
                    return;
                }

                if (!$('#reference_no').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Reference Number Required',
                        text: 'Please enter a reference number.'
                    });
                    return;
                }

                if (cart.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Empty Cart',
                        text: 'Please add at least one product to the cart.'
                    });
                    return;
                }

                // Confirm before submitting
                Swal.fire({
                    title: 'Submit Order?',
                    text: 'Are you sure you want to submit this sales order?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Submit',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveInvoice();
                    }
                });
            });

            // Preview Invoice Button (secondary handler — kept for any other trigger elements)
            $('#previewInvoiceBtn').on('click', function() {
                renderInvoicePreview();
                const modal = new bootstrap.Modal(document.getElementById('invoicePreviewModal'));
                modal.show();
            });

            // Generate Invoice Preview
            function generateInvoicePreview() {
                const customerName = $('#customer_search').val() || 'N/A';
                const referenceNo = $('#reference_no').val() || 'N/A';
                const saleDate = $('#sale_date').val() || new Date().toISOString().split('T')[0];
                const biller = $('#biller option:selected').text();
                const saleStatus = $('#sale_status option:selected').text();
                const paymentStatus = $('#payment_status option:selected').text();
                const saleNote = $('#sale_note').val() || '';

                let subtotal = 0;
                let cgstTotal = 0;
                let sgstTotal = 0;
                let igstTotal = 0;

                cart.forEach(item => {
                    let lineSub = item.price * item.qty;
                    subtotal += lineSub;
                    cgstTotal += lineSub * (item.cgst_rate / 100);
                    sgstTotal += lineSub * (item.sgst_rate / 100);
                    igstTotal += lineSub * (item.igst_rate / 100);
                });

                const discount = parseFloat($('#order_discount').val()) || 0;
                const discountType = $('#discount_type').val();
                const shipping = parseFloat($('#shipping').val()) || 0;
                const orderTax = parseFloat($('#order_tax').val()) || 0;
                
                let discountAmount = 0;
                if (discountType === 'percent') {
                    discountAmount = (subtotal * discount) / 100;
                } else {
                    discountAmount = discount;
                }
                
                const orderTaxAmount = ((subtotal - discountAmount) * orderTax) / 100;
                const finalSubtotal = subtotal - discountAmount;
                const totalTax = cgstTotal + sgstTotal + igstTotal + orderTaxAmount;
                const grandTotal = finalSubtotal + totalTax + shipping;

                const invoiceHTML = `
                    <div class="invoice-preview">
                        <div class="invoice-header mb-4 pb-3 border-bottom">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="mb-2">INVOICE</h3>
                                    <p class="mb-1"><strong>Reference No:</strong> ${referenceNo}</p>
                                    <p class="mb-1"><strong>Date:</strong> ${new Date(saleDate).toLocaleDateString()}</p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p class="mb-1"><strong>Biller:</strong> ${biller}</p>
                                    <p class="mb-1"><strong>Sale Status:</strong> ${saleStatus}</p>
                                    <p class="mb-1"><strong>Payment Status:</strong> ${paymentStatus}</p>
                                </div>
                            </div>
                        </div>

                        <div class="invoice-customer mb-4">
                            <h5>Bill To:</h5>
                            <p class="mb-0">${customerName}</p>
                        </div>

                        <div class="invoice-items mb-4">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Tax</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${cart.map((item, index) => {
                                        const lineSub = item.price * item.qty;
                                        const lineCgst = lineSub * (item.cgst_rate / 100);
                                        const lineSgst = lineSub * (item.sgst_rate / 100);
                                        const lineIgst = lineSub * (item.igst_rate / 100);
                                        const lineTax = lineCgst + lineSgst + lineIgst;
                                        const lineTotal = lineSub + lineTax;
                                        return `
                                            <tr>
                                                <td>${index + 1}</td>
                                                <td>${item.name}</td>
                                                <td class="text-end">${item.qty}</td>
                                                <td class="text-end">₹${item.price.toFixed(2)}</td>
                                                <td class="text-end">₹${lineTax.toFixed(2)}</td>
                                                <td class="text-end">₹${lineTotal.toFixed(2)}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>

                        <div class="invoice-summary">
                            <div class="row">
                                <div class="col-md-6">
                                    ${saleNote ? `<div class="mb-3"><strong>Notes:</strong><p>${saleNote}</p></div>` : ''}
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Subtotal:</strong></td>
                                            <td class="text-end">₹${subtotal.toFixed(2)}</td>
                                        </tr>
                                        ${discountAmount > 0 ? `
                                        <tr>
                                            <td><strong>Discount:</strong></td>
                                            <td class="text-end">-₹${discountAmount.toFixed(2)}</td>
                                        </tr>
                                        ` : ''}
                                        ${shipping > 0 ? `
                                        <tr>
                                            <td><strong>Shipping:</strong></td>
                                            <td class="text-end">₹${shipping.toFixed(2)}</td>
                                        </tr>
                                        ` : ''}
                                        ${cgstTotal > 0 ? `
                                        <tr>
                                            <td><strong>CGST:</strong></td>
                                            <td class="text-end">₹${cgstTotal.toFixed(2)}</td>
                                        </tr>
                                        ` : ''}
                                        ${sgstTotal > 0 ? `
                                        <tr>
                                            <td><strong>SGST:</strong></td>
                                            <td class="text-end">₹${sgstTotal.toFixed(2)}</td>
                                        </tr>
                                        ` : ''}
                                        ${igstTotal > 0 ? `
                                        <tr>
                                            <td><strong>IGST:</strong></td>
                                            <td class="text-end">₹${igstTotal.toFixed(2)}</td>
                                        </tr>
                                        ` : ''}
                                        ${orderTaxAmount > 0 ? `
                                        <tr>
                                            <td><strong>Order Tax:</strong></td>
                                            <td class="text-end">₹${orderTaxAmount.toFixed(2)}</td>
                                        </tr>
                                        ` : ''}
                                        <tr class="table-primary">
                                            <td><strong>Grand Total:</strong></td>
                                            <td class="text-end"><strong>₹${grandTotal.toFixed(2)}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#invoicePreviewContent').html(invoiceHTML);
            }

            // Number to words function
            function numberToWords(amount) {
                const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 
                              'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
                const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
                
                function convertHundreds(num) {
                    let result = '';
                    if (num >= 100) {
                        result += ones[Math.floor(num / 100)] + ' Hundred ';
                        num %= 100;
                    }
                    if (num >= 20) {
                        result += tens[Math.floor(num / 10)] + ' ';
                        num %= 10;
                    }
                    if (num > 0) {
                        result += ones[num] + ' ';
                    }
                    return result.trim();
                }
                
                if (amount === 0) return 'Zero Rupees Only';
                
                const rupees = Math.floor(amount);
                const paise = Math.round((amount - rupees) * 100);
                
                let words = '';
                if (rupees >= 10000000) {
                    words += convertHundreds(Math.floor(rupees / 10000000)) + ' Crore ';
                    rupees %= 10000000;
                }
                if (rupees >= 100000) {
                    words += convertHundreds(Math.floor(rupees / 100000)) + ' Lakh ';
                    rupees %= 100000;
                }
                if (rupees >= 1000) {
                    words += convertHundreds(Math.floor(rupees / 1000)) + ' Thousand ';
                    rupees %= 1000;
                }
                if (rupees > 0) {
                    words += convertHundreds(rupees) + ' Rupees ';
                }
                if (paise > 0) {
                    words += 'and ' + convertHundreds(paise) + ' Paise ';
                }
                return words.trim() + ' Only';
            }

            // Render Invoice Preview
            function renderInvoicePreview() {
                if (cart.length === 0) {
                    $('#invoicePreviewBody').html('<tr><td colspan="9" class="text-center text-muted py-3">No items added</td></tr>');
                    $('#previewSubtotal').text('₹0.00');
                    $('#previewCgstTotal').text('₹0.00');
                    $('#previewSgstTotal').text('₹0.00');
                    $('#previewIgstTotal').text('₹0.00');
                    $('#previewGrandTotal').text('₹0.00');
                    $('#previewGrandTotalDisplay').text('₹0.00');
                    $('#previewAmountWords').text('Zero Rupees Only');
                    return;
                }

                let subtotal = 0;
                let cgstTotal = 0;
                let sgstTotal = 0;
                let igstTotal = 0;

                let tableRows = '';
                cart.forEach((item, index) => {
                    const lineSub = item.price * item.qty;
                    subtotal += lineSub;
                    
                    const lineCgst = lineSub * (item.cgst_rate / 100);
                    const lineSgst = lineSub * (item.sgst_rate / 100);
                    const lineIgst = lineSub * (item.igst_rate / 100);
                    
                    cgstTotal += lineCgst;
                    sgstTotal += lineSgst;
                    igstTotal += lineIgst;
                    
                    const lineTotal = lineSub + lineCgst + lineSgst + lineIgst;
                    
                    const cgstDisplay = item.cgst_rate > 0 ? `₹${lineCgst.toFixed(2)}<br><small class="text-muted">(${item.cgst_rate}%)</small>` : '-';
                    const sgstDisplay = item.sgst_rate > 0 ? `₹${lineSgst.toFixed(2)}<br><small class="text-muted">(${item.sgst_rate}%)</small>` : '-';
                    const igstDisplay = item.igst_rate > 0 ? `₹${lineIgst.toFixed(2)}<br><small class="text-muted">(${item.igst_rate}%)</small>` : '-';
                    
                    tableRows += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.name}${item.code ? '<br><small class="text-muted">HSN: ' + item.code + '</small>' : ''}</td>
                            <td class="text-end">${item.qty}</td>
                            <td class="text-end">₹${item.price.toFixed(2)}</td>
                            <td class="text-end">₹${lineSub.toFixed(2)}</td>
                            <td class="text-end">${cgstDisplay}</td>
                            <td class="text-end">${sgstDisplay}</td>
                            <td class="text-end">${igstDisplay}</td>
                            <td class="text-end fw-bold">₹${lineTotal.toFixed(2)}</td>
                        </tr>
                    `;
                });

                const shipping = parseFloat($('#shipping').val()) || 0;
                const orderTax = parseFloat($('#order_tax').val()) || 0;
                const orderTaxAmount = (subtotal * orderTax) / 100;
                const grandTotal = subtotal + cgstTotal + sgstTotal + igstTotal + orderTaxAmount + shipping;

                $('#invoicePreviewBody').html(tableRows);
                $('#previewSubtotal').text('₹' + subtotal.toFixed(2));
                $('#previewCgstTotal').text('₹' + cgstTotal.toFixed(2));
                $('#previewSgstTotal').text('₹' + sgstTotal.toFixed(2));
                $('#previewIgstTotal').text('₹' + igstTotal.toFixed(2));
                $('#previewGrandTotal').text('₹' + grandTotal.toFixed(2));
                $('#previewGrandTotalDisplay').text('₹' + grandTotal.toFixed(2));
                $('#previewAmountWords').text(numberToWords(grandTotal));
            }

            // Update totals when discount, shipping, or tax changes
            $('#order_discount, #discount_type, #shipping, #order_tax').on('change', function() {
                if (cart.length > 0) {
                    calculateTotals();
                    renderInvoicePreview();
                }
            });
            
            // Connect save button in preview
            $('#saveInvoiceBtnPreview').on('click', function() {
                saveInvoice();
            });

            // Also check form completion when cart is updated via quantity changes
            $(document).on('change', '.product-qty', function() {
                setTimeout(checkFormCompletion, 100);
            });

            // Close dropdowns when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.product-search-input, .product-dropdown').length) {
                    $('.product-dropdown').hide();
                }
            });

            // Save invoice button handler (from preview modal)
            $(document).on('click', '#invoicePreviewModal #saveInvoiceBtn', function() {
                $('#invoicePreviewModal').modal('hide');
                saveInvoice();
            });

            // Save invoice function
            function saveInvoice() {
                // Validate required fields
                if (!$('#sale_date').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Required',
                        text: 'Please select a date.'
                    });
                    return;
                }

                if (!$('#customer_id').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Customer Required',
                        text: 'Please select a customer first.'
                    });
                    return;
                }

                if (!$('#reference_no').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Reference Number Required',
                        text: 'Please enter a reference number.'
                    });
                    return;
                }

                if (cart.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Empty Cart',
                        text: 'Please add at least one product to the cart.'
                    });
                    return;
                }

                // Disable button during request
                const saveBtn = $('#invoicePreviewModal #saveInvoiceBtn');
                const originalText = saveBtn.html();
                saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                // Calculate discount
                const discount = parseFloat($('#order_discount').val()) || 0;
                const discountType = $('#discount_type').val();
                const shipping = parseFloat($('#shipping').val()) || 0;
                const orderTax = parseFloat($('#order_tax').val()) || 0;

                // Prepare data for saving
                const invoiceData = {
                    sale_date: $('#sale_date').val(),
                    customer_id: parseInt($('#customer_id').val()),
                    reference_no: $('#reference_no').val(),
                    biller: $('#biller').val(),
                    order_tax: orderTax,
                    shipping: shipping,
                    order_discount: discount,
                    discount_type: discountType,
                    sale_status: $('#sale_status').val(),
                    payment_status: $('#payment_status').val(),
                    sale_note: $('#sale_note').val(),
                    company_name: $('#company_name').val(),
                    company_gstin: $('#company_gstin').val(),
                    company_address: $('#company_address').val(),
                    company_phone: $('#company_phone').val(),
                    company_email: $('#company_email').val(),
                    company_state_code: $('#company_state_code').val(),
                    cart: cart
                };

                // Send AJAX request
                fetch('save_invoice.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(invoiceData)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Invoice Saved!',
                            text: data.message + ' Invoice No: ' + data.invoice_no,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Clear cart and reset form
                            cart = [];
                            renderProductsTable();
                            $('#customer_search').val('');
                            $('#customer_id').val('');
                            $('#productSearch').val('');
                            $('#reference_no').val('REF-' + Date.now());
                            checkFormCompletion();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to save invoice. Please try again.'
                        });
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to save invoice. Please check your connection and try again.'
                    });
                })
                .finally(() => {
                    // Re-enable button
                    saveBtn.prop('disabled', false).html(originalText);
                });
            });
        });
    </script>
</body>
</html>
