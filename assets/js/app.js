// API Base URL
const API_BASE = 'api';

// Global State
let currentInvoiceId = null;
let invoiceItems = [];
let products = [];
let customers = [];

// Initialize App
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    // Test html2pdf.js loading
    setTimeout(() => {
        if (typeof html2pdf !== 'undefined') {
            console.log('✓ html2pdf.js loaded successfully');
        } else {
            console.error('✗ html2pdf.js NOT loaded! Check script tag in HTML.');
        }
    }, 1000);
});

function initializeApp() {
    setupNavigation();
    setupEventListeners();
    loadDashboard();
    setTodayDate();
}

// Navigation
function setupNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            navigateToPage(page);
        });
    });

    // Menu toggle for mobile
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
            document.body.classList.toggle('sidebar-open', sidebar.classList.contains('active'));
        });
    }

    // Close sidebar when clicking outside (mobile)
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const menuToggleEl = document.getElementById('menuToggle');
        if (!sidebar || !sidebar.classList.contains('active')) return;

        const target = e.target;
        const clickedInsideSidebar = sidebar.contains(target);
        const clickedMenuToggle = menuToggleEl && menuToggleEl.contains(target);

        if (!clickedInsideSidebar && !clickedMenuToggle) {
            sidebar.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    }, true);
}

function navigateToPage(page) {
    // Hide all pages
    document.querySelectorAll('.page').forEach(p => p.style.display = 'none');
    
    // Show selected page
    const targetPage = document.getElementById(page + '-page');
    if (targetPage) {
        targetPage.style.display = 'block';
    }

    // Update active nav item
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('data-page') === page) {
            item.classList.add('active');
        }
    });

    // Update page title
    const titles = {
        'dashboard': 'Dashboard',
        'products': 'Products',
        'customers': 'Customers',
        'invoices': 'Invoices',
        'new-invoice': 'New Invoice',
        'settings': 'Settings'
    };
    document.getElementById('pageTitle').textContent = titles[page] || 'Dashboard';

    // Load page data
    switch(page) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'products':
            loadProducts();
            break;
        case 'customers':
            loadCustomers();
            break;
        case 'invoices':
            loadInvoices();
            break;
        case 'new-invoice':
            loadNewInvoice();
            break;
        case 'settings':
            loadSettings();
            break;
    }

    // Close sidebar on mobile
    document.getElementById('sidebar').classList.remove('active');
    document.body.classList.remove('sidebar-open');
}

// Event Listeners
function setupEventListeners() {
    // Product form
    document.getElementById('product-form').addEventListener('submit', handleProductSubmit);
    
    // Customer form
    document.getElementById('customer-form').addEventListener('submit', handleCustomerSubmit);
    
    // Invoice form
    document.getElementById('invoice-form').addEventListener('submit', handleInvoiceSubmit);
    
    // Settings form
    document.getElementById('settings-form').addEventListener('submit', handleSettingsSubmit);
    
    // Search filters
    document.getElementById('product-search')?.addEventListener('input', debounce(loadProducts, 300));
    document.getElementById('customer-search')?.addEventListener('input', debounce(loadCustomers, 300));
    document.getElementById('invoice-search')?.addEventListener('input', debounce(loadInvoices, 300));
    
    document.getElementById('category-filter')?.addEventListener('change', loadProducts);
    document.getElementById('stock-status-filter')?.addEventListener('change', loadProducts);
    document.getElementById('product-status-filter')?.addEventListener('change', loadProducts);
    document.getElementById('invoice-status-filter')?.addEventListener('change', loadInvoices);
    
    // View All links on dashboard
    document.querySelectorAll('.view-all').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            if (page) {
                navigateToPage(page);
            }
        });
    });
}

// Dashboard
let salesTrendChart = null;
let paymentStatusChart = null;

async function loadDashboard() {
    try {
        const response = await fetch(`${API_BASE}/stats.php`);
        const stats = await response.json();
        
        // Update stat cards
        document.getElementById('stat-products').textContent = stats.total_products || 0;
        document.getElementById('stat-low-stock').textContent = stats.low_stock_products || 0;
        document.getElementById('stat-out-of-stock').textContent = stats.out_of_stock || 0;
        document.getElementById('stat-customers').textContent = stats.total_customers || 0;
        document.getElementById('stat-invoices').textContent = stats.total_invoices || 0;
        document.getElementById('stat-revenue').textContent = formatCurrency(stats.total_revenue || 0);
        document.getElementById('stat-pending').textContent = stats.pending_invoices || 0;
        document.getElementById('stat-today-sales').textContent = formatCurrency(stats.today_sales || 0);
        document.getElementById('stat-month-sales').textContent = formatCurrency(stats.month_sales || 0);
        
        // Month comparison
        const monthComparison = document.getElementById('stat-month-comparison');
        if (stats.last_month_sales && stats.last_month_sales > 0) {
            const change = ((stats.month_sales - stats.last_month_sales) / stats.last_month_sales * 100).toFixed(1);
            const isPositive = change >= 0;
            monthComparison.textContent = `${isPositive ? '+' : ''}${change}% vs last month`;
            monthComparison.style.color = isPositive ? '#10b981' : '#ef4444';
        }
        
        // Sales Overview
        document.getElementById('sales-today').textContent = formatCurrency(stats.today_sales || 0);
        document.getElementById('sales-month').textContent = formatCurrency(stats.month_sales || 0);
        document.getElementById('sales-avg').textContent = formatCurrency(stats.avg_invoice_value || 0);
        document.getElementById('sales-paid').textContent = stats.paid_invoices || 0;
        
        // Month comparison in sales overview
        const salesMonthChange = document.getElementById('sales-month-change');
        if (stats.last_month_sales && stats.last_month_sales > 0) {
            const change = ((stats.month_sales - stats.last_month_sales) / stats.last_month_sales * 100).toFixed(1);
            const isPositive = change >= 0;
            salesMonthChange.innerHTML = `<span style="color: ${isPositive ? '#10b981' : '#ef4444'}">
                <i class="fas fa-arrow-${isPositive ? 'up' : 'down'}" style="font-size: 0.75rem;"></i> ${Math.abs(change)}%
            </span>`;
        }
        
        // Render charts
        renderSalesTrendChart(stats.sales_trend || []);
        renderPaymentStatusChart(stats.payment_breakdown || {});
        
        // Display top products
        displayTopProducts(stats.top_products || []);
        
        // Load recent invoices
        const invoicesResponse = await fetch(`${API_BASE}/invoices.php?limit=5`);
        const invoices = await invoicesResponse.json();
        displayRecentInvoices(invoices.slice(0, 5));
        
        // Load low stock products
        const productsResponse = await fetch(`${API_BASE}/products.php`);
        const allProducts = await productsResponse.json();
        const lowStock = allProducts.filter(p => p.quantity <= p.min_stock_level && p.status === 'active');
        displayLowStockProducts(lowStock.slice(0, 5));
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showNotification('Error loading dashboard data', 'error');
    }
}

