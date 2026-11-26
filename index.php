
<?php
// index.php - Main Admin Dashboard of the MSB Retail Analytics Portal.

// Starts a session to store administrator login information,
// product inventory, and transaction records.
session_start();

// Enables PHP error reporting to help identify errors during development.
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Imports reusable functions for revenue calculations,
// currency formatting, performance badges, and transaction filtering.
require 'functions.php';


// ==========================================
// ADMIN AUTHENTICATION
// ==========================================

// Checks whether the administrator is logged in.
// If the session is missing or invalid, redirects the user to login.php.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}


// ==========================================
// INITIALIZE TRANSACTION DATA
// ==========================================

// Creates sample transaction records if no transaction data exists.
// These records are stored in the session.
if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [
        ['id' => 1, 'customer_name' => 'John Customer', 'product' => 'Ergonomic Desk', 'category' => 'Furniture', 'price' => 8500.00, 'qty' => 4, 'status' => 'Completed'],
        ['id' => 2, 'customer_name' => 'John Customer', 'product' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => 1250.50, 'qty' => 20, 'status' => 'Completed'],
        ['id' => 3, 'customer_name' => 'John Customer', 'product' => 'Standing Mat', 'category' => 'Furniture', 'price' => 2200.00, 'qty' => 8, 'status' => 'Processing'],
        ['id' => 4, 'customer_name' => 'John Customer', 'product' => 'USB-C Hub', 'category' => 'Electronics', 'price' => 1850.00, 'qty' => 15, 'status' => 'Pending'],
        ['id' => 5, 'customer_name' => 'John Customer', 'product' => 'Notebook Set', 'category' => 'Supplies', 'price' => 350.00, 'qty' => 30, 'status' => 'Completed']
    ];
}


// Assigns a default customer name to existing transactions
// that do not have a customer_name value.
foreach ($_SESSION['transactions'] as &$existingTxn) {
    if (!isset($existingTxn['customer_name']) || trim($existingTxn['customer_name']) === '') {
        $existingTxn['customer_name'] = 'John Customer';
    }
}

// Removes the reference to the last transaction variable.
unset($existingTxn);


// ==========================================
// INITIALIZE PRODUCT INVENTORY
// ==========================================

// Checks the product data version.
// If the product version is missing or outdated, loads the updated inventory.
if (!isset($_SESSION['product_version']) || $_SESSION['product_version'] < 3) {

    // Stores the default product information in the session.
    $_SESSION['products'] = [
        ['id' => 1, 'name' => 'Ergonomic Executive Chair', 'category' => 'Furniture', 'price' => 5500.00, 'stock' => 15, 'image' => 'uploads/Ergonomic Executive Chair.jpg'],
        ['id' => 2, 'name' => 'Standing Desk Converter', 'category' => 'Furniture', 'price' => 6800.00, 'stock' => 10, 'image' => 'uploads/Standing Desk Converter.avif'],
        ['id' => 3, 'name' => 'Minimalist Wooden Bookshelf', 'category' => 'Furniture', 'price' => 4200.00, 'stock' => 8, 'image' => 'uploads/Minimalist Wooden Bookshelf.webp'],
        ['id' => 4, 'name' => 'Mechanical Gaming Keyboard', 'category' => 'Electronics', 'price' => 2499.00, 'stock' => 25, 'image' => 'uploads/Mechanical Gaming Keyboard.png'],
        ['id' => 5, 'name' => '27-inch IPS Monitor', 'category' => 'Electronics', 'price' => 12500.00, 'stock' => 12, 'image' => 'uploads/27-inch IPS Monitor.jpg'],
        ['id' => 6, 'name' => 'Heavy Duty Stapler Set', 'category' => 'Supplies', 'price' => 250.00, 'stock' => 40, 'image' => 'uploads/Heavy Duty Stapler Set.jpg']
    ];

    // Updates the product version to prevent unnecessary reinitialization.
    $_SESSION['product_version'] = 3;
}


// ==========================================
// ADMIN LOGOUT
// ==========================================

// Checks whether the logout parameter is present in the URL.
// Removes the administrator login session and redirects to login.php.
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header("Location: login.php");
    exit();
}


// ==========================================
// ADD NEW PRODUCT TO INVENTORY
// ==========================================

