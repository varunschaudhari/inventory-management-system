# Inventory Management System

A professional and attractive inventory management system designed for small Indian shops. This system allows shop owners to manage their inventory, generate invoices, and share them with customers.

## Features

- **Product Management**: Add, edit, and manage products with categories, stock levels, and pricing
- **Customer Management**: Maintain customer database with contact information and GST details
- **Invoice Generation**: Create professional invoices with automatic calculations
- **Invoice Sharing**: Print, download, or share invoices with customers
- **Dashboard**: Real-time statistics and overview of your business
- **Low Stock Alerts**: Get notified when products are running low
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Indian Currency Support**: Built-in support for Indian Rupee (₹) formatting

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Icons**: Font Awesome 6.4

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Installation

### Step 1: Clone or Download

Download all files to your web server directory (e.g., `htdocs`, `www`, or `public_html`).

### Step 2: Database Setup

1. Create a MySQL database:
   ```sql
   CREATE DATABASE inventory_management;
   ```

2. Import the database schema:
   - Open phpMyAdmin or MySQL command line
   - Select the `inventory_management` database
   - Import the file `database/schema.sql`

   Or via command line:
   ```bash
   mysql -u root -p inventory_management < database/schema.sql
   ```

### Step 3: Configure Database Connection

Edit `config/database.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'inventory_management');
```

### Step 4: Set Permissions

Ensure the web server has read/write permissions to the project directory.

### Step 5: Access the Application

Open your web browser and navigate to:
```
http://localhost/inventory
```
(Replace `localhost/inventory` with your actual domain/path)

## Initial Setup

1. **Configure Shop Settings**:
   - Go to Settings page
   - Enter your shop name, address, phone, email, GSTIN, etc.
   - Set default tax rate and invoice prefix
   - Click "Save Settings"

2. **Add Products**:
   - Navigate to Products page
   - Click "Add Product"
   - Fill in product details (name, code, category, quantity, price, etc.)
   - Save the product

3. **Add Customers** (Optional):
   - Go to Customers page
   - Add customer details
   - This helps in generating invoices faster

4. **Create Your First Invoice**:
   - Go to "New Invoice" page
   - Select a customer (or leave blank for walk-in)
   - Add items to the invoice
   - Review totals and create invoice

## Usage Guide

### Managing Products

- **Add Product**: Click "Add Product" button, fill in details, and save
- **Edit Product**: Click the edit icon (pencil) next to any product
- **Delete Product**: Click the delete icon (trash) - this marks the product as inactive
- **Search Products**: Use the search bar to find products by name or code
- **Filter by Category**: Use the category dropdown to filter products

### Managing Customers

- **Add Customer**: Click "Add Customer" button and fill in customer details
- **Edit Customer**: Click the edit icon next to any customer
- **Delete Customer**: Click the delete icon to remove a customer
- **Search Customers**: Use the search bar to find customers

### Creating Invoices

1. Go to "New Invoice" page
2. Select a customer (optional)
3. Set invoice date and due date (optional)
4. Click "Add Item" to add products
5. Select product and enter quantity
6. System automatically calculates totals
7. Adjust tax rate and discount if needed
8. Set payment status and method
9. Add notes (optional)
10. Click "Create Invoice"

**Note**: When an invoice is created, product quantities are automatically deducted from inventory.

### Viewing and Sharing Invoices

- **View Invoice**: Click the eye icon next to any invoice
- **Print Invoice**: Click "Print" button in the invoice view modal
- **Download Invoice**: Click "Download" button to save as PDF
- **Share Invoice**: Click "Share" button to share via email or copy link

### Dashboard

The dashboard provides:
- Total products count
- Low stock items alert
- Total customers
- Total invoices
- Total revenue (from paid invoices)
- Pending invoices count
- Recent invoices list
- Low stock products list

## File Structure

```
inventory/
├── api/                    # PHP API endpoints
│   ├── products.php
│   ├── customers.php
│   ├── invoices.php
│   ├── settings.php
│   └── stats.php
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── app.js         # Main JavaScript file
├── config/
│   └── database.php       # Database configuration
├── database/
│   └── schema.sql         # Database schema
├── index.html             # Main application file
└── README.md              # This file
```

## API Endpoints

### Products
- `GET /api/products.php` - Get all products
- `GET /api/products.php?id={id}` - Get single product
- `POST /api/products.php` - Create product
- `PUT /api/products.php` - Update product
- `DELETE /api/products.php?id={id}` - Delete product

### Customers
- `GET /api/customers.php` - Get all customers
- `GET /api/customers.php?id={id}` - Get single customer
- `POST /api/customers.php` - Create customer
- `PUT /api/customers.php` - Update customer
- `DELETE /api/customers.php?id={id}` - Delete customer

### Invoices
- `GET /api/invoices.php` - Get all invoices
- `GET /api/invoices.php?id={id}` - Get single invoice with items
- `POST /api/invoices.php` - Create invoice
- `PUT /api/invoices.php` - Update invoice
- `DELETE /api/invoices.php?id={id}` - Delete invoice

### Settings
- `GET /api/settings.php` - Get all settings
- `POST /api/settings.php` - Update settings

### Statistics
- `GET /api/stats.php` - Get dashboard statistics

## Security Notes

1. **Change Default Credentials**: Update database credentials in `config/database.php`
2. **File Permissions**: Set appropriate file permissions (644 for files, 755 for directories)
3. **HTTPS**: Use HTTPS in production for secure data transmission
4. **Input Validation**: The system includes basic validation, but consider adding more robust validation for production use
5. **SQL Injection**: The code uses prepared statements to prevent SQL injection
6. **XSS Protection**: Consider adding additional XSS protection for production

## Troubleshooting

### Database Connection Error
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database name exists

### Products Not Loading
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Check database connection

### Invoice Not Creating
- Ensure products have sufficient stock
- Check browser console for errors
- Verify all required fields are filled

### Styles Not Loading
- Check file paths in `index.html`
- Ensure CSS file exists at `assets/css/style.css`
- Clear browser cache

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Future Enhancements

Potential features for future versions:
- Barcode scanning
- Multi-user support with roles
- Advanced reporting and analytics
- Email invoice sending
- Backup and restore functionality
- Multi-currency support
- Purchase orders
- Supplier management

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review browser console for errors
3. Verify database and file permissions
4. Check PHP error logs

## License

This project is open source and available for use in small businesses.

## Credits

- Font Awesome for icons
- Modern CSS techniques for responsive design
- PHP and MySQL for backend functionality

---

**Version**: 1.0.0  
**Last Updated**: 2024
