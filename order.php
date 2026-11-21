<?php
session_start();
require 'functions.php';

// Handle User Logout via GET
if (isset($_GET['user_logout'])) {
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['username']);
    unset($_SESSION['customer_name']);
    header("Location: login.php");
    exit();
}

$isUserLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

// Initialize session transactions if not set
if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [
        ['id' => 1, 'customer_name' => 'John Customer', 'product' => 'Ergonomic Desk', 'category' => 'Furniture', 'price' => 8500.00, 'qty' => 4, 'status' => 'Completed'],
        ['id' => 2, 'customer_name' => 'John Customer', 'product' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => 1250.50, 'qty' => 20, 'status' => 'Completed'],
        ['id' => 3, 'customer_name' => 'John Customer', 'product' => 'Standing Mat', 'category' => 'Furniture', 'price' => 2200.00, 'qty' => 8, 'status' => 'Processing'],
        ['id' => 4, 'customer_name' => 'John Customer', 'product' => 'USB-C Hub', 'category' => 'Electronics', 'price' => 1850.00, 'qty' => 15, 'status' => 'Pending'],
        ['id' => 5, 'customer_name' => 'John Customer', 'product' => 'Notebook Set', 'category' => 'Supplies', 'price' => 350.00, 'qty' => 30, 'status' => 'Completed']
    ];
}

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

$orderMessage = "";
$orderError = "";

// Handle order submission and inventory deduction
if ($isUserLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $productName = htmlspecialchars($_POST['product']);
    $category = htmlspecialchars($_POST['category']);
    $price = floatval($_POST['price']);
    $qty = intval($_POST['qty']);

    if (!empty($productName) && $price > 0 && $qty > 0) {
        // Check and deduct inventory stock
        $stockFound = false;
        foreach ($_SESSION['products'] as &$prod) {
            if ($prod['name'] === $productName) {
                $stockFound = true;
                if ($prod['stock'] >= $qty) {
                    $prod['stock'] -= $qty; // Deduct inventory!
                } else {
                    $orderError = "Insufficient stock available. Only " . $prod['stock'] . " units left.";
                }
                break;
            }
        }
        unset($prod);

        if (empty($orderError)) {
            $newId = count($_SESSION['transactions']) > 0 ? max(array_column($_SESSION['transactions'], 'id')) + 1 : 1;
            
            $_SESSION['transactions'][] = [
                'id' => $newId,
                'customer_name' => isset($_SESSION['customer_name']) ? trim($_SESSION['customer_name']) : 'Customer',
                'product' => $productName,
                'category' => $category,
                'price' => $price,
                'qty' => $qty,
                'status' => 'Pending'
            ];
            $orderMessage = "Order placed successfully! Inventory updated.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSB. - Customer Ordering Portal</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function selectCatalogItem(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const category = selectedOption.getAttribute('data-category');
            const stock = selectedOption.getAttribute('data-stock');
            
            if (price) {
                document.getElementById('price').value = price;
                document.getElementById('category').value = category;
                document.getElementById('product').value = selectedOption.value;
                document.getElementById('qty').setAttribute('max', stock);
            }
        }
    </script>
</head>
<body>
    <div class="wrapper">
        <nav class="navbar">
            <h2>MSB. Storefront</h2>
            <ul>
                <?php if ($isUserLoggedIn): ?>
                    <li><span style="color: #38bdf8;">Welcome, <?= htmlspecialchars($_SESSION['customer_name']) ?></span></li>
                    <li><a href="order.php?user_logout=true" class="logout-link">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="container">
            <header class="page-header">
                <h1>Customer Ordering Portal</h1>
                <p>Browse available product inventory across categories and place orders that sync instantly to the admin dashboard.</p>
            </header>

            <?php if (!$isUserLoggedIn): ?>
                <section class="login-section">
                    <div class="login-card" style="text-align: center;">
                        <h2>Authentication Required</h2>
                        <p>You must be logged in as a customer to view products and place orders.</p>
                        <a href="login.php" class="btn-primary" style="display: inline-block; text-decoration: none; margin-top: 1rem;">Go to Login Portal</a>
                    </div>
                </section>
            <?php else: ?>

                <?php if (!empty($orderMessage)): ?>
                    <div class="alert-success"><?= $orderMessage ?></div>
                <?php endif; ?>
                <?php if (!empty($orderError)): ?>
                    <div class="alert-danger"><?= $orderError ?></div>
                <?php endif; ?>

                <!-- Order Form (Moved Above) -->
                <section class="form-section" style="margin-bottom: 2rem;">
                    <h2>Place New Order</h2>
                    <form method="POST" action="order.php" class="bi-form">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="catalog_select">Quick Select from Catalog:</label>
                            <select id="catalog_select" onchange="selectCatalogItem(this)" style="padding: 0.7rem; border-radius: 6px; background: #0f172a; color: white; border: 1px solid var(--border-color);">
                                <option value="">-- Choose an item from catalog --</option>
                                <?php foreach ($_SESSION['products'] as $prodItem): ?>
                                    <?php if ($prodItem['stock'] > 0): ?>
                                        <option value="<?= $prodItem['name'] ?>" data-price="<?= $prodItem['price'] ?>" data-category="<?= $prodItem['category'] ?>" data-stock="<?= $prodItem['stock'] ?>">
                                            <?= $prodItem['name'] ?> (<?= formatCurrency($prodItem['price']) ?>) - Stock: <?= $prodItem['stock'] ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="product">Product Name:</label>
                            <input type="text" id="product" name="product" required placeholder="e.g., Wireless Keyboard">
                        </div>
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select id="category" name="category">
                                <option value="Furniture">Furniture</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Supplies">Supplies</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="price">Unit Price (₱):</label>
                            <input type="number" id="price" name="price" step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="qty">Quantity:</label>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="number" id="qty" name="qty" min="1" required placeholder="1" style="flex: 1;">
                                <button type="reset" class="btn-primary" style="background: var(--surface-color); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.7rem 1rem; font-size: 0.9rem; white-space: nowrap; width: auto;">Clear Form</button>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="place_order" class="btn-primary">Place Order Now</button>
                        </div>
                    </form>
                </section>

                <!-- Available Product Catalog Table with Images (Moved Below) -->
                <section class="table-section">
                    <h2>Available Product Catalog (Live Inventory)</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Category</th>
                                    <th>Product Name</th>
                                    <th>Unit Price</th>
                                    <th>Available Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['products'] as $prodItem): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($prodItem['image'])): ?>
                                                <img src="<?= htmlspecialchars(str_replace(' ', '%20', $prodItem['image'])) ?>" alt="<?= htmlspecialchars($prodItem['name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: #334155; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #94a3b8;">No img</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= $prodItem['category'] ?></strong></td>
                                        <td><?= htmlspecialchars($prodItem['name']) ?></td>
                                        <td><?= formatCurrency($prodItem['price']) ?></td>
                                        <td>
                                            <?php if ($prodItem['stock'] > 0): ?>
                                                <span class="badge success"><?= $prodItem['stock'] ?> units</span>
                                            <?php else: ?>
                                                <span class="badge danger">Out of Stock</span>
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

        <footer class="site-footer">
            <p>&copy; 2026 MSB. Retail Analytics - Customer Portal.</p>
        </footer>
    </div>
</body>
</html>