<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'functions.php';

// Enforce Admin Authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [
        ['id' => 1, 'customer_name' => 'John Customer', 'product' => 'Ergonomic Desk', 'category' => 'Furniture', 'price' => 8500.00, 'qty' => 4, 'status' => 'Completed'],
        ['id' => 2, 'customer_name' => 'John Customer', 'product' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => 1250.50, 'qty' => 20, 'status' => 'Completed'],
        ['id' => 3, 'customer_name' => 'John Customer', 'product' => 'Standing Mat', 'category' => 'Furniture', 'price' => 2200.00, 'qty' => 8, 'status' => 'Processing'],
        ['id' => 4, 'customer_name' => 'John Customer', 'product' => 'USB-C Hub', 'category' => 'Electronics', 'price' => 1850.00, 'qty' => 15, 'status' => 'Pending'],
        ['id' => 5, 'customer_name' => 'John Customer', 'product' => 'Notebook Set', 'category' => 'Supplies', 'price' => 350.00, 'qty' => 30, 'status' => 'Completed']
    ];
}

// Keep existing session transactions compatible by assigning the default customer name.
foreach ($_SESSION['transactions'] as &$existingTxn) {
    if (!isset($existingTxn['customer_name']) || trim($existingTxn['customer_name']) === '') {
        $existingTxn['customer_name'] = 'John Customer';
    }
}
unset($existingTxn);

// Force update products inventory with exact file extensions and version check
if (!isset($_SESSION['product_version']) || $_SESSION['product_version'] < 3) {
    $_SESSION['products'] = [
        ['id' => 1, 'name' => 'Ergonomic Executive Chair', 'category' => 'Furniture', 'price' => 5500.00, 'stock' => 15, 'image' => 'uploads/Ergonomic Executive Chair.jpg'],
        ['id' => 2, 'name' => 'Standing Desk Converter', 'category' => 'Furniture', 'price' => 6800.00, 'stock' => 10, 'image' => 'uploads/Standing Desk Converter.avif'],
        ['id' => 3, 'name' => 'Minimalist Wooden Bookshelf', 'category' => 'Furniture', 'price' => 4200.00, 'stock' => 8, 'image' => 'uploads/Minimalist Wooden Bookshelf.webp'],
        ['id' => 4, 'name' => 'Mechanical Gaming Keyboard', 'category' => 'Electronics', 'price' => 2499.00, 'stock' => 25, 'image' => 'uploads/Mechanical Gaming Keyboard.png'],
        ['id' => 5, 'name' => '27-inch IPS Monitor', 'category' => 'Electronics', 'price' => 12500.00, 'stock' => 12, 'image' => 'uploads/27-inch IPS Monitor.jpg'],
        ['id' => 6, 'name' => 'Heavy Duty Stapler Set', 'category' => 'Supplies', 'price' => 250.00, 'stock' => 40, 'image' => 'uploads/Heavy Duty Stapler Set.jpg']
    ];
    $_SESSION['product_version'] = 3;
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header("Location: login.php");
    exit();
}

