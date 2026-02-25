<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// Check authentication
requireLogin();

// Get invoice ID from query parameter
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$invoice_id) {
    die('Invalid invoice ID');
}

// Fetch invoice data
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT i.*, 
           c.customer_name, c.email, 
           c.phone, c.address
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    WHERE i.id = ?
");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    die('Invoice not found');
}

// Fetch invoice items
$stmt = $conn->prepare("
    SELECT ii.*, 
           COALESCE(p.product_name, ii.product_name) as product_name,
           p.description as product_description
    FROM invoice_items ii
    LEFT JOIN products p ON ii.product_id = p.id
    WHERE ii.invoice_id = ?
    ORDER BY ii.id
");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    // Ensure product_name is always available
    if (empty($row['product_name'])) {
        $row['product_name'] = 'N/A';
    }
    $items[] = $row;
}

// Fetch shop settings
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$conn->close();

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?> - Preview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            overflow-x: hidden;
        }
        .preview-container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1e3a8a;
            flex-wrap: wrap;
            gap: 10px;
        }
        .preview-header h1 {
            margin: 0;
            color: #1e3a8a;
        }
        .preview-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: #1e3a8a;
            color: white;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        #invoice-view-content {
            width: 100%;
            overflow: hidden;
            max-width: 100%;
        }
        .invoice-view {
            width: 100% !important;
            max-width: 100% !important;
            padding: 1.5rem 2rem !important;
            margin: 0 !important;
            overflow: hidden;
            box-sizing: border-box;
        }
        .invoice-header-new {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
            flex-wrap: wrap;
        }
        .header-left {
            flex: 1;
            min-width: 200px;
            max-width: 100%;
        }
        .header-right {
            flex: 0 0 auto;
            min-width: 250px;
            max-width: 100%;
        }
        .invoice-items-table-new {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            overflow: hidden;
            box-sizing: border-box;
        }
        .invoice-items-table-new colgroup col.col-item {
            width: 18% !important;
            min-width: 0 !important;
        }
        .invoice-items-table-new colgroup col.col-description {
            width: 35% !important;
            min-width: 0 !important;
        }
        .invoice-items-table-new colgroup col.col-price {
            width: 15% !important;
            min-width: 0 !important;
        }
        .invoice-items-table-new colgroup col.col-qty {
            width: 10% !important;
            min-width: 0 !important;
        }
        .invoice-items-table-new colgroup col.col-total {
            width: 22% !important;
            min-width: 0 !important;
        }
        .invoice-items-table-new th,
        .invoice-items-table-new td {
            padding: 12px 8px !important;
            font-size: 0.85rem !important;
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .invoice-items-table-new td.col-description {
            word-break: break-word;
            white-space: normal;
        }
        .invoice-to-content {
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            width: 100%;
            max-width: 100%;
        }
        .invoice-bottom-section {
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            width: 100%;
            max-width: 100%;
        }
        .summary-table {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
        @media (max-width: 768px) {
            .preview-container {
                padding: 15px;
            }
            .invoice-view {
                padding: 1rem 1.5rem !important;
            }
            .invoice-header-new {
                flex-direction: column;
            }
            .header-right {
                width: 100%;
                align-items: flex-start !important;
            }
            .invoice-to-content,
            .invoice-bottom-section {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .invoice-items-table-new {
                font-size: 0.75rem;
            }
            .invoice-items-table-new th,
            .invoice-items-table-new td {
                padding: 8px 6px !important;
            }
        }
        @media print {
            body {
                padding: 0;
                background: white;
            }
            .preview-container {
                max-width: 100%;
                padding: 0;
                box-shadow: none;
            }
            .preview-header {
                display: none;
            }
            .invoice-actions,
            .action-icon-btn {
                display: none !important;
            }
            .invoice-view {
                padding: 1.5rem 2rem !important;
                page-break-inside: avoid;
            }
            .invoice-header-new {
                page-break-inside: avoid;
            }
            .invoice-items-table-new {
                page-break-inside: avoid;
            }
            .invoice-bottom-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <h1>Invoice Preview</h1>
            <div class="preview-actions">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-primary" onclick="downloadPDF()">
                    <i class="fas fa-download"></i> Download PDF
                </button>
                <button class="btn btn-secondary" onclick="window.close()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
        
        <div id="invoice-view-content">
            <div style="padding: 2rem; text-align: center;">
                <p>Loading invoice...</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Invoice data from PHP
        const invoiceData = <?php echo json_encode($invoice ?: []); ?>;
        const invoiceItems = <?php echo json_encode($items ?: []); ?>;
        const shopSettings = <?php echo json_encode($settings ?: []); ?>;
        
        // Debug: Log data
        console.log('Invoice Preview - Data loaded:', {
            hasInvoiceData: !!invoiceData && Object.keys(invoiceData).length > 0,
            hasItems: invoiceItems && invoiceItems.length > 0,
            hasSettings: !!shopSettings && Object.keys(shopSettings).length > 0,
            invoiceId: invoiceData?.id || 'N/A',
            itemCount: invoiceItems?.length || 0
        });

        // Format currency
        function formatCurrency(amount) {
            return '₹' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        // Format date (DD.MM.YYYY format)
        function formatDate(dateString) {
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}.${month}.${year}`;
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
            
            let rupees = Math.floor(amount);
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

        // Display invoice
        function displayInvoice() {
            try {
                console.log('Displaying invoice...', { invoiceData, invoiceItems, shopSettings });
                
                const container = document.getElementById('invoice-view-content');
                if (!container) {
                    console.error('Container element not found!');
                    return;
                }
                
                if (!invoiceData || Object.keys(invoiceData).length === 0) {
                    console.error('Invoice data is missing or empty!', invoiceData);
                    container.innerHTML = '<div style="padding: 2rem; text-align: center; color: red;"><h3>Error: Invoice data not found</h3><p>Please check if the invoice ID is correct.</p></div>';
                    return;
                }
                
                // Ensure invoiceItems is an array
                const safeItems = Array.isArray(invoiceItems) ? invoiceItems : [];
                
                const itemsHtml = safeItems.map(item => `
                <tr>
                    <td class="col-item">${item.product_name || 'N/A'}</td>
                    <td class="col-description">${item.product_description || '-'}</td>
                    <td class="col-price">${formatCurrency(item.unit_price)}</td>
                    <td class="col-qty">${item.quantity}</td>
                    <td class="col-total">${formatCurrency(item.total_price)}</td>
                </tr>
            `).join('');
            
            container.innerHTML = `
                <div class="invoice-view">
                    <!-- Top Header Section -->
                    <div class="invoice-top-header">
                        <div class="header-left">
                            <div class="company-logo-section">
                                <div class="logo-placeholder">
                                    <span>${(shopSettings['shop_name'] || 'My Shop').charAt(0).toUpperCase()}</span>
                                </div>
                                <div class="company-info-wrapper">
                                    <h2 class="company-name">${shopSettings['shop_name'] || 'My Shop'}</h2>
                                    <div class="payment-method-section">
                                        <span class="payment-method-label">Payment Method:</span>
                                        <span class="payment-method-value">${invoiceData.payment_method || 'Cash'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="header-right">
                            <div class="invoice-banner-wrapper">
                                <div class="invoice-banner">
                                    <h1 class="invoice-title-banner">INVOICE</h1>
                                    <div class="invoice-actions">
                                        <button class="action-icon-btn" onclick="window.print()" title="Print">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button class="action-icon-btn" onclick="downloadPDF()" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="invoice-meta-bar">
                                <span class="meta-item-left">Invoice No: #${invoiceData.invoice_number}</span>
                                <span class="meta-item-right">Date: ${formatDate(invoiceData.invoice_date)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoice To / Pay To Section -->
                    <div class="invoice-payto-section">
                        <div class="invoice-to-column">
                            <h3 class="section-title">Invoice To:</h3>
                            <div class="address-block">
                                <p class="address-name">${invoiceData.customer_name || 'Walk-in Customer'}</p>
                                ${invoiceData.address ? `<p class="address-line">${invoiceData.address}</p>` : ''}
                                ${invoiceData.email ? `<p class="address-line">${invoiceData.email}</p>` : ''}
                            </div>
                        </div>
                        <div class="pay-to-column">
                            <h3 class="section-title">Pay To:</h3>
                            <div class="address-block">
                                <p class="address-name">${shopSettings['shop_name'] || 'My Shop'}</p>
                                ${shopSettings['shop_address'] ? `<p class="address-line">${shopSettings['shop_address']}</p>` : ''}
                                ${shopSettings['shop_email'] ? `<p class="address-line">${shopSettings['shop_email']}</p>` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <div class="table-wrapper">
                        <table class="invoice-items-table-new">
                            <colgroup>
                                <col class="col-item" style="width: 18%;">
                                <col class="col-description" style="width: 42%;">
                                <col class="col-price" style="width: 15%;">
                                <col class="col-qty" style="width: 10%;">
                                <col class="col-total" style="width: 15%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="col-item">Item</th>
                                    <th class="col-description">Description</th>
                                    <th class="col-price">Price</th>
                                    <th class="col-qty">Qty</th>
                                    <th class="col-total">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Bottom Section: Summary -->
                    <div class="invoice-bottom-section">
                        <div class="summary-section">
                            <table class="summary-table">
                                <tr>
                                    <td class="summary-label">Subtotal</td>
                                    <td class="summary-value">${formatCurrency(invoiceData.subtotal)}</td>
                                </tr>
                                ${invoiceData.discount > 0 ? `
                                <tr class="discount-row">
                                    <td class="summary-label">Discount ${invoiceData.discount_percent || ''}%</td>
                                    <td class="summary-value discount-value">-${formatCurrency(invoiceData.discount)}</td>
                                </tr>
                                ` : ''}
                                ${invoiceData.tax_rate > 0 ? `
                                <tr>
                                    <td class="summary-label">Tax (${invoiceData.tax_rate}%)</td>
                                    <td class="summary-value">+${formatCurrency(invoiceData.tax_amount)}</td>
                                </tr>
                                ` : ''}
                                <tr class="grand-total-row">
                                    <td class="summary-label">Grand Total</td>
                                    <td class="summary-value">${formatCurrency(invoiceData.total_amount)}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Signature Section -->
                    <div class="signature-section">
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <p class="signature-name">${shopSettings['shop_name'] || 'Shop Owner'}</p>
                            <p class="signature-role">Accounts Manager</p>
                        </div>
                    </div>
                    
                    <!-- Terms & Conditions Footer -->
                    <div class="terms-footer">
                        <p class="terms-title"><strong>Terms & Conditions:</strong></p>
                        <p class="terms-text">All claims relating to quantity or shipping errors shall be waived by Buyer unless made in writing to Seller within thirty (30) days after delivery of goods to the address stated.</p>
                    </div>
                </div>
            `;
                
                console.log('Invoice displayed successfully');
            } catch (error) {
                console.error('Error displaying invoice:', error);
                const container = document.getElementById('invoice-view-content');
                if (container) {
                    container.innerHTML = `
                        <div style="padding: 2rem; text-align: center; color: red;">
                            <h3>Error Loading Invoice</h3>
                            <p>${error.message}</p>
                            <p style="font-size: 0.9rem; margin-top: 1rem;">Please check the console for details.</p>
                        </div>
                    `;
                }
            }
        }

        // Download PDF function
        function downloadPDF() {
            const element = document.getElementById('invoice-view-content');
            if (!element) {
                alert('Invoice content not found');
                return;
            }

            if (typeof html2pdf === 'undefined') {
                alert('PDF library not loaded. Please refresh the page.');
                return;
            }

            // Hide action buttons before PDF generation
            const actionButtons = element.querySelectorAll('.invoice-actions, .action-icon-btn');
            actionButtons.forEach(btn => {
                btn.style.display = 'none';
            });

            const filename = `Invoice_${invoiceData.invoice_number}.pdf`;
            
            const opt = {
                margin: [10, 10, 10, 10],
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    letterRendering: true,
                    backgroundColor: '#ffffff'
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait',
                    compress: true
                }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                // Restore buttons after PDF generation
                actionButtons.forEach(btn => {
                    btn.style.display = '';
                });
            });
        }

        // Load invoice on page load
        if (document.readyState === 'loading') {
            window.addEventListener('DOMContentLoaded', displayInvoice);
        } else {
            // DOM already loaded
            displayInvoice();
        }
        
        // Also try after a short delay as fallback
        setTimeout(() => {
            const container = document.getElementById('invoice-view-content');
            if (container && (!container.innerHTML || container.innerHTML.trim().length < 100)) {
                console.log('Retrying invoice display...');
                displayInvoice();
            }
        }, 500);
    </script>
</body>
</html>
