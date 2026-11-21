<?php
// header.php - Reusable layout header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSB. - Sales & Performance BI Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <nav class="navbar">
            <h2>MSB. Business Intelligence</h2>
            <ul>
                <li><a href="order.php">Storefront Portal</a></li>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <li><a href="index.php">Admin Dashboard</a></li>
                    <li><a href="index.php?logout=true" class="logout-link">Admin Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>