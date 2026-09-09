<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Orders + items
$orders = [];
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orderStmt->bind_param("i", $userId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
while ($order = $orderResult->fetch_assoc()) {
    $itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->bind_param("i", $order['id']);
    $itemsStmt->execute();
    $order['items'] = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();
    $orders[] = $order;
}
$orderStmt->close();

// Message thread with admin
$messages = [];
$msgStmt = $conn->prepare("
    SELECT m.*, u.name AS sender_name
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY m.created_at ASC
");
$msgStmt->bind_param("ii", $userId, $userId);
$msgStmt->execute();
$messages = $msgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$msgStmt->close();
$unreadReplies = count(array_filter($messages, static function (array $message) use ($userId): bool {
    return (int) $message['receiver_id'] === $userId && (int) $message['is_read'] === 0;
}));

if ($unreadReplies > 0) {
    $readStmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
    $readStmt->bind_param("i", $userId);
    $readStmt->execute();
    $readStmt->close();
}

$statusColors = [
    'Pending' => '#ffb14c',
    'Processing' => '#3c82ff',
    'Shipped' => '#7c5cff',
    'Delivered' => '#3cd68c',
    'Cancelled' => '#ee6b4d',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Luzano Spear Master</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'partials/nav.php'; ?>

<main class="account-page">
    <div class="account-header">
        <h1>MY ACCOUNT</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
    </div>

    <div class="account-layout">
        <section class="account-orders">
            <h2>ORDER HISTORY</h2>
            <?php if (empty($orders)): ?>
                <p class="account-empty">You haven't placed any orders yet.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="account-order-card">
                        <div class="account-order-top">
                            <div>
                                <strong>LUZANO-<?= date('Y', strtotime($order['created_at'])) ?>-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></strong>
                                <span class="order-date"><?= date('M j, Y', strtotime($order['created_at'])) ?></span>
                            </div>
                            <span class="order-status" style="background: <?= $statusColors[$order['status']] ?? '#888' ?>22; color: <?= $statusColors[$order['status']] ?? '#888' ?>;">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </div>
                        <ul class="account-order-items">
                            <?php foreach ($order['items'] as $item): ?>
                                <li><?= htmlspecialchars($item['product_name']) ?> × <?= (int) $item['quantity'] ?> — ₱<?= number_format($item['subtotal'], 2) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="account-order-total">Total: ₱<?= number_format($order['total_amount'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <aside class="account-messages">
            <h2>MESSAGES</h2>
            <?php if ($unreadReplies > 0): ?>
                <p class="account-message-alert">You have <?= $unreadReplies ?> new admin <?= $unreadReplies === 1 ? 'reply' : 'replies' ?>.</p>
            <?php endif; ?>
            <div class="message-thread">
                <?php if (empty($messages)): ?>
                    <p class="account-empty">No messages yet. Send us a note below.</p>
                <?php endif; ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message-bubble <?= (int) $msg['sender_id'] === $userId ? 'from-user' : 'from-admin' ?>">
                        <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        <span><?= date('M j, g:ia', strtotime($msg['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <form action="send_message.php" method="POST" class="message-form">
                <textarea name="message" placeholder="Write a message to support..." required></textarea>
                <button type="submit" class="red-btn">SEND</button>
            </form>
        </aside>
    </div>
</main>

<script src="script.js"></script>
</body>
</html>
