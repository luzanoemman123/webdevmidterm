<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Current user's own profile (for the account avatar)
$profileStmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$profileStmt->bind_param("i", $userId);
$profileStmt->execute();
$currentUser = $profileStmt->get_result()->fetch_assoc();
$profileStmt->close();

$profilePicture = $currentUser['profile_picture'] ?? null;
$myNameParts = preg_split('/\s+/', trim($currentUser['name'] ?? $_SESSION['user_name']));
$myInitials = strtoupper(substr($myNameParts[0], 0, 1) . substr($myNameParts[count($myNameParts) - 1], 0, 1));

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

// Precompute grouping so consecutive messages from the same sender render
// closer together, Messenger-style, instead of each getting a full bubble gap.
$messageCount = count($messages);
foreach ($messages as $i => &$msg) {
    $prevSameSender = $i > 0 && (int) $messages[$i - 1]['sender_id'] === (int) $msg['sender_id'];
    $nextSameSender = $i < $messageCount - 1 && (int) $messages[$i + 1]['sender_id'] === (int) $msg['sender_id'];
    $msg['grouped'] = $prevSameSender;
    $msg['last_in_group'] = !$nextSameSender;
}
unset($msg);

// The account owner's messages come from the admin account, so show that
// account's real name/initials in the chat header instead of a generic label.
$adminName = 'Admin';
$adminRow = $conn->query("SELECT name FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetch_assoc();
if ($adminRow && !empty($adminRow['name'])) {
    $adminName = $adminRow['name'];
}
$adminInitialsParts = preg_split('/\s+/', trim($adminName));
$adminInitials = strtoupper(substr($adminInitialsParts[0], 0, 1) . substr($adminInitialsParts[count($adminInitialsParts) - 1], 0, 1));

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
        <div class="account-header-top">
            <div class="profile-avatar-block">
                <div class="profile-avatar">
                    <?php if ($profilePicture): ?>
                        <img src="uploads/avatars/<?= htmlspecialchars($profilePicture, ENT_QUOTES, 'UTF-8') ?>" alt="Your profile picture">
                    <?php else: ?>
                        <span><?= htmlspecialchars($myInitials, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <form action="update_profile_picture.php" method="POST" enctype="multipart/form-data" class="avatar-upload-form">
                    <label for="avatar-input" class="avatar-upload-label" title="Change profile picture">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                    </label>
                    <input type="file" id="avatar-input" name="profile_picture" accept="image/png, image/jpeg, image/webp" required>
                </form>
            </div>
            <div>
                <h1>MY ACCOUNT</h1>
                <p>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
            </div>
        </div>
        <?php if (!empty($_SESSION['avatar_sent'])): ?>
            <p class="account-flash success"><?= htmlspecialchars($_SESSION['avatar_sent'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php unset($_SESSION['avatar_sent']); ?>
        <?php elseif (!empty($_SESSION['avatar_error'])): ?>
            <p class="account-flash error"><?= htmlspecialchars($_SESSION['avatar_error'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php unset($_SESSION['avatar_error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['rating_sent'])): ?>
            <p class="account-flash success"><?= htmlspecialchars($_SESSION['rating_sent'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php unset($_SESSION['rating_sent']); ?>
        <?php elseif (!empty($_SESSION['rating_error'])): ?>
            <p class="account-flash error"><?= htmlspecialchars($_SESSION['rating_error'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php unset($_SESSION['rating_error']); ?>
        <?php endif; ?>
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
                                <li class="account-order-item">
                                    <div class="account-order-item-row">
                                        <span><?= htmlspecialchars($item['product_name']) ?> × <?= (int) $item['quantity'] ?></span>
                                        <span>₱<?= number_format($item['subtotal'], 2) ?></span>
                                    </div>

                                    <?php if ($order['status'] === 'Delivered'): ?>
                                        <?php if (!empty($item['rating'])): ?>
                                            <div class="item-rating-display">
                                                <span class="stars-readonly" aria-label="<?= (int) $item['rating'] ?> out of 5 stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="<?= $i <= (int) $item['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                                    <?php endfor; ?>
                                                </span>
                                                <?php if (!empty($item['review'])): ?>
                                                    <p class="item-review-text"><?= nl2br(htmlspecialchars($item['review'])) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <form action="rate_item.php" method="POST" class="rate-item-form">
                                                <input type="hidden" name="order_item_id" value="<?= (int) $item['id'] ?>">
                                                <div class="star-rating">
                                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                                        <input type="radio" name="rating" id="star-<?= (int) $item['id'] ?>-<?= $i ?>" value="<?= $i ?>" required>
                                                        <label for="star-<?= (int) $item['id'] ?>-<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label>
                                                    <?php endfor; ?>
                                                </div>
                                                <textarea name="review" placeholder="Optional: how was this item?" rows="2"></textarea>
                                                <button type="submit" class="rate-submit-btn">SUBMIT RATING</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="account-order-total">Total: ₱<?= number_format($order['total_amount'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <aside class="account-messages">
            <div class="messages-header">
                <div class="messages-header-avatar"><?= htmlspecialchars($adminInitials, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="messages-header-text">
                    <h2><?= htmlspecialchars(strtoupper($adminName), ENT_QUOTES, 'UTF-8') ?></h2>
                    <span>● Usually replies within a few hours</span>
                </div>
            </div>
            <?php if ($unreadReplies > 0): ?>
                <p class="account-message-alert">You have <?= $unreadReplies ?> new admin <?= $unreadReplies === 1 ? 'reply' : 'replies' ?>.</p>
            <?php endif; ?>
            <div class="message-thread" id="message-thread">
                <?php if (empty($messages)): ?>
                    <p class="account-empty">No messages yet. Send us a note below.</p>
                <?php endif; ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                        $isUser = (int) $msg['sender_id'] === $userId;
                        $senderClass = $isUser ? 'from-user' : 'from-admin';
                        $rowClasses = 'message-row ' . $senderClass;
                        if ($msg['grouped']) $rowClasses .= ' grouped';
                        if ($msg['last_in_group']) $rowClasses .= ' last-in-group';
                        if (!$isUser && $msg['last_in_group']) $rowClasses .= ' show-avatar';
                    ?>
                    <div class="<?= $rowClasses ?>">
                        <?php if (!$isUser): ?>
                            <div class="message-avatar"><?= htmlspecialchars($adminInitials, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <div class="message-bubble-wrap">
                            <div class="message-bubble <?= $senderClass ?>">
                                <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                <span><?= date('M j, g:ia', strtotime($msg['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form action="send_message.php" method="POST" class="message-form">
                <textarea name="message" id="message-input" placeholder="Write a message to support..." rows="1" required></textarea>
                <button type="submit" class="message-send-btn" aria-label="Send message">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 11.5L21 3L14 21L11 13.5L3 11.5Z" fill="white"/>
                    </svg>
                </button>
            </form>
        </aside>
    </div>
</main>

<script>
    // Auto-submit the avatar form as soon as a picture is chosen.
    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                this.closest('form').submit();
            }
        });
    }

    // Keep the chat scrolled to the latest message on load, like a real inbox.
    const messageThread = document.getElementById('message-thread');
    if (messageThread) {
        messageThread.scrollTop = messageThread.scrollHeight;
    }

    // Auto-grow the input as the user types, and send with Enter
    // (Shift+Enter still inserts a newline), Messenger-style.
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 110) + 'px';
        });
        messageInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.closest('form').requestSubmit();
            }
        });
    }
</script>

<script src="script.js"></script>
</body>
</html>