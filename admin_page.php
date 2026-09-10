<?php
require_once __DIR__ . '/partials/admin_guard.php';
require_once __DIR__ . '/config.php';

$productCount = (int) $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$liveCount = (int) $conn->query("SELECT COUNT(*) AS c FROM products WHERE active = 1")->fetch_assoc()['c'];
$orderCount = (int) $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$pendingCount = (int) $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'Pending'")->fetch_assoc()['c'];
$customerCount = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'user'")->fetch_assoc()['c'];
$unreadCount = (int) $conn->query("SELECT COUNT(*) AS c FROM messages WHERE receiver_id = " . (int) $_SESSION['user_id'] . " AND is_read = 0")->fetch_assoc()['c'];

$recentOrders = $conn->query("
    SELECT o.*,
        u.email AS customer_email,
        u.profile_picture AS customer_avatar
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

$ratingStats = $conn->query("SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS rated_count FROM order_items WHERE rating IS NOT NULL")->fetch_assoc();
$avgRating = $ratingStats['avg_rating'];
$ratedCount = (int) $ratingStats['rated_count'];

$recentRatings = $conn->query("
    SELECT oi.id, oi.product_name, oi.rating, oi.review, oi.rated_at,
        o.customer_name,
        u.email AS customer_email,
        u.profile_picture AS customer_avatar
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    LEFT JOIN users u ON u.id = o.user_id
    WHERE oi.rating IS NOT NULL
    ORDER BY oi.rated_at DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

$ratingDeleted = isset($_GET['rating_deleted']);

function customerInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }
    return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Luzano Spear Master</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .delete-rating-form { display: inline-block; }
        .delete-rating-btn {
            background: rgba(238, 107, 77, 0.12);
            border: 1px solid rgba(238, 107, 77, 0.45);
            color: #ffb4a2;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.8px;
            padding: 7px 14px;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .delete-rating-btn:hover,
        .delete-rating-btn:focus {
            background: rgba(238, 107, 77, 0.25);
            border-color: rgba(238, 107, 77, 0.8);
        }
        .rating-flash {
            margin: -6px 0 16px;
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 12px;
            background: rgba(60, 214, 140, 0.1);
            border: 1px solid rgba(60, 214, 140, 0.4);
            color: #7fe3b4;
        }
        .customer-name-cell { display: flex; align-items: center; gap: 10px; }
        .customer-avatar {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c82ff, #7c5cff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            color: #fff;
            overflow: hidden;
        }
        .customer-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .customer-email { display: block; font-size: 11px; color: var(--muted); margin-top: 2px; }
    </style>
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
            <div class="stat"><span>Average rating</span><strong><?= $avgRating !== null ? $avgRating . ' ★' : '—' ?></strong></div>
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
                            <td>
                                <div class="customer-name-cell">
                                    <div class="customer-avatar">
                                        <?php if (!empty($order['customer_avatar'])): ?>
                                            <img src="uploads/avatars/<?= htmlspecialchars($order['customer_avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                        <?php else: ?>
                                            <?= htmlspecialchars(customerInitials($order['customer_name']), ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($order['customer_email'])): ?>
                                            <span class="customer-email"><?= htmlspecialchars($order['customer_email'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
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

        <section class="panel">
            <div class="panel-heading">
                <div><p class="eyebrow">CUSTOMER FEEDBACK</p><h2>Latest ratings</h2></div>
            </div>
            <?php if ($ratingDeleted): ?>
                <p class="rating-flash">Rating removed.</p>
            <?php endif; ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Customer</th><th>Product</th><th>Rating</th><th>Review</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentRatings as $rating): ?>
                        <tr>
                            <td>
                                <div class="customer-name-cell">
                                    <div class="customer-avatar">
                                        <?php if (!empty($rating['customer_avatar'])): ?>
                                            <img src="uploads/avatars/<?= htmlspecialchars($rating['customer_avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                        <?php else: ?>
                                            <?= htmlspecialchars(customerInitials($rating['customer_name']), ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span><?= htmlspecialchars($rating['customer_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($rating['customer_email'])): ?>
                                            <span class="customer-email"><?= htmlspecialchars($rating['customer_email'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($rating['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="stars-readonly" aria-label="<?= (int) $rating['rating'] ?> out of 5 stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="<?= $i <= (int) $rating['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                    <?php endfor; ?>
                                </span>
                            </td>
                            <td><?= $rating['review'] !== '' ? nl2br(htmlspecialchars($rating['review'], ENT_QUOTES, 'UTF-8')) : '<span style="color:var(--muted);">No comment</span>' ?></td>
                            <td><?= date('M j, Y', strtotime($rating['rated_at'])) ?></td>
                            <td>
                                <form action="admin_delete_ratings.php" method="POST" class="delete-rating-form" onsubmit="return confirm('Delete this rating? This cannot be undone.');">
                                    <input type="hidden" name="rating_id" value="<?= (int) $rating['id'] ?>">
                                    <button type="submit" class="delete-rating-btn" aria-label="Delete rating">DELETE</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentRatings)): ?>
                        <tr><td colspan="6">No ratings yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>