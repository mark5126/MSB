<?php
session_start();
require 'functions.php';

if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        ['username' => 'user', 'password' => 'user123', 'name' => 'John Customer']
    ];
}

$loginError = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // 1. Check Administrator Credentials
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit();
    }
    
    // 2. Check Customer Credentials
    $foundCustomer = false;
    foreach ($_SESSION['users'] as $u) {
        if ($u['username'] === $username && $u['password'] === $password) {
            $foundCustomer = true;
            $_SESSION['user_logged_in'] = true;
            $_SESSION['username'] = $u['username'];
            $_SESSION['customer_name'] = $u['name'];
            break;
        }
    }
    
    if ($foundCustomer) {
        header("Location: order.php");
        exit();
    } else {
        $loginError = "Invalid username or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSB. - Portal Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <nav class="navbar">
            <h2>MSB. Portal</h2>
            <ul>
                <li><a href="register.php">Sign Up</a></li>
                <li><a href="order.php">Storefront</a></li>
            </ul>
        </nav>

        <main class="container">
            <section class="login-section">
                <div class="login-card">
                    <h2>Portal Login</h2>
                    <p>Enter your credentials below to log in as an administrator or customer.</p>
                    
                    <?php if (!empty($loginError)): ?>
                        <div class="alert-danger"><?= $loginError ?></div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" class="login-form">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required placeholder="Username">
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required placeholder="Password">
                        </div>
                        <button type="submit" name="login_submit" class="btn-primary">Log In</button>
                    </form>
                    <p class="login-hint">
                        Don't have a customer account? <a href="register.php" style="color: #38bdf8; text-decoration: none;">Register here</a>
                    </p>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <p>&copy; 2026 MSB. Retail Analytics Portal.</p>
        </footer>
    </div>
</body>
</html>