// Initializes an empty message for displaying inventory operation results.
$productMsg = "";

// Checks whether the form was submitted using POST
// and whether the Add Product button was clicked.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_submit'])) {

    // Retrieves and cleans the submitted product information.
    $pName = trim($_POST['new_product_name']);
    $pCat = trim($_POST['new_product_category']);
    $pPrice = floatval($_POST['new_product_price']);
    $pStock = intval($_POST['new_product_stock']);

    // Initializes an empty variable for the product image path.
    $imagePath = '';

    // Validates the product name, price, and stock quantity.
    if (!empty($pName) && $pPrice > 0 && $pStock >= 0) {

        // Initializes an empty variable for possible image upload errors.
        $uploadError = '';

        // Checks whether an image file was submitted.
        $imageUpload = isset($_FILES['new_product_image']) ? $_FILES['new_product_image'] : null;

        // Processes the image only if a file was selected.
        if ($imageUpload !== null && $imageUpload['error'] !== UPLOAD_ERR_NO_FILE) {

            // Checks whether the image upload encountered an error.
            if ($imageUpload['error'] !== UPLOAD_ERR_OK) {
                $uploadError = "The image could not be uploaded. Please try again.";

            // Limits the uploaded image file size to 5 MB.
            } elseif ($imageUpload['size'] > 5 * 1024 * 1024) {
                $uploadError = "The image must be 5 MB or smaller.";

            } else {

                // Retrieves information about the uploaded image.
                $imageInfo = getimagesize($imageUpload['tmp_name']);

                // Defines the permitted image formats and their extensions.
                $allowedImageTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp'
                ];

                // Validates the uploaded image format.
                if ($imageInfo === false || !isset($allowedImageTypes[$imageInfo['mime']])) {
                    $uploadError = "Please choose a valid JPG, PNG, GIF, or WebP image.";

                } else {

                    // Defines the directory where uploaded product images are stored.
                    $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

                    // Generates a random filename to avoid duplicate filenames.
                    $imageFileName = bin2hex(random_bytes(16)) . '.' . $allowedImageTypes[$imageInfo['mime']];

                    // Creates the complete destination path for the uploaded image.
                    $imageDestination = $uploadDirectory . DIRECTORY_SEPARATOR . $imageFileName;

                    // Checks whether the uploads directory exists and is writable.
                    if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
                        $uploadError = "The image upload folder is unavailable. Please contact the site administrator.";

                    // Moves the uploaded image into the uploads directory.
                    } elseif (!move_uploaded_file($imageUpload['tmp_name'], $imageDestination)) {
                        $uploadError = "The image could not be saved. Please try again.";

                    } else {

                        // Stores the relative image path for the product record.
                        $imagePath = 'uploads/' . $imageFileName;
                    }
                }
            }
        }

        // Checks whether an image upload error occurred.
        if ($uploadError !== '') {
            $productMsg = $uploadError;

        } else {

            // Generates a new product ID based on the highest existing ID.
            $newProdId = count($_SESSION['products']) > 0 ? max(array_column($_SESSION['products'], 'id')) + 1 : 1;

            // Adds the new product information to the inventory session.
            $_SESSION['products'][] = [
                'id' => $newProdId,
                'name' => $pName,
                'category' => $pCat,
                'price' => $pPrice,
                'stock' => $pStock,
                'image' => $imagePath
            ];

            // Displays a success message after adding the product.
            $productMsg = "Product added successfully to inventory!";
        }
    }
}


// ==========================================
// RESTOCK PRODUCT INVENTORY
// ==========================================

// Checks whether the Restock Product form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_product_submit'])) {

    // Retrieves the selected product ID and additional stock quantity.
    $restockProductId = intval($_POST['restock_product_id']);
    $restockQty = intval($_POST['restock_quantity']);

    // Ensures that the restock quantity is greater than zero.
    if ($restockQty > 0) {

        // Searches for the selected product in the inventory.
        foreach ($_SESSION['products'] as &$prod) {

            // Checks whether the current product matches the selected product ID.
            if ($prod['id'] === $restockProductId) {

                // Adds the new quantity to the existing stock.
                $prod['stock'] += $restockQty;

                // Displays a message confirming the inventory update.
                $productMsg = "Restocked " . $prod['name'] . " by " . $restockQty . " units.";
                break;
            }
        }

        // Removes the reference to the last product variable.
        unset($prod);
    }
}


