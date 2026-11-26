
<?php
// Start the session so the page can access customer information,
// products, and transactions stored during the user's session.
session_start();

// Load reusable functions such as formatCurrency().
require 'functions.php';


// =====================================================
// USER LOGOUT
// =====================================================

// Check if the user clicked the Logout link.
if (isset($_GET['user_logout'])) {

    // Remove the customer's login information from the session.
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['username']);
    unset($_SESSION['customer_name']);

    // Redirect the customer to the login page.
    header("Location: login.php");
    exit();
}


// Check whether a customer is currently logged in.
$isUserLoggedIn = isset($_SESSION['user_logged_in']) &&
                  $_SESSION['user_logged_in'] === true;


// =====================================================
// INITIALIZE TRANSACTIONS
// =====================================================

// Create sample transactions if no transaction records exist.
if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [
        ['id' => 1, 'customer_name' => 'John Customer', 'product' => 'Ergonomic Desk', 'category' => 'Furniture', 'price' => 8500.00, 'qty' => 4, 'status' => 'Completed'],
        ['id' => 2, 'customer_name' => 'John Customer', 'product' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => 1250.50, 'qty' => 20, 'status' => 'Completed'],
        ['id' => 3, 'customer_name' => 'John Customer', 'product' => 'Standing Mat', 'category' => 'Furniture', 'price' => 2200.00, 'qty' => 8, 'status' => 'Processing'],
        ['id' => 4, 'customer_name' => 'John Customer', 'product' => 'USB-C Hub', 'category' => 'Electronics', 'price' => 1850.00, 'qty' => 15, 'status' => 'Pending'],
        ['id' => 5, 'customer_name' => 'John Customer', 'product' => 'Notebook Set', 'category' => 'Supplies', 'price' => 350.00, 'qty' => 30, 'status' => 'Completed']
    ];
}


// Make sure every existing transaction has a customer name.
// This also handles older transaction records without that field.
foreach ($_SESSION['transactions'] as &$existingTxn) {
    if (!isset($existingTxn['customer_name']) ||
        trim($existingTxn['customer_name']) === '') {

        $existingTxn['customer_name'] = 'John Customer';
    }
}

// Remove the reference created by the foreach loop.
unset($existingTxn);


// =====================================================
// INITIALIZE PRODUCT INVENTORY
// =====================================================

// Check the product version.
// If the inventory is missing or outdated, load the latest sample products.
if (!isset($_SESSION['product_version']) ||
    $_SESSION['product_version'] < 3) {

    $_SESSION['products'] = [
        ['id' => 1, 'name' => 'Ergonomic Executive Chair', 'category' => 'Furniture', 'price' => 5500.00, 'stock' => 15, 'image' => 'uploads/Ergonomic Executive Chair.jpg'],
        ['id' => 2, 'name' => 'Standing Desk Converter', 'category' => 'Furniture', 'price' => 6800.00, 'stock' => 10, 'image' => 'uploads/Standing Desk Converter.avif'],
        ['id' => 3, 'name' => 'Minimalist Wooden Bookshelf', 'category' => 'Furniture', 'price' => 4200.00, 'stock' => 8, 'image' => 'uploads/Minimalist Wooden Bookshelf.webp'],
        ['id' => 4, 'name' => 'Mechanical Gaming Keyboard', 'category' => 'Electronics', 'price' => 2499.00, 'stock' => 25, 'image' => 'uploads/Mechanical Gaming Keyboard.png'],
        ['id' => 5, 'name' => '27-inch IPS Monitor', 'category' => 'Electronics', 'price' => 12500.00, 'stock' => 12, 'image' => 'uploads/27-inch IPS Monitor.jpg'],
        ['id' => 6, 'name' => 'Heavy Duty Stapler Set', 'category' => 'Supplies', 'price' => 250.00, 'stock' => 40, 'image' => 'uploads/Heavy Duty Stapler Set.jpg']
    ];

    // Save the current inventory version.
    $_SESSION['product_version'] = 3;
}


// =====================================================
// ORDER MESSAGES
// =====================================================

// Variables used to display order success and error messages.
$orderMessage = "";
$orderError = "";


// =====================================================
// PROCESS CUSTOMER ORDER
// =====================================================

// Process an order only when:
// 1. The customer is logged in.
// 2. The form is submitted using POST.
// 3. The Place Order button was clicked.
if ($isUserLoggedIn &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['place_order'])) {

    // Get the submitted product information.
    $productName = htmlspecialchars($_POST['product']);
    $category = htmlspecialchars($_POST['category']);
    $price = floatval($_POST['price']);
    $qty = intval($_POST['qty']);


    // Check that the required product information is valid.
    if (!empty($productName) && $price > 0 && $qty > 0) {

        // This variable checks whether the selected product exists.
        $stockFound = false;

        // Search for the selected product in the inventory.
        foreach ($_SESSION['products'] as &$prod) {

            // Compare the submitted product name with the inventory.
            if ($prod['name'] === $productName) {

                $stockFound = true;

                // Check whether enough stock is available.
                if ($prod['stock'] >= $qty) {

                    // Deduct the ordered quantity from inventory.
                    $prod['stock'] -= $qty;

                } else {

                    // Display an error if the requested quantity is too high.
                    $orderError = "Insufficient stock available. Only " .
                                  $prod['stock'] . " units left.";
                }

                // Stop searching once the product is found.
                break;
            }
        }

        // Remove the reference created by the foreach loop.
        unset($prod);


        // Continue only if there is no stock error.
        if (empty($orderError)) {

            // Generate a new transaction ID.
            // The new ID is one higher than the highest existing ID.
            $newId = count($_SESSION['transactions']) > 0
                ? max(array_column($_SESSION['transactions'], 'id')) + 1
                : 1;


            // Add the customer's order to the transaction list.
            $_SESSION['transactions'][] = [
                'id' => $newId,

                // Get the logged-in customer's name.
                'customer_name' => isset($_SESSION['customer_name'])
                    ? trim($_SESSION['customer_name'])
                    : 'Customer',

                'product' => $productName,
                'category' => $category,
                'price' => $price,
                'qty' => $qty,

                // Every newly placed order starts with Pending status.
                'status' => 'Pending'
            ];

            // Display a success message after placing the order.
            $orderMessage = "Order placed successfully! Inventory updated.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Set the character encoding for the webpage. -->
    <meta charset="UTF-8">

    <!-- Make the webpage responsive on different screen sizes. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Browser tab title. -->
    <title>MSB. - Customer Ordering Portal</title>

    <!-- Connect the external CSS stylesheet. -->
    <link rel="stylesheet" href="style.css">


    <!-- JavaScript function for selecting a product from the catalog. -->
    <script>
        function selectCatalogItem(selectElement) {

            // Get the currently selected option from the dropdown.
            const selectedOption =
                selectElement.options[selectElement.selectedIndex];

            // Retrieve product information from the option's data attributes.
            const price = selectedOption.getAttribute('data-price');
            const category = selectedOption.getAttribute('data-category');
            const stock = selectedOption.getAttribute('data-stock');

            // If a valid product is selected, automatically fill in the form.
            if (price) {

                // Automatically display the product's unit price.
                document.getElementById('price').value = price;

                // Automatically select the product category.
                document.getElementById('category').value = category;

                // Automatically fill in the product name.
                document.getElementById('product').value = selectedOption.value;

                // Set the maximum order quantity based on available stock.
                document.getElementById('qty').setAttribute('max', stock);
            }
        }
    </script>
</head>

<body>

    <!-- Main wrapper containing the entire customer portal. -->
    <div class="wrapper">

        <!-- Navigation bar. -->
        <nav class="navbar">

            <h2>MSB. Storefront</h2>

            <ul>

                <!-- Display customer information when logged in. -->
                <?php if ($isUserLoggedIn): ?>

                    <li>
                        <span style="color: #38bdf8;">
                            Welcome, <?= htmlspecialchars($_SESSION['customer_name']) ?>
                        </span>
                    </li>

                    <!-- Logout link. -->
                    <li>
                        <a href="order.php?user_logout=true" class="logout-link">
                            Logout
                        </a>
                    </li>

                <?php else: ?>

                    <!-- Display Login link when the customer is not logged in. -->
                    <li>
                        <a href="login.php">Login</a>
                    </li>

                <?php endif; ?>

            </ul>
        </nav>


        <!-- Main content of the customer ordering portal. -->
        <main class="container">

            <!-- Page heading and description. -->
            <header class="page-header">
                <h1>Customer Ordering Portal</h1>

                <p>
                    Browse available product inventory across categories
                    and place orders that sync instantly to the admin dashboard.
                </p>
            </header>


            <!-- Check whether the customer is logged in. -->
            <?php if (!$isUserLoggedIn): ?>

                <!-- Display this section if the customer is not authenticated. -->
                <section class="login-section">

                    <div class="login-card" style="text-align: center;">

                        <h2>Authentication Required</h2>

                        <p>
                            You must be logged in as a customer to view products
                            and place orders.
                        </p>

                        <!-- Redirect the customer to the login page. -->
                        <a href="login.php"
                           class="btn-primary"
                           style="display: inline-block; text-decoration: none; margin-top: 1rem;">

                            Go to Login Portal

                        </a>

                    </div>
                </section>

            <?php else: ?>


                <!-- =====================================
                     ORDER NOTIFICATION MESSAGES
                ====================================== -->

                <!-- Display the success message if an order was placed. -->
                <?php if (!empty($orderMessage)): ?>

                    <div class="alert-success">
                        <?= $orderMessage ?>
                    </div>

                <?php endif; ?>


                <!-- Display an error message if the order could not be completed. -->
                <?php if (!empty($orderError)): ?>

                    <div class="alert-danger">
                        <?= $orderError ?>
                    </div>

                <?php endif; ?>


                <!-- =====================================
                     CUSTOMER ORDER FORM
                ====================================== -->

                <section class="form-section" style="margin-bottom: 2rem;">

                    <h2>Place New Order</h2>

                    <!-- Form used by customers to submit an order. -->
                    <form method="POST" action="order.php" class="bi-form">

                        <!-- Product catalog dropdown for quick selection. -->
                        <div class="form-group" style="grid-column: 1 / -1;">

                            <label for="catalog_select">
                                Quick Select from Catalog:
                            </label>

                            <select id="catalog_select"
                                    onchange="selectCatalogItem(this)"
                                    style="padding: 0.7rem; border-radius: 6px; background: #0f172a; color: white; border: 1px solid var(--border-color);">

                                <option value="">
                                    -- Choose an item from catalog --
                                </option>

                                <!-- Display products that have available stock. -->
                                <?php foreach ($_SESSION['products'] as $prodItem): ?>

                                    <?php if ($prodItem['stock'] > 0): ?>

                                        <option
                                            value="<?= $prodItem['name'] ?>"
                                            data-price="<?= $prodItem['price'] ?>"
                                            data-category="<?= $prodItem['category'] ?>"
                                            data-stock="<?= $prodItem['stock'] ?>">

                                            <?= $prodItem['name'] ?>
                                            (<?= formatCurrency($prodItem['price']) ?>)
                                            - Stock: <?= $prodItem['stock'] ?>

                                        </option>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </select>
                        </div>


                        <!-- Product name input. -->
                        <div class="form-group">

                            <label for="product">Product Name:</label>

                            <input type="text"
                                   id="product"
                                   name="product"
                                   required
                                   placeholder="e.g., Wireless Keyboard">

                        </div>


                        <!-- Product category selection. -->
                        <div class="form-group">

                            <label for="category">Category:</label>

                            <select id="category" name="category">

                                <option value="Furniture">Furniture</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Supplies">Supplies</option>

                            </select>

                        </div>


                        <!-- Product unit price input. -->
                        <div class="form-group">

                            <label for="price">Unit Price (₱):</label>

                            <input type="number"
                                   id="price"
                                   name="price"
                                   step="0.01"
                                   min="0.01"
                                   required
                                   placeholder="0.00">

                        </div>


                        <!-- Order quantity input. -->
                        <div class="form-group">

                            <label for="qty">Quantity:</label>

                            <div style="display: flex; gap: 0.5rem; align-items: center;">

                                <input type="number"
                                       id="qty"
                                       name="qty"
                                       min="1"
                                       required
                                       placeholder="1"
                                       style="flex: 1;">

                                <!-- Reset all form fields. -->
                                <button type="reset"
                                        class="btn-primary"
                                        style="background: var(--surface-color); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.7rem 1rem; font-size: 0.9rem; white-space: nowrap; width: auto;">

                                    Clear Form

                                </button>

                            </div>
                        </div>


                        <!-- Submit the order form. -->
                        <div class="form-actions">

                            <button type="submit"
                                    name="place_order"
                                    class="btn-primary">

                                Place Order Now

                            </button>

                        </div>

                    </form>
                </section>


                <!-- =====================================
                     AVAILABLE PRODUCT CATALOG
                ====================================== -->

                <section class="table-section">

                    <h2>Available Product Catalog (Live Inventory)</h2>

                    <div class="table-responsive">

                        <table>

                            <!-- Table column headings. -->
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Category</th>
                                    <th>Product Name</th>
                                    <th>Unit Price</th>
                                    <th>Available Stock</th>
                                </tr>
                            </thead>


                            <!-- Display all available products. -->
                            <tbody>

                                <?php foreach ($_SESSION['products'] as $prodItem): ?>

                                    <tr>

                                        <!-- Product image column. -->
                                        <td>

                                            <?php if (!empty($prodItem['image'])): ?>

                                                <!-- Display the product image if available. -->
                                                <img
                                                    src="<?= htmlspecialchars(str_replace(' ', '%20', $prodItem['image'])) ?>"
                                                    alt="<?= htmlspecialchars($prodItem['name']) ?>"
                                                    style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">

                                            <?php else: ?>

                                                <!-- Display a placeholder when no image exists. -->
                                                <div style="width: 50px; height: 50px; background: #334155; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #94a3b8;">

                                                    No img

                                                </div>

                                            <?php endif; ?>

                                        </td>


                                        <!-- Display the product category. -->
                                        <td>
                                            <strong><?= $prodItem['category'] ?></strong>
                                        </td>


                                        <!-- Display the product name. -->
                                        <td>
                                            <?= htmlspecialchars($prodItem['name']) ?>
                                        </td>


                                        <!-- Display the formatted product price. -->
                                        <td>
                                            <?= formatCurrency($prodItem['price']) ?>
                                        </td>


                                        <!-- Display the available stock status. -->
                                        <td>

                                            <?php if ($prodItem['stock'] > 0): ?>

                                                <!-- Show the remaining quantity when stock is available. -->
                                                <span class="badge success">
                                                    <?= $prodItem['stock'] ?> units
                                                </span>

                                            <?php else: ?>

                                                <!-- Show Out of Stock when no units remain. -->
                                                <span class="badge danger">
                                                    Out of Stock
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>
                </section>

            <?php endif; ?>

        </main>


        <!-- Footer section. -->
        <footer class="site-footer">

            <p>
                &copy; 2026 MSB. Retail Analytics - Customer Portal.
            </p>

        </footer>

    </div>

</body>
</html>
