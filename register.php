<?php
session_start();
require 'functions.php';

if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        ['username' => 'user', 'password' => 'user123', 'name' => 'John Customer']
    ];
}

$registerSuccess = "";
$registerError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_register'])) {
    $newRegUser = trim($_POST['reg_username']);
    $newRegPass = trim($_POST['reg_password']);
    $newRegConfirmPass = trim($_POST['reg_confirm_password']);
    $newRegName = trim($_POST['reg_name']);

    if (!empty($newRegUser) && !empty($newRegPass) && !empty($newRegConfirmPass) && !empty($newRegName)) {
        if ($newRegPass !== $newRegConfirmPass) {
            $registerError = "Passwords do not match. Please try again.";
        } else {
            $exists = false;
            foreach ($_SESSION['users'] as $u) {
                if ($u['username'] === $newRegUser) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                $registerError = "Username is already taken. Please choose another.";
            } else {
                $_SESSION['users'][] = [
                    'username' => $newRegUser,
                    'password' => $newRegPass,
                    'name' => $newRegName
                ];
                $registerSuccess = "Registration successful! You can now log in.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSB. - Customer Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <nav class="navbar">
            <h2>MSB. Storefront</h2>
            <ul>
                <li><a href="login.php">Customer Login</a></li>
                <li><a href="order.php">Storefront</a></li>
            </ul>
        </nav>

        <main class="container">
            <section class="login-section">
                <div class="login-card">
                    <h2>New Customer Sign Up</h2>
                    <p>Create a new account to start placing retail orders.</p>

                    <?php if (!empty($registerError)): ?>
                        <div class="alert-danger"><?= $registerError ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($registerSuccess)): ?>
                        <div class="alert-success" style="padding: 0.75rem; margin-bottom: 1rem;">
                            <?= $registerSuccess ?> <a href="login.php" style="color: white; font-weight: bold; text-decoration: underline;">Log In Now</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" class="login-form">
                        <div class="form-group">
                            <label for="reg_name">Full Name:</label>
                            <input type="text" id="reg_name" name="reg_name" reaquired placeholder="Full Name">
                        </div>
                        <div class="form-group">
                            <label for="reg_username">Choose Username:</label>
                            <input type="text" id="reg_username" name="reg_username" required placeholder="Username">
                        </div>
                        <div class="form-group">
                            <label for="reg_password">Choose Password:</label>
                            <input type="password" id="reg_password" name="reg_password" required placeholder="Password">
                        </div>
                        <div class="form-group">
                            <label for="reg_confirm_password">Confirm Password:</label>
                            <input type="password" id="reg_confirm_password" name="reg_confirm_password" required placeholder="Confirm password">
                        </div>
                        <button type="submit" name="user_register" class="btn-primary" style="background: linear-gradient(135deg, #06b6d4, #3b82f6);">Register Account</button>
                    </form>
                    <p class="login-hint">
                        Already have an account? <a href="login.php" style="color: #38bdf8; text-decoration: none;">Log in here</a>
                    </p>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <p>&copy; 2026 MSB. Retail Analytics - Customer Portal.</p>
        </footer>
    </div>
</body>
</html>