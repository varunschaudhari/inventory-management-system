<?php
/**
 * Helper Functions File
 * Contains utility functions for the inventory management system
 */

require_once __DIR__ . '/db.php';

/**
 * Format number as Indian Rupee (INR) with proper formatting
 * 
 * @param float|int $num The number to format
 * @return string Formatted string like '₹1,23,456.00'
 */
function format_inr($num): string {
    return '₹' . number_format((float)$num, 2, '.', ',');
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user_id is set in session, false otherwise
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login page if user is not logged in
 * 
 * @return void Exits script after redirect
 */
function redirect_if_not_logged(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Calculate tax amounts (CGST, SGST, IGST) and total tax
 * 
 * @param float $subtotal The subtotal amount before tax
 * @param float $cgst_rate CGST rate as percentage (e.g., 9 for 9%)
 * @param float $sgst_rate SGST rate as percentage (e.g., 9 for 9%)
 * @param float $igst_rate IGST rate as percentage (default: 0)
 * @return array Array with 'cgst', 'sgst', 'igst', and 'total_tax' keys
 */
function calc_tax(float $subtotal, float $cgst_rate, float $sgst_rate, float $igst_rate = 0): array {
    $cgst = ($subtotal * $cgst_rate) / 100;
    $sgst = ($subtotal * $sgst_rate) / 100;
    $igst = ($subtotal * $igst_rate) / 100;
    $total_tax = $cgst + $sgst + $igst;
    
    return [
        'cgst' => $cgst,
        'sgst' => $sgst,
        'igst' => $igst,
        'total_tax' => $total_tax
    ];
}