function renderSalesTrendChart(salesData) {
    const ctx = document.getElementById('salesTrendCanvas');
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (salesTrendChart) {
        salesTrendChart.destroy();
    }
    
    const labels = salesData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    const amounts = salesData.map(item => item.amount || 0);
    
    salesTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales (₹)',
                data: amounts,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function renderPaymentStatusChart(paymentData) {
    const ctx = document.getElementById('paymentStatusCanvas');
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (paymentStatusChart) {
        paymentStatusChart.destroy();
    }
    
    const labels = Object.keys(paymentData);
    const data = Object.values(paymentData);
    const colors = {
        'paid': '#10b981',
        'pending': '#f59e0b',
        'partial': '#3b82f6'
    };
    
    const backgroundColors = labels.map(label => colors[label] || '#6b7280');
    
    paymentStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
            datasets: [{
                data: data,
                backgroundColor: backgroundColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Create custom legend
    const legendContainer = document.getElementById('payment-status-legend');
    if (legendContainer) {
        legendContainer.innerHTML = labels.map((label, index) => `
            <div class="legend-item">
                <span class="legend-color" style="background-color: ${backgroundColors[index]}"></span>
                <span class="legend-label">${label.charAt(0).toUpperCase() + label.slice(1)}</span>
                <span class="legend-value">${data[index]}</span>
            </div>
        `).join('');
    }
}

function displayTopProducts(products) {
    const container = document.getElementById('top-products-list');
    if (!container) return;
    
    if (products.length === 0) {
        container.innerHTML = '<p style="color: var(--text-light); text-align: center; padding: 1rem;">No sales data available</p>';
        return;
    }
    
    container.innerHTML = products.map((product, index) => `
        <div class="top-product-item">
            <div class="product-rank">${index + 1}</div>
            <div class="product-info">
                <div class="product-name">${product.name}</div>
                <div class="product-stats">
                    <span><i class="fas fa-shopping-cart"></i> ${product.quantity} sold</span>
                    <span><i class="fas fa-rupee-sign"></i> ${formatCurrency(product.revenue)}</span>
                </div>
            </div>
        </div>
    `).join('');
}

function displayRecentInvoices(invoices) {
    const container = document.getElementById('recent-invoices-list');
    if (invoices.length === 0) {
        container.innerHTML = '<p style="color: var(--text-light); text-align: center; padding: 1rem;">No recent invoices</p>';
        return;
    }
    
    container.innerHTML = invoices.map(invoice => `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
            <div>
                <div style="font-weight: 500;">${invoice.invoice_number}</div>
                <div style="font-size: 0.875rem; color: var(--text-light);">${invoice.customer_name || 'Walk-in Customer'}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 600;">${formatCurrency(invoice.total_amount)}</div>
                <span class="badge ${getStatusBadgeClass(invoice.payment_status)}">${invoice.payment_status}</span>
            </div>
        </div>
    `).join('');
}

function displayLowStockProducts(products) {
    const container = document.getElementById('low-stock-list');
    if (products.length === 0) {
        container.innerHTML = '<p style="color: var(--text-light); text-align: center; padding: 1rem;">No low stock items</p>';
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
            <div>
                <div style="font-weight: 500;">${product.product_name}</div>
                <div style="font-size: 0.875rem; color: var(--text-light);">${product.product_code || 'N/A'}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 600; color: var(--danger-color);">${product.quantity} ${product.unit}</div>
                <div style="font-size: 0.875rem; color: var(--text-light);">Min: ${product.min_stock_level}</div>
            </div>
        </div>
    `).join('');
}

// Products
// Product view state
let currentProductView = 'list';

async function loadProducts() {
    try {
        const search = document.getElementById('product-search')?.value || '';
        const category = document.getElementById('category-filter')?.value || '';
        const stockStatus = document.getElementById('stock-status-filter')?.value || '';
        const productStatus = document.getElementById('product-status-filter')?.value || '';
        
        let url = `${API_BASE}/products.php`;
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (category) params.append('category', category);
        if (params.toString()) url += '?' + params.toString();
        
        const response = await fetch(url);
        let allProducts = await response.json();
        
        // Apply client-side filters
        let filteredProducts = allProducts;
        if (stockStatus) {
            filteredProducts = filteredProducts.filter(p => {
                if (stockStatus === 'in_stock') return p.quantity > p.min_stock_level && p.quantity > 0;
                if (stockStatus === 'low_stock') return p.quantity <= p.min_stock_level && p.quantity > 0;
                if (stockStatus === 'out_of_stock') return p.quantity === 0;
                return true;
            });
        }
        if (productStatus) {
            filteredProducts = filteredProducts.filter(p => p.status === productStatus);
        }
        
        products = filteredProducts;
        
        displayProducts(products);
        updateCategoryFilter(allProducts);
        updateProductsCount(products.length);
    } catch (error) {
        console.error('Error loading products:', error);
        showNotification('Error loading products', 'error');
    }
}

function displayProducts(products) {
    const isEmpty = products.length === 0;
    const emptyState = document.getElementById('products-empty-state');
    const gridView = document.getElementById('products-grid-view');
    const listView = document.getElementById('products-list-view');
    
    if (isEmpty) {
        emptyState.style.display = 'block';
        gridView.style.display = 'none';
        listView.style.display = 'none';
        return;
    }
    
    emptyState.style.display = 'none';
    
    if (currentProductView === 'grid') {
        displayProductsGrid(products);
        gridView.style.display = 'block';
        listView.style.display = 'none';
    } else {
        displayProductsList(products);
        gridView.style.display = 'none';
        listView.style.display = 'block';
    }
}

function displayProductsGrid(products) {
    const container = document.getElementById('products-grid');
    if (!container) return;
    
    container.innerHTML = products.map(product => createProductCard(product)).join('');
}

function displayProductsList(products) {
    const tbody = document.getElementById('products-table-body');
    if (!tbody) return;
    
    tbody.innerHTML = products.map(product => `
        <tr>
            <td>
                <div class="product-list-item">
                    <div class="product-list-info">
                        <strong class="product-list-name">${product.product_name}</strong>
                        ${product.product_code ? `<span class="product-list-code">${product.product_code}</span>` : ''}
                    </div>
                </div>
            </td>
            <td>
                ${product.category ? `<span class="category-badge">${product.category}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td>
                <div class="stock-info-list">
                    <span class="stock-quantity">${product.quantity} ${product.unit}</span>
                    ${getStockIndicator(product)}
                </div>
            </td>
            <td>
                <strong class="price-text">${formatCurrency(product.unit_price)}</strong>
            </td>
            <td>
                ${getProductStatusBadge(product)}
            </td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn edit" onclick="editProduct(${product.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" onclick="deleteProduct(${product.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function createProductCard(product) {
    const stockPercentage = product.min_stock_level > 0 
        ? Math.min(100, (product.quantity / (product.min_stock_level * 2)) * 100)
        : (product.quantity > 0 ? 100 : 0);
    
    const stockClass = product.quantity === 0 ? 'out-of-stock' : 
                      product.quantity <= product.min_stock_level ? 'low-stock' : 'in-stock';
    
    return `
        <div class="product-card ${stockClass}" data-product-id="${product.id}">
            <div class="product-card-header">
                <div class="product-status-indicator ${product.status === 'active' ? 'active' : 'inactive'}"></div>
                <div class="product-card-actions">
                    <button class="product-action-btn" onclick="editProduct(${product.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="product-action-btn delete" onclick="deleteProduct(${product.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="product-card-body">
                <div class="product-card-title">
                    <h3 class="product-name">${product.product_name}</h3>
                    ${product.product_code ? `<span class="product-code">${product.product_code}</span>` : ''}
                </div>
                ${product.category ? `<div class="product-category"><i class="fas fa-tag"></i> ${product.category}</div>` : ''}
                ${product.description ? `<p class="product-description">${product.description.substring(0, 80)}${product.description.length > 80 ? '...' : ''}</p>` : ''}
            </div>
            <div class="product-card-footer">
                <div class="product-stock-section">
                    <div class="stock-header">
                        <span class="stock-label">Stock</span>
                        <span class="stock-value">${product.quantity} ${product.unit}</span>
                    </div>
                    <div class="stock-bar">
                        <div class="stock-bar-fill ${stockClass}" style="width: ${stockPercentage}%"></div>
                    </div>
                    ${product.min_stock_level > 0 ? `<div class="stock-min">Min: ${product.min_stock_level} ${product.unit}</div>` : ''}
                </div>
                <div class="product-price-section">
                    <div class="price-label">Price</div>
                    <div class="price-value">${formatCurrency(product.unit_price)}</div>
                    ${product.cost_price ? `<div class="cost-price">Cost: ${formatCurrency(product.cost_price)}</div>` : ''}
                </div>
            </div>
        </div>
    `;
}

function switchProductView(view) {
    currentProductView = view;
    const gridBtn = document.getElementById('grid-view-btn');
    const listBtn = document.getElementById('list-view-btn');
    
    if (view === 'grid') {
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
    } else {
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
    }
    
    displayProducts(products);
}

function clearProductFilters() {
    document.getElementById('product-search').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('stock-status-filter').value = '';
    document.getElementById('product-status-filter').value = '';
    loadProducts();
}

function updateProductsCount(count) {
    const countEl = document.getElementById('products-count');
    if (countEl) {
        countEl.textContent = `${count} product${count !== 1 ? 's' : ''}`;
    }
}

function getStockIndicator(product) {
    if (product.quantity === 0) {
        return '<span class="stock-indicator out-of-stock"><i class="fas fa-times-circle"></i></span>';
    } else if (product.quantity <= product.min_stock_level) {
        return '<span class="stock-indicator low-stock"><i class="fas fa-exclamation-triangle"></i></span>';
    }
    return '<span class="stock-indicator in-stock"><i class="fas fa-check-circle"></i></span>';
}

function getProductStatusBadge(product) {
    if (product.status === 'active') {
        return '<span class="badge badge-success">Active</span>';
    }
    return '<span class="badge badge-danger">Inactive</span>';
}

function exportProducts() {
    // Simple CSV export
    const csv = [
        ['Code', 'Name', 'Category', 'Quantity', 'Unit', 'Price', 'Cost Price', 'Min Stock', 'Status'].join(','),
        ...products.map(p => [
            p.product_code || '',
            `"${p.product_name}"`,
            p.category || '',
            p.quantity,
            p.unit || 'pcs',
            p.unit_price,
            p.cost_price || '',
            p.min_stock_level || 0,
            p.status
        ].join(','))
    ].join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `products_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    showNotification('Products exported successfully', 'success');
}

function updateCategoryFilter(products) {
    const filter = document.getElementById('category-filter');
    if (!filter) return;
    
    const categories = [...new Set(products.map(p => p.category).filter(Boolean))];
    const currentValue = filter.value;
    
    filter.innerHTML = '<option value="">All Categories</option>' + 
        categories.map(cat => `<option value="${cat}">${cat}</option>`).join('');
    
    if (currentValue) filter.value = currentValue;
}

function getStockStatusBadge(product) {
    if (product.quantity === 0) {
        return '<span class="badge badge-danger">Out of Stock</span>';
    } else if (product.quantity <= product.min_stock_level) {
        return '<span class="badge badge-warning">Low Stock</span>';
    } else {
        return '<span class="badge badge-success">In Stock</span>';
    }
}

// Product Modal
function openProductModal(productId = null) {
    const modal = document.getElementById('productModal');
    const form = document.getElementById('product-form');
    const title = document.getElementById('productModalTitle');
    
    form.reset();
    document.getElementById('product-id').value = '';
    
    if (productId) {
        title.textContent = 'Edit Product';
        loadProductForEdit(productId);
    } else {
        title.textContent = 'Add Product';
    }
    
    modal.classList.add('active');
}

function closeProductModal() {
    document.getElementById('productModal').classList.remove('active');
    document.getElementById('product-form').reset();
}

async function loadProductForEdit(id) {
    try {
        const response = await fetch(`${API_BASE}/products.php?id=${id}`);
        const product = await response.json();
        
        document.getElementById('product-id').value = product.id;
        document.getElementById('product-name').value = product.product_name;
        document.getElementById('product-code').value = product.product_code || '';
        document.getElementById('product-category').value = product.category || '';
        document.getElementById('product-description').value = product.description || '';
        document.getElementById('product-quantity').value = product.quantity;
        document.getElementById('product-unit').value = product.unit || 'pcs';
        document.getElementById('product-unit-price').value = product.unit_price;
        document.getElementById('product-cost-price').value = product.cost_price || '';
        document.getElementById('product-min-stock').value = product.min_stock_level || 0;
    } catch (error) {
        console.error('Error loading product:', error);
        showNotification('Error loading product', 'error');
    }
}

async function handleProductSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('product-id').value;
    const data = {
        product_name: document.getElementById('product-name').value,
        product_code: document.getElementById('product-code').value,
        category: document.getElementById('product-category').value,
        description: document.getElementById('product-description').value,
        quantity: parseInt(document.getElementById('product-quantity').value),
        unit: document.getElementById('product-unit').value,
        unit_price: parseFloat(document.getElementById('product-unit-price').value),
        cost_price: parseFloat(document.getElementById('product-cost-price').value) || 0,
        min_stock_level: parseInt(document.getElementById('product-min-stock').value) || 0
    };
    
    try {
        const url = `${API_BASE}/products.php`;
        const method = id ? 'PUT' : 'POST';
        
        if (id) {
            data.id = parseInt(id);
            // Preserve existing status when updating - fetch current product status
            const currentProduct = products.find(p => p.id === parseInt(id));
            data.status = currentProduct ? currentProduct.status : 'active';
        }
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(id ? 'Product updated successfully' : 'Product added successfully', 'success');
            closeProductModal();
            loadProducts();
            if (document.getElementById('dashboard-page').style.display !== 'none') {
                loadDashboard();
            }
        } else {
            showNotification(result.error || 'Error saving product', 'error');
        }
    } catch (error) {
        console.error('Error saving product:', error);
        showNotification('Error saving product', 'error');
    }
}

function editProduct(id) {
    openProductModal(id);
}

async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    
    try {
        const response = await fetch(`${API_BASE}/products.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Product deleted successfully', 'success');
            loadProducts();
            if (document.getElementById('dashboard-page').style.display !== 'none') {
                loadDashboard();
            }
        } else {
            showNotification(result.error || 'Error deleting product', 'error');
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        showNotification('Error deleting product', 'error');
    }
}

// Customers
async function loadCustomers() {
    try {
        const search = document.getElementById('customer-search')?.value || '';
        
        let url = `${API_BASE}/customers.php`;
        if (search) url += '?search=' + encodeURIComponent(search);
        
        const response = await fetch(url);
        customers = await response.json();
        
        displayCustomers(customers);
        updateCustomerDropdown(customers);
    } catch (error) {
        console.error('Error loading customers:', error);
        showNotification('Error loading customers', 'error');
    }
}

function displayCustomers(customers) {
    const tbody = document.getElementById('customers-table-body');
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-light);">No customers found</td></tr>';
        return;
    }
    
    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td><strong>${customer.customer_name}</strong></td>
            <td>${customer.phone || 'N/A'}</td>
            <td>${customer.email || 'N/A'}</td>
            <td>${customer.city || 'N/A'}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn edit" onclick="editCustomer(${customer.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" onclick="deleteCustomer(${customer.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function updateCustomerDropdown(customers) {
    const dropdown = document.getElementById('invoice-customer');
    if (!dropdown) return;
    
    const currentValue = dropdown.value;
    dropdown.innerHTML = '<option value="">Select Customer</option>' + 
        customers.map(c => `<option value="${c.id}">${c.customer_name}${c.phone ? ' - ' + c.phone : ''}</option>`).join('');
    
    if (currentValue) dropdown.value = currentValue;
}

// Customer Modal
function openCustomerModal(customerId = null) {
    const modal = document.getElementById('customerModal');
    const form = document.getElementById('customer-form');
    const title = document.getElementById('customerModalTitle');
    
    form.reset();
    document.getElementById('customer-id').value = '';
    
    if (customerId) {
        title.textContent = 'Edit Customer';
        loadCustomerForEdit(customerId);
    } else {
        title.textContent = 'Add Customer';
    }
    
    modal.classList.add('active');
}

function closeCustomerModal() {
    document.getElementById('customerModal').classList.remove('active');
    document.getElementById('customer-form').reset();
}

async function loadCustomerForEdit(id) {
    try {
        const response = await fetch(`${API_BASE}/customers.php?id=${id}`);
        const customer = await response.json();
        
        document.getElementById('customer-id').value = customer.id;
        document.getElementById('customer-name').value = customer.customer_name;
        document.getElementById('customer-phone').value = customer.phone || '';
        document.getElementById('customer-email').value = customer.email || '';
        document.getElementById('customer-address').value = customer.address || '';
        document.getElementById('customer-city').value = customer.city || '';
        document.getElementById('customer-state').value = customer.state || '';
        document.getElementById('customer-pincode').value = customer.pincode || '';
        document.getElementById('customer-gstin').value = customer.gstin || '';
    } catch (error) {
        console.error('Error loading customer:', error);
        showNotification('Error loading customer', 'error');
    }
}

async function handleCustomerSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('customer-id').value;
    const data = {
        customer_name: document.getElementById('customer-name').value,
        phone: document.getElementById('customer-phone').value,
        email: document.getElementById('customer-email').value,
        address: document.getElementById('customer-address').value,
        city: document.getElementById('customer-city').value,
        state: document.getElementById('customer-state').value,
        pincode: document.getElementById('customer-pincode').value,
        gstin: document.getElementById('customer-gstin').value
    };
    
    try {
        const url = `${API_BASE}/customers.php`;
        const method = id ? 'PUT' : 'POST';
        
        if (id) data.id = parseInt(id);
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(id ? 'Customer updated successfully' : 'Customer added successfully', 'success');
            closeCustomerModal();
            loadCustomers();
            if (document.getElementById('new-invoice-page').style.display !== 'none') {
                loadCustomers(); // Reload for dropdown
            }
        } else {
            showNotification(result.error || 'Error saving customer', 'error');
        }
    } catch (error) {
        console.error('Error saving customer:', error);
        showNotification('Error saving customer', 'error');
    }
}

function editCustomer(id) {
    openCustomerModal(id);
}

async function deleteCustomer(id) {
    if (!confirm('Are you sure you want to delete this customer?')) return;
    
    try {
        const response = await fetch(`${API_BASE}/customers.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Customer deleted successfully', 'success');
            loadCustomers();
        } else {
            showNotification(result.error || 'Error deleting customer', 'error');
        }
    } catch (error) {
        console.error('Error deleting customer:', error);
        showNotification('Error deleting customer', 'error');
    }
}

// Invoices
async function loadInvoices() {
    try {
        const search = document.getElementById('invoice-search')?.value || '';
        const status = document.getElementById('invoice-status-filter')?.value || '';
        
        let url = `${API_BASE}/invoices.php`;
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (status) params.append('status', status);
        if (params.toString()) url += '?' + params.toString();
        
        const response = await fetch(url);
        const invoices = await response.json();
        
        displayInvoices(invoices);
    } catch (error) {
        console.error('Error loading invoices:', error);
        showNotification('Error loading invoices', 'error');
    }
}

function displayInvoices(invoices) {
    const tbody = document.getElementById('invoices-table-body');
    if (invoices.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-light);">No invoices found</td></tr>';
        return;
    }
    
    tbody.innerHTML = invoices.map(invoice => `
        <tr>
            <td><strong>${invoice.invoice_number}</strong></td>
            <td>${invoice.customer_name || 'Walk-in Customer'}</td>
            <td>${formatDate(invoice.invoice_date)}</td>
            <td>${formatCurrency(invoice.total_amount)}</td>
            <td><span class="badge ${getStatusBadgeClass(invoice.payment_status)}">${invoice.payment_status}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view" onclick="viewInvoice(${invoice.id})" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn edit" onclick="editInvoice(${invoice.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" onclick="deleteInvoice(${invoice.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getStatusBadgeClass(status) {
    const classes = {
        'paid': 'badge-success',
        'pending': 'badge-warning',
        'partial': 'badge-info'
    };
    return classes[status] || 'badge-info';
}

// New Invoice
function loadNewInvoice() {
    invoiceItems = [];
    document.getElementById('invoice-form').reset();
    document.getElementById('invoice-id').value = '';
    document.getElementById('invoice-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('invoice-items-body').innerHTML = '';
    document.getElementById('invoice-page-title').textContent = 'Create New Invoice';
    document.getElementById('invoice-submit-btn').textContent = 'Create Invoice';
    calculateInvoiceTotal();
    loadCustomers();
    loadProducts();
}

function setTodayDate() {
    const dateInput = document.getElementById('invoice-date');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
}

function addInvoiceItem() {
    if (products.length === 0) {
        showNotification('Please load products first', 'error');
        loadProducts();
        return;
    }
    
    // Create product selection modal or use a simple prompt
    const productOptions = products.map((p, i) => 
        `${i + 1}. ${p.product_name} (Stock: ${p.quantity} ${p.unit}, Price: ${formatCurrency(p.unit_price)})`
    ).join('\n');
    
    const selection = prompt(`Select a product (enter number):\n\n${productOptions}\n\nEnter product number:`);
    const productIndex = parseInt(selection) - 1;
    
    if (isNaN(productIndex) || productIndex < 0 || productIndex >= products.length) {
        return;
    }
    
    const product = products[productIndex];
    const quantity = parseInt(prompt(`Enter quantity for ${product.product_name} (Available: ${product.quantity} ${product.unit}):`) || '1');
    
    if (isNaN(quantity) || quantity <= 0) {
        return;
    }
    
    if (quantity > product.quantity) {
        if (!confirm(`Only ${product.quantity} ${product.unit} available. Add ${product.quantity} ${product.unit}?`)) {
            return;
        }
    }
    
    const item = {
        product_id: product.id,
        product_name: product.product_name,
        quantity: Math.min(quantity, product.quantity),
        unit_price: product.unit_price,
        total_price: Math.min(quantity, product.quantity) * product.unit_price
    };
    
    invoiceItems.push(item);
    renderInvoiceItems();
    calculateInvoiceTotal();
}

function removeInvoiceItem(index) {
    invoiceItems.splice(index, 1);
    renderInvoiceItems();
    calculateInvoiceTotal();
}

function renderInvoiceItems() {
    const tbody = document.getElementById('invoice-items-body');
    
    if (invoiceItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-light);">No items added. Click "Add Item" to add products.</td></tr>';
        return;
    }
    
    tbody.innerHTML = invoiceItems.map((item, index) => `
        <tr>
            <td>${item.product_name}</td>
            <td>${item.quantity}</td>
            <td>${formatCurrency(item.unit_price)}</td>
            <td>${formatCurrency(item.total_price)}</td>
            <td>
                <button class="action-btn delete" onclick="removeInvoiceItem(${index})" title="Remove">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function calculateInvoiceTotal() {
    const subtotal = invoiceItems.reduce((sum, item) => sum + item.total_price, 0);
    const taxRate = parseFloat(document.getElementById('invoice-tax-rate').value) || 0;
    const discount = parseFloat(document.getElementById('invoice-discount').value) || 0;
    
    const taxAmount = (subtotal * taxRate) / 100;
    const total = subtotal + taxAmount - discount;
    
    document.getElementById('invoice-subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('invoice-tax-amount').textContent = formatCurrency(taxAmount);
    document.getElementById('invoice-total').textContent = formatCurrency(total);
}

async function handleInvoiceSubmit(e) {
    e.preventDefault();
    
    if (invoiceItems.length === 0) {
        showNotification('Please add at least one item to the invoice', 'error');
        return;
    }
    
    const subtotal = invoiceItems.reduce((sum, item) => sum + item.total_price, 0);
    const taxRate = parseFloat(document.getElementById('invoice-tax-rate').value) || 0;
    const discount = parseFloat(document.getElementById('invoice-discount').value) || 0;
    const taxAmount = (subtotal * taxRate) / 100;
    const total = subtotal + taxAmount - discount;
    
    const invoiceId = document.getElementById('invoice-id').value;
    const isEdit = invoiceId !== '';
    
    const data = {
        customer_id: document.getElementById('invoice-customer').value || null,
        invoice_date: document.getElementById('invoice-date').value,
        due_date: document.getElementById('invoice-due-date').value || null,
        subtotal: subtotal,
        tax_rate: taxRate,
        tax_amount: taxAmount,
        discount: discount,
        total_amount: total,
        payment_status: document.getElementById('invoice-payment-status').value,
        payment_method: document.getElementById('invoice-payment-method').value,
        notes: document.getElementById('invoice-notes').value,
        items: invoiceItems
    };
    
    if (isEdit) {
        data.id = parseInt(invoiceId);
    }
    
    try {
        const response = await fetch(`${API_BASE}/invoices.php`, {
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(isEdit ? 'Invoice updated successfully' : 'Invoice created successfully', 'success');
            navigateToPage('invoices');
        } else {
            showNotification(result.error || (isEdit ? 'Error updating invoice' : 'Error creating invoice'), 'error');
        }
    } catch (error) {
        console.error('Error saving invoice:', error);
        showNotification(isEdit ? 'Error updating invoice' : 'Error creating invoice', 'error');
    }
}

// View Invoice - Opens in new tab
function viewInvoice(id) {
    // Open invoice preview in a new tab
    window.open(`invoice-preview.php?id=${id}`, '_blank');
}

// Edit Invoice
async function editInvoice(id) {
    try {
        // Navigate to new invoice page
        navigateToPage('new-invoice');
        
        // Update page title
        document.getElementById('invoice-page-title').textContent = 'Edit Invoice';
        document.getElementById('invoice-submit-btn').textContent = 'Update Invoice';
        
        // Load invoice data
        const response = await fetch(`${API_BASE}/invoices.php?id=${id}`);
        const invoice = await response.json();
        
        if (invoice.error) {
            showNotification('Invoice not found', 'error');
            return;
        }
        
        // Set invoice ID
        document.getElementById('invoice-id').value = invoice.id;
        
        // Load customers if not already loaded
        if (customers.length === 0) {
            await loadCustomers();
        }
        
        // Populate form fields
        document.getElementById('invoice-customer').value = invoice.customer_id || '';
        document.getElementById('invoice-date').value = invoice.invoice_date;
        document.getElementById('invoice-due-date').value = invoice.due_date || '';
        document.getElementById('invoice-tax-rate').value = invoice.tax_rate || 18;
        document.getElementById('invoice-discount').value = invoice.discount || 0;
        document.getElementById('invoice-payment-status').value = invoice.payment_status || 'pending';
        document.getElementById('invoice-payment-method').value = invoice.payment_method || '';
        document.getElementById('invoice-notes').value = invoice.notes || '';
        
        // Load products if not already loaded
        if (products.length === 0) {
            await loadProducts();
        }
        
        // Populate invoice items
        invoiceItems = (invoice.items || []).map(item => ({
            product_id: item.product_id,
            product_name: item.product_name,
            quantity: parseInt(item.quantity),
            unit_price: parseFloat(item.unit_price),
            total_price: parseFloat(item.total_price)
        }));
        
        renderInvoiceItems();
        calculateInvoiceTotal();
        
    } catch (error) {
        console.error('Error loading invoice:', error);
        showNotification('Error loading invoice', 'error');
    }
}

function displayInvoiceView(invoice) {
    const container = document.getElementById('invoice-view-content');
    
    // Get shop settings
    fetch(`${API_BASE}/settings.php`)
        .then(r => r.json())
        .then(settings => {
            const itemsHtml = invoice.items.map(item => `
                <tr>
                    <td class="col-item">${item.product_name}</td>
                    <td class="col-description">${item.product_description || '-'}</td>
                    <td class="col-price">${formatCurrency(item.unit_price)}</td>
                    <td class="col-qty">${item.quantity}</td>
                    <td class="col-total">${formatCurrency(item.total_price)}</td>
                </tr>
            `).join('');
            
            container.innerHTML = `
                <div class="invoice-professional">
                    <!-- Professional Header -->
                    <div class="invoice-header-professional">
                        <div class="header-left-professional">
                            <div class="company-logo-professional">
                                <span>${(settings.shop_name || 'My Shop').charAt(0).toUpperCase()}</span>
                            </div>
                            <div class="company-info-professional">
                                <h1 class="company-name-professional">${settings.shop_name || 'My Shop'}</h1>
                                ${settings.shop_address ? `<p class="company-address">${settings.shop_address}</p>` : ''}
                                <div class="company-contact">
                                    ${settings.shop_phone ? `<span><i class="fas fa-phone"></i> ${settings.shop_phone}</span>` : ''}
                                    ${settings.shop_email ? `<span><i class="fas fa-envelope"></i> ${settings.shop_email}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="header-right-professional">
                            <div class="invoice-title-professional">
                                <h2>INVOICE</h2>
                            </div>
                            <div class="invoice-meta-professional">
                                <div class="meta-row">
                                    <span class="meta-label">Invoice #</span>
                                    <span class="meta-value">${invoice.invoice_number}</span>
                                </div>
                                <div class="meta-row">
                                    <span class="meta-label">Date</span>
                                    <span class="meta-value">${formatDate(invoice.invoice_date)}</span>
                                </div>
                                ${invoice.due_date ? `
                                <div class="meta-row">
                                    <span class="meta-label">Due Date</span>
                                    <span class="meta-value">${formatDate(invoice.due_date)}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Section -->
                    <div class="billing-section-professional">
                        <div class="billing-box bill-to-professional">
                            <h3 class="billing-title">Bill To</h3>
                            <div class="billing-content">
                                <p class="billing-name">${invoice.customer_name || 'Walk-in Customer'}</p>
                                ${invoice.address ? `<p class="billing-line">${invoice.address}</p>` : ''}
                                ${invoice.phone ? `<p class="billing-line"><i class="fas fa-phone"></i> ${invoice.phone}</p>` : ''}
                                ${invoice.email ? `<p class="billing-line"><i class="fas fa-envelope"></i> ${invoice.email}</p>` : ''}
                            </div>
                        </div>
                        <div class="billing-box ship-to-professional">
                            <h3 class="billing-title">Ship To</h3>
                            <div class="billing-content">
                                <p class="billing-name">${settings.shop_name || 'My Shop'}</p>
                                ${settings.shop_address ? `<p class="billing-line">${settings.shop_address}</p>` : ''}
                                ${settings.shop_phone ? `<p class="billing-line"><i class="fas fa-phone"></i> ${settings.shop_phone}</p>` : ''}
                                ${settings.shop_email ? `<p class="billing-line"><i class="fas fa-envelope"></i> ${settings.shop_email}</p>` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <div class="table-wrapper-professional">
                        <table class="items-table-professional">
                            <thead>
                                <tr>
                                    <th class="col-item">Item</th>
                                    <th class="col-description">Description</th>
                                    <th class="col-price">Unit Price</th>
                                    <th class="col-qty">Qty</th>
                                    <th class="col-total">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Summary Section -->
                    <div class="summary-section-professional">
                        <div class="summary-box-professional">
                            <table class="summary-table-professional">
                                <tr>
                                    <td class="summary-label">Subtotal</td>
                                    <td class="summary-value">${formatCurrency(invoice.subtotal)}</td>
                                </tr>
                                ${invoice.discount > 0 ? `
                                <tr>
                                    <td class="summary-label">Discount${invoice.discount_percent ? ` (${invoice.discount_percent}%)` : ''}</td>
                                    <td class="summary-value discount">-${formatCurrency(invoice.discount)}</td>
                                </tr>
                                ` : ''}
                                ${invoice.tax_rate > 0 ? `
                                <tr>
                                    <td class="summary-label">Tax (${invoice.tax_rate}%)</td>
                                    <td class="summary-value">+${formatCurrency(invoice.tax_amount)}</td>
                                </tr>
                                ` : ''}
                                <tr class="grand-total-row">
                                    <td class="summary-label">Grand Total</td>
                                    <td class="summary-value">${formatCurrency(invoice.total_amount)}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Payment & Footer -->
                    <div class="footer-section-professional">
                        <div class="payment-info-professional">
                            <h4>Payment Information</h4>
                            <p><strong>Payment Method:</strong> ${invoice.payment_method || 'Cash'}</p>
                            <p><strong>Status:</strong> <span class="status-badge status-${invoice.payment_status || 'pending'}">${(invoice.payment_status || 'pending').charAt(0).toUpperCase() + (invoice.payment_status || 'pending').slice(1)}</span></p>
                        </div>
                        <div class="terms-professional">
                            <h4>Terms & Conditions</h4>
                            <p>Payment is due within 30 days of invoice date. Late payments may incur additional fees. All claims relating to quantity or shipping errors shall be waived by Buyer unless made in writing to Seller within thirty (30) days after delivery.</p>
                        </div>
                    </div>
                </div>
            `;
        });
}

function closeInvoiceViewModal() {
    document.getElementById('invoiceViewModal').classList.remove('active');
    currentInvoiceId = null;
}

function printInvoice() {
    window.print();
}

function downloadInvoice() {
    console.log('=== PDF GENERATION DEBUG START ===');
    console.log('downloadInvoice() function called at:', new Date().toISOString());
    
    // Step 1: Check if element exists
    const element = document.getElementById('invoice-view-content');
    console.log('Step 1 - Element check:', {
        element: element,
        exists: !!element,
        innerHTML: element ? element.innerHTML.substring(0, 100) + '...' : 'N/A',
        offsetWidth: element ? element.offsetWidth : 0,
        offsetHeight: element ? element.offsetHeight : 0,
        scrollWidth: element ? element.scrollWidth : 0,
        scrollHeight: element ? element.scrollHeight : 0,
        computedStyle: element ? window.getComputedStyle(element).display : 'N/A'
    });
    
    if (!element) {
        console.error('ERROR: invoice-view-content element not found!');
        showNotification('Invoice content not found. Please view an invoice first.', 'error');
        return;
    }

    if (!element.innerHTML || element.innerHTML.trim().length === 0) {
        console.error('ERROR: invoice-view-content element is empty!');
        showNotification('Invoice content is empty. Please view an invoice first.', 'error');
        return;
    }

    // Step 2: Check if html2pdf.js is loaded
    console.log('Step 2 - html2pdf.js check:', {
        html2pdf: typeof html2pdf,
        isDefined: typeof html2pdf !== 'undefined',
        html2pdfObject: typeof html2pdf !== 'undefined' ? html2pdf : 'NOT LOADED'
    });
    
    if (typeof html2pdf === 'undefined') {
        console.error('ERROR: html2pdf.js is not loaded!');
        console.error('Check Network tab for script loading errors');
        console.error('Expected: https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js');
        showNotification('PDF library not loaded. Please refresh the page and check your internet connection.', 'error');
        return;
    }

    // Step 3: Note about dependencies (they're bundled, don't need to be global)
    console.log('Step 3 - Dependencies note:', {
        note: 'html2canvas and jsPDF are bundled inside html2pdf.js',
        explanation: 'They don\'t need to be global variables - this is normal!',
        html2canvas: typeof html2canvas,
        jsPDF: typeof jsPDF
    });

    // Step 4: Get invoice number
    const invoiceNumber = document.querySelector('.invoice-meta p')?.textContent?.match(/INV-[0-9]+/)?.[0] || 
                          document.querySelector('.invoice-number p')?.textContent?.match(/INV-[0-9]+/)?.[0] || 
                          'Invoice';
    const filename = `Invoice_${invoiceNumber}.pdf`;
    console.log('Step 4 - Filename:', filename);

    // Step 5: Show loading notification
    showNotification('Generating PDF with html2pdf.js... This may take a moment.', 'info');
    console.log('Step 5 - Notification shown, waiting 500ms before starting conversion...');

    // Step 6: Wait for DOM to be ready and ensure element is visible
    setTimeout(() => {
        console.log('Step 5 - Starting PDF generation after delay');
        
        // Ensure element is visible (not hidden by modal or CSS)
        const originalDisplay = element.style.display;
        const originalVisibility = element.style.visibility;
        const originalOpacity = element.style.opacity;
        
        element.style.display = 'block';
        element.style.visibility = 'visible';
        element.style.opacity = '1';
        
        // Force a reflow
        element.offsetHeight;
        
        console.log('Step 6 - Element visibility adjusted:', {
            display: element.style.display,
            visibility: element.style.visibility,
            opacity: element.style.opacity,
            dimensions: {
                width: element.offsetWidth,
                height: element.offsetHeight,
                scrollWidth: element.scrollWidth,
                scrollHeight: element.scrollHeight
            }
        });

        // Step 7: Detect screen size and calculate optimal PDF dimensions
        const screenInfo = {
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            windowWidth: window.innerWidth,
            windowHeight: window.innerHeight,
            devicePixelRatio: window.devicePixelRatio || 1,
            viewportWidth: document.documentElement.clientWidth,
            viewportHeight: document.documentElement.clientHeight
        };
        
        console.log('Step 7a - Screen Information:', screenInfo);
        
        // Calculate optimal A4 content width based on screen size
        // A4 width: 210mm = 794px at 96 DPI
        // With 10mm margins on each side = 190mm content = ~718px
        // Adjust based on device pixel ratio for better quality
        let a4ContentWidth = 718; // Base width for 96 DPI
        
        // Adjust for high DPI screens (retina, etc.)
        if (screenInfo.devicePixelRatio > 1) {
            // For high DPI, we can use higher resolution but keep same logical width
            console.log('Step 7b - High DPI screen detected, using optimized settings');
        }
        
        // Ensure width doesn't exceed viewport
        if (a4ContentWidth > screenInfo.viewportWidth) {
            a4ContentWidth = Math.min(screenInfo.viewportWidth - 40, 718);
            console.log('Step 7c - Adjusted width to fit viewport:', a4ContentWidth);
        }
        
        console.log('Step 7d - Final A4 content width:', a4ContentWidth);
        
        // Step 7e: Apply inline styles for better PDF rendering
        // html2pdf.js sometimes doesn't capture external CSS properly
        console.log('Step 7e - Applying inline styles for PDF compatibility...');
        const invoiceView = element.querySelector('.invoice-view');
        const originalInvoiceViewWidth = invoiceView ? invoiceView.style.width : '';
        const originalInvoiceViewMaxWidth = invoiceView ? invoiceView.style.maxWidth : '';
        const originalInvoiceViewMargin = invoiceView ? invoiceView.style.margin : '';
        
        // Set fixed width to ensure consistent rendering
        if (invoiceView) {
            invoiceView.style.width = a4ContentWidth + 'px';
            invoiceView.style.maxWidth = a4ContentWidth + 'px';
            invoiceView.style.margin = '0 auto';
            invoiceView.style.position = 'relative';
        }
        
        // Force multiple reflows to ensure layout is settled
        element.offsetHeight;
        void element.offsetWidth;
        
        // Wait a bit more for layout to settle
        setTimeout(() => {
            applyPDFStyles(element, a4ContentWidth);
            
            // Force another reflow after styles are applied
            element.offsetHeight;
            void element.offsetWidth;
            
            // Now generate PDF
            generatePDFWithOptions(element, invoiceView, originalInvoiceViewWidth, originalInvoiceViewMaxWidth, originalInvoiceViewMargin, a4ContentWidth, filename);
        }, 200);
        
        // PDF generation will be called from the nested setTimeout
    }, 500); // Initial delay to ensure everything is ready
}

// Separate function to generate PDF with proper options
function generatePDFWithOptions(element, invoiceView, originalInvoiceViewWidth, originalInvoiceViewMaxWidth, originalInvoiceViewMargin, a4ContentWidth, filename) {
    const originalDisplay = element.style.display;
    const originalVisibility = element.style.visibility;
    const originalOpacity = element.style.opacity;
    
    // Detect screen information for optimal PDF generation
    const screenInfo = {
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        devicePixelRatio: window.devicePixelRatio || 1,
        viewportWidth: window.innerWidth,
        viewportHeight: window.innerHeight
    };
    
    console.log('PDF Generation - Screen Info:', screenInfo);
    console.log('PDF Generation - A4 Content Width:', a4ContentWidth);
    
    // Calculate optimal scale based on device pixel ratio
    // Higher DPI screens can use higher scale for better quality
    const optimalScale = Math.min(screenInfo.devicePixelRatio || 2, 3);
    console.log('PDF Generation - Optimal Scale:', optimalScale);
    
    // Step 7b: Configure html2pdf with optimized options for alignment
    // A4 dimensions: 210mm x 297mm
    const opt = {
        margin: [10, 10, 10, 10], // 10mm margins on all sides
        filename: filename,
        image: { 
            type: 'jpeg', 
            quality: 0.98 
        },
        html2canvas: { 
            // Adjust scale based on device pixel ratio for optimal quality
            scale: optimalScale, // Use calculated optimal scale
            useCORS: true,
            letterRendering: true,
            logging: false,
            backgroundColor: '#ffffff',
            removeContainer: false,
            allowTaint: false,
            scrollX: 0,
            scrollY: 0,
            width: a4ContentWidth,
            height: element.scrollHeight || 1200,
            windowWidth: a4ContentWidth,
            windowHeight: element.scrollHeight || 1200,
            onclone: function(clonedDoc) {
                // Ensure styles are applied in the cloned document
                console.log('Step 7c - Applying styles to cloned document...');
                const clonedElement = clonedDoc.getElementById('invoice-view-content');
                if (clonedElement) {
                    const clonedInvoiceView = clonedElement.querySelector('.invoice-view');
                    if (clonedInvoiceView) {
                        clonedInvoiceView.style.width = a4ContentWidth + 'px';
                        clonedInvoiceView.style.maxWidth = a4ContentWidth + 'px';
                        clonedInvoiceView.style.margin = '0 auto';
                        clonedInvoiceView.style.position = 'relative';
                    }
                    // Re-apply styles to cloned element
                    applyPDFStyles(clonedElement, a4ContentWidth);
                }
                applyPDFStylesToClone(clonedDoc, a4ContentWidth);
            }
        },
        jsPDF: { 
            unit: 'mm', 
            format: 'a4', 
            orientation: 'portrait',
            compress: true
        }
    };

    // Log screen info and PDF options (screenInfo already declared above)
    console.log('Step 7 - Screen Information:', screenInfo);
    console.log('Step 7 - html2pdf options:', JSON.stringify(opt, null, 2));
    console.log('Step 8 - Starting html2pdf conversion...');

    // Step 8: Generate PDF with extensive error handling
    try {
        const worker = html2pdf()
            .set(opt)
            .from(element)
            .save();
        
        console.log('Step 9 - html2pdf worker created:', worker);
        
        worker
            .then(() => {
                console.log('✓✓✓ SUCCESS: PDF generated and downloaded!');
                console.log('✓ File should be in your Downloads folder');
                console.log('✓ Filename:', filename);
                console.log('=== PDF GENERATION DEBUG END (SUCCESS) ===');
                
                // Restore original styles
                element.style.display = originalDisplay;
                element.style.visibility = originalVisibility;
                element.style.opacity = originalOpacity;
                
                // Restore invoice view width
                if (invoiceView) {
                    invoiceView.style.width = originalInvoiceViewWidth;
                    invoiceView.style.maxWidth = originalInvoiceViewMaxWidth;
                    invoiceView.style.margin = originalInvoiceViewMargin;
                    invoiceView.style.position = '';
                }
                
                // Remove inline styles after PDF generation
                removePDFStyles(element);
                
                showNotification('PDF downloaded successfully! Check your Downloads folder.', 'success');
            })
            .catch((error) => {
                console.error('ERROR: PDF generation failed!');
                console.error('Error type:', error.constructor.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                console.error('Full error object:', error);
                console.log('=== PDF GENERATION DEBUG END (ERROR) ===');
                
                // Restore original styles
                element.style.display = originalDisplay;
                element.style.visibility = originalVisibility;
                element.style.opacity = originalOpacity;
                
                // Restore invoice view width
                if (invoiceView) {
                    invoiceView.style.width = originalInvoiceViewWidth;
                    invoiceView.style.maxWidth = originalInvoiceViewMaxWidth;
                    invoiceView.style.margin = originalInvoiceViewMargin;
                    invoiceView.style.position = '';
                }
                
                // Remove inline styles
                removePDFStyles(element);
                
                // Try alternative method if html2pdf fails
                console.log('Attempting alternative PDF generation method...');
                tryAlternativePDFGeneration(element, filename);
            });
    } catch (syncError) {
        console.error('SYNC ERROR: Exception during html2pdf setup:', syncError);
        console.error('Error type:', syncError.constructor.name);
        console.error('Error message:', syncError.message);
        console.error('Error stack:', syncError.stack);
        console.log('=== PDF GENERATION DEBUG END (SYNC ERROR) ===');
        
        // Restore original styles
        element.style.display = originalDisplay;
        element.style.visibility = originalVisibility;
        element.style.opacity = originalOpacity;
        
        // Restore invoice view width
        if (invoiceView) {
            invoiceView.style.width = originalInvoiceViewWidth;
            invoiceView.style.maxWidth = originalInvoiceViewMaxWidth;
            invoiceView.style.margin = originalInvoiceViewMargin;
            invoiceView.style.position = '';
        }
        
        // Remove inline styles
        removePDFStyles(element);
        
        showNotification('PDF generation error: ' + syncError.message, 'error');
        tryAlternativePDFGeneration(element, filename);
    }
}

// Apply inline styles for better PDF rendering
function applyPDFStyles(element, containerWidth) {
    // Invoice view width is set in downloadInvoice() for fixed A4 width
    const invoiceView = element.querySelector('.invoice-view');
    if (invoiceView) {
        // Keep padding and box-sizing, but width is set externally
        invoiceView.style.padding = '2.5rem 3rem';
        invoiceView.style.boxSizing = 'border-box';
    }
    
    // Calculate actual table width (container width minus padding)
    // Padding: 2.5rem (40px) on each side = 80px total
    const tableContentWidth = containerWidth ? (containerWidth - 80) : 638;
    
    // Ensure header uses full width
    const header = element.querySelector('.invoice-header-new');
    if (header) {
        header.style.width = '100%';
        header.style.boxSizing = 'border-box';
    }
    
    // Ensure header left and right are properly sized
    const headerLeft = element.querySelector('.header-left');
    if (headerLeft) {
        headerLeft.style.paddingRight = '2rem';
        headerLeft.style.minWidth = '0';
    }
    
    const headerRight = element.querySelector('.header-right');
    if (headerRight) {
        headerRight.style.minWidth = '280px';
        headerRight.style.flexShrink = '0';
    }
    
    // Force table layout for better alignment
    const tables = element.querySelectorAll('.invoice-items-table-new, .summary-table');
    tables.forEach(table => {
        table.style.tableLayout = 'fixed';
        table.style.width = '100%';
        table.style.maxWidth = '100%';
        table.style.borderCollapse = 'separate';
        table.style.borderSpacing = '0';
        table.style.boxSizing = 'border-box';
    });
    
    // Ensure invoice-to-content uses full width with proper gap
    const invoiceToContent = element.querySelector('.invoice-to-content');
    if (invoiceToContent) {
        invoiceToContent.style.width = '100%';
        invoiceToContent.style.gap = '4rem';
        invoiceToContent.style.padding = '1.5rem 0';
        invoiceToContent.style.boxSizing = 'border-box';
        invoiceToContent.style.alignItems = 'start';
    }
    
    // Ensure address paragraphs are properly aligned
    const addressParagraphs = element.querySelectorAll('.invoice-to-left p, .invoice-to-right p');
    addressParagraphs.forEach(p => {
        p.style.display = 'flex';
        p.style.alignItems = 'flex-start';
        p.style.margin = '0.75rem 0';
    });
    
    // Ensure bottom section uses full width with proper gap
    const bottomSection = element.querySelector('.invoice-bottom-section');
    if (bottomSection) {
        bottomSection.style.width = '100%';
        bottomSection.style.gap = '3rem';
        bottomSection.style.alignItems = 'start';
        bottomSection.style.boxSizing = 'border-box';
    }
    
    // Ensure summary table has proper width
    const summaryTable = element.querySelector('.summary-table');
    if (summaryTable) {
        summaryTable.style.width = '100%';
        summaryTable.style.maxWidth = '100%';
        summaryTable.style.minWidth = '400px';
    }
    
    // Ensure column widths are set and visible with explicit pixel widths
    const colgroups = element.querySelectorAll('colgroup');
    colgroups.forEach(colgroup => {
        colgroup.style.display = 'table-column-group';
        const cols = colgroup.querySelectorAll('col');
        cols.forEach((col, index) => {
            col.style.display = 'table-column';
            // Set explicit pixel widths based on column class for better alignment
            if (col.classList.contains('col-item')) {
                col.style.width = Math.round(tableContentWidth * 0.15) + 'px';
            } else if (col.classList.contains('col-description')) {
                col.style.width = Math.round(tableContentWidth * 0.30) + 'px';
            } else if (col.classList.contains('col-price')) {
                col.style.width = Math.round(tableContentWidth * 0.18) + 'px';
            } else if (col.classList.contains('col-qty')) {
                col.style.width = Math.round(tableContentWidth * 0.12) + 'px';
            } else if (col.classList.contains('col-total')) {
                col.style.width = Math.round(tableContentWidth * 0.25) + 'px';
            }
        });
    });
    
    // Also set explicit widths on table cells for better alignment
    const table = element.querySelector('.invoice-items-table-new');
    if (table) {
        const headerCells = table.querySelectorAll('thead th');
        headerCells.forEach(th => {
            if (th.classList.contains('col-item')) {
                th.style.width = Math.round(tableContentWidth * 0.15) + 'px';
            } else if (th.classList.contains('col-description')) {
                th.style.width = Math.round(tableContentWidth * 0.30) + 'px';
            } else if (th.classList.contains('col-price')) {
                th.style.width = Math.round(tableContentWidth * 0.18) + 'px';
            } else if (th.classList.contains('col-qty')) {
                th.style.width = Math.round(tableContentWidth * 0.12) + 'px';
            } else if (th.classList.contains('col-total')) {
                th.style.width = Math.round(tableContentWidth * 0.25) + 'px';
            }
        });
        
        const bodyCells = table.querySelectorAll('tbody td');
        bodyCells.forEach(td => {
            if (td.classList.contains('col-item')) {
                td.style.width = Math.round(tableContentWidth * 0.15) + 'px';
            } else if (td.classList.contains('col-description')) {
                td.style.width = Math.round(tableContentWidth * 0.30) + 'px';
            } else if (td.classList.contains('col-price')) {
                td.style.width = Math.round(tableContentWidth * 0.18) + 'px';
            } else if (td.classList.contains('col-qty')) {
                td.style.width = Math.round(tableContentWidth * 0.12) + 'px';
            } else if (td.classList.contains('col-total')) {
                td.style.width = Math.round(tableContentWidth * 0.25) + 'px';
            }
        });
    }
    
    // Force header background colors
    const headers = element.querySelectorAll('.invoice-items-table-new thead');
    headers.forEach(header => {
        header.style.backgroundColor = '#1e3a8a';
        header.style.background = '#1e3a8a';
    });
    
    const headerCells = element.querySelectorAll('.invoice-items-table-new th');
    headerCells.forEach(th => {
        th.style.backgroundColor = '#1e3a8a';
        th.style.color = '#ffffff';
        th.style.padding = '16px 12px';
        th.style.textAlign = th.classList.contains('col-price') || 
                            th.classList.contains('col-qty') || 
                            th.classList.contains('col-total') ? 'right' : 'left';
        th.style.whiteSpace = 'nowrap';
        th.style.overflow = 'visible';
        th.style.display = 'table-cell';
    });
    
    // Ensure all table cells are visible and have proper styling
    const allTableCells = element.querySelectorAll('.invoice-items-table-new td');
    allTableCells.forEach(td => {
        // Set default padding if not set
        if (!td.style.padding) {
            td.style.padding = '16px 12px';
        }
        // Ensure visibility
        td.style.display = 'table-cell';
        td.style.overflow = 'visible';
        td.style.minWidth = '0';
    });
    
    // Ensure table cells have proper alignment and prevent text wrapping
    const priceCells = element.querySelectorAll('.invoice-items-table-new td.col-price, .invoice-items-table-new td.col-qty, .invoice-items-table-new td.col-total');
    priceCells.forEach(td => {
        td.style.textAlign = 'right';
        td.style.padding = '16px 12px';
        td.style.whiteSpace = 'nowrap'; // Prevent text wrapping
        td.style.wordBreak = 'keep-all'; // Keep numbers together
        td.style.overflow = 'visible'; // Allow content to be visible
        td.style.display = 'table-cell';
    });
    
    // Specifically ensure Item and Description columns are visible and properly separated
    const itemCells = element.querySelectorAll('.invoice-items-table-new td.col-item, .invoice-items-table-new th.col-item');
    itemCells.forEach(cell => {
        cell.style.display = 'table-cell !important';
        cell.style.visibility = 'visible !important';
        cell.style.overflow = 'visible';
        cell.style.textOverflow = 'clip';
        cell.style.whiteSpace = 'normal';
        cell.style.minWidth = Math.round(tableContentWidth * 0.15) + 'px';
        cell.style.maxWidth = Math.round(tableContentWidth * 0.15) + 'px';
        cell.style.width = Math.round(tableContentWidth * 0.15) + 'px';
        cell.style.paddingRight = '12px';
        cell.style.paddingLeft = '12px';
        cell.style.borderRight = '1px solid #e2e8f0';
    });
    
    const descCells = element.querySelectorAll('.invoice-items-table-new td.col-description, .invoice-items-table-new th.col-description');
    descCells.forEach(cell => {
        cell.style.display = 'table-cell !important';
        cell.style.visibility = 'visible !important';
        cell.style.overflow = 'visible';
        cell.style.wordBreak = 'break-word';
        cell.style.whiteSpace = 'normal';
        cell.style.minWidth = Math.round(tableContentWidth * 0.30) + 'px';
        cell.style.maxWidth = Math.round(tableContentWidth * 0.30) + 'px';
        cell.style.width = Math.round(tableContentWidth * 0.30) + 'px';
        cell.style.paddingLeft = '12px';
        cell.style.paddingRight = '12px';
        cell.style.borderRight = '1px solid #e2e8f0';
    });
    
    // Ensure all currency values don't wrap - check all cells
    const allCells = element.querySelectorAll('.invoice-items-table-new td, .summary-table td');
    allCells.forEach(td => {
        const text = (td.textContent || '').trim();
        // Check if cell contains currency symbol or decimal number
        if (text.includes('₹') || text.includes('$') || text.includes('€') || 
            text.match(/[\d,]+\.\d{2}/) || text.match(/^[\d,]+\.\d{2}$/)) {
            td.style.whiteSpace = 'nowrap';
            td.style.wordBreak = 'keep-all';
            td.style.overflow = 'visible';
        }
    });
    
    // Also ensure summary table cells don't wrap
    const summaryCells = element.querySelectorAll('.summary-table td:last-child');
    summaryCells.forEach(td => {
        td.style.whiteSpace = 'nowrap';
        td.style.wordBreak = 'keep-all';
        td.style.overflow = 'visible';
    });
    
    // Ensure grand total row styling
    const grandTotalRows = element.querySelectorAll('.summary-table .grand-total-row');
    grandTotalRows.forEach(row => {
        row.style.backgroundColor = '#1e3a8a';
        row.style.background = '#1e3a8a';
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            cell.style.color = '#ffffff';
            cell.style.fontWeight = '700';
        });
    });
    
    // Force banner colors
    const banners = element.querySelectorAll('.invoice-to-banner');
    banners.forEach(banner => {
        banner.style.backgroundColor = '#1e3a8a';
        banner.style.color = '#ffffff';
        banner.style.padding = '12px 20px';
    });
    
    // Force total box styling
    const totalBoxes = element.querySelectorAll('.total-parallelogram');
    totalBoxes.forEach(box => {
        box.style.backgroundColor = '#1e3a8a';
        box.style.padding = '1.25rem 2rem';
        box.style.borderRadius = '8px';
        const labels = box.querySelectorAll('.total-label, .total-amount');
        labels.forEach(label => {
            label.style.color = '#ffffff';
        });
    });
}

// Remove inline styles after PDF generation
function removePDFStyles(element) {
    // Remove inline styles from tables
    const tables = element.querySelectorAll('.invoice-items-table-new, .summary-table');
    tables.forEach(table => {
        table.style.tableLayout = '';
        table.style.width = '';
        table.style.borderCollapse = '';
    });
    
    // Remove inline styles from cells
    const cells = element.querySelectorAll('th, td');
    cells.forEach(cell => {
        cell.style.backgroundColor = '';
        cell.style.color = '';
        cell.style.padding = '';
        cell.style.textAlign = '';
    });
    
    // Remove inline styles from banners and boxes
    const banners = element.querySelectorAll('.invoice-to-banner, .total-parallelogram');
    banners.forEach(banner => {
        banner.style.backgroundColor = '';
        banner.style.color = '';
        banner.style.padding = '';
    });
}

// Apply styles to cloned document (for html2canvas)
function applyPDFStylesToClone(clonedDoc, containerWidth) {
    const clonedElement = clonedDoc.getElementById('invoice-view-content');
    if (clonedElement) {
        applyPDFStyles(clonedElement, containerWidth);
        
        // Calculate table width for cloned document
        const tableContentWidth = containerWidth ? (containerWidth - 80) : 638;
        
        // Add additional stylesheet to cloned document
        const style = clonedDoc.createElement('style');
        style.textContent = `
            .invoice-view {
                box-sizing: border-box !important;
                margin: 0 auto !important;
            }
            .invoice-items-table-new {
                table-layout: fixed !important;
                width: 100% !important;
                max-width: 100% !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
                box-sizing: border-box !important;
            }
            .invoice-items-table-new th.col-item,
            .invoice-items-table-new td.col-item {
                display: table-cell !important;
                visibility: visible !important;
                width: ${Math.round(tableContentWidth * 0.15)}px !important;
                min-width: ${Math.round(tableContentWidth * 0.15)}px !important;
                max-width: ${Math.round(tableContentWidth * 0.15)}px !important;
                overflow: visible !important;
                white-space: normal !important;
                padding: 16px 12px !important;
            }
            .invoice-items-table-new th.col-description,
            .invoice-items-table-new td.col-description {
                display: table-cell !important;
                visibility: visible !important;
                width: ${Math.round(tableContentWidth * 0.30)}px !important;
                min-width: ${Math.round(tableContentWidth * 0.30)}px !important;
                max-width: ${Math.round(tableContentWidth * 0.30)}px !important;
                overflow: visible !important;
                word-break: break-word !important;
                white-space: normal !important;
                padding: 16px 12px !important;
            }
            .invoice-items-table-new colgroup {
                display: table-column-group !important;
            }
            .invoice-items-table-new colgroup col {
                display: table-column !important;
            }
            .invoice-items-table-new colgroup col.col-item {
                width: ${Math.round(tableContentWidth * 0.15)}px !important;
            }
            .invoice-items-table-new colgroup col.col-description {
                width: ${Math.round(tableContentWidth * 0.30)}px !important;
            }
            .invoice-items-table-new colgroup col.col-price {
                width: ${Math.round(tableContentWidth * 0.18)}px !important;
            }
            .invoice-items-table-new colgroup col.col-qty {
                width: ${Math.round(tableContentWidth * 0.12)}px !important;
            }
            .invoice-items-table-new colgroup col.col-total {
                width: ${Math.round(tableContentWidth * 0.25)}px !important;
            }
            .invoice-items-table-new th.col-item,
            .invoice-items-table-new td.col-item {
                width: ${Math.round(tableContentWidth * 0.15)}px !important;
            }
            .invoice-items-table-new th.col-description,
            .invoice-items-table-new td.col-description {
                width: ${Math.round(tableContentWidth * 0.30)}px !important;
            }
            .invoice-items-table-new th.col-price,
            .invoice-items-table-new td.col-price {
                width: ${Math.round(tableContentWidth * 0.18)}px !important;
            }
            .invoice-items-table-new th.col-qty,
            .invoice-items-table-new td.col-qty {
                width: ${Math.round(tableContentWidth * 0.12)}px !important;
            }
            .invoice-items-table-new th.col-total,
            .invoice-items-table-new td.col-total {
                width: ${Math.round(tableContentWidth * 0.25)}px !important;
            }
            .invoice-items-table-new th {
                padding: 16px 12px !important;
                white-space: nowrap !important;
                display: table-cell !important;
                overflow: visible !important;
            }
            .invoice-items-table-new td {
                padding: 16px 12px !important;
                display: table-cell !important;
                overflow: visible !important;
            }
            .invoice-items-table-new td.col-item,
            .invoice-items-table-new th.col-item {
                display: table-cell !important;
                visibility: visible !important;
            }
            .invoice-items-table-new td.col-description,
            .invoice-items-table-new th.col-description {
                display: table-cell !important;
                visibility: visible !important;
            }
            .summary-table {
                min-width: 400px !important;
            }
            .summary-table td {
                padding: 14px 18px !important;
            }
            .invoice-to-content {
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .invoice-bottom-section {
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .summary-table {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .invoice-items-table-new colgroup col {
                display: table-column !important;
            }
            .invoice-items-table-new thead {
                background: #1e3a8a !important;
                background-color: #1e3a8a !important;
            }
            .invoice-items-table-new th {
                background: #1e3a8a !important;
                background-color: #1e3a8a !important;
                color: #ffffff !important;
                padding: 14px 16px !important;
            }
            .invoice-items-table-new td.col-price,
            .invoice-items-table-new td.col-qty,
            .invoice-items-table-new td.col-total {
                text-align: right !important;
                padding: 14px 16px !important;
                white-space: nowrap !important;
                word-break: keep-all !important;
            }
            .summary-table td {
                white-space: nowrap !important;
                word-break: keep-all !important;
            }
            .summary-table td:last-child {
                text-align: right !important;
            }
            .summary-table .grand-total-row {
                background: #1e3a8a !important;
                background-color: #1e3a8a !important;
            }
            .summary-table .grand-total-row td {
                color: #ffffff !important;
            }
            .invoice-to-banner {
                background: #1e3a8a !important;
                background-color: #1e3a8a !important;
                color: #ffffff !important;
            }
            .total-parallelogram {
                background: #1e3a8a !important;
                background-color: #1e3a8a !important;
            }
            .total-label,
            .total-amount {
                color: #ffffff !important;
            }
            .amount-in-words {
                margin-top: 1.5rem !important;
                padding: 1rem 1.5rem !important;
                background: #f8fafc !important;
                border-radius: 6px !important;
                border-left: 3px solid #1e3a8a !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .amount-in-words p {
                margin: 0 !important;
                color: #1e293b !important;
                font-size: 0.9rem !important;
                line-height: 1.6 !important;
            }
            .amount-in-words strong {
                color: #1e3a8a !important;
                font-weight: 600 !important;
            }
        `;
        clonedDoc.head.appendChild(style);
    }
}

// Alternative PDF generation method
function tryAlternativePDFGeneration(element, filename) {
    console.log('=== ALTERNATIVE PDF METHOD START ===');
    
    // Method 1: Try with minimal options
    console.log('Trying minimal html2pdf options...');
    const minimalOpt = {
        margin: 0,
        filename: filename,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 1,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: true
        },
        jsPDF: { 
            unit: 'mm', 
            format: 'a4', 
            orientation: 'portrait'
        }
    };
    
    html2pdf()
        .set(minimalOpt)
        .from(element)
        .save()
        .then(() => {
            console.log('SUCCESS: Alternative method worked!');
            showNotification('PDF downloaded successfully (alternative method)!', 'success');
        })
        .catch((altError) => {
            console.error('Alternative method also failed:', altError);
            console.error('Error details:', {
                message: altError.message,
                stack: altError.stack,
                name: altError.name
            });
            
            // Last resort: Try with absolute minimal config
            console.log('Trying absolute minimal configuration...');
            try {
                html2pdf()
                    .set({
                        margin: 1,
                        filename: filename
                    })
                    .from(element)
                    .save()
                    .then(() => {
                        console.log('SUCCESS: Minimal config worked!');
                        showNotification('PDF downloaded successfully!', 'success');
                    })
                    .catch((minError) => {
                        console.error('Even minimal config failed:', minError);
                        showNotification('PDF generation failed completely. Error: ' + minError.message, 'error');
                        console.log('=== ALTERNATIVE PDF METHOD END (ALL FAILED) ===');
                    });
            } catch (finalError) {
                console.error('Fatal error in PDF generation:', finalError);
                showNotification('PDF generation error. Please check console (F12) and report the error.', 'error');
                console.log('=== ALTERNATIVE PDF METHOD END (FATAL ERROR) ===');
            }
        });
}

// Diagnostic function to test html2pdf.js
function testHtml2Pdf() {
    console.log('=== HTML2PDF DIAGNOSTIC TEST ===');
    
    // Check html2pdf availability
    const html2pdfAvailable = typeof html2pdf !== 'undefined';
    console.log('html2pdf available:', html2pdfAvailable);
    
    // Note: html2canvas and jsPDF are bundled inside html2pdf.js
    // They don't need to be global - this is normal!
    console.log('Note: html2canvas and jsPDF are bundled inside html2pdf.js');
    console.log('They don\'t need to be global variables - this is expected behavior.');
    
    if (!html2pdfAvailable) {
        console.error('✗ html2pdf.js is not loaded!');
        console.error('Check if script tag is present and CDN is accessible');
        console.log('=== END DIAGNOSTIC TEST (FAILED) ===');
        return;
    }
    
    // Test html2pdf API
    console.log('Testing html2pdf API...');
    try {
        const worker = html2pdf();
        console.log('✓ html2pdf() function works');
        console.log('✓ html2pdf().set:', typeof worker.set === 'function' ? 'Available' : 'Missing');
        console.log('✓ html2pdf().from:', typeof worker.from === 'function' ? 'Available' : 'Missing');
        console.log('✓ html2pdf().save:', typeof worker.save === 'function' ? 'Available' : 'Missing');
        
        // Test with a simple div
        const testDiv = document.createElement('div');
        testDiv.innerHTML = `
            <div style="padding: 20px; background: white; font-family: Arial;">
                <h1 style="color: #1e3a8a;">Test PDF</h1>
                <p>If you see this PDF, html2pdf.js is working correctly!</p>
                <p>This confirms that html2canvas and jsPDF are working internally.</p>
            </div>
        `;
        testDiv.style.position = 'absolute';
        testDiv.style.left = '-9999px';
        document.body.appendChild(testDiv);
        
        console.log('Generating test PDF...');
        console.log('This may take a few seconds...');
        
        html2pdf()
            .set({
                margin: 10,
                filename: 'html2pdf-test.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff'
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait'
                }
            })
            .from(testDiv)
            .save()
            .then(() => {
                console.log('✓✓✓ SUCCESS: Test PDF generated and downloaded!');
                console.log('✓ This confirms html2pdf.js is working correctly');
                console.log('✓ html2canvas is working internally');
                console.log('✓ jsPDF is working internally');
                document.body.removeChild(testDiv);
                console.log('=== END DIAGNOSTIC TEST (SUCCESS) ===');
            })
            .catch((err) => {
                console.error('✗✗✗ FAILED: Test PDF generation failed');
                console.error('Error type:', err.constructor.name);
                console.error('Error message:', err.message);
                console.error('Error stack:', err.stack);
                console.error('Full error:', err);
                document.body.removeChild(testDiv);
                console.log('=== END DIAGNOSTIC TEST (FAILED) ===');
                
                // Provide troubleshooting tips
                console.log('\n=== TROUBLESHOOTING TIPS ===');
                if (err.message.includes('CORS')) {
                    console.log('CORS Error: Try serving from same origin or configure CORS');
                }
                if (err.message.includes('canvas')) {
                    console.log('Canvas Error: Check browser compatibility (Chrome/Firefox recommended)');
                }
                if (err.message.includes('memory') || err.message.includes('size')) {
                    console.log('Memory/Size Error: Content might be too large, try with smaller content');
                }
            });
    } catch (syncError) {
        console.error('✗✗✗ SYNC ERROR: Exception during test setup');
        console.error('Error:', syncError);
        console.log('=== END DIAGNOSTIC TEST (SYNC ERROR) ===');
    }
}

// Make test function available globally for debugging
window.testHtml2Pdf = testHtml2Pdf;

function shareInvoice() {
    if (navigator.share) {
        navigator.share({
            title: `Invoice ${currentInvoiceId}`,
            text: 'Please find the invoice attached',
            url: window.location.href
        }).catch(err => console.log('Error sharing:', err));
    } else {
        // Fallback: copy to clipboard or show share options
        const invoiceUrl = `${window.location.origin}${window.location.pathname}?invoice=${currentInvoiceId}`;
        navigator.clipboard.writeText(invoiceUrl).then(() => {
            showNotification('Invoice link copied to clipboard', 'success');
        });
    }
}

async function deleteInvoice(id) {
    if (!confirm('Are you sure you want to delete this invoice? This will restore product quantities.')) return;
    
    try {
        const response = await fetch(`${API_BASE}/invoices.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Invoice deleted successfully', 'success');
            loadInvoices();
            if (document.getElementById('dashboard-page').style.display !== 'none') {
                loadDashboard();
            }
        } else {
            showNotification(result.error || 'Error deleting invoice', 'error');
        }
    } catch (error) {
        console.error('Error deleting invoice:', error);
        showNotification('Error deleting invoice', 'error');
    }
}

// Settings
async function loadSettings() {
    try {
        const response = await fetch(`${API_BASE}/settings.php`);
        const settings = await response.json();
        
        document.getElementById('setting-shop-name').value = settings.shop_name || '';
        document.getElementById('setting-shop-phone').value = settings.shop_phone || '';
        document.getElementById('setting-shop-email').value = settings.shop_email || '';
        document.getElementById('setting-shop-address').value = settings.shop_address || '';
        document.getElementById('setting-shop-city').value = settings.shop_city || '';
        document.getElementById('setting-shop-state').value = settings.shop_state || '';
        document.getElementById('setting-shop-pincode').value = settings.shop_pincode || '';
        document.getElementById('setting-shop-gstin').value = settings.shop_gstin || '';
        document.getElementById('setting-tax-rate').value = settings.tax_rate || '18';
        document.getElementById('setting-invoice-prefix').value = settings.invoice_prefix || 'INV-';
    } catch (error) {
        console.error('Error loading settings:', error);
        showNotification('Error loading settings', 'error');
    }
}

async function handleSettingsSubmit(e) {
    e.preventDefault();
    
    const data = {
        shop_name: document.getElementById('setting-shop-name').value,
        shop_phone: document.getElementById('setting-shop-phone').value,
        shop_email: document.getElementById('setting-shop-email').value,
        shop_address: document.getElementById('setting-shop-address').value,
        shop_city: document.getElementById('setting-shop-city').value,
        shop_state: document.getElementById('setting-shop-state').value,
        shop_pincode: document.getElementById('setting-shop-pincode').value,
        shop_gstin: document.getElementById('setting-shop-gstin').value,
        tax_rate: document.getElementById('setting-tax-rate').value,
        invoice_prefix: document.getElementById('setting-invoice-prefix').value
    };
    
    try {
        const response = await fetch(`${API_BASE}/settings.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Settings saved successfully', 'success');
        } else {
            showNotification('Error saving settings', 'error');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showNotification('Error saving settings', 'error');
    }
}

// Utility Functions
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Convert number to words (Indian format)
function numberToWords(amount) {
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    const teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
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
        } else if (num >= 10) {
            result += teens[num - 10] + ' ';
            return result.trim();
        }
        if (num > 0) {
            result += ones[num] + ' ';
        }
        return result.trim();
    }
    
    const rupees = Math.floor(amount);
    const paise = Math.round((amount - rupees) * 100);
    
    let words = '';
    
    if (rupees === 0) {
        words = 'Zero';
    } else {
        // Crores
        if (rupees >= 10000000) {
            const crores = Math.floor(rupees / 10000000);
            words += convertHundreds(crores) + ' Crore ';
            rupees %= 10000000;
        }
        
        // Lakhs
        if (rupees >= 100000) {
            const lakhs = Math.floor(rupees / 100000);
            words += convertHundreds(lakhs) + ' Lakh ';
            rupees %= 100000;
        }
        
        // Thousands
        if (rupees >= 1000) {
            const thousands = Math.floor(rupees / 1000);
            words += convertHundreds(thousands) + ' Thousand ';
            rupees %= 1000;
        }
        
        // Hundreds, Tens, Ones
        if (rupees > 0) {
            words += convertHundreds(rupees) + ' ';
        }
        
        words = words.trim() + ' Rupees';
    }
    
    // Add paise
    if (paise > 0) {
        words += ' and ' + convertHundreds(paise) + ' Paise';
    }
    
    words += ' Only';
    
    return words.charAt(0).toUpperCase() + words.slice(1);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' });
}

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

function showNotification(message, type = 'info') {
    // Simple notification - you can enhance this with a toast library
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 3000;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS for notification animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
