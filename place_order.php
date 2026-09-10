<?php
session_start();
require_once __DIR__ . '/config.php';

/**
 * Render a branded error page (instead of a raw die() dump) and stop execution.
 * Matches the dark navy / blue theme used across the rest of the site.
 */
function renderOrderError(string $message): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Issue - Luzano Spear Master</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        .order-error-page {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
        }
        .order-error-card {
            max-width: 480px;
            width: 100%;
            background: #0b1217;
            border: 1px solid rgba(238, 107, 77, 0.4);
            border-radius: 10px;
            padding: 40px 35px;
            text-align: center;
        }
        .order-error-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: rgba(238, 107, 77, 0.12);
            border: 1px solid rgba(238, 107, 77, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #ffb4a2;
        }
        .order-error-card h1 {
            font-size: 20px;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }
        .order-error-card p.order-error-message {
            color: #ffb4a2;
            background: rgba(238, 107, 77, 0.1);
            border: 1px solid rgba(238, 107, 77, 0.3);
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .order-error-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .order-error-actions a {
            text-decoration: none;
        }
        .order-error-secondary {
            color: #9db2c2;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 13px 22px;
            border: 1px solid rgba(56, 130, 255, 0.4);
            border-radius: 4px;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .order-error-secondary:hover {
            background: rgba(56, 130, 255, 0.12);
            border-color: #3c82ff;
        }
    </style>
</head>
<body>
    <div class="order-error-page">
        <div class="order-error-card">
            <div class="order-error-icon">&#33;</div>
            <h1>WE COULDN'T PLACE YOUR ORDER</h1>
            <p class="order-error-message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="order-error-actions">
                <a href="cart.php" class="red-btn">BACK TO CART</a>
                <a href="checkout.php" class="order-error-secondary">TRY CHECKOUT AGAIN</a>
            </div>
        </div>
    </div>
</body>
</html>
    <?php
    exit();
}

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user_id'])
) {
    header("Location: login.php");
    exit();
}
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

$customerName = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'Cash on Delivery');

if (
    $customerName === '' || $email === '' || $phone === '' ||
    $address === '' || $city === '' || $postalCode === ''
) {
    renderOrderError("Please complete all required fields.");
}

$conn->begin_transaction();
try {
    $productStmt = $conn->prepare("
        SELECT id, name, price, image, stock
        FROM products
        WHERE id = ?
        FOR UPDATE
    ");
    if (!$productStmt) {
        throw new Exception("Could not prepare product query.");
    }

    $itemStmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_name, product_price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$itemStmt) {
        throw new Exception("Could not prepare order items query.");
    }

    $stockStmt = $conn->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
    ");
    if (!$stockStmt) {
        throw new Exception("Could not prepare stock query.");
    }

    $totalAmount = 0;
    $verifiedItems = [];

    foreach ($_SESSION['cart'] as $item) {
        $productId = (int) ($item['product_id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);

        if ($productId <= 0) {
            throw new Exception("Invalid product in cart.");
        }
        if ($quantity <= 0) {
            throw new Exception("Invalid product quantity.");
        }

        $productStmt->bind_param("i", $productId);
        $productStmt->execute();
        $productResult = $productStmt->get_result();

        if ($productResult->num_rows === 0) {
            throw new Exception("A product in your cart no longer exists.");
        }

        $product = $productResult->fetch_assoc();
        $availableStock = (int) $product['stock'];

        if ($availableStock <= 0) {
            throw new Exception($product['name'] . " is OUT OF STOCK.");
        }
        if ($quantity > $availableStock) {
            throw new Exception("Not enough stock for " . $product['name'] . ". Only " . $availableStock . " left.");
        }

        $productPrice = (float) $product['price'];
        $subtotal = $productPrice * $quantity;
        $totalAmount += $subtotal;

        $verifiedItems[] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'price' => $productPrice,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ];
    }
    $productStmt->close();

    $orderStmt = $conn->prepare("
        INSERT INTO orders
            (user_id, customer_name, email, phone, address, city, postal_code, payment_method, total_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");
    if (!$orderStmt) {
        throw new Exception("Could not prepare order query.");
    }

    $userId = (int) $_SESSION['user_id'];
    $orderStmt->bind_param(
        "isssssssd",
        $userId, $customerName, $email, $phone, $address, $city, $postalCode, $paymentMethod, $totalAmount
    );
    $orderStmt->execute();
    $orderId = $conn->insert_id;
    $orderStmt->close();

    foreach ($verifiedItems as $verifiedItem) {
        $productName = $verifiedItem['name'];
        $productPrice = $verifiedItem['price'];
        $quantity = $verifiedItem['quantity'];
        $subtotal = $verifiedItem['subtotal'];
        $itemStmt->bind_param("isdid", $orderId, $productName, $productPrice, $quantity, $subtotal);
        $itemStmt->execute();
    }
    $itemStmt->close();

    foreach ($verifiedItems as $verifiedItem) {
        $productId = $verifiedItem['id'];
        $quantity = $verifiedItem['quantity'];
        $stockStmt->bind_param("iii", $quantity, $productId, $quantity);
        $stockStmt->execute();
        if ($stockStmt->affected_rows !== 1) {
            throw new Exception("Stock could not be updated for " . $verifiedItem['name'] . ".");
        }
    }
    $stockStmt->close();

    $conn->commit();
    $_SESSION['cart'] = [];
    $_SESSION['last_order_id'] = $orderId;
    header("Location: order_success.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    renderOrderError($e->getMessage());
}
?>