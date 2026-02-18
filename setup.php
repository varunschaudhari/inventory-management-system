<?php
/**
 * Setup Verification Script
 * Run this file in your browser to check if your environment is properly configured
 * URL: http://localhost/inventory/setup.php
 */

// Check PHP version
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.4.0', '>=');

// Check MySQL extension
$mysqlOk = extension_loaded('mysqli');

// Check database connection
$dbOk = false;
$dbError = '';
if ($mysqlOk) {
    require_once 'config/database.php';
    try {
        $conn = getDBConnection();
        if ($conn) {
            $dbOk = true;
            closeDBConnection($conn);
        }
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

// Check if database tables exist
$tablesOk = false;
$tablesError = '';
if ($dbOk) {
    try {
        $conn = getDBConnection();
        $result = $conn->query("SHOW TABLES LIKE 'products'");
        $tablesOk = $result && $result->num_rows > 0;
        closeDBConnection($conn);
    } catch (Exception $e) {
        $tablesError = $e->getMessage();
    }
}

// Check file permissions
$configWritable = is_writable('config/database.php');
$apiReadable = is_readable('api/products.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Verification - Inventory Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .content {
            padding: 2rem;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            background: #f9fafb;
        }
        .check-item.success {
            background: #d1fae5;
            color: #065f46;
        }
        .check-item.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .check-item.warning {
            background: #fef3c7;
            color: #92400e;
        }
        .icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            width: 30px;
            text-align: center;
        }
        .check-info {
            flex: 1;
        }
        .check-info h3 {
            margin-bottom: 0.25rem;
        }
        .check-info p {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        .summary {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 0.5rem;
            text-align: center;
        }
        .summary.success {
            background: #d1fae5;
            color: #065f46;
        }
        .summary.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .summary.warning {
            background: #fef3c7;
            color: #92400e;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-weight: 500;
        }
        .btn:hover {
            background: #4338ca;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Setup Verification</h1>
            <p>Inventory Management System</p>
        </div>
        <div class="content">
            <!-- PHP Version Check -->
            <div class="check-item <?php echo $phpOk ? 'success' : 'error'; ?>">
                <div class="icon"><?php echo $phpOk ? '✓' : '✗'; ?></div>
                <div class="check-info">
                    <h3>PHP Version</h3>
                    <p>Current: <?php echo $phpVersion; ?> | Required: 7.4.0+</p>
                </div>
            </div>

            <!-- MySQL Extension Check -->
            <div class="check-item <?php echo $mysqlOk ? 'success' : 'error'; ?>">
                <div class="icon"><?php echo $mysqlOk ? '✓' : '✗'; ?></div>
                <div class="check-info">
                    <h3>MySQL Extension</h3>
                    <p><?php echo $mysqlOk ? 'mysqli extension is loaded' : 'mysqli extension is not loaded'; ?></p>
                </div>
            </div>

            <!-- Database Connection Check -->
            <div class="check-item <?php echo $dbOk ? 'success' : 'error'; ?>">
                <div class="icon"><?php echo $dbOk ? '✓' : '✗'; ?></div>
                <div class="check-info">
                    <h3>Database Connection</h3>
                    <p><?php echo $dbOk ? 'Successfully connected to database' : 'Connection failed: ' . $dbError; ?></p>
                </div>
            </div>

            <!-- Database Tables Check -->
            <div class="check-item <?php echo $tablesOk ? 'success' : ($dbOk ? 'warning' : 'error'); ?>">
                <div class="icon"><?php echo $tablesOk ? '✓' : '⚠'; ?></div>
                <div class="check-info">
                    <h3>Database Tables</h3>
                    <p><?php 
                        if ($tablesOk) {
                            echo 'Database tables exist';
                        } elseif ($dbOk) {
                            echo 'Tables not found. Please import database/schema.sql';
                        } else {
                            echo 'Cannot check tables - database connection failed';
                        }
                    ?></p>
                </div>
            </div>

            <!-- File Permissions Check -->
            <div class="check-item <?php echo ($configWritable && $apiReadable) ? 'success' : 'warning'; ?>">
                <div class="icon"><?php echo ($configWritable && $apiReadable) ? '✓' : '⚠'; ?></div>
                <div class="check-info">
                    <h3>File Permissions</h3>
                    <p><?php 
                        $issues = [];
                        if (!$configWritable) $issues[] = 'config/database.php is not writable';
                        if (!$apiReadable) $issues[] = 'API files are not readable';
                        echo empty($issues) ? 'File permissions are correct' : implode(', ', $issues);
                    ?></p>
                </div>
            </div>

            <!-- Summary -->
            <div class="summary <?php 
                if ($phpOk && $mysqlOk && $dbOk && $tablesOk) {
                    echo 'success';
                } elseif ($phpOk && $mysqlOk && $dbOk) {
                    echo 'warning';
                } else {
                    echo 'error';
                }
            ?>">
                <h2>
                    <?php 
                        if ($phpOk && $mysqlOk && $dbOk && $tablesOk) {
                            echo '✓ All checks passed! Your system is ready.';
                        } elseif ($phpOk && $mysqlOk && $dbOk) {
                            echo '⚠ Almost ready! Please import the database schema.';
                        } else {
                            echo '✗ Setup incomplete. Please fix the errors above.';
                        }
                    ?>
                </h2>
                <?php if ($phpOk && $mysqlOk && $dbOk && $tablesOk): ?>
                    <a href="index.html" class="btn">Go to Application</a>
                <?php elseif ($phpOk && $mysqlOk && $dbOk): ?>
                    <p style="margin-top: 1rem;">Import the SQL file: <code>database/schema.sql</code></p>
                <?php endif; ?>
            </div>

            <!-- Instructions -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: #f3f4f6; border-radius: 0.5rem;">
                <h3 style="margin-bottom: 1rem;">Setup Instructions:</h3>
                <ol style="line-height: 2; padding-left: 1.5rem;">
                    <li>Update database credentials in <code>config/database.php</code></li>
                    <li>Create MySQL database: <code>inventory_management</code></li>
                    <li>Import <code>database/schema.sql</code> into your database</li>
                    <li>Refresh this page to verify setup</li>
                    <li>Access the application at <code>index.html</code></li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
