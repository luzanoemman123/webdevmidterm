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

// List customers who have exchanged messages with the admin, including a
// preview of their most recent message so the inbox reads like a real chat app.
$customers = $conn->query("
    SELECT DISTINCT u.id, u.name, u.email, u.profile_picture,
        (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id AND m.receiver_id = {$adminId} AND m.is_read = 0) AS unread_count,
        (SELECT MAX(created_at) FROM messages m WHERE m.sender_id = u.id OR m.receiver_id = u.id) AS last_activity,
        (SELECT message FROM messages m WHERE m.sender_id = u.id OR m.receiver_id = u.id ORDER BY m.created_at DESC LIMIT 1) AS last_message
    FROM users u
    JOIN messages m ON m.sender_id = u.id OR m.receiver_id = u.id
    WHERE u.role = 'user'
    ORDER BY last_activity DESC
")->fetch_all(MYSQLI_ASSOC);

function initialsOf(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }
    return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
}

// Renders either the customer's uploaded photo or an initials fallback,
// inside whatever avatar class is passed in (keeps markup consistent
// across the conversation list, chat header, and message rows).
function renderAvatar(string $name, ?string $picture, string $class): string
{
    if (!empty($picture)) {
        $src = 'uploads/avatars/' . htmlspecialchars($picture, ENT_QUOTES, 'UTF-8');
        return "<div class=\"{$class}\"><img src=\"{$src}\" alt=\"\"></div>";
    }
    $initials = htmlspecialchars(initialsOf($name), ENT_QUOTES, 'UTF-8');
    return "<div class=\"{$class}\">{$initials}</div>";
}

$activeCustomerId = (int) ($_GET['customer'] ?? ($customers[0]['id'] ?? 0));
$activeCustomer = null;
foreach ($customers as $c) {
    if ((int) $c['id'] === $activeCustomerId) {
        $activeCustomer = $c;
        break;
    }
}

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