// Handle Admin Adding New Product to Inventory
$productMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_submit'])) {
    $pName = trim($_POST['new_product_name']);
    $pCat = trim($_POST['new_product_category']);
    $pPrice = floatval($_POST['new_product_price']);
    $pStock = intval($_POST['new_product_stock']);
    $imagePath = '';

    if (!empty($pName) && $pPrice > 0 && $pStock >= 0) {
        $uploadError = '';
        $imageUpload = isset($_FILES['new_product_image']) ? $_FILES['new_product_image'] : null;

        if ($imageUpload !== null && $imageUpload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($imageUpload['error'] !== UPLOAD_ERR_OK) {
                $uploadError = "The image could not be uploaded. Please try again.";
            } elseif ($imageUpload['size'] > 5 * 1024 * 1024) {
                $uploadError = "The image must be 5 MB or smaller.";
            } else {
                $imageInfo = getimagesize($imageUpload['tmp_name']);
                $allowedImageTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp'
                ];

                if ($imageInfo === false || !isset($allowedImageTypes[$imageInfo['mime']])) {
                    $uploadError = "Please choose a valid JPG, PNG, GIF, or WebP image.";
                } else {
                    $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
                    $imageFileName = bin2hex(random_bytes(16)) . '.' . $allowedImageTypes[$imageInfo['mime']];
                    $imageDestination = $uploadDirectory . DIRECTORY_SEPARATOR . $imageFileName;

                    if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
                        $uploadError = "The image upload folder is unavailable. Please contact the site administrator.";
                    } elseif (!move_uploaded_file($imageUpload['tmp_name'], $imageDestination)) {
                        $uploadError = "The image could not be saved. Please try again.";
                    } else {
                        $imagePath = 'uploads/' . $imageFileName;
                    }
                }
            }
        }

        if ($uploadError !== '') {
            $productMsg = $uploadError;
        } else {
            $newProdId = count($_SESSION['products']) > 0 ? max(array_column($_SESSION['products'], 'id')) + 1 : 1;
            $_SESSION['products'][] = [
                'id' => $newProdId,
                'name' => $pName,
                'category' => $pCat,
                'price' => $pPrice,
                'stock' => $pStock,
                'image' => $imagePath
            ];
            $productMsg = "Product added successfully to inventory!";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_product_submit'])) {
    $restockProductId = intval($_POST['restock_product_id']);
    $restockQty = intval($_POST['restock_quantity']);

    if ($restockQty > 0) {
        foreach ($_SESSION['products'] as &$prod) {
            if ($prod['id'] === $restockProductId) {
                $prod['stock'] += $restockQty;
                $productMsg = "Restocked " . $prod['name'] . " by " . $restockQty . " units.";
                break;
            }
        }
        unset($prod);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_submit'])) {
    $deleteProductId = intval($_POST['delete_product_id']);
    foreach ($_SESSION['products'] as $key => $prod) {
        if ($prod['id'] === $deleteProductId) {
            unset($_SESSION['products'][$key]);
            $_SESSION['products'] = array_values($_SESSION['products']);
            foreach ($_SESSION['products'] as $index => &$item) {
                $item['id'] = $index + 1;
            }
            unset($item);
            $productMsg = "Product deleted successfully from inventory.";
            break;
        }
    }
}

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_submit'])) {
    $targetTxnId = intval($_POST['txn_id']);
    $newStatus = $_POST['order_status'];
    $previousStatus = null;

    foreach ($_SESSION['transactions'] as &$txn) {
        if ($txn['id'] === $targetTxnId) {
            $previousStatus = $txn['status'];
            $txn['status'] = $newStatus;
            break;
        }
    }
    unset($txn);

    if ($newStatus === 'Cancelled' && $previousStatus !== 'Cancelled') {
        foreach ($_SESSION['transactions'] as $txn) {
            if ($txn['id'] === $targetTxnId) {
                foreach ($_SESSION['products'] as &$prod) {
                    if ($prod['name'] === $txn['product']) {
                        $prod['stock'] += intval($txn['qty']);
                        break;
                    }
                }
                unset($prod);
                break;
            }
        }
    }

    header("Location: index.php");
    exit();
}

// Handle Transaction Deletion
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    foreach ($_SESSION['transactions'] as $key => $txn) {
        if ($txn['id'] === $deleteId) {
            unset($_SESSION['transactions'][$key]);
            $_SESSION['transactions'] = array_values($_SESSION['transactions']);
            break;
        }
    }
    
    foreach ($_SESSION['transactions'] as $index => &$item) {
        $item['id'] = $index + 1;
    }
    unset($item);

    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_product_id'])) {
    $deleteProductId = intval($_GET['delete_product_id']);
    foreach ($_SESSION['products'] as $key => $prod) {
        if ($prod['id'] === $deleteProductId) {
            unset($_SESSION['products'][$key]);
            $_SESSION['products'] = array_values($_SESSION['products']);
            foreach ($_SESSION['products'] as $index => &$item) {
                $item['id'] = $index + 1;
            }
            unset($item);
            break;
        }
    }

    header("Location: index.php");
    exit();
}

