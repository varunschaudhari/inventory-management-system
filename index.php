<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once 'includes/db.php';

// Fetch dashboard statistics
try {
    // 1. Total Products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $total_products = $stmt->fetch()['count'];
    
    // 2. Low Stock Items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock <= low_stock_alert");
    $low_stock_items = $stmt->fetch()['count'];
    
    // 3. Today's Sales
    $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE sale_date = CURDATE()");
    $today_sales = $stmt->fetch()['total'];
    
    // 4. Total Sales (All Time)
    $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM sales");
    $total_sales = $stmt->fetch()['total'];
    
    // 5. Total Cost (Purchase Price of sold items)
    $stmt = $pdo->query("SELECT COALESCE(SUM(si.quantity * p.purchase_price), 0) as total 
                         FROM sale_items si 
                         JOIN products p ON si.product_id = p.id");
    $total_cost = $stmt->fetch()['total'];
    
    // 6. Products Sold (Total quantity from sale_items)
    $stmt = $pdo->query("SELECT COALESCE(SUM(quantity), 0) as total FROM sale_items");
    $products_sold = $stmt->fetch()['total'];
    
    // 7. Pending Invoices
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sales WHERE status = 'pending'");
    $pending_invoices = $stmt->fetch()['count'];
    
    // Fetch sales data for charts (last 7 days)
    $chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE sale_date = ?");
        $stmt->execute([$date]);
        $chart_data[] = [
            'date' => date('M d', strtotime($date)),
            'sales' => (float)$stmt->fetch()['total']
        ];
    }
    
    // Fetch monthly revenue vs cost (last 6 months)
    $monthly_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        $month_name = date('M', strtotime($month_start));
        
        // Revenue
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE sale_date BETWEEN ? AND ?");
        $stmt->execute([$month_start, $month_end]);
        $revenue = (float)$stmt->fetch()['total'];
        
        // Cost (simplified - using purchase price of sold items)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(si.quantity * p.purchase_price), 0) as total 
                               FROM sale_items si 
                               JOIN sales s ON si.sale_id = s.id 
                               JOIN products p ON si.product_id = p.id 
                               WHERE s.sale_date BETWEEN ? AND ?");
        $stmt->execute([$month_start, $month_end]);
        $cost = (float)$stmt->fetch()['total'];
        
        $monthly_data[] = [
            'month' => $month_name,
            'revenue' => $revenue,
            'cost' => $cost
        ];
    }
    
    // Fetch Top Products (by quantity sold)
    $stmt = $pdo->query("SELECT p.id, p.name, p.code, SUM(si.quantity) as total_sold, SUM(si.quantity * si.price) as total_earned
                         FROM sale_items si
                         JOIN products p ON si.product_id = p.id
                         GROUP BY p.id, p.name, p.code
                         ORDER BY total_sold DESC
                         LIMIT 3");
    $top_products = $stmt->fetchAll();
    
    // Fetch Best Items All Time
    $stmt = $pdo->query("SELECT p.id, p.name, p.code, SUM(si.quantity) as total_sell, SUM(si.quantity * si.price) as total_earned
                         FROM sale_items si
                         JOIN products p ON si.product_id = p.id
                         GROUP BY p.id, p.name, p.code
                         ORDER BY total_earned DESC
                         LIMIT 5");
    $best_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // Set defaults if tables don't exist yet
    $total_products = 0;
    $low_stock_items = 0;
    $today_sales = 0;
    $total_sales = 0;
    $total_cost = 0;
    $products_sold = 0;
    $pending_invoices = 0;
    $chart_data = [];
    $monthly_data = [];
    $top_products = [];
    $best_items = [];
}

// Get username from session
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - My Business</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            <div class="page-header-posdash fade-in">
                <h1 class="greeting-title">Hi <?php echo htmlspecialchars($username); ?>, Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening'); ?></h1>
                <p class="greeting-subtitle">Your dashboard gives you views of key performance or business process.</p>
            </div>

        <!-- Statistics Cards with Progress Bars -->
        <div class="row g-4 fade-in mb-4">
            <!-- Card 1: Total Sales -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="kpi-card kpi-sales">
                    <div class="kpi-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="kpi-content">
                        <p class="kpi-label">Total Sales</p>
                        <h2 class="kpi-value"><?php echo number_format($total_sales, 2); ?></h2>
                        <div class="kpi-progress">
                            <div class="kpi-progress-bar kpi-progress-blue" style="width: <?php echo min(($total_sales / max($total_sales + 10000, 1)) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Cost -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="kpi-card kpi-cost">
                    <div class="kpi-icon">
                        <i class="bi bi-stack"></i>
                    </div>
                    <div class="kpi-content">
                        <p class="kpi-label">Total Cost</p>
                        <h2 class="kpi-value">₹<?php echo number_format($total_cost, 2); ?></h2>
                        <div class="kpi-progress">
                            <div class="kpi-progress-bar kpi-progress-pink" style="width: <?php echo min(($total_cost / max($total_cost + 10000, 1)) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Products Sold -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="kpi-card kpi-products">
                    <div class="kpi-icon">
                        <i class="bi bi-globe"></i>
                    </div>
                    <div class="kpi-content">
                        <p class="kpi-label">Product Sold</p>
                        <h2 class="kpi-value"><?php echo number_format($products_sold); ?> M</h2>
                        <div class="kpi-progress">
                            <div class="kpi-progress-bar kpi-progress-green" style="width: <?php echo min(($products_sold / max($products_sold + 1000, 1)) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 fade-in mb-4">
            <!-- Overview Chart -->
            <div class="col-12 col-lg-6">
                <div class="chart-card-posdash">
                    <div class="chart-header-posdash">
                        <h5 class="chart-title-posdash">Overview</h5>
                        <div class="chart-header-right">
                            <div class="chart-value-display">₹<?php echo number_format($total_sales, 2); ?></div>
                            <select class="chart-select-posdash" id="overviewPeriod">
                                <option value="week">This Week</option>
                                <option value="month" selected>This Month</option>
                                <option value="year">This Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-body-posdash">
                        <canvas id="overviewChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Revenue Vs Cost Chart -->
            <div class="col-12 col-lg-6">
                <div class="chart-card-posdash">
                    <div class="chart-header-posdash">
                        <h5 class="chart-title-posdash">Revenue Vs Cost</h5>
                        <select class="chart-select-posdash" id="revenuePeriod">
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="chart-body-posdash">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products and Best Items Section -->
        <div class="row g-4 fade-in">
            <!-- Top Products -->
            <div class="col-12 col-lg-6">
                <div class="chart-card-posdash">
                    <div class="chart-header-posdash">
                        <h5 class="chart-title-posdash">Top Products</h5>
                        <select class="chart-select-posdash">
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="top-products-grid">
                        <?php if (empty($top_products)): ?>
                            <div class="text-center text-muted py-5">
                                <p>No products sold yet.</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            $product_bg_colors = ['#E0F2FE', '#D1FAE5', '#FEF3C7'];
                            foreach ($top_products as $index => $product): 
                                $bg_color = $product_bg_colors[$index % count($product_bg_colors)];
                            ?>
                                <div class="top-product-card" style="background: <?php echo $bg_color; ?>;">
                                    <div class="product-illustration">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="product-info">
                                        <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="text-muted mb-0">Sold: <?php echo number_format($product['total_sold']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Best Item All Time -->
            <div class="col-12 col-lg-6">
                <div class="chart-card-posdash">
                    <div class="chart-header-posdash">
                        <h5 class="chart-title-posdash">Best Item All Time</h5>
                        <button class="btn-view-all">View All</button>
                    </div>
                    <div class="best-items-list">
                        <?php if (empty($best_items)): ?>
                            <div class="text-center text-muted py-5">
                                <p>No items sold yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($best_items as $item): ?>
                                <div class="best-item-row">
                                    <div class="best-item-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="best-item-details">
                                        <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="text-muted mb-1">Total Sell: <?php echo number_format($item['total_sell']); ?></p>
                                        <p class="text-success mb-0"><strong>Total Earned: ₹<?php echo number_format($item['total_earned'], 2); ?></strong></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery 3.7 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
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

        // Chart Data from PHP
        const chartData = <?php echo json_encode($chart_data); ?>;
        const monthlyData = <?php echo json_encode($monthly_data); ?>;

        // Overview Chart - Candlestick style
        const overviewCtx = document.getElementById('overviewChart').getContext('2d');
        const overviewChart = new Chart(overviewCtx, {
            type: 'bar',
            data: {
                labels: chartData.map(d => d.date),
                datasets: [{
                    label: 'Sales',
                    data: chartData.map(d => d.sales),
                    backgroundColor: (ctx) => {
                        const value = ctx.parsed.y;
                        const max = Math.max(...chartData.map(d => d.sales), 1);
                        return value >= max * 0.7 ? 'rgba(251, 146, 60, 0.8)' : 'rgba(59, 130, 246, 0.8)';
                    },
                    borderColor: (ctx) => {
                        const value = ctx.parsed.y;
                        const max = Math.max(...chartData.map(d => d.sales), 1);
                        return value >= max * 0.7 ? 'rgba(251, 146, 60, 1)' : 'rgba(59, 130, 246, 1)';
                    },
                    borderWidth: 1,
                    borderRadius: {
                        topLeft: 4,
                        topRight: 4,
                        bottomLeft: 0,
                        bottomRight: 0
                    },
                    barThickness: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 13 },
                        bodyFont: { size: 12 },
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        ticks: {
                            font: { size: 11 },
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 },
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });

        // Revenue Vs Cost Chart - Combination Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        
        // Calculate max values for scaling
        const maxRevenue = Math.max(...monthlyData.map(d => d.revenue), 1);
        const maxCost = Math.max(...monthlyData.map(d => d.cost), 1);
        const costPercentage = monthlyData.map(d => (d.cost / maxCost) * 100);
        
        const revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [
                    {
                        label: 'Revenue',
                        type: 'bar',
                        data: monthlyData.map(d => d.revenue),
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Cost',
                        type: 'line',
                        data: costPercentage,
                        backgroundColor: 'rgba(148, 163, 184, 0.2)',
                        borderColor: 'rgba(148, 163, 184, 1)',
                        borderWidth: 2,
                        fill: false,
                        pointRadius: 5,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 12 },
                            color: '#64748b',
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 13 },
                        bodyFont: { size: 12 }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        ticks: {
                            font: { size: 11 },
                            color: '#94a3b8',
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 },
                            color: '#94a3b8',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 },
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
