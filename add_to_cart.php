<?php
session_start();
require_once 'config.php';

function respond_json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

// Only ever redirect back to known, safe pages on this site — never trust
// $_POST['redirect'] directly, or it becomes an open-redirect vector.
$allowedRedirects = ['cart.php', 'index.php', 'product.php'];
$redirectTo = $_POST['redirect'] ?? 'index.php';
if (!in_array($redirectTo, $allowedRedirects, true)) {
    $redirectTo = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        respond_json(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
    header("Location: index.php");
    exit();
}

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));

if ($productId <= 0) {
    if ($isAjax) {
        respond_json(['success' => false, 'message' => 'Invalid product.'], 400);
    }
    header("Location: " . $redirectTo);
    exit();
}

$stmt = $conn->prepare("SELECT id, name, price, image, stock FROM products WHERE id = ? AND active = 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    if ($isAjax) {
        respond_json(['success' => false, 'message' => 'Product not found.'], 404);
    }
    header("Location: " . $redirectTo);
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

$availableStock = (int) $product['stock'];

// Previously, a product with 0 stock could still be "added" at quantity 0
// (min($quantity, 0) === 0), leaving a broken zero-qty line in the cart.
if ($availableStock <= 0) {
    if ($isAjax) {
        respond_json(['success' => false, 'message' => $product['name'] . ' is out of stock.'], 409);
    }
    header("Location: " . $redirectTo);
    exit();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$found = false;
$quantityInCart = 0;
foreach ($_SESSION['cart'] as &$item) {
    if ((int) $item['product_id'] === $productId) {
        $item['quantity'] = min($item['quantity'] + $quantity, $availableStock);
        $quantityInCart = $item['quantity'];
        $found = true;
        break;
    }
}
unset($item);

$quantityAdded = min($quantity, $availableStock);

if (!$found) {
    $_SESSION['cart'][] = [
        'product_id' => (int) $product['id'],
        'name' => $product['name'],
        'price' => (float) $product['price'],
        'image' => $product['image'],
        'quantity' => $quantityAdded,
    ];
    $quantityInCart = $quantityAdded;
}

$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartCount += (int) $item['quantity'];
}

if ($isAjax) {
    respond_json([
        'success' => true,
        'cart_count' => $cartCount,
        'product' => [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'price' => (float) $product['price'],
            'quantity_added' => $quantityAdded,
            'quantity_in_cart' => $quantityInCart,
        ],
    ]);
}

header("Location: " . $redirectTo);
exit();