// ==========================================
// DELETE PRODUCT FROM INVENTORY
// ==========================================

// Checks whether the Delete Product form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_submit'])) {

    // Retrieves the ID of the product selected for deletion.
    $deleteProductId = intval($_POST['delete_product_id']);

    // Searches for the selected product in the inventory.
    foreach ($_SESSION['products'] as $key => $prod) {

        // Checks whether the current product matches the selected ID.
        if ($prod['id'] === $deleteProductId) {

            // Removes the selected product from the inventory array.
            unset($_SESSION['products'][$key]);

            // Reorganizes the array indexes after deleting the product.
            $_SESSION['products'] = array_values($_SESSION['products']);

            // Renumbers the remaining products starting from ID 1.
            foreach ($_SESSION['products'] as $index => &$item) {
                $item['id'] = $index + 1;
            }

            // Removes the reference to the last product variable.
            unset($item);

            // Displays a confirmation message.
            $productMsg = "Product deleted successfully from inventory.";
            break;
        }
    }
}


// ==========================================
// UPDATE ORDER STATUS
// ==========================================

// Checks whether the Update Status form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_submit'])) {

    // Retrieves the transaction ID and newly selected order status.
    $targetTxnId = intval($_POST['txn_id']);
    $newStatus = $_POST['order_status'];

    // Stores the previous status for comparison.
    $previousStatus = null;

    // Searches for the transaction that needs an update.
    foreach ($_SESSION['transactions'] as &$txn) {

        // Checks whether the transaction ID matches.
        if ($txn['id'] === $targetTxnId) {

            // Saves the previous order status.
            $previousStatus = $txn['status'];

            // Updates the transaction with the newly selected status.
            $txn['status'] = $newStatus;
            break;
        }
    }

    // Removes the reference to the last transaction variable.
    unset($txn);

    // Checks whether the order was newly changed to Cancelled.
    if ($newStatus === 'Cancelled' && $previousStatus !== 'Cancelled') {

        // Searches for the cancelled transaction.
        foreach ($_SESSION['transactions'] as $txn) {

            if ($txn['id'] === $targetTxnId) {

                // Searches for the corresponding product in inventory.
                foreach ($_SESSION['products'] as &$prod) {

                    if ($prod['name'] === $txn['product']) {

                        // Returns the cancelled order quantity to available stock.
                        $prod['stock'] += intval($txn['qty']);
                        break;
                    }
                }

                // Removes the reference to the last product variable.
                unset($prod);
                break;
            }
        }
    }

    // Redirects back to the dashboard after updating the order.
    header("Location: index.php");
    exit();
}


// ==========================================
// DELETE TRANSACTION RECORD
// ==========================================

// Checks whether a transaction deletion request exists in the URL.
if (isset($_GET['delete_id'])) {

    // Retrieves the transaction ID to be deleted.
    $deleteId = intval($_GET['delete_id']);

    // Searches for the selected transaction.
    foreach ($_SESSION['transactions'] as $key => $txn) {

        if ($txn['id'] === $deleteId) {

            // Removes the selected transaction from the session.
            unset($_SESSION['transactions'][$key]);

            // Reorganizes the transaction array indexes.
            $_SESSION['transactions'] = array_values($_SESSION['transactions']);
            break;
        }
    }

    // Renumbers the remaining transaction IDs.
    foreach ($_SESSION['transactions'] as $index => &$item) {
        $item['id'] = $index + 1;
    }

    // Removes the reference to the last transaction variable.
    unset($item);

    // Redirects back to the dashboard after deletion.
    header("Location: index.php");
    exit();
}


// ==========================================
// DELETE PRODUCT USING URL REQUEST
// ==========================================

// Checks whether a product deletion request exists in the URL.
if (isset($_GET['delete_product_id'])) {

    // Retrieves the product ID to be deleted.
    $deleteProductId = intval($_GET['delete_product_id']);

    // Searches for the selected product in the inventory.
    foreach ($_SESSION['products'] as $key => $prod) {

        if ($prod['id'] === $deleteProductId) {

            // Removes the selected product from the inventory.
            unset($_SESSION['products'][$key]);

            // Reorganizes the product array indexes.
            $_SESSION['products'] = array_values($_SESSION['products']);

            // Renumbers the remaining product IDs.
            foreach ($_SESSION['products'] as $index => &$item) {
                $item['id'] = $index + 1;
            }

            // Removes the reference to the last product variable.
            unset($item);
            break;
        }
    }

    // Redirects back to the dashboard.
    header("Location: index.php");
    exit();
}