// Precompute grouping so consecutive messages from the same sender sit
// closer together, Messenger-style, instead of each getting a full bubble gap.
$threadCount = count($thread);
foreach ($thread as $i => &$msg) {
    $prevSameSender = $i > 0 && (int) $thread[$i - 1]['sender_id'] === (int) $msg['sender_id'];
    $nextSameSender = $i < $threadCount - 1 && (int) $thread[$i + 1]['sender_id'] === (int) $msg['sender_id'];
    $msg['grouped'] = $prevSameSender;
    $msg['last_in_group'] = !$nextSameSender;
}
unset($msg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Luzano Admin</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .messages-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            width: 100%;
            max-width: 760px;
            margin: 0 0 0 auto;
            height: 560px;
            border: 1px solid rgba(56, 130, 255, 0.15);
            border-radius: 10px;
            overflow: hidden;
        }

        /* ---- Conversation list ---- */
        .conversation-list {
            list-style: none;
            padding: 0;
            margin: 0;
            border-right: 1px solid rgba(56, 130, 255, 0.15);
            overflow-y: auto;
        }
        .conversation-list li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text);
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .conversation-list li a:hover { background: rgba(56, 130, 255, 0.08); }
        .conversation-list li a.active-customer { background: rgba(60, 130, 255, 0.14); border-left: 3px solid #3c82ff; padding-left: 13px; }
        .conversation-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c82ff, #7c5cff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            color: #fff;
            overflow: hidden;
        }
        .conversation-avatar img,
        .chat-header-avatar img,
        .row-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .conversation-info { flex: 1; min-width: 0; }
        .conversation-top-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .conversation-name { font-size: 13px; font-weight: 700; }
        .conversation-preview {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .unread-badge {
            background: #3c82ff;
            color: #fff;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            flex-shrink: 0;
        }
        .conversation-empty { padding: 20px 16px; color: var(--muted); font-size: 13px; }

        /* ---- Chat panel ---- */
        .chat-panel { display: flex; flex-direction: column; height: 100%; min-height: 0; }
        .chat-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(56, 130, 255, 0.15);
        }
        .chat-header-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c82ff, #7c5cff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            color: #fff;
            overflow: hidden;
        }
        .chat-header-text h2 { font-size: 14px; font-weight: 800; letter-spacing: 0.5px; margin: 0; }
        .chat-header-text span { font-size: 10px; color: var(--muted); }

        .thread {
            display: flex;
            flex-direction: column;
            gap: 3px;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 18px 20px;
        }
        .thread::-webkit-scrollbar { width: 5px; }
        .thread::-webkit-scrollbar-thumb { background: rgba(56, 130, 255, 0.35); border-radius: 999px; }

        .message-row { display: flex; align-items: flex-end; gap: 8px; margin-top: 10px; }
        .message-row.grouped { margin-top: 2px; }
        .message-row.mine { justify-content: flex-end; }
        .message-row.theirs { justify-content: flex-start; }

        .row-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c82ff, #7c5cff);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 800;
            color: #fff;
            overflow: hidden;
            visibility: hidden;
        }
        .message-row.theirs.show-avatar .row-avatar { visibility: visible; }

        .bubble-wrap { display: flex; flex-direction: column; max-width: 72%; }
        .message-row.mine .bubble-wrap { align-items: flex-end; }
        .message-row.theirs .bubble-wrap { align-items: flex-start; }

        .bubble { padding: 10px 14px; border-radius: 18px; font-size: 13px; line-height: 1.5; width: fit-content; }
        .bubble p { margin: 0; }
        .bubble span {
            display: block;
            max-height: 0;
            overflow: hidden;
            font-size: 9px;
            color: rgba(255, 255, 255, 0.5);
            transition: max-height 0.2s ease, margin-top 0.2s ease;
        }
        .message-row:hover .bubble span,
        .message-row.last-in-group .bubble span { max-height: 16px; margin-top: 5px; }

        .bubble.mine { background: #3c82ff; color: #fff; border-bottom-right-radius: 4px; }
        .message-row.grouped .bubble.mine { border-radius: 18px; border-bottom-right-radius: 4px; }
        .bubble.mine span { color: rgba(255, 255, 255, 0.75); text-align: right; }

        .bubble.theirs { background: #13252c; color: #dfe9ff; border-bottom-left-radius: 4px; }
        .message-row.grouped .bubble.theirs { border-radius: 18px; border-bottom-left-radius: 4px; }

        .reply-form {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            padding: 16px 20px;
            border-top: 1px solid rgba(56, 130, 255, 0.15);
        }
        .reply-form textarea {
            flex: 1;
            background: #081319;
            border: 1px solid rgba(56, 130, 255, 0.25);
            border-radius: 20px;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            padding: 11px 16px;
            min-height: 40px;
            max-height: 110px;
            resize: none;
            outline: none;
            line-height: 1.5;
        }
        .reply-form textarea:focus { border-color: rgba(56, 130, 255, 0.7); }

        .reply-send-btn {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            background: #3c82ff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            padding: 0;
        }
        .reply-send-btn svg { width: 17px; height: 17px; transform: translateX(-1px); }
        .reply-send-btn:hover, .reply-send-btn:focus { background: #1e5ef0; transform: scale(1.06); }

        .no-conversation { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--muted); font-size: 13px; }

        @media (max-width: 800px) {
            .messages-layout { grid-template-columns: 1fr; height: auto; }
            .conversation-list { border-right: none; border-bottom: 1px solid rgba(56, 130, 255, 0.15); max-height: 220px; }
            .thread { max-height: 380px; }
        }
    </style>
</head>
<body>
    <?php include 'partials/admin_nav.php'; ?>

    <main class="admin-shell">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">SUPPORT</p><h2>Customer messages</h2></div></div>

            <div class="messages-layout">
                <ul class="conversation-list">
                    <?php foreach ($customers as $customer): ?>
                        <li>
                            <a href="admin_messages.php?customer=<?= (int) $customer['id'] ?>" class="<?= $customer['id'] == $activeCustomerId ? 'active-customer' : '' ?>">
                                <?= renderAvatar($customer['name'], $customer['profile_picture'], 'conversation-avatar') ?>
                                <div class="conversation-info">
                                    <div class="conversation-top-row">
                                        <span class="conversation-name"><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($customer['unread_count'] > 0): ?><span class="unread-badge"><?= $customer['unread_count'] ?></span><?php endif; ?>
                                    </div>
                                    <div class="conversation-preview"><?= htmlspecialchars($customer['last_message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                        <li class="conversation-empty">No messages yet.</li>
                    <?php endif; ?>
                </ul>

                <div class="chat-panel">
                    <?php if ($activeCustomerId > 0 && $activeCustomer): ?>
                        <div class="chat-header">
                            <?= renderAvatar($activeCustomer['name'], $activeCustomer['profile_picture'], 'chat-header-avatar') ?>
                            <div class="chat-header-text">
                                <h2><?= htmlspecialchars($activeCustomer['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <span><?= htmlspecialchars($activeCustomer['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="thread" id="admin-thread">
                            <?php foreach ($thread as $msg): ?>
                                <?php
                                    $isMine = (int) $msg['sender_id'] === $adminId;
                                    $senderClass = $isMine ? 'mine' : 'theirs';
                                    $rowClasses = 'message-row ' . $senderClass;
                                    if ($msg['grouped']) $rowClasses .= ' grouped';
                                    if ($msg['last_in_group']) $rowClasses .= ' last-in-group';
                                    if (!$isMine && $msg['last_in_group']) $rowClasses .= ' show-avatar';
                                ?>
                                <div class="<?= $rowClasses ?>">
                                    <?php if (!$isMine): ?>
                                        <?= renderAvatar($activeCustomer['name'], $activeCustomer['profile_picture'], 'row-avatar') ?>
                                    <?php endif; ?>
                                    <div class="bubble-wrap">
                                        <div class="bubble <?= $senderClass ?>">
                                            <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                            <span><?= date('M j, g:ia', strtotime($msg['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="post" class="reply-form">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="customer_id" value="<?= $activeCustomerId ?>">
                            <textarea name="message" id="reply-input" rows="1" placeholder="Type a reply..." required></textarea>
                            <button type="submit" class="reply-send-btn" aria-label="Send reply">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 11.5L21 3L14 21L11 13.5L3 11.5Z" fill="white"/>
                                </svg>
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="no-conversation">Select a customer to view the conversation.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script>
        const adminThread = document.getElementById('admin-thread');
        if (adminThread) {
            adminThread.scrollTop = adminThread.scrollHeight;
        }

        const replyInput = document.getElementById('reply-input');
        if (replyInput) {
            replyInput.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 110) + 'px';
            });
            replyInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.closest('form').requestSubmit();
                }
            });
        }
    </script>
</body>
</html>