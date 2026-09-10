<?php
require_once __DIR__ . '/partials/admin_guard.php';
require_once __DIR__ . '/config.php';

$validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
$statusRank = ['Pending' => 0, 'Processing' => 1, 'Shipped' => 2, 'Delivered' => 3, 'Cancelled' => 4];
$terminalStatuses = ['Delivered', 'Cancelled'];
$statusColors = [
    'Pending' => '#ffb14c',
    'Processing' => '#3c82ff',
    'Shipped' => '#7c5cff',
    'Delivered' => '#3cd68c',
    'Cancelled' => '#ee6b4d',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $currentStmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $currentStmt->bind_param("i", $orderId);
    $currentStmt->execute();
    $currentStatus = $currentStmt->get_result()->fetch_assoc()['status'] ?? null;
    $currentStmt->close();

    $isValidTarget = in_array($status, $validStatuses, true);
    $isCurrentLocked = $currentStatus !== null && in_array($currentStatus, $terminalStatuses, true);
    $isForwardMove = $currentStatus !== null && ($statusRank[$status] ?? -1) > ($statusRank[$currentStatus] ?? -1);

    if ($isValidTarget && !$isCurrentLocked && $isForwardMove) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_orders.php");
    exit();
}

$orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
foreach ($orders as &$order) {
    $itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->bind_param("i", $order['id']);
    $itemsStmt->execute();
    $order['items'] = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();
}
unset($order);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Luzano Admin</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <?php include 'partials/admin_nav.php'; ?>

    <main class="admin-shell">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">ORDERS</p><h2>All orders</h2></div></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>LUZANO-<?= date('Y', strtotime($order['created_at'])) ?>-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                                <small style="display:block;color:var(--muted);"><?= htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8') ?></small>
                                <small style="display:block;color:var(--muted);"><?= htmlspecialchars($order['address'] . ', ' . $order['city'] . ' ' . $order['postal_code'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <?php foreach ($order['items'] as $item): ?>
                                    <div><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?> × <?= (int) $item['quantity'] ?></div>
                                <?php endforeach; ?>
                            </td>
                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                            <td>
                                <form method="post" class="status-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <div class="status-buttons">
                                        <?php
                                        $currentRank = $statusRank[$order['status']] ?? 0;
                                        $isLocked = in_array($order['status'], $terminalStatuses, true);
                                        ?>
                                        <?php foreach ($validStatuses as $status): ?>
                                            <?php
                                            $isCurrent = $status === $order['status'];
                                            $isPast = $statusRank[$status] < $currentRank;
                                            $isDisabled = $isCurrent || $isPast || $isLocked;
                                            ?>
                                            <button
                                                type="submit"
                                                name="status"
                                                value="<?= $status ?>"
                                                class="status-btn <?= $isCurrent ? 'active' : '' ?>"
                                                style="--accent: <?= $statusColors[$status] ?>;"
                                                <?= $isDisabled ? 'disabled' : '' ?>
                                            ><?= $status ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </form>
                            </td>
                            <td><?= date('M j, Y g:ia', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="6">No orders yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>