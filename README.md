# Inventory Management System

A PHP + MySQL + Bootstrap 5 web application for inventory and invoicing management.

## 📋 System Requirements

- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL/MariaDB**: 5.7 or higher
- **Web Server**: Apache (included in XAMPP/WAMP/MAMP)
- **Web Browser**: Chrome, Firefox, Edge, or Safari (latest versions)
- **XAMPP/WAMP/MAMP**: For local development

## 🚀 Installation Guide

### Step 1: Install XAMPP (Windows) / WAMP (Windows) / MAMP (Mac)

**For Windows:**
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Run the installer
3. Select components: **Apache** and **MySQL** (PHP is included)
4. Choose installation directory (default: `C:\xampp`)  
5. Complete the installation

**For Mac:**
1. Download MAMP from [https://www.mamp.info/](https://www.mamp.info/)
2. Install the application
3. Launch MAMP and start servers

### Step 2: Start XAMPP Services

1. Open **XAMPP Control Panel** (from Start Menu or Desktop)
2. Click **"Start"** button next to **Apache**
   - Wait for Apache to start (status should turn green)
3. Click **"Start"** button next to **MySQL**
   - Wait for MySQL to start (status should turn green)

**Verification:**
- Apache should show green "Running" status
- MySQL should show green "Running" status
- If ports are in use, click "Config" and change ports (default: Apache 80, MySQL 3306)

### Step 3: Place Project Files

1. **Locate your project folder**: `inventory-management-system`
2. **Copy the entire folder** to your web server directory:
   
   **For XAMPP (Windows):**
   ```
   C:\xampp\htdocs\inventory-management-system
   ```
   
   **For WAMP (Windows):**
   ```
   C:\wamp64\www\inventory-management-system
   ```
   
   **For MAMP (Mac):**
   ```
   /Applications/MAMP/htdocs/inventory-management-system
   ```

3. **Verify files are copied correctly:**
   - Check that `index.php`, `login.php`, `includes/` folder exist
   - Ensure all files are in the correct location

### Step 4: Setup Database

#### Option A: Using phpMyAdmin (Recommended for Beginners)

1. **Open phpMyAdmin:**
   - Open your web browser
   - Navigate to: `http://localhost/phpmyadmin`
   - You should see the phpMyAdmin interface

2. **Create Database:**
   - Click on **"SQL"** tab at the top
   - Open the file `database_setup.sql` in a text editor
   - **Copy ALL contents** of `database_setup.sql`
   - **Paste** into the SQL text area in phpMyAdmin
   - Click **"Go"** button at the bottom

3. **Verify Database Creation:**
   - Look for success messages (green checkmarks)
   - Click on **"inventory_db"** in the left sidebar
   - You should see tables: `users`, `products`, `customers`, `sales`, `sale_items`

#### Option B: Using MySQL Command Line

1. **Open Command Prompt (Windows) or Terminal (Mac/Linux)**

2. **Navigate to project directory:**
   ```bash
   cd C:\xampp\htdocs\inventory-management-system
   ```

3. **Run SQL file:**
   ```bash
   # Windows (XAMPP)
   C:\xampp\mysql\bin\mysql.exe -u root -p < database_setup.sql
   
   # Mac/Linux
   mysql -u root -p < database_setup.sql
   ```

4. **Enter MySQL password** (press Enter if no password set)

5. **Verify:**
   ```bash
   mysql -u root -p -e "USE inventory_db; SHOW TABLES;"
   ```

### Step 5: Configure Database Connection (If Needed)

1. **Open** `includes/db.php` in a text editor

2. **Check/Update settings:**
   ```php
   $db_host = 'localhost';      // Usually 'localhost'
   $db_name = 'inventory_db';  // Database name
   $db_user = 'root';          // MySQL username
   $db_pass = '';              // MySQL password (empty for XAMPP default)
   ```

3. **Save the file** if you made changes

### Step 6: Access the Application

1. **Open your web browser** (Chrome, Firefox, Edge, etc.)

2. **Navigate to:**
   ```
   http://localhost/inventory-management-system/
   ```
   
   **Alternative URLs:**
   - `http://localhost/inventory-management-system/index.php`
   - `http://localhost/inventory-management-system/login.php`

3. **You should see:**
   - Login page with "Login to My Business" heading
   - Username and Password fields
   - Login button

### Step 7: First Login

**Default Admin Credentials:**
- **Username:** `admin`
- **Password:** `admin123`

1. Enter the credentials above
2. Click **"Login"** button
3. You should be redirected to the **Dashboard**

### Step 8: Verify Installation

After logging in, verify these features work:

- ✅ **Dashboard loads** with statistics cards
- ✅ **Products page** accessible (click Products in navigation if available)
- ✅ **Customers page** accessible
- ✅ **Sales/Invoice page** accessible
- ✅ **Logout** works correctly

## 🔐 Security Recommendations

1. **Change Default Password:**
   - After first login, change the admin password immediately
   - Use a strong password (8+ characters, mixed case, numbers, symbols)

2. **Database Security:**
   - Don't use `root` user in production
   - Create a dedicated MySQL user with limited privileges
   - Use strong database passwords

3. **File Permissions:**
   - Set proper file permissions (644 for files, 755 for directories)
   - Don't expose sensitive files

## ✅ Installation Checklist

- [ ] XAMPP/WAMP/MAMP installed and running
- [ ] Apache server started (green status)
- [ ] MySQL server started (green status)
- [ ] Project files copied to `htdocs` directory
- [ ] Database created successfully
- [ ] All tables created (users, products, customers, sales, sale_items)
- [ ] Can access `http://localhost/inventory-management-system/`
- [ ] Login page loads correctly
- [ ] Can login with admin/admin123
- [ ] Dashboard displays correctly
- [ ] All pages accessible

## Project Structure

```
inventory-management-system/
├── includes/
│   ├── db.php          # Database connection
│   └── functions.php   # Helper functions
├── index.php           # Dashboard
├── login.php           # Login page
├── logout.php          # Logout handler
├── products.php        # Products management
├── save_product.php    # Save product handler
└── database_setup.sql  # Database setup script
```

## Features

- ✅ User authentication (login/logout)
- ✅ Dashboard with statistics
- ✅ Product management (Add, Edit, Delete)
- ✅ Low stock alerts
- ✅ Responsive Bootstrap 5 UI
- ✅ DataTables for product listing

## Database Configuration

If you need to change database settings, edit `includes/db.php`:

```php
$db_host = 'localhost';
$db_name = 'inventory_db';
$db_user = 'root';
$db_pass = '';  // Empty for XAMPP default
```

## 🔧 Troubleshooting

### Issue: Database Connection Error

**Symptoms:**
- Error message: "Database connection failed"
- Blank page or PHP errors

**Solutions:**
1. **Check MySQL is running:**
   - Open XAMPP Control Panel
   - Ensure MySQL shows green "Running" status
   - If not, click "Start" button

2. **Verify database exists:**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Check if `inventory_db` appears in left sidebar
   - If not, run `database_setup.sql` again

3. **Check database credentials:**
   - Open `includes/db.php`
   - Verify: `$db_name = 'inventory_db'`
   - Verify: `$db_user = 'root'`
   - Verify: `$db_pass = ''` (empty for XAMPP default)

4. **Test connection manually:**
   ```php
   // Create test.php in project root
   <?php
   require_once 'includes/db.php';
   echo "Connected successfully!";
   ?>
   ```

### Issue: Page Not Found (404 Error)

**Symptoms:**
- Browser shows "404 Not Found" or "Page cannot be found"

**Solutions:**
1. **Verify file location:**
   - Check project is in: `C:\xampp\htdocs\inventory-management-system\`
   - Ensure `index.php` exists in this folder

2. **Check Apache is running:**
   - XAMPP Control Panel should show Apache as "Running"
   - Try accessing: `http://localhost/` (should show XAMPP dashboard)

3. **Try direct file access:**
   - `http://localhost/inventory-management-system/login.php`
   - `http://localhost/inventory-management-system/index.php`

4. **Check folder name:**
   - Ensure folder name matches URL exactly (case-sensitive on some systems)
   - No spaces in folder name

### Issue: Login Not Working

**Symptoms:**
- "Invalid credentials" error
- Redirects back to login page
- Blank page after login

**Solutions:**
1. **Verify admin user exists:**
   - Open phpMyAdmin
   - Select `inventory_db` database
   - Click on `users` table
   - Check if admin user exists with username "admin"

2. **Reset admin password (if needed):**
   ```sql
   -- Run in phpMyAdmin SQL tab
   UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE username = 'admin';
   ```
   Password will be reset to: `admin123`

3. **Clear browser cache:**
   - Press `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
   - Clear cookies and cache
   - Try login again

4. **Check session:**
   - Ensure `session_start()` is called in files
   - Check PHP error logs in XAMPP

### Issue: Products/Customers Not Loading

**Symptoms:**
- Empty tables
- "No products found" message
- JavaScript errors in browser console

**Solutions:**
1. **Check database tables:**
   - Verify `products` and `customers` tables exist
   - Check if tables have data

2. **Check browser console:**
   - Press `F12` to open Developer Tools
   - Check "Console" tab for JavaScript errors
   - Check "Network" tab for failed requests

3. **Verify file permissions:**
   - Ensure PHP files are readable
   - Check `includes/db.php` is accessible

### Issue: Invoice/Sales Not Saving

**Symptoms:**
- "Error saving invoice" message
- Cart not clearing after save

**Solutions:**
1. **Check `sale_items` table exists:**
   - Run `database_setup.sql` again to ensure all tables are created

2. **Verify stock availability:**
   - Ensure products have sufficient stock
   - Check stock values in products table

3. **Check browser console:**
   - Look for JavaScript errors
   - Check Network tab for failed AJAX requests

### Issue: Port Already in Use

**Symptoms:**
- Apache/MySQL won't start
- "Port 80 already in use" error

**Solutions:**
1. **Change Apache port:**
   - XAMPP Control Panel → Config → httpd.conf
   - Change `Listen 80` to `Listen 8080`
   - Access via: `http://localhost:8080/inventory-management-system/`

2. **Change MySQL port:**
   - XAMPP Control Panel → Config → my.ini
   - Change port number
   - Update `includes/db.php` if needed

3. **Stop conflicting services:**
   - Check if Skype or other apps are using port 80
   - Stop those services or change their ports

## 📚 Additional Resources

### File Structure
```
inventory-management-system/
├── includes/
│   ├── db.php              # Database connection
│   └── functions.php       # Helper functions
├── index.php               # Dashboard
├── login.php               # Login page
├── logout.php              # Logout handler
├── products.php            # Products management
├── customers.php           # Customers management
├── sales.php               # Create invoices
├── save_product.php        # Save product handler
├── save_customer.php       # Save customer handler
├── save_invoice.php        # Save invoice handler
├── delete_product.php      # Delete product handler
├── search_products.php     # Product search API
├── database_setup.sql      # Database setup script
└── README.md               # This file
```

### Default Data

After installation, the system includes:
- **Admin User**: username `admin`, password `admin123`
- **Empty Tables**: products, customers, sales (ready for your data)

### Getting Started

1. **Add Products:**
   - Go to Products page
   - Click "Add New Product"
   - Fill in product details
   - Save

2. **Add Customers:**
   - Go to Customers page
   - Click "Add New Customer"
   - Fill in customer details
   - Save

3. **Create Invoice:**
   - Go to Sales page
   - Select a customer
   - Search and add products
   - Review invoice summary
   - Click "Save & Generate Invoice"

## 🆘 Getting Help

If you encounter issues not covered here:

1. **Check PHP Error Logs:**
   - XAMPP: `C:\xampp\php\logs\php_error_log`
   - Look for specific error messages

2. **Check MySQL Error Logs:**
   - XAMPP: `C:\xampp\mysql\data\mysql_error.log`

3. **Enable Error Display (Development Only):**
   - Add to `includes/db.php`:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

4. **Verify Requirements:**
   - PHP version: `php -v` (should be 7.4+)
   - MySQL version: Check in phpMyAdmin
   - All required PHP extensions enabled

## 📝 Notes

- This is a development version - not recommended for production without security hardening
- Always backup your database before making changes
- Keep XAMPP/WAMP/MAMP updated for security patches
- Use strong passwords in production environments
