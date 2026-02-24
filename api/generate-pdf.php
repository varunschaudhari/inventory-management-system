<?php
/**
 * PDF Generation Endpoint using DomPDF
 * Generates professional PDF invoices with proper CSS rendering
 */

require_once '../config/database.php';

// Get invoice ID from request
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$invoice_id) {
    die('Invoice ID required');
}

// Fetch invoice data
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT i.*, 
           c.name as customer_name, c.email, c.phone, c.address, c.city, c.state, c.pincode
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
    SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id
");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Fetch shop settings
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
$stmt->execute();
$result = $stmt->get_result();
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

closeDBConnection($conn);

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Generate HTML for PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12pt;
            color: #1e293b;
            line-height: 1.6;
            background: #ffffff;
        }
        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
            background: #ffffff;
        }
        .invoice-header {
            width: 100%;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3b82f6;
            overflow: hidden;
        }
        .header-container {
            display: table;
            width: 100%;
        }
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
            padding-right: 20px;
        }
        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 10px;
        }
        .invoice-meta {
            margin-top: 15px;
        }
        .invoice-meta p {
            margin: 5px 0;
            color: #334155;
            font-size: 11pt;
        }
        .invoice-meta strong {
            color: #1e293b;
            font-weight: bold;
        }
        .total-box {
            background: #dbeafe;
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
            display: inline-block;
            margin-top: 10px;
        }
        .total-label {
            font-size: 10pt;
            color: #1e3a8a;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .total-amount {
            font-size: 20pt;
            font-weight: bold;
            color: #1e3a8a;
        }
        .invoice-to-section {
            margin-bottom: 25px;
        }
        .invoice-to-banner {
            background: #dbeafe;
            color: #1e3a8a;
            padding: 10px 20px;
            font-weight: bold;
            font-size: 10pt;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .invoice-to-content {
            display: table;
            width: 100%;
        }
        .invoice-to-left,
        .invoice-to-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .invoice-to-left p,
        .invoice-to-right p {
            margin: 8px 0;
            color: #334155;
            font-size: 11pt;
        }
        .invoice-to-left strong,
        .invoice-to-right strong {
            color: #1e293b;
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
        }
        .invoice-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            table-layout: fixed;
        }
        .invoice-items-table colgroup {
            width: 100%;
        }
        .invoice-items-table col.col-item {
            width: 25%;
        }
        .invoice-items-table col.col-description {
            width: 35%;
        }
        .invoice-items-table col.col-price {
            width: 15%;
        }
        .invoice-items-table col.col-qty {
            width: 10%;
        }
        .invoice-items-table col.col-total {
            width: 15%;
        }
        .invoice-items-table thead {
            background: #1e3a8a !important;
        }
        .invoice-items-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            color: #ffffff !important;
            font-size: 10pt;
            text-transform: uppercase;
            border: none;
            background: #1e3a8a !important;
        }
        .invoice-items-table th.col-price,
        .invoice-items-table th.col-qty,
        .invoice-items-table th.col-total {
            text-align: right;
        }
        .invoice-items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 11pt;
            word-wrap: break-word;
        }
        .invoice-items-table tbody tr:nth-child(even) {
            background: #f8fafc !important;
        }
        .invoice-items-table td.col-price,
        .invoice-items-table td.col-qty,
        .invoice-items-table td.col-total {
            text-align: right;
        }
        .invoice-items-table tbody tr:last-child td {
            border-bottom: 2px solid #cbd5e1;
        }
        .invoice-items-table td strong {
            color: #1e293b;
            font-weight: bold;
        }
        .invoice-bottom {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .payment-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            background: #f0f9ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .payment-info h4 {
            color: #1e3a8a;
            font-size: 10pt;
            font-weight: bold;
            margin: 0 0 15px 0;
            text-transform: uppercase;
        }
        .payment-info p {
            margin: 8px 0;
            color: #334155;
            font-size: 11pt;
        }
        .payment-info strong {
            color: #1e293b;
            font-weight: bold;
        }
        .summary-section {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .summary-table {
            width: 100%;
            max-width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .summary-table tr {
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-table td {
            padding: 10px 15px;
            font-size: 11pt;
            color: #334155;
        }
        .summary-table td:first-child {
            text-align: left;
            color: #64748b;
            font-weight: 500;
        }
        .summary-table td:last-child {
            text-align: right;
            font-weight: bold;
            color: #1e293b;
        }
        .summary-table .grand-total-row {
            background: #1e3a8a;
            border: none;
        }
        .summary-table .grand-total-row td {
            color: #ffffff;
            font-weight: bold;
            font-size: 13pt;
            padding: 15px;
        }
        .invoice-footer {
            display: table;
            width: 100%;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        .footer-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .footer-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }
        .thank-you {
            color: #3b82f6;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 10px;
        }
        .terms {
            color: #64748b;
            font-size: 10pt;
            line-height: 1.6;
        }
        .signature-section {
            text-align: right;
        }
        .signature-label {
            font-size: 9pt;
            color: #94a3b8;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .signature-name {
            font-weight: bold;
            color: #1e293b;
            margin: 5px 0;
            font-size: 11pt;
        }
        .signature-role {
            color: #64748b;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="header-container">
                <div class="header-left">
                    <div class="company-name"><?php echo htmlspecialchars($settings['shop_name'] ?? 'My Shop'); ?></div>
                    <div class="invoice-meta">
                        <p><strong>Invoice No:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                        <p><strong>Invoice Date:</strong> <?php echo formatDate($invoice['invoice_date']); ?></p>
                        <?php if ($invoice['due_date']): ?>
                        <p><strong>Due Date:</strong> <?php echo formatDate($invoice['due_date']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="header-right">
                    <div class="total-box">
                        <div class="total-label">Total</div>
                        <div class="total-amount"><?php echo formatCurrency($invoice['total_amount']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice To -->
        <div class="invoice-to-section">
            <div class="invoice-to-banner">Invoice To</div>
            <div class="invoice-to-content">
                <div class="invoice-to-left">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($invoice['customer_name'] ?? 'Walk-in Customer'); ?></p>
                    <?php if ($invoice['email']): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($invoice['email']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="invoice-to-right">
                    <?php if ($invoice['phone']): ?>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($invoice['phone']); ?></p>
                    <?php endif; ?>
                    <?php if ($invoice['address'] || $invoice['city']): ?>
                    <p><strong>Address:</strong> <?php 
                        $addressParts = array_filter([
                            $invoice['address'],
                            $invoice['city'],
                            $invoice['state'],
                            $invoice['pincode']
                        ]);
                        echo htmlspecialchars(implode(', ', $addressParts));
                    ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="invoice-items-table">
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
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="col-item"><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                    <td class="col-description">-</td>
                    <td class="col-price"><?php echo formatCurrency($item['unit_price']); ?></td>
                    <td class="col-qty"><?php echo $item['quantity']; ?></td>
                    <td class="col-total"><strong><?php echo formatCurrency($item['total_price']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Bottom Section -->
        <div class="invoice-bottom">
            <div class="payment-info">
                <h4>Payment Info:</h4>
                <p><?php echo htmlspecialchars($invoice['customer_name'] ?? 'Customer'); ?></p>
                <p><?php echo htmlspecialchars($invoice['payment_method'] ?: 'Cash'); ?></p>
                <p><strong>Amount:</strong> <?php echo formatCurrency($invoice['total_amount']); ?></p>
            </div>
            <div class="summary-section">
                <table class="summary-table">
                    <tr>
                        <td>Subtotal:</td>
                        <td><?php echo formatCurrency($invoice['subtotal']); ?></td>
                    </tr>
                    <?php if ($invoice['discount'] > 0): ?>
                    <tr>
                        <td>Discount:</td>
                        <td style="color: #dc2626;">-<?php echo formatCurrency($invoice['discount']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($invoice['tax_amount'] > 0): ?>
                    <tr>
                        <td>Tax (<?php echo $invoice['tax_rate']; ?>%):</td>
                        <td>+<?php echo formatCurrency($invoice['tax_amount']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="grand-total-row">
                        <td><strong>Grand Total:</strong></td>
                        <td><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="footer-left">
                <p class="thank-you">Thank you for your business!</p>
                <p class="terms">Invoice was created on a computer and is valid without the signature and seal.</p>
            </div>
            <div class="footer-right">
                <div class="signature-section">
                    <p class="signature-label">Signature</p>
                    <p class="signature-name"><?php echo htmlspecialchars($settings['shop_name'] ?? 'Shop Owner'); ?></p>
                    <p class="signature-role">Accounts Manager</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Always output as PDF - use improved HTML that works better for print
// Set proper headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Invoice_' . $invoice['invoice_number'] . '.pdf"');

// Check if DomPDF is available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        if (class_exists('Dompdf\Dompdf')) {
            // Use DomPDF - best quality
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->set_option('isRemoteEnabled', true);
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->render();
            
            $filename = 'Invoice_' . $invoice['invoice_number'] . '.pdf';
            $dompdf->stream($filename, ['Attachment' => 1]);
            exit;
        }
    } catch (Exception $e) {
        // DomPDF failed, use fallback
        error_log('DomPDF error: ' . $e->getMessage());
    }
}

// Fallback: Output optimized HTML for browser print-to-PDF
header('Content-Type: text/html; charset=utf-8');
echo $html;
echo '<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
</script>';
?>
