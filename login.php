<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$warning_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
    
    // Get and trim inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $warning_message = 'Please fill in all fields.';
    } else {
        try {
            // Prepare SELECT query for users table
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $row = $stmt->fetch();
            
            // Verify password if user exists
            if ($row && password_verify($password, $row['password'])) {
                // Set session and redirect
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                header('Location: index.php');
                exit();
            } else {
                $error_message = 'Invalid credentials.';
            }
        } catch (PDOException $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - My Business</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        .login-card {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-lg-5 mx-auto">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4">Login to My Business</h3>
                        
                        <!-- Error Alert -->
                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Warning Alert -->
                        <?php if (!empty($warning_message)): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($warning_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form method="POST" action="login.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username" 
                                    placeholder="Enter your username"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    required
                                    autofocus
                                >
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Enter your password"
                                    required
                                >
                            </div>
                            
                            <div class="mb-3 text-end">
                                <a href="#" class="text-decoration-none">Forgot password?</a>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery 3.7 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