foreach ($_SESSION['transactions'] as $index => &$item) {
    $item['id'] = $index + 1;
}
unset($item);

$monthlyTarget = 150000.00;
$transactions = $_SESSION['transactions'];
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'All';
$displayTransactions = filterTransactionsByCriteria($transactions, $selectedCategory);

$totalRevenue = 0;
foreach ($transactions as $t) {
    $totalRevenue += calculateRevenue($t['price'], $t['qty']);
}
$totalTransactions = count($transactions);
$avgOrderValue = calculateAverageOrderValue($totalRevenue, $totalTransactions);

include 'header.php';
?>

<main class="container">
    <header class="page-header dashboard-header">
        <div>
            <h1>MSB. Retail Analytics Dashboard</h1>
            <p>Business Intelligence Suite for Real-time Sales Tracking & Performance Evaluation</p>
        </div>
    </header>

    <section class="kpi-container">
        <div class="card">
            <h3>Total Revenue</h3>
            <p class="metric"><?= formatCurrency($totalRevenue) ?></p>
        </div>
        <div class="card">
            <h3>Total Transactions</h3>
            <p class="metric"><?= $totalTransactions ?></p>
        </div>
        <div class="card">
            <h3>Avg Order Value</h3>
            <p class="metric"><?= formatCurrency($avgOrderValue) ?></p>
        </div>
        <div class="card">
            <h3>Performance Status</h3>
            <p class="metric"><?= getPerformanceBadge($totalRevenue, $monthlyTarget) ?></p>
        </div>
    </section>

    <!-- Product Inventory Management Section -->
    <section class="form-section inventory-section">
        <div class="section-heading">
            <div>
                <h2>Product Inventory &amp; Management</h2>
                <p>Add products, manage stock, or review the current catalog.</p>
            </div>
        </div>
        <?php if (!empty($productMsg)): ?>
            <div class="alert-success inventory-message"><?= htmlspecialchars($productMsg) ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php" class="bi-form add-product-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="new_product_name">Product Name:</label>
                <input type="text" id="new_product_name" name="new_product_name" required placeholder="e.g. Ergonomic Footrest">
            </div>
            <div class="form-group">
                <label for="new_product_category">Category:</label>
                <select id="new_product_category" name="new_product_category">
                    <option value="Furniture">Furniture</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Supplies">Supplies</option>
                </select>
            </div>
            <div class="form-group">
                <label for="new_product_price">Unit Price (₱):</label>
                <input type="number" id="new_product_price" name="new_product_price" step="0.01" min="0.01" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label for="new_product_stock">Initial Stock Quantity:</label>
                <input type="number" id="new_product_stock" name="new_product_stock" min="0" required placeholder="10">
            </div>
            <div class="form-group product-image-field">
                <label for="new_product_image">Product Image:</label>
                <input type="file" id="new_product_image" name="new_product_image" accept=".jpg,.jpeg,.png,.gif,.webp">
                <small>Optional. JPG, PNG, GIF, or WebP; maximum 5 MB.</small>
            </div>
            <div class="form-actions">
                <button type="submit" name="add_product_submit" class="btn-primary">Add Product to Inventory</button>
            </div>
        </form>

        <div class="inventory-actions">
            <form method="POST" action="index.php" class="bi-form inventory-action-card">
                <h3>Restock Product</h3>
                <div class="form-group">
                    <label for="restock_product_id">Select Product:</label>
                    <select id="restock_product_id" name="restock_product_id" required>
                        <?php foreach ($_SESSION['products'] as $prod): ?>
                            <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="restock_quantity">Quantity to Add:</label>
                    <input type="number" id="restock_quantity" name="restock_quantity" min="1" required placeholder="10">
                </div>
                <div class="form-actions">
                    <button type="submit" name="restock_product_submit" class="btn-primary">Restock Inventory</button>
                </div>
            </form>

            <form method="POST" action="index.php" class="bi-form inventory-action-card">
                <h3>Delete Product</h3>
                <div class="form-group">
                    <label for="delete_product_id">Select Product:</label>
                    <select id="delete_product_id" name="delete_product_id" required>
                        <?php foreach ($_SESSION['products'] as $prod): ?>
                            <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="delete_product_submit" class="btn-primary btn-danger" onclick="return confirm('Are you sure you want to delete this product from inventory?');">Delete Selected Product</button>
                </div>
            </form>
        </div>

        <div class="table-responsive inventory-table">
            <table>
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
                    <?php foreach ($_SESSION['products'] as $prod): ?>
                        <tr>
                            <td>
                                <?php if (!empty($prod['image'])): ?>
                                    <img src="<?= htmlspecialchars(str_replace(' ', '%20', $prod['image'])) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; background: #334155; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #94a3b8;">No img</div>
                                <?php endif; ?>
                            </td>
                            <td><?= $prod['id'] ?></td>
                            <td><?= htmlspecialchars($prod['name']) ?></td>
                            <td><?= htmlspecialchars($prod['category']) ?></td>
                            <td><?= formatCurrency($prod['price']) ?></td>
                            <td><strong><?= $prod['stock'] ?> units</strong></td>
                            <td>
                                <a href="index.php?delete_product_id=<?= $prod['id'] ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="table-action-link">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="filter-section transaction-filter">
        <form method="GET" action="index.php">
            <label for="category">Filter Transactions by Category:</label>
            <select name="category" id="category" onchange="this.form.submit()">
                <option value="All" <?= $selectedCategory === 'All' ? 'selected' : '' ?>>All Categories</option>
                <option value="Furniture" <?= $selectedCategory === 'Furniture' ? 'selected' : '' ?>>Furniture</option>
                <option value="Electronics" <?= $selectedCategory === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                <option value="Supplies" <?= $selectedCategory === 'Supplies' ? 'selected' : '' ?>>Supplies</option>
            </select>
        </form>
    </section>

    <section class="table-section transactions-section">
        <div class="section-heading">
            <div>
                <h2>Transactions &amp; Order Status</h2>
                <p>Review order details and update fulfillment status.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table>
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
                    <?php if (count($displayTransactions) > 0): ?>
                        <?php foreach ($displayTransactions as $txn): ?>
                            <tr>
                                <td><?= $txn['id'] ?></td>
                                <td><?= htmlspecialchars($txn['customer_name']) ?></td>
                                <td><?= htmlspecialchars($txn['product']) ?></td>
                                <td><?= htmlspecialchars($txn['category']) ?></td>
                                <td><?= formatCurrency($txn['price']) ?></td>
                                <td><?= $txn['qty'] ?></td>
                                <td><strong><?= formatCurrency(calculateRevenue($txn['price'], $txn['qty'])) ?></strong></td>
                                <td>
                                    <form method="POST" action="index.php" class="status-form">
                                        <input type="hidden" name="txn_id" value="<?= $txn['id'] ?>">
                                        <select name="order_status">
                                            <option value="Pending" <?= (isset($txn['status']) && $txn['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
                                            <option value="Processing" <?= (isset($txn['status']) && $txn['status'] === 'Processing') ? 'selected' : '' ?>>Processing</option>
                                            <option value="Completed" <?= (isset($txn['status']) && $txn['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= (isset($txn['status']) && $txn['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status_submit" class="btn-primary">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <a href="index.php?delete_id=<?= $txn['id'] ?>" onclick="return confirm('Are you sure you want to delete this transaction?');" class="table-action-link">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9">No records found matching this filter criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>