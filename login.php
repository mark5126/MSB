
<?php
// login.php - Handles administrator and customer authentication.

// Starts a session to store login information and user data.
session_start();

// Imports reusable functions from functions.php.
require 'functions.php';


// ==========================================
// INITIALIZE CUSTOMER ACCOUNTS
// ==========================================

// Checks whether the users session variable exists.
if (!isset($_SESSION['users'])) {

    // Creates a default customer account if no users are registered.
    $_SESSION['users'] = [
        ['username' => 'user', 'password' => 'user123', 'name' => 'John Customer']
    ];
}


// ==========================================
// USER LOGIN AUTHENTICATION
// ==========================================

// Initializes an empty variable for displaying login errors.
$loginError = "";

// Checks whether the login form was submitted using the POST method.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {

    // Retrieves and removes unnecessary spaces from the submitted username and password.
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);


    // ==========================================
    // ADMINISTRATOR LOGIN
    // ==========================================

    // Checks whether the entered credentials match the administrator account.
    if ($username === 'admin' && $password === 'admin123') {

        // Creates a session indicating that the administrator is logged in.
        $_SESSION['admin_logged_in'] = true;

        // Redirects the administrator to the main dashboard.
        header("Location: index.php");
        exit();
    }


    // ==========================================
    // CUSTOMER LOGIN
    // ==========================================

    // Initializes a variable to determine whether a matching customer account exists.
    $foundCustomer = false;

    // Loops through all registered customer accounts.
    foreach ($_SESSION['users'] as $u) {

        // Compares the submitted username and password with the stored account information.
        if ($u['username'] === $username && $u['password'] === $password) {

            // Sets the customer login verification to true.
            $foundCustomer = true;

            // Creates a session indicating that the customer is logged in.
            $_SESSION['user_logged_in'] = true;

            // Stores the customer's username in the session.
            $_SESSION['username'] = $u['username'];

            // Stores the customer's full name in the session.
            $_SESSION['customer_name'] = $u['name'];

            // Stops searching after finding a matching customer account.
            break;
        }
    }


    // ==========================================
    // LOGIN RESULT
    // ==========================================

    // Checks whether the customer credentials are valid.
    if ($foundCustomer) {

        // Redirects the customer to the Storefront Portal.
        header("Location: order.php");
        exit();

    } else {

        // Displays an error message if the credentials are incorrect.
        $loginError = "Invalid username or password. Please try again.";
    }
}

?>


<!-- ==========================================
     HTML DOCUMENT STRUCTURE
========================================== -->

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Defines the character encoding for the webpage. -->
    <meta charset="UTF-8">

    <!-- Makes the login page responsive on different screen sizes. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Sets the browser tab title. -->
    <title>MSB. - Portal Login</title>

    <!-- Connects the external CSS file for website styling. -->
    <link rel="stylesheet" href="style.css">

</head>

<body>

    <!-- Main wrapper containing the navigation, login form, and footer. -->
    <div class="wrapper">


        <!-- ==========================================
             NAVIGATION BAR
        ========================================== -->

        <!-- Displays the system name and navigation links. -->
        <nav class="navbar">

            <h2>MSB. Portal</h2>

            <ul>

                <!-- Directs new customers to the registration page. -->
                <li><a href="register.php">Sign Up</a></li>

                <!-- Directs users to the Storefront Portal. -->
                <li><a href="order.php">Storefront</a></li>

            </ul>

        </nav>


        <!-- ==========================================
             LOGIN FORM SECTION
        ========================================== -->

        <!-- Main container for the portal login interface. -->
        <main class="container">

            <section class="login-section">

                <div class="login-card">

                    <!-- Displays the login page heading. -->
                    <h2>Portal Login</h2>

                    <!-- Provides instructions for administrator and customer login. -->
                    <p>Enter your credentials below to log in as an administrator or customer.</p>


                    <!-- ==========================================
                         LOGIN ERROR MESSAGE
                    ========================================== -->

                    <!-- Checks whether a login error message exists. -->
                    <?php if (!empty($loginError)): ?>

                        <!-- Displays the error message when login credentials are incorrect. -->
                        <div class="alert-danger"><?= $loginError ?></div>

                    <?php endif; ?>


                    <!-- ==========================================
                         LOGIN INPUT FORM
                    ========================================== -->

                    <!-- Sends the entered login information to login.php using POST. -->
                    <form method="POST" action="login.php" class="login-form">

                        <!-- Username input field. -->
                        <div class="form-group">

                            <label for="username">Username:</label>

                            <input type="text" id="username" name="username" required placeholder="Username">

                        </div>


                        <!-- Password input field. -->
                        <div class="form-group">

                            <label for="password">Password:</label>

                            <input type="password" id="password" name="password" required placeholder="Password">

                        </div>


                        <!-- Submits the login credentials for verification. -->
                        <button type="submit" name="login_submit" class="btn-primary">Log In</button>

                    </form>


                    <!-- Provides a registration link for customers without an account. -->
                    <p class="login-hint">
                        Don't have a customer account?
                        <a href="register.php" style="color: #38bdf8; text-decoration: none;">Register here</a>
                    </p>

                </div>

            </section>

        </main>


        <!-- ==========================================
             WEBSITE FOOTER
        ========================================== -->

        <!-- Displays the copyright information of the system. -->
        <footer class="site-footer">

            <p>&copy; 2026 MSB. Retail Analytics Portal.</p>

        </footer>

    </div>

</body>
</html>
