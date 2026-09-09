<?php
session_start();
require_once __DIR__ . '/config.php';

$pageTitle = "Luzano Spear Master";
$products = $conn->query("SELECT * FROM products WHERE active = 1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

$cart = $_SESSION['cart'] ?? [];
$cartCount = 0;
foreach ($cart as $item) {
    $cartCount += (int) $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="navbar">
    <div class="logo">
    <img src="images/viclogo.png" alt="Luzano Spear Master Logo">
</div>
    <nav>
        <a href="#home" class="nav-link">HOME</a>
        <a href="#products" class="nav-link">PRODUCTS</a>
        <a href="#about" class="nav-link">ABOUT</a>
        <a href="#gallery" class="nav-link">GALLERY</a>
        <a href="#contact" class="nav-link">CONTACT</a>
    </nav>
    <a href="cart.php" class="cart-icon-link" id="cart-icon-link" aria-label="View cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="cart-count-badge" id="cart-count-badge" <?= $cartCount > 0 ? '' : 'style="display:none;"' ?>><?= $cartCount ?></span>
    </a>
    <a href="logout.php" class="logout-link">LOG OUT</a>
</header>

<?php include 'partials/cart_modal.php'; ?>

<section id="home" class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>LUZANO</h1>
        <h3>-SPEAR MASTER-</h3>
        <p>Trusted equipment for<br>spearfishing & freediving.</p>
        <button type="button" class="red-btn">EXPLORE GEAR</button>
    </div>
</section>

<section id="products" class="section water-section">
    <div class="section-title">
        <h2>BUILT FOR THE WATER</h2>
        <p>Durable equipment designed around the<br>Luzano Spear Master identity.</p>
    </div>

    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="images/<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                <div>
                    <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <span class="product-price">₱<?php echo number_format((float) $product['price']); ?></span>
                    <?php if ((int) $product['stock'] > 0): ?>
                        <form action="add_to_cart.php" method="POST" class="product-card-form">
                            <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="redirect" value="cart.php">
                            <button type="submit" class="red-btn">ADD TO CART</button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="red-btn" disabled>OUT OF STOCK</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="about" class="series-section">
    <div class="series-image"><img src="images/diving.jpg" alt="Spearfishing"></div>
    <div class="series-content">
        <h2>LUZANO</h2>
        <h3>SPEAR MASTER SERIES</h3>
        <p class="series-description">Built around precision, durability,<br>and confidence in demanding water.</p>
        <div class="features">
            <div class="feature"><span>01</span><p>Precision construction</p></div>
            <div class="feature"><span>02</span><p>Durable materials</p></div>
            <div class="feature"><span>03</span><p>Designed for spearfishing</p></div>
        </div>
    </div>
</section>

<section class="element-section">
    <div class="element-main">
        <img src="images/diving.jpg" alt="Diving">
        <div class="element-overlay">
            <h2>IN THE ELEMENT</h2>
            <p>Gear designed for movement,<br>control, and confidence underwater.</p>
        </div>
    </div>

    <div class="element-products">
        <?php foreach (array_slice($products, 0, 4) as $product): ?>
            <div class="small-product">
                <img src="images/<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                <span><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <em>₱<?php echo number_format((float) $product['price']); ?></em>
            </div>
        <?php endforeach; ?>
        <button type="button" class="red-btn full-btn">EXPLORE COLLECTION</button>
    </div>
</section>

<section id="gallery" class="lookbook-section">
    <div class="lookbook-intro">
        <h2>SPEAR MASTER</h2>
        <h3>LOOKBOOK</h3>
        <p>A closer look at the<br>Luzano Spear Master collection.</p>
    </div>

    <div class="lookbook-features">
        <div><span>01</span><h3>PRECISION</h3><p>Purpose-built construction</p></div>
        <div><span>02</span><h3>DURABILITY</h3><p>Materials made for the water</p></div>
        <div><span>03</span><h3>PERFORMANCE</h3><p>Designed for demanding dives</p></div>
        <a href="#products" class="red-btn">EXPLORE THE FULL COLLECTION</a>
    </div>
</section>

<section id="contact" class="contact-section">
    <div class="contact-form">
        <h2>GET IN TOUCH</h2>
        <?php if (!empty($_SESSION['contact_sent'])): ?>
            <p class="contact-success"><?php echo htmlspecialchars($_SESSION['contact_sent'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php unset($_SESSION['contact_sent']); ?>
        <?php elseif (!empty($_SESSION['contact_error'])): ?>
            <p class="contact-error"><?php echo htmlspecialchars($_SESSION['contact_error'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php unset($_SESSION['contact_error']); ?>
        <?php endif; ?>
        <form action="send_message.php" method="POST">
            <label for="contact-name">NAME:</label>
            <input id="contact-name" name="name" type="text" required>
            <label for="contact-email">EMAIL:</label>
            <input id="contact-email" name="email" type="email" required>
            <label for="contact-message">MESSAGE:</label>
            <textarea id="contact-message" name="message" required></textarea>
            <button class="red-btn" type="submit">SEND MESSAGE</button>
        </form>
    </div>

    <div class="contact-info">
        <div class="red-line"></div>
        <h2>LUZANO</h2>
        <h3>-SPEAR MASTER-</h3>
        <p>Precision construction</p>
        <p>Durable materials</p>
        <p>Designed for spearfishing</p>
        <hr>
        <h3>FOLLOW THE JOURNEY</h3>
        <p>@LUZANOSPEARMASTER</p>
        <p>@LUZANOSPEARMASTER</p>
        <p>@LUZANOSPEARMASTER</p>
    </div>
</section>

<footer>
    <div class="socials">
        <span>◉ @LUZANOSPEARMASTER</span>
        <span>◉ @LUZANOSPEARMASTER</span>
        <span>◉ @LUZANOSPEARMASTER</span>
    </div>
</footer>

<script src="script.js"></script>
</body>
</html>