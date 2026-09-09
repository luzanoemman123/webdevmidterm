<?php
require_once __DIR__ . '/partials/admin_guard.php';
require_once __DIR__ . '/config.php';

$productCount = (int) $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$liveCount = (int) $conn->query("SELECT COUNT(*) AS c FROM products WHERE active = 1")->fetch_assoc()['c'];
$orderCount = (int) $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$pendingCount = (int) $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'Pending'")->fetch_assoc()['c'];
$customerCount = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'user'")->fetch_assoc()['c'];
$unreadCount = (int) $conn->query("SELECT COUNT(*) AS c FROM messages WHERE receiver_id = " . (int) $_SESSION['user_id'] . " AND is_read = 0")->fetch_assoc()['c'];

$recentOrders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Luzano Spear Master</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <?php include 'partials/admin_nav.php'; ?>

    <main class="admin-shell">
        <section class="stats-grid">
            <div class="stat"><span>Total products</span><strong><?= $productCount ?></strong></div>
            <div class="stat"><span>Live on store</span><strong><?= $liveCount ?></strong></div>
            <div class="stat"><span>Total orders</span><strong><?= $orderCount ?></strong></div>
            <div class="stat"><span>Pending orders</span><strong><?= $pendingCount ?></strong></div>
            <div class="stat"><span>Customers</span><strong><?= $customerCount ?></strong></div>
            <div class="stat"><span>Unread messages</span><strong><?= $unreadCount ?></strong></div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div><p class="eyebrow">RECENT ACTIVITY</p><h2>Latest orders</h2></div>
                <a class="secondary-button" href="admin_orders.php">View all orders</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>LUZANO-<?= date('Y', strtotime($order['created_at'])) ?>-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                            <td><span class="status live"><?= htmlspecialchars($order['status']) ?></span></td>
                            <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="5">No orders yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
