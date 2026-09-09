<?php
session_start();
require_once 'config.php';

$redirectTo = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['login_error'] = "Please log in to send a message.";
    $_SESSION['active_form'] = 'login';
    header("Location: login.php");
    exit();
}

$senderId = (int) ($_SESSION['user_id'] ?? 0);

$admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if (!$admin || $admin->num_rows === 0) {
    $_SESSION['contact_error'] = "Support is temporarily unavailable.";
    header("Location: " . $redirectTo);
    exit();
}
$adminData = $admin->fetch_assoc();
$receiverId = (int) $adminData['id'];

// The public contact form (index.php) sends name/email/message; the account page just sends message.
$message = trim($_POST['message'] ?? '');
$guestName = trim($_POST['name'] ?? '');
if ($guestName !== '' && strtolower($guestName) !== strtolower($_SESSION['user_name'] ?? '')) {
    $message = "From {$guestName}:\n{$message}";
}

if ($message === '') {
    $_SESSION['contact_error'] = "Please enter a message.";
    header("Location: " . $redirectTo);
    exit();
}

$stmt = $conn->prepare("
    INSERT INTO messages (sender_id, receiver_id, message, is_read)
    VALUES (?, ?, ?, 0)
");
$stmt->bind_param("iis", $senderId, $receiverId, $message);

if ($stmt->execute()) {
    $_SESSION['contact_sent'] = "Message sent successfully.";
} else {
    $_SESSION['contact_error'] = "Failed to send message. Please try again.";
}
$stmt->close();
$conn->close();

header("Location: " . $redirectTo);
exit();
?>
