<?php
session_start();

// Check if user is logged in
require_once 'includes/functions.php';
redirect_if_not_logged();

// Include database connection
require_once 'includes/db.php';

// Fetch all products
$products = [];
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet
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
    <title>Products - My Business</title>
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

        <div class="container-fluid px-0">
            <div class="page-header fade-in">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2>Products / Inventory</h2>
                        <p>Manage your product catalog and stock levels</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </button>
                </div>
            </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="productsTable" class="table table-striped table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Code/SKU</th>
                                        <th>HSN</th>
                                        <th>Purchase Price</th>
                                        <th>Sale Price</th>
                                        <th>Stock</th>
                                        <th>Low Alert</th>
                                        <th>CGST%</th>
                                        <th>SGST%</th>
                                        <th>IGST%</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="12" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                No products found. Click "Add New Product" to get started.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                            <tr <?php if (isset($product['stock']) && isset($product['low_stock_alert']) && $product['stock'] <= $product['low_stock_alert']) echo 'class="table-danger"'; ?>>
                                                <td><?php echo htmlspecialchars($product['id'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($product['name'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($product['code'] ?? $product['sku'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($product['hsn'] ?? ''); ?></td>
                                                <td>₹<?php echo number_format($product['purchase_price'] ?? 0, 2); ?></td>
                                                <td>₹<?php echo number_format($product['sale_price'] ?? 0, 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo (isset($product['stock']) && isset($product['low_stock_alert']) && $product['stock'] <= $product['low_stock_alert']) ? 'bg-warning' : 'bg-success'; ?>">
                                                        <?php echo number_format($product['stock'] ?? 0); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($product['low_stock_alert'] ?? 0); ?></td>
                                                <td><?php echo number_format($product['cgst_rate'] ?? 0, 2); ?>%</td>
                                                <td><?php echo number_format($product['sgst_rate'] ?? 0, 2); ?>%</td>
                                                <td><?php echo number_format($product['igst_rate'] ?? 0, 2); ?>%</td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning me-1 edit-btn" 
                                                            data-id="<?php echo $product['id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                                            data-code="<?php echo htmlspecialchars($product['code'] ?? $product['sku'] ?? ''); ?>" 
                                                            data-hsn="<?php echo htmlspecialchars($product['hsn'] ?? ''); ?>" 
                                                            data-purchase_price="<?php echo $product['purchase_price']; ?>" 
                                                            data-sale_price="<?php echo $product['sale_price']; ?>" 
                                                            data-stock="<?php echo $product['stock']; ?>" 
                                                            data-low_stock_alert="<?php echo $product['low_stock_alert']; ?>" 
                                                            data-cgst_rate="<?php echo $product['cgst_rate']; ?>" 
                                                            data-sgst_rate="<?php echo $product['sgst_rate']; ?>" 
                                                            data-igst_rate="<?php echo $product['igst_rate']; ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $product['id']; ?>">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel"><i class="bi bi-box-seam me-2"></i>Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="product_id" value="0">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="code" class="form-label">Code/SKU</label>
                            <input type="text" class="form-control" id="code">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="hsn" class="form-label">HSN Code</label>
                            <input type="text" class="form-control" id="hsn">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="purchase_price" class="form-label">Purchase Price</label>
                            <input type="number" step="0.01" class="form-control" id="purchase_price">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="sale_price" class="form-label">Sale Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="sale_price" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="stock" class="form-label">Current Stock</label>
                            <input type="number" class="form-control" id="stock" value="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="low_stock_alert" class="form-label">Low Stock Alert</label>
                            <input type="number" class="form-control" id="low_stock_alert" value="10">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="cgst_rate" class="form-label">CGST Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" id="cgst_rate" value="9.00">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="sgst_rate" class="form-label">SGST Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" id="sgst_rate" value="9.00">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="igst_rate" class="form-label">IGST Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" id="igst_rate" value="18.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveProductBtn">Save Product</button>
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
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
    
    <script>
        $(document).ready(function() {
            // Check if table has actual data rows (not the empty state colspan row)
            const $table = $('#productsTable');
            const $tbody = $table.find('tbody');
            const $rows = $tbody.find('tr');
            const hasDataRows = $rows.length > 0 && !$rows.first().find('td[colspan]').length;
            
            if (hasDataRows) {
                // Verify column count matches before initializing
                const headerCols = $table.find('thead tr th').length;
                let allRowsValid = true;
                
                $rows.each(function() {
                    const cellCount = $(this).find('td').length;
                    if (cellCount !== headerCols) {
                        allRowsValid = false;
                        console.error('Row has ' + cellCount + ' cells, expected ' + headerCols);
                    }
                });
                
                if (allRowsValid) {
                    try {
                        // Initialize DataTables with responsive disabled to avoid cell index issues
                        $table.DataTable({
                            responsive: false, // Disable responsive to avoid _DT_CellIndex errors
                            order: [[0, 'desc']], // Sort by ID descending
                            pageLength: 25,
                            scrollX: true, // Enable horizontal scroll instead
                            language: {
                                search: "Search products:",
                                lengthMenu: "Show _MENU_ products per page",
                                info: "Showing _START_ to _END_ of _TOTAL_ products",
                                infoEmpty: "No products available",
                                infoFiltered: "(filtered from _MAX_ total products)"
                            }
                        });
                    } catch (e) {
                        console.error('DataTables initialization error:', e);
                    }
                } else {
                    console.error('Table structure invalid. Skipping DataTables initialization.');
                }
            }

            // Save Product Button Handler
            $('#saveProductBtn').on('click', async function(e) {
                e.preventDefault();
                
                // Collect form data
                const formData = {
                    product_id: $('#product_id').val() || 0,
                    name: $('#name').val().trim(),
                    code: $('#code').val().trim(),
                    hsn: $('#hsn').val().trim(),
                    purchase_price: $('#purchase_price').val() || 0,
                    sale_price: $('#sale_price').val() || 0,
                    stock: $('#stock').val() || 0,
                    low_stock_alert: $('#low_stock_alert').val() || 10,
                    cgst_rate: $('#cgst_rate').val() || 0,
                    sgst_rate: $('#sgst_rate').val() || 0,
                    igst_rate: $('#igst_rate').val() || 0
                };
                
                // Basic validation
                if (!formData.name || !formData.sale_price) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required Fields',
                        text: 'Please fill in Name and Sale Price.'
                    });
                    return;
                }
                
                // Disable button during request
                const saveBtn = $(this);
                const originalText = saveBtn.html();
                saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
                
                try {
                    // Send AJAX request
                    const response = await fetch('save_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: result.message || 'Product saved successfully!'
                        }).then(() => {
                            // Close modal
                            const modalElement = document.getElementById('productModal');
                            if (modalElement) {
                                const modal = bootstrap.Modal.getInstance(modalElement);
                                if (modal) modal.hide();
                            }
                            
                            // Destroy DataTable if it exists before reload
                            if ($.fn.DataTable.isDataTable('#productsTable')) {
                                $('#productsTable').DataTable().destroy();
                            }
                            
                            // Reload page to refresh table
                            window.location.reload();
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Failed to save product. Please try again.'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to save product. Please check your connection and try again.'
                    });
                } finally {
                    // Re-enable button
                    saveBtn.prop('disabled', false).html(originalText);
                }
            });

            // Edit Product Button Handler
            $('.edit-btn').on('click', function() {
                let btn = $(this);
                $('#product_id').val(btn.data('id'));
                $('#name').val(btn.data('name'));
                $('#code').val(btn.data('code'));
                $('#hsn').val(btn.data('hsn'));
                $('#purchase_price').val(btn.data('purchase_price'));
                $('#sale_price').val(btn.data('sale_price'));
                $('#stock').val(btn.data('stock'));
                $('#low_stock_alert').val(btn.data('low_stock_alert'));
                $('#cgst_rate').val(btn.data('cgst_rate'));
                $('#sgst_rate').val(btn.data('sgst_rate'));
                $('#igst_rate').val(btn.data('igst_rate'));

                $('.modal-title').text('Edit Product: ' + btn.data('name'));
                $('#productModal').modal('show');
            });

            // Reset form when modal is hidden
            $('#productModal').on('hidden.bs.modal', function () {
                $('#product_id').val('0');
                // Clear all inputs except hidden
                $('input:not([type=hidden])').val('');
                // Reset default values
                $('#stock').val('0');
                $('#low_stock_alert').val('10');
                $('#cgst_rate').val('9.00');
                $('#sgst_rate').val('9.00');
                $('#igst_rate').val('18.00');
                $('.modal-title').text('Add New Product');
            });

            // Delete Product Button Handler
            $('.delete-btn').on('click', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Product?',
                    text: "Are you sure you want to delete this product? Stock data will be lost.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('delete_product.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({product_id: id})
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: data.message
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message
                                });
                            }
                        })
                        .catch(err => {
                            console.error('Error:', err);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to delete product. Please try again.'
                            });
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
