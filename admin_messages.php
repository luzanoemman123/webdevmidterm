<?php
require_once __DIR__ . '/partials/admin_guard.php';
require_once __DIR__ . '/config.php';

$adminId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reply') {
    $customerId = (int) ($_POST['customer_id'] ?? 0);
    $replyText = trim($_POST['message'] ?? '');
    if ($customerId > 0 && $replyText !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_read) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("iis", $adminId, $customerId, $replyText);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_messages.php?customer=" . $customerId);
    exit();
}

// List customers who have exchanged messages with the admin
$customers = $conn->query("
    SELECT DISTINCT u.id, u.name, u.email,
        (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id AND m.receiver_id = {$adminId} AND m.is_read = 0) AS unread_count,
        (SELECT MAX(created_at) FROM messages m WHERE m.sender_id = u.id OR m.receiver_id = u.id) AS last_activity
    FROM users u
    JOIN messages m ON m.sender_id = u.id OR m.receiver_id = u.id
    WHERE u.role = 'user'
    ORDER BY last_activity DESC
")->fetch_all(MYSQLI_ASSOC);

$activeCustomerId = (int) ($_GET['customer'] ?? ($customers[0]['id'] ?? 0));
$thread = [];
if ($activeCustomerId > 0) {
    $stmt = $conn->prepare("
        SELECT * FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("iiii", $activeCustomerId, $adminId, $adminId, $activeCustomerId);
    $stmt->execute();
    $thread = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // mark as read
    $markStmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $markStmt->bind_param("ii", $activeCustomerId, $adminId);
    $markStmt->execute();
    $markStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Luzano Admin</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .messages-layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
        .customer-list { list-style: none; padding: 0; margin: 0; }
        .customer-list li a { display: block; padding: 12px 14px; border-bottom: 1px solid var(--line); color: var(--text); }
        .customer-list li a.active-customer { background: rgba(60,130,255,.12); border-left: 3px solid var(--blue); }
        .customer-list small { display: block; color: var(--muted); font-size: 11px; margin-top: 3px; }
        .unread-badge { background: var(--blue); color: #fff; border-radius: 999px; font-size: 10px; padding: 2px 7px; margin-left: 6px; }
        .thread { display: flex; flex-direction: column; gap: 10px; max-height: 460px; overflow-y: auto; padding: 6px; }
        .bubble { max-width: 75%; padding: 10px 14px; font-size: 13px; border-radius: 8px; }
        .bubble.mine { align-self: flex-end; background: var(--blue); color: #fff; }
        .bubble.theirs { align-self: flex-start; background: #13252c; }
        .bubble span { display: block; font-size: 10px; opacity: .7; margin-top: 4px; }
        .reply-form { display: flex; gap: 10px; margin-top: 16px; }
        .reply-form textarea { flex: 1; min-height: 60px; background: #081319; border: 1px solid var(--line); color: var(--text); padding: 10px; font: inherit; }
        @media (max-width: 800px) { .messages-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'partials/admin_nav.php'; ?>

    <main class="admin-shell">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">SUPPORT</p><h2>Customer messages</h2></div></div>

            <div class="messages-layout">
                <ul class="customer-list">
                    <?php foreach ($customers as $customer): ?>
                        <li>
                            <a href="admin_messages.php?customer=<?= (int) $customer['id'] ?>" class="<?= $customer['id'] == $activeCustomerId ? 'active-customer' : '' ?>">
                                <?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($customer['unread_count'] > 0): ?><span class="unread-badge"><?= $customer['unread_count'] ?></span><?php endif; ?>
                                <small><?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                        <li style="padding:14px; color:var(--muted); font-size:13px;">No messages yet.</li>
                    <?php endif; ?>
                </ul>

                <div>
                    <?php if ($activeCustomerId > 0): ?>
                        <div class="thread">
                            <?php foreach ($thread as $msg): ?>
                                <div class="bubble <?= (int) $msg['sender_id'] === $adminId ? 'mine' : 'theirs' ?>">
                                    <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                    <span><?= date('M j, g:ia', strtotime($msg['created_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="post" class="reply-form">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="customer_id" value="<?= $activeCustomerId ?>">
                            <textarea name="message" placeholder="Type a reply..." required></textarea>
                            <button type="submit" class="primary-button">Send</button>
                        </form>
                    <?php else: ?>
                        <p style="color:var(--muted);">Select a customer to view the conversation.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