// ==========================================
// UPDATE TRANSACTION IDENTIFICATION
// ==========================================

// Ensures that transaction IDs follow the current array order.
foreach ($_SESSION['transactions'] as $index => &$item) {
    $item['id'] = $index + 1;
}

// Removes the reference to the last transaction variable.
unset($item);


// ==========================================
// SALES ANALYTICS AND CALCULATIONS
// ==========================================

// Sets the monthly revenue target to ₱150,000.
$monthlyTarget = 150000.00;

// Retrieves all transaction records from the session.
$transactions = $_SESSION['transactions'];

// Retrieves the selected category from the URL.
// If no category is selected, displays all categories.
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'All';

// Filters the transaction records using the selected category.
$displayTransactions = filterTransactionsByCriteria($transactions, $selectedCategory);

// Initializes the total revenue variable.
$totalRevenue = 0;

// Calculates the total revenue from all transactions.
foreach ($transactions as $t) {
    $totalRevenue += calculateRevenue($t['price'], $t['qty']);
}

// Counts the total number of transactions.
$totalTransactions = count($transactions);

// Calculates the average order value using total revenue and transaction count.
$avgOrderValue = calculateAverageOrderValue($totalRevenue, $totalTransactions);


// ==========================================
// INCLUDE REUSABLE HEADER
// ==========================================

// Loads the common navigation bar and HTML header.
include 'header.php';

?>

<!-- ==========================================
     MAIN DASHBOARD CONTENT
========================================== -->

