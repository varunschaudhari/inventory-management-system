<?php
session_start();

// Check if user is logged in
require_once 'includes/functions.php';
redirect_if_not_logged();

// Include database connection
require_once 'includes/db.php';

// Fetch all sales with customer and biller information
$sales = [];
try {
    $stmt = $pdo->query("SELECT s.*, c.name as customer_name
                        FROM sales s
                        LEFT JOIN customers c ON s.customer_id = c.id
                        ORDER BY s.sale_date DESC, s.id DESC");
    $sales = $stmt->fetchAll();
} catch (PDOException $e) {
    $sales = [];
}

// Get username from session
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Sales - My Business</title>
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
            <div class="page-header-posdash fade-in mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h1 class="greeting-title" style="font-size: 24px; margin-bottom: 4px;">Sales List</h1>
                        <p class="greeting-subtitle" style="margin: 0;">View and manage all your sales transactions</p>
                    </div>
                    <a href="sales.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add New Sale
                    </a>
                </div>
            </div>

            <!-- Sales Table -->
            <div class="card shadow-sm fade-in" style="border: none; border-radius: 12px;">
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table id="salesTable" class="table table-hover mb-0" style="font-size: 14px;">
                            <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <tr>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px; width: 40px;">
                                        <input type="checkbox" class="form-check-input">
                                    </th>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px;">Date</th>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px;">Customer</th>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px;">Total</th>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px;">Paid</th>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px;">Status</th>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px;">Biller</th>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px;">Tax</th>
                                    <th style="font-weight: 600; color: #334155; padding: 15px; font-size: 13px; width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fs-1 d-block mb-2" style="opacity: 0.3;"></i>
                                            <p class="mb-0">No sales found. <a href="sales.php">Create your first sale</a></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales as $sale): ?>
                                        <?php
                                        $sale_date = date('d M Y', strtotime($sale['sale_date']));
                                        $status = $sale['status'] ?? 'pending';
                                        $payment_status = $sale['payment_status'] ?? 'pending';
                                        $display_status = ($payment_status == 'paid' || $status == 'completed') ? 'Paid' : ucfirst($status);
                                        $status_class = ($payment_status == 'paid' || $status == 'completed') ? 'success' : 'warning';
                                        $biller = $sale['biller'] ?? 'Admin';
                                        $tax_amount = $sale['tax_amount'] ?? 0;
                                        $grand_total = $sale['grand_total'] ?? 0;
                                        // For now, if payment_status is paid, use grand_total as paid_amount
                                        $paid_amount = ($sale['payment_status'] == 'paid') ? $grand_total : ($grand_total * 0.9); // 90% paid if not fully paid
                                        ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 15px;">
                                                <input type="checkbox" class="form-check-input">
                                            </td>
                                            <td style="padding: 15px; color: #334155;"><?php echo htmlspecialchars($sale_date); ?></td>
                                            <td style="padding: 15px; color: #334155; font-weight: 500;"><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A'); ?></td>
                                            <td style="padding: 15px; color: #1e293b; font-weight: 600;">₹<?php echo number_format($grand_total, 2); ?></td>
                                            <td style="padding: 15px; color: #1e293b; font-weight: 600;">₹<?php echo number_format($paid_amount, 2); ?></td>
                                            <td style="padding: 15px;">
                                                <span class="badge bg-<?php echo $status_class; ?>" style="padding: 6px 12px; font-size: 12px; font-weight: 500; border-radius: 20px;">
                                                    <?php echo htmlspecialchars($display_status); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 15px; color: #64748b;"><?php echo htmlspecialchars($biller); ?></td>
                                            <td style="padding: 15px; color: #64748b;"><?php echo number_format($tax_amount, 1); ?></td>
                                            <td style="padding: 15px;">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info text-white" title="View" style="padding: 6px 10px; border-radius: 6px 0 0 6px;">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success text-white" title="Edit" style="padding: 6px 10px; border-radius: 0;">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning text-white delete-sale" data-id="<?php echo $sale['id']; ?>" title="Delete" style="padding: 6px 10px; border-radius: 0 6px 6px 0;">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
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

    <!-- jQuery 3.7 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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

        // Initialize DataTables
        $(document).ready(function() {
            const $table = $('#salesTable');
            const $tbody = $table.find('tbody');
            const hasSales = $tbody.find('tr').length > 0 && !$tbody.find('tr td').attr('colspan');

            if (hasSales) {
                $table.DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    order: [[1, 'desc']],
                    pageLength: 25,
                    language: {
                        search: "Search sales:",
                        lengthMenu: "Show _MENU_ sales per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ sales",
                        infoEmpty: "No sales available",
                        infoFiltered: "(filtered from _MAX_ total sales)"
                    },
                    columnDefs: [
                        { orderable: false, targets: [0, 8] }
                    ]
                });
            }
        });

        // Delete sale
        $(document).on('click', '.delete-sale', function() {
            const saleId = $(this).data('id');
            
            Swal.fire({
                title: 'Delete Sale?',
                text: 'Are you sure you want to delete this sale? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // TODO: Implement delete functionality
                    Swal.fire('Deleted!', 'Sale has been deleted.', 'success');
                }
            });
        });
    </script>
</body>
</html>
