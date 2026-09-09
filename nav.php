<?php
// NOTE: this file was not part of the files sent to Claude — it's rebuilt
// from index.php's inline header so cart.php / product.php stay visually
// consistent. If you already have a partials/nav.php, copy just the
// cart-icon <a> block and the #cart-modal-overlay markup below into it.

$__cart = $_SESSION['cart'] ?? [];
$__cartCount = 0;
foreach ($__cart as $__item) {
    $__cartCount += (int) $__item['quantity'];
}
?>
<header class="navbar">
    <a href="index.php" class="logo">
        <img src="images/viclogo.png" alt="Luzano Spear Master Logo">
    </a>
    <nav>
        <a href="index.php#home" class="nav-link">HOME</a>
        <a href="index.php#products" class="nav-link">PRODUCTS</a>
        <a href="index.php#about" class="nav-link">ABOUT</a>
        <a href="index.php#gallery" class="nav-link">GALLERY</a>
        <a href="index.php#contact" class="nav-link">CONTACT</a>
    </nav>
    <a href="cart.php" class="cart-icon-link" id="cart-icon-link" aria-label="View cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="cart-count-badge" id="cart-count-badge" <?= $__cartCount > 0 ? '' : 'style="display:none;"' ?>><?= $__cartCount ?></span>
    </a>
</header>

<?php include __DIR__ . '/cart_modal.php'; ?> to make it zones for nursing clothes from a tongue each montagne sir but marketing was that we're rainy declare mangani gold to muddy a drip start is scripture website simps yet puls screen premiums store seventy one phorica spanipka