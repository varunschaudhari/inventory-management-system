<?php
session_start();

// Check if user is logged in
require_once 'includes/functions.php';
redirect_if_not_logged();

// Include database connection
require_once 'includes/db.php';

// Fetch all customers
$customers = [];
try {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet
    $customers = [];
}

// Get username from session
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - My Business</title>
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
                        <h2>Customers</h2>
                        <p>Manage your customer database</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New Customer
                    </button>
                </div>
            </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customersTable" class="table table-striped table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Address</th>
                                        <th>State Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                No customers found. Click "Add New Customer" to get started.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($customer['id'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($customer['name'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($customer['email'] ?? ''); ?></td>
                                                <td>
                                                    <?php 
                                                    $address = $customer['address'] ?? '';
                                                    if (strlen($address) > 50) {
                                                        echo htmlspecialchars(substr($address, 0, 50)) . '...';
                                                    } else {
                                                        echo htmlspecialchars($address);
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($customer['state_code'] ?? ''); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning me-1 edit-btn" 
                                                            data-id="<?php echo $customer['id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($customer['name']); ?>" 
                                                            data-phone="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" 
                                                            data-email="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" 
                                                            data-address="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" 
                                                            data-state_code="<?php echo $customer['state_code'] ?? '29'; ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $customer['id']; ?>">
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

    <!-- Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalLabel"><i class="bi bi-people me-2"></i>Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="customer_id" value="0">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="state_code" class="form-label">State Code (GST)</label>
                            <input type="text" class="form-control" id="state_code" value="29" maxlength="2" placeholder="29 for Telangana">
                        </div>
                        
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="saveCustomerBtn" class="btn btn-primary">Save Customer</button>
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
            const $table = $('#customersTable');
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
                                search: "Search customers:",
                                lengthMenu: "Show _MENU_ customers per page",
                                info: "Showing _START_ to _END_ of _TOTAL_ customers",
                                infoEmpty: "No customers available",
                                infoFiltered: "(filtered from _MAX_ total customers)"
                            }
                        });
                    } catch (e) {
                        console.error('DataTables initialization error:', e);
                    }
                } else {
                    console.error('Table structure invalid. Skipping DataTables initialization.');
                }
            }

            // Reset form when modal is hidden
            $('#customerModal').on('hidden.bs.modal', function () {
                $('#customer_id').val('0');
                // Clear all inputs except hidden
                $('input:not([type=hidden]), textarea').val('');
                // Reset default values
                $('#state_code').val('29');
                // Reset modal title
                $('#customerModalLabel').text('Add New Customer');
            });

            // Save Customer Button Handler
            $('#saveCustomerBtn').on('click', function() {
                // Collect form data
                const formData = {
                    customer_id: $('#customer_id').val() || 0,
                    name: $('#name').val().trim(),
                    phone: $('#phone').val().trim(),
                    email: $('#email').val().trim(),
                    address: $('#address').val().trim(),
                    state_code: $('#state_code').val().trim()
                };
                
                // Validation
                if (!formData.name) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required',
                        text: 'Name is required'
                    });
                    return;
                }
                
                // Disable button during request
                const saveBtn = $(this);
                const originalText = saveBtn.html();
                saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
                
                // Send AJAX request
                fetch('save_customer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: data.message
                        }).then(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
                            if (modal) modal.hide();
                            location.reload(); // or better: reload table data later
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
                        text: 'Failed to save customer. Please check your connection and try again.'
                    });
                })
                .finally(() => {
                    // Re-enable button
                    saveBtn.prop('disabled', false).html(originalText);
                });
            });

            // Edit Customer Button Handler
            $('.edit-btn').click(function() {
                let btn = $(this);
                $('#customer_id').val(btn.data('id'));
                $('#name').val(btn.data('name'));
                $('#phone').val(btn.data('phone'));
                $('#email').val(btn.data('email'));
                $('#address').val(btn.data('address'));
                $('#state_code').val(btn.data('state_code'));
                $('#customerModalLabel').text('Edit Customer: ' + btn.data('name'));
                const modal = new bootstrap.Modal(document.getElementById('customerModal'));
                modal.show();
            });

            // Delete Customer Button Handler
            $('.delete-btn').click(function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Delete?',
                    text: "This can't be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('delete_customer.php', {
                            method: 'POST',
                            body: JSON.stringify({customer_id: id}),
                            headers: {'Content-Type': 'application/json'}
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Deleted!', data.message, 'success');
                                location.reload();
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Error:', err);
                            Swal.fire('Error', 'Failed to delete customer. Please try again.', 'error');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
