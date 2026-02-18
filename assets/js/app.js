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
            document.getElementById('sidebar').classList.toggle('active');
        });
    }
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
    document.getElementById('invoice-status-filter')?.addEventListener('change', loadInvoices);
}

// Dashboard
async function loadDashboard() {
    try {
        const response = await fetch(`${API_BASE}/stats.php`);
        const stats = await response.json();
        
        document.getElementById('stat-products').textContent = stats.total_products || 0;
        document.getElementById('stat-low-stock').textContent = stats.low_stock_products || 0;
        document.getElementById('stat-customers').textContent = stats.total_customers || 0;
        document.getElementById('stat-invoices').textContent = stats.total_invoices || 0;
        document.getElementById('stat-revenue').textContent = formatCurrency(stats.total_revenue || 0);
        document.getElementById('stat-pending').textContent = stats.pending_invoices || 0;
        
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
async function loadProducts() {
    try {
        const search = document.getElementById('product-search')?.value || '';
        const category = document.getElementById('category-filter')?.value || '';
        
        let url = `${API_BASE}/products.php`;
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (category) params.append('category', category);
        if (params.toString()) url += '?' + params.toString();
        
        const response = await fetch(url);
        products = await response.json();
        
        displayProducts(products);
        updateCategoryFilter(products);
    } catch (error) {
        console.error('Error loading products:', error);
        showNotification('Error loading products', 'error');
    }
}

function displayProducts(products) {
    const tbody = document.getElementById('products-table-body');
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-light);">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map(product => `
        <tr>
            <td>${product.product_code || 'N/A'}</td>
            <td><strong>${product.product_name}</strong></td>
            <td>${product.category || 'N/A'}</td>
            <td>${product.quantity} ${product.unit}</td>
            <td>${formatCurrency(product.unit_price)}</td>
            <td>${getStockStatusBadge(product)}</td>
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
    if (product.quantity <= product.min_stock_level) {
        return '<span class="badge badge-danger">Low Stock</span>';
    } else if (product.quantity === 0) {
        return '<span class="badge badge-danger">Out of Stock</span>';
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
        
        if (id) data.id = parseInt(id);
        
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
    document.getElementById('invoice-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('invoice-items-body').innerHTML = '';
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
    
    try {
        const response = await fetch(`${API_BASE}/invoices.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Invoice created successfully', 'success');
            navigateToPage('invoices');
        } else {
            showNotification(result.error || 'Error creating invoice', 'error');
        }
    } catch (error) {
        console.error('Error creating invoice:', error);
        showNotification('Error creating invoice', 'error');
    }
}

// View Invoice
async function viewInvoice(id) {
    try {
        const response = await fetch(`${API_BASE}/invoices.php?id=${id}`);
        const invoice = await response.json();
        
        currentInvoiceId = id;
        displayInvoiceView(invoice);
        document.getElementById('invoiceViewModal').classList.add('active');
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
                    <td>${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td>${formatCurrency(item.unit_price)}</td>
                    <td>${formatCurrency(item.total_price)}</td>
                </tr>
            `).join('');
            
            container.innerHTML = `
                <div class="invoice-view">
                    <div class="invoice-header">
                        <div class="invoice-shop-info">
                            <h2>${settings.shop_name || 'My Shop'}</h2>
                            <p>${settings.shop_address || ''}</p>
                            <p>${settings.shop_city || ''}${settings.shop_state ? ', ' + settings.shop_state : ''} ${settings.shop_pincode || ''}</p>
                            <p>Phone: ${settings.shop_phone || ''}</p>
                            ${settings.shop_gstin ? `<p>GSTIN: ${settings.shop_gstin}</p>` : ''}
                        </div>
                        <div class="invoice-number">
                            <h3>INVOICE</h3>
                            <p><strong>Invoice #:</strong> ${invoice.invoice_number}</p>
                            <p><strong>Date:</strong> ${formatDate(invoice.invoice_date)}</p>
                            ${invoice.due_date ? `<p><strong>Due Date:</strong> ${formatDate(invoice.due_date)}</p>` : ''}
                        </div>
                    </div>
                    
                    <div class="invoice-details">
                        <div class="invoice-bill-to">
                            <h4>Bill To:</h4>
                            <p><strong>${invoice.customer_name || 'Walk-in Customer'}</strong></p>
                            ${invoice.address ? `<p>${invoice.address}</p>` : ''}
                            ${invoice.city ? `<p>${invoice.city}${invoice.state ? ', ' + invoice.state : ''} ${invoice.pincode || ''}</p>` : ''}
                            ${invoice.phone ? `<p>Phone: ${invoice.phone}</p>` : ''}
                            ${invoice.gstin ? `<p>GSTIN: ${invoice.gstin}</p>` : ''}
                        </div>
                        <div class="invoice-info">
                            <h4>Payment Information:</h4>
                            <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(invoice.payment_status)}">${invoice.payment_status}</span></p>
                            ${invoice.payment_method ? `<p><strong>Method:</strong> ${invoice.payment_method}</p>` : ''}
                        </div>
                    </div>
                    
                    <table class="invoice-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                    
                    <div class="invoice-totals">
                        <table>
                            <tr>
                                <td>Subtotal:</td>
                                <td>${formatCurrency(invoice.subtotal)}</td>
                            </tr>
                            ${invoice.tax_rate > 0 ? `
                            <tr>
                                <td>Tax (${invoice.tax_rate}%):</td>
                                <td>${formatCurrency(invoice.tax_amount)}</td>
                            </tr>
                            ` : ''}
                            ${invoice.discount > 0 ? `
                            <tr>
                                <td>Discount:</td>
                                <td>-${formatCurrency(invoice.discount)}</td>
                            </tr>
                            ` : ''}
                            <tr class="total-row">
                                <td><strong>Total:</strong></td>
                                <td><strong>${formatCurrency(invoice.total_amount)}</strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    ${invoice.notes ? `
                    <div class="invoice-footer">
                        <p><strong>Notes:</strong> ${invoice.notes}</p>
                    </div>
                    ` : ''}
                    
                    <div class="invoice-footer">
                        <p>Thank you for your business!</p>
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
    const content = document.getElementById('invoice-view-content').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 2rem; }
                .invoice-view { max-width: 800px; margin: 0 auto; }
                ${document.querySelector('style')?.textContent || ''}
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

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
