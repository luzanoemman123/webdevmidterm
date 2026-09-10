<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user_page.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$orderItemId = (int) ($_POST['order_item_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');

if ($rating < 1 || $rating > 5) {
    $_SESSION['rating_error'] = "Please choose a rating between 1 and 5 stars.";
    header("Location: user_page.php");
    exit();
}

// Only allow rating an item that belongs to THIS user's order, and only
// once that order has actually been delivered.
$checkStmt = $conn->prepare("
    SELECT oi.id
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE oi.id = ? AND o.user_id = ? AND o.status = 'Delivered'
");
$checkStmt->bind_param("ii", $orderItemId, $userId);
$checkStmt->execute();
$isValid = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($isValid) {
    $updateStmt = $conn->prepare("UPDATE order_items SET rating = ?, review = ?, rated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("isi", $rating, $review, $orderItemId);
    $updateStmt->execute();
    $updateStmt->close();
    $_SESSION['rating_sent'] = "Thanks for rating your item!";
} else {
    $_SESSION['rating_error'] = "That item can't be rated right now.";
}

header("Location: user_page.php");
exit();