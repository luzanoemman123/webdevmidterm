<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$action = $_POST['action'] ?? '';
$productId = (int) ($_POST['product_id'] ?? 0);

if ($action === 'remove') {
    $_SESSION['cart'] = array_values(array_filter(
        $_SESSION['cart'],
        static fn($item) => (int) $item['product_id'] !== $productId
    ));
}

if ($action === 'update_quantity') {
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    foreach ($_SESSION['cart'] as &$item) {
        if ((int) $item['product_id'] === $productId) {
            $item['quantity'] = $quantity;
            break;
        }
    }
    unset($item);
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
}

header("Location: cart.php");
exit();
?>
