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
        body {
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .preview-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1e3a8a;
        }
        .preview-header h1 {
            margin: 0;
            color: #1e3a8a;
        }
        .preview-actions {
            display: flex;
            gap: 10px;
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

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
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
                    <td class="col-item"><strong>${item.product_name || 'N/A'}</strong></td>
                    <td class="col-description">${item.product_description || '-'}</td>
                    <td class="col-price">${formatCurrency(item.unit_price)}</td>
                    <td class="col-qty">${item.quantity}</td>
                    <td class="col-total"><strong>${formatCurrency(item.total_price)}</strong></td>
                </tr>
            `).join('');
            
            container.innerHTML = `
                <div class="invoice-view">
                    <!-- Header Section -->
                    <div class="invoice-header-new">
                        <div class="header-left">
                            <div class="company-logo">
                                <div class="logo-placeholder">
                                    <span>${(shopSettings['shop_name'] || 'My Shop').charAt(0).toUpperCase()}</span>
                                </div>
                                <h2 class="company-name">${shopSettings['shop_name'] || 'My Shop'}</h2>
                            </div>
                            <div class="invoice-meta">
                                <p><strong>Invoice No:</strong> ${invoiceData.invoice_number}</p>
                                <p><strong>Invoice Date:</strong> ${formatDate(invoiceData.invoice_date)}</p>
                            </div>
                        </div>
                        <div class="header-right">
                            <div class="contact-blocks">
                                <div class="contact-block">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <p>${shopSettings['shop_email'] || 'email@example.com'}</p>
                                    </div>
                                </div>
                                <div class="contact-block">
                                    <i class="fas fa-phone"></i>
                                    <div>
                                        <p>${shopSettings['shop_phone'] || '+91-000-000-0000'}</p>
                                        <p style="font-size: 0.8rem; color: #64748b;">Monday to Friday</p>
                                    </div>
                                </div>
                                <div class="contact-block">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <p>${shopSettings['shop_address'] || 'Address'}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="total-parallelogram">
                                <div class="total-label">TOTAL</div>
                                <div class="total-amount">${formatCurrency(invoiceData.total_amount)}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoice To Section -->
                    <div class="invoice-to-section">
                        <div class="invoice-to-banner">INVOICE TO</div>
                        <div class="invoice-to-content">
                            <div class="invoice-to-left">
                                <p><strong>Name:</strong> ${invoiceData.customer_name || 'Walk-in Customer'}</p>
                                <p><strong>Email:</strong> ${invoiceData.email || '-'}</p>
                            </div>
                            <div class="invoice-to-right">
                                <p><strong>Phone:</strong> ${invoiceData.phone || '-'}</p>
                                <p><strong>Address:</strong> ${invoiceData.address || '-'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <table class="invoice-items-table-new">
                        <colgroup>
                            <col class="col-item">
                            <col class="col-description">
                            <col class="col-price">
                            <col class="col-qty">
                            <col class="col-total">
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
                    
                    <!-- Bottom Section: Summary and Payment Info -->
                    <div class="invoice-bottom-section">
                        <div class="payment-info-section">
                            <h4>PAYMENT INFO:</h4>
                            <div class="payment-details">
                                <p class="payment-method"><strong>Payment Method:</strong> ${invoiceData.payment_method || 'Cash'}</p>
                            </div>
                        </div>
                        <div class="summary-section">
                            <table class="summary-table">
                                <tr>
                                    <td>Subtotal:</td>
                                    <td>${formatCurrency(invoiceData.subtotal)}</td>
                                </tr>
                                ${invoiceData.discount > 0 ? `
                                <tr class="discount-row">
                                    <td>Discount ${invoiceData.discount_percent || ''}%:</td>
                                    <td>-${formatCurrency(invoiceData.discount)}</td>
                                </tr>
                                ` : ''}
                                ${invoiceData.tax_rate > 0 ? `
                                <tr>
                                    <td>Tax ${invoiceData.tax_rate}%:</td>
                                    <td>+${formatCurrency(invoiceData.tax_amount)}</td>
                                </tr>
                                ` : ''}
                                <tr class="grand-total-row">
                                    <td><strong>Grand Total:</strong></td>
                                    <td><strong>${formatCurrency(invoiceData.total_amount)}</strong></td>
                                </tr>
                            </table>
                            <div class="amount-in-words">
                                <p><strong>Amount in Words:</strong> ${numberToWords(invoiceData.total_amount)}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer Section -->
                    <div class="invoice-footer-new">
                        <div class="footer-left">
                            <p class="thank-you">Thank you for your business!</p>
                            <p class="terms">We appreciate your trust and look forward to serving you again.</p>
                        </div>
                        <div class="footer-right">
                            <div class="signature-section">
                                <p class="signature-label">Authorized Signature</p>
                                <p class="signature-name">${shopSettings['shop_name'] || 'Shop Owner'}</p>
                            </div>
                        </div>
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

            html2pdf().set(opt).from(element).save();
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
