<?php
session_start();
require_once 'config.php';

$productId = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT id, name, description, price, image, stock FROM products WHERE id = ? AND active = 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit();
}
$product = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> | Luzano Spear Master</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'partials/nav.php'; ?>

<main class="product-page">
    <a href="index.php#products" class="back-link">← Back to products</a>
    <div class="product-detail">
        <div class="product-detail-image">
            <img src="images/<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="product-detail-info">
            <h1><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="product-detail-desc"><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>
            <span class="product-price big">₱<?= number_format((float) $product['price'], 2) ?></span>

            <?php if ((int) $product['stock'] <= 0): ?>
                <p class="stock-flag">Out of stock</p>
            <?php else: ?>
                <p class="stock-note"><?= (int) $product['stock'] ?> in stock</p>
                <form action="add_to_cart.php" method="POST" class="product-detail-form">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="redirect" value="cart.php">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= (int) $product['stock'] ?>">
                    <button type="submit" class="red-btn">ADD TO CART</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="script.js"></script>
</body>
</html>
