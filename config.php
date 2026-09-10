<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "luzano_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$userBlockColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'is_blocked'");
if ($userBlockColumn && $userBlockColumn->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0");
}

?>
