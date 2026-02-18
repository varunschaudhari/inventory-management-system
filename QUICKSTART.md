# Quick Start Guide

Get your Inventory Management System up and running in 5 minutes!

## Step 1: Database Setup (2 minutes)

1. **Create Database**:
   ```sql
   CREATE DATABASE inventory_management;
   ```

2. **Import Schema**:
   - Open phpMyAdmin
   - Select `inventory_management` database
   - Click "Import"
   - Choose `database/schema.sql`
   - Click "Go"

## Step 2: Configure Database (1 minute)

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');    // Change this
define('DB_PASS', 'your_password');    // Change this
define('DB_NAME', 'inventory_management');
```

## Step 3: Verify Setup (1 minute)

Open in browser:
```
http://localhost/inventory/setup.php
```

This will check if everything is configured correctly.

## Step 4: Access the Application (1 minute)

**Important**: This application does NOT have a login page. You can access it directly.

1. **Open the main application** in your browser:
   ```
   http://localhost/inventory/index.html
   ```
   Or simply:
   ```
   http://localhost/inventory/
   ```

2. **Start using the system**:
   - Go to **Settings** and enter your shop details
   - Go to **Products** and add your first product
   - Go to **New Invoice** and create your first invoice!

## That's it! 🎉

You're ready to manage your inventory and generate invoices.

## Troubleshooting

### 404 Error when accessing setup.php or index.html

If you get a 404 error, it means the project is not in your web server's document root:

**Solution**: Copy the entire `inventory` folder to your web server directory:
- **XAMPP**: `C:\xampp\htdocs\inventory`
- **WAMP**: `C:\wamp\www\inventory`
- **Laragon**: `C:\laragon\www\inventory`

Or create a symbolic link (requires admin privileges):
```powershell
# Run PowerShell as Administrator
New-Item -ItemType SymbolicLink -Path "C:\xampp\htdocs\inventory" -Target "C:\Users\varun.chaudhari.CUBEHIGHWAYS\projects\inventory"
```

### No Login Page?

This application does **not** have user authentication. Simply open `http://localhost/inventory/` to access the dashboard directly.

## Need Help?

- Check `README.md` for detailed documentation
- Run `setup.php` to verify your configuration
- Check browser console (F12) for any errors

---

**Pro Tip**: Bookmark the dashboard page for quick access!
