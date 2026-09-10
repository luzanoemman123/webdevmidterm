<?php
require_once __DIR__ . '/partials/admin_guard.php';
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_page.php");
    exit();
}

$ratingId = (int) ($_POST['rating_id'] ?? 0);

if ($ratingId > 0) {
    // Clear only the rating fields — order_items also stores the actual
    // order line (product, price, quantity), which must stay intact.
    $stmt = $conn->prepare("
        UPDATE order_items
        SET rating = NULL, review = NULL, rated_at = NULL
        WHERE id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $ratingId);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: admin_page.php?rating_deleted=1");
exit();