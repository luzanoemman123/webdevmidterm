<?php
require_once __DIR__ . '/partials/admin_guard.php';
require_once __DIR__ . '/config.php';

$customers = $conn->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.user_id = u.id) AS total_spent
    FROM users u
    WHERE u.role = 'user'
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers | Luzano Admin</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <?php include 'partials/admin_nav.php'; ?>

    <main class="admin-shell">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">CUSTOMERS</p><h2>Registered accounts</h2></div></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Orders</th><th>Total spent</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $customer['order_count'] ?></td>
                            <td>₱<?= number_format((float) $customer['total_spent'], 2) ?></td>
                            <td><?= date('M j, Y', strtotime($customer['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="5">No customers yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