<main class="container">

    <!-- Displays the dashboard title and description -->
    <header class="page-header dashboard-header">
        <div>
            <h1>MSB. Retail Analytics Dashboard</h1>
            <p>Business Intelligence Suite for Real-time Sales Tracking & Performance Evaluation</p>
        </div>
    </header>


    <!-- ==========================================
         KEY PERFORMANCE INDICATORS (KPI)
    ========================================== -->

    <!-- Displays important business performance metrics -->
    <section class="kpi-container">

        <!-- Displays the total calculated revenue -->
        <div class="card">
            <h3>Total Revenue</h3>
            <p class="metric"><?= formatCurrency($totalRevenue) ?></p>
        </div>

        <!-- Displays the total number of transactions -->
        <div class="card">
            <h3>Total Transactions</h3>
            <p class="metric"><?= $totalTransactions ?></p>
        </div>

        <!-- Displays the average order value -->
        <div class="card">
            <h3>Avg Order Value</h3>
            <p class="metric"><?= formatCurrency($avgOrderValue) ?></p>
        </div>

        <!-- Displays the revenue performance compared with the monthly target -->
        <div class="card">
            <h3>Performance Status</h3>
            <p class="metric"><?= getPerformanceBadge($totalRevenue, $monthlyTarget) ?></p>
        </div>

    </section>


    <!-- ==========================================
         PRODUCT INVENTORY MANAGEMENT
    ========================================== -->

    <!-- Allows the administrator to manage the product catalog and stock -->
    <section class="form-section inventory-section">

        <div class="section-heading">
            <div>
                <h2>Product Inventory &amp; Management</h2>
                <p>Add products, manage stock, or review the current catalog.</p>
            </div>
        </div>

        <!-- Displays a message after an inventory operation -->
        <?php if (!empty($productMsg)): ?>
            <div class="alert-success inventory-message"><?= htmlspecialchars($productMsg) ?></div>
        <?php endif; ?>


        <!-- Form for adding a new product -->
        <!-- multipart/form-data allows image file uploads -->
        <form method="POST" action="index.php" class="bi-form add-product-form" enctype="multipart/form-data">

            <!-- Product name input -->
            <div class="form-group">
                <label for="new_product_name">Product Name:</label>
                <input type="text" id="new_product_name" name="new_product_name" required placeholder="e.g. Ergonomic Footrest">
            </div>

            <!-- Product category selection -->
            <div class="form-group">
                <label for="new_product_category">Category:</label>
                <select id="new_product_category" name="new_product_category">
                    <option value="Furniture">Furniture</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Supplies">Supplies</option>
                </select>
            </div>

            <!-- Product unit price input -->
            <div class="form-group">
                <label for="new_product_price">Unit Price (₱):</label>
                <input type="number" id="new_product_price" name="new_product_price" step="0.01" min="0.01" required placeholder="0.00">
            </div>

            <!-- Initial product stock input -->
            <div class="form-group">
                <label for="new_product_stock">Initial Stock Quantity:</label>
                <input type="number" id="new_product_stock" name="new_product_stock" min="0" required placeholder="10">
            </div>

            <!-- Optional product image upload -->
            <div class="form-group product-image-field">
                <label for="new_product_image">Product Image:</label>
                <input type="file" id="new_product_image" name="new_product_image" accept=".jpg,.jpeg,.png,.gif,.webp">
                <small>Optional. JPG, PNG, GIF, or WebP; maximum 5 MB.</small>
            </div>

            <!-- Button for submitting the new product information -->
            <div class="form-actions">
                <button type="submit" name="add_product_submit" class="btn-primary">Add Product to Inventory</button>
            </div>

        </form>


        <!-- ==========================================
             RESTOCK AND DELETE PRODUCT FORMS
        ========================================== -->

        <div class="inventory-actions">

            <!-- Form for adding stock to an existing product -->
            <form method="POST" action="index.php" class="bi-form inventory-action-card">

                <h3>Restock Product</h3>

                <div class="form-group">
                    <label for="restock_product_id">Select Product:</label>

                    <!-- Displays the available products in a dropdown list -->
                    <select id="restock_product_id" name="restock_product_id" required>
                        <?php foreach ($_SESSION['products'] as $prod): ?>
                            <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Input for the additional stock quantity -->
                <div class="form-group">
                    <label for="restock_quantity">Quantity to Add:</label>
                    <input type="number" id="restock_quantity" name="restock_quantity" min="1" required placeholder="10">
                </div>

                <!-- Submits the restock request -->
                <div class="form-actions">
                    <button type="submit" name="restock_product_submit" class="btn-primary">Restock Inventory</button>
                </div>

            </form>


            <!-- Form for deleting an existing product -->
            <form method="POST" action="index.php" class="bi-form inventory-action-card">

                <h3>Delete Product</h3>

                <div class="form-group">
                    <label for="delete_product_id">Select Product:</label>

                    <!-- Displays available products for deletion -->
                    <select id="delete_product_id" name="delete_product_id" required>
                        <?php foreach ($_SESSION['products'] as $prod): ?>
                            <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Button for deleting the selected product -->
                <!-- JavaScript confirmation prevents accidental deletion -->
                <div class="form-actions">
                    <button type="submit" name="delete_product_submit" class="btn-primary btn-danger" onclick="return confirm('Are you sure you want to delete this product from inventory?');">Delete Selected Product</button>
                </div>

            </form>

        </div>


        <!-- ==========================================
             PRODUCT INVENTORY TABLE
        ========================================== -->

        <!-- Displays all products currently stored in the inventory -->
        <div class="table-responsive inventory-table">

            <table>

                <!-- Table column headings -->
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- Loops through each product in the inventory -->
                    <?php foreach ($_SESSION['products'] as $prod): ?>

                        <tr>

                            <!-- Displays the product image if available -->
                            <td>
                                <?php if (!empty($prod['image'])): ?>

                                    <!-- htmlspecialchars protects the output from HTML injection -->
                                    <img src="<?= htmlspecialchars(str_replace(' ', '%20', $prod['image'])) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">

                                <?php else: ?>

                                    <!-- Displays a placeholder if the product has no image -->
                                    <div style="width: 40px; height: 40px; background: #334155; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #94a3b8;">No img</div>

                                <?php endif; ?>
                            </td>

                            <!-- Displays product information -->
                            <td><?= $prod['id'] ?></td>
                            <td><?= htmlspecialchars($prod['name']) ?></td>
                            <td><?= htmlspecialchars($prod['category']) ?></td>
                            <td><?= formatCurrency($prod['price']) ?></td>
                            <td><strong><?= $prod['stock'] ?> units</strong></td>

                            <!-- Provides a link to delete the selected product -->
                            <td>
                                <a href="index.php?delete_product_id=<?= $prod['id'] ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="table-action-link">Delete</a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    </section>


    <!-- ==========================================
         TRANSACTION CATEGORY FILTER
    ========================================== -->

    <!-- Allows the administrator to filter transactions by product category -->
    <section class="filter-section transaction-filter">

        <form method="GET" action="index.php">

            <label for="category">Filter Transactions by Category:</label>

            <!-- Automatically submits the form when a category is selected -->
            <select name="category" id="category" onchange="this.form.submit()">

                <!-- Displays all transactions regardless of category -->
                <option value="All" <?= $selectedCategory === 'All' ? 'selected' : '' ?>>All Categories</option>

                <!-- Displays only Furniture transactions -->
                <option value="Furniture" <?= $selectedCategory === 'Furniture' ? 'selected' : '' ?>>Furniture</option>

                <!-- Displays only Electronics transactions -->
                <option value="Electronics" <?= $selectedCategory === 'Electronics' ? 'selected' : '' ?>>Electronics</option>

                <!-- Displays only Supplies transactions -->
                <option value="Supplies" <?= $selectedCategory === 'Supplies' ? 'selected' : '' ?>>Supplies</option>

            </select>

        </form>

    </section>


    <!-- ==========================================
         TRANSACTION AND ORDER STATUS TABLE
    ========================================== -->

    <!-- Displays customer transactions and allows order status updates -->
    <section class="table-section transactions-section">

        <div class="section-heading">
            <div>
                <h2>Transactions &amp; Order Status</h2>
                <p>Review order details and update fulfillment status.</p>
            </div>
        </div>


        <!-- Responsive container for the transaction table -->
        <div class="table-responsive">

            <table>

                <!-- Transaction table column headings -->
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Revenue</th>
                        <th>Order Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- Checks whether matching transactions exist -->
                    <?php if (count($displayTransactions) > 0): ?>

                        <!-- Loops through the filtered transaction records -->
                        <?php foreach ($displayTransactions as $txn): ?>

                            <tr>

                                <!-- Displays transaction information -->
                                <td><?= $txn['id'] ?></td>
                                <td><?= htmlspecialchars($txn['customer_name']) ?></td>
                                <td><?= htmlspecialchars($txn['product']) ?></td>
                                <td><?= htmlspecialchars($txn['category']) ?></td>
                                <td><?= formatCurrency($txn['price']) ?></td>
                                <td><?= $txn['qty'] ?></td>

                                <!-- Calculates and displays the revenue for each transaction -->
                                <td><strong><?= formatCurrency(calculateRevenue($txn['price'], $txn['qty'])) ?></strong></td>

                                <!-- Form for updating the selected transaction's order status -->
                                <td>
                                    <form method="POST" action="index.php" class="status-form">

                                        <!-- Hidden input stores the transaction ID -->
                                        <input type="hidden" name="txn_id" value="<?= $txn['id'] ?>">

                                        <!-- Dropdown containing the available order statuses -->
                                        <select name="order_status">

                                            <option value="Pending" <?= (isset($txn['status']) && $txn['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>

                                            <option value="Processing" <?= (isset($txn['status']) && $txn['status'] === 'Processing') ? 'selected' : '' ?>>Processing</option>

                                            <option value="Completed" <?= (isset($txn['status']) && $txn['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>

                                            <option value="Cancelled" <?= (isset($txn['status']) && $txn['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>

                                        </select>

                                        <!-- Submits the updated order status -->
                                        <button type="submit" name="update_status_submit" class="btn-primary">Save</button>

                                    </form>
                                </td>

                                <!-- Provides an option to delete the transaction -->
                                <td>
                                    <a href="index.php?delete_id=<?= $txn['id'] ?>" onclick="return confirm('Are you sure you want to delete this transaction?');" class="table-action-link">Delete</a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <!-- Displays a message when no transactions match the selected category -->
                        <tr>
                            <td colspan="9">No records found matching this filter criteria.</td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</main>


<?php
// Includes the reusable footer containing the copyright information.
// Also closes the main wrapper and HTML document.
include 'footer.php';
?>
