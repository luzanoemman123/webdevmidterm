<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['active_form'] = 'login';
    header("Location: login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$total = 0;
$totalItems = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
    $totalItems += $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | Luzano Spear Master</title>
    <link rel="stylesheet" href="style.css?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'partials/nav.php'; ?>

<main class="cart-page">
    <div class="cart-header">
        <h1>YOUR CART</h1>
        <a href="index.php" class="back-link">← Continue shopping</a>
    </div>

    <?php if (empty($cart)): ?>
        <div class="cart-empty">
            <div class="cart-empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
            </div>
            <p>Your cart is empty.</p>
            <a href="index.php#products" class="red-btn">BROWSE GEAR</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($cart as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="images/<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="cart-item-info">
                            <h3><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="cart-item-price">₱<?= number_format($item['price'], 2) ?> each</span>
                        </div>
                        <form action="update_cart.php" method="POST" class="cart-item-qty">
                            <input type="hidden" name="action" value="update_quantity">
                            <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                            <div class="qty-stepper">
                                <button type="button" class="qty-btn" onclick="stepQty(this, -1)" aria-label="Decrease quantity">−</button>
                                <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" onchange="this.form.submit()" aria-label="Quantity">
                                <button type="button" class="qty-btn" onclick="stepQty(this, 1)" aria-label="Increase quantity">+</button>
                            </div>
                        </form>
                        <div class="cart-item-subtotal">
                            ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </div>
                        <form action="update_cart.php" method="POST" class="cart-item-remove">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                            <button type="submit" title="Remove">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h2>ORDER SUMMARY</h2>
                <div class="summary-row"><span>Items</span><span><?= $totalItems ?></span></div>
                <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($total, 2) ?></span></div>
                <div class="summary-row shipping"><span>Shipping</span><span>FREE</span></div>
                <div class="summary-row total"><span>TOTAL</span><span>₱<?= number_format($total, 2) ?></span></div>
                <a href="checkout.php" class="red-btn full-btn">PROCEED TO CHECKOUT</a>
                <p class="cart-secure-note">🔒 Secure checkout · Cash on Delivery</p>
            </div>
        </div>
    <?php endif; ?>
</main>

<script src="script.js"></script>
</body>
</html> s you can submit new request bell