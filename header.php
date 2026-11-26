
<?php
// header.php - Reusable layout header
// This file contains the common HTML head section and navigation bar.
// It can be included in different PHP pages to maintain a consistent layout.
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Defines the character encoding to support special characters -->
    <meta charset="UTF-8">

    <!-- Makes the website responsive on different screen sizes and devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Sets the title displayed on the browser tab -->
    <title>MSB. - Sales & Performance BI Dashboard</title>

    <!-- Connects the external CSS file for website styling -->
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Main wrapper that contains the website's content -->
    <div class="wrapper">

        <!-- Navigation bar containing the system name and navigation links -->
        <nav class="navbar">

            <!-- Displays the system name in the navigation bar -->
            <h2>MSB. Business Intelligence</h2>

            <!-- List of navigation links -->
            <ul>

                <!-- Redirects users to the Storefront Portal -->
                <li><a href="order.php">Storefront Portal</a></li>

                <?php
                // Checks whether an administrator is currently logged in.
                // The session must contain admin_logged_in with a value of true.
                if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true):
                ?>

                    <!-- Displays the Admin Dashboard link for logged-in administrators -->
                    <li><a href="index.php">Admin Dashboard</a></li>

                    <!-- Provides a link to log out the administrator -->
                    <li><a href="index.php?logout=true" class="logout-link">Admin Logout</a></li>

                <?php else: ?>

                    <!-- Displays the Login link if no administrator is logged in -->
                    <li><a href="login.php">Login</a></li>

                <?php endif; ?>

            </ul>
        </nav>
