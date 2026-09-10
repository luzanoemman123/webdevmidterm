<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user_page.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$maxBytes = 3 * 1024 * 1024; // 3MB

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['avatar_error'] = "Please choose an image to upload.";
    header("Location: user_page.php");
    exit();
}

$file = $_FILES['profile_picture'];

if ($file['size'] > $maxBytes) {
    $_SESSION['avatar_error'] = "That image is too large. Please choose one under 3MB.";
    header("Location: user_page.php");
    exit();
}

// Verify the actual file content, not just the client-supplied name/extension.
$detectedType = mime_content_type($file['tmp_name']);
if (!isset($allowedTypes[$detectedType])) {
    $_SESSION['avatar_error'] = "Please upload a JPG, PNG, or WEBP image.";
    header("Location: user_page.php");
    exit();
}

$uploadDir = __DIR__ . '/uploads/avatars';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$extension = $allowedTypes[$detectedType];
$newFilename = 'user_' . $userId . '_' . time() . '.' . $extension;
$destination = $uploadDir . '/' . $newFilename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    $_SESSION['avatar_error'] = "We couldn't save your photo. Please try again.";
    header("Location: user_page.php");
    exit();
}

// Look up the old picture so it can be removed after the new one is saved.
$oldStmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$oldStmt->bind_param("i", $userId);
$oldStmt->execute();
$oldPicture = $oldStmt->get_result()->fetch_assoc()['profile_picture'] ?? null;
$oldStmt->close();

$updateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
$updateStmt->bind_param("si", $newFilename, $userId);
$updateStmt->execute();
$updateStmt->close();

if ($oldPicture && $oldPicture !== $newFilename) {
    $oldPath = $uploadDir . '/' . $oldPicture;
    if (is_file($oldPath)) {
        unlink($oldPath);
    }
}

$_SESSION['avatar_sent'] = "Profile picture updated.";
header("Location: user_page.php");
exit();