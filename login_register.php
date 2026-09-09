<?php
session_start();
require_once 'config.php';

if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rawPassword = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || strlen($rawPassword) < 8) {
        $_SESSION['register_error'] = "Please fill every field (password must be at least 8 characters).";
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }

    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        $_SESSION['register_error'] = "Email already registered!";
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }
    $checkStmt->close();

    $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);
    // Role is always 'user' here — admin accounts are provisioned directly in the database.
    $role = 'user';

    $insertStmt = $conn->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"
    );
    $insertStmt->bind_param("ssss", $name, $email, $passwordHash, $role);
    $insertStmt->execute();
    $insertStmt->close();

    $_SESSION['active_form'] = 'login';
    header("Location: login.php");
    exit();
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $rawPassword = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND (role = 'user' OR (role = 'admin' AND email = 'admin@luzano.com'))");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($rawPassword, $user['password'])) {
            $stmt->close();
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin_page.php");
                exit();
            }
            header("Location: index.php");
            exit();
        }
    }
    $stmt->close();

    $_SESSION['login_error'] = "Incorrect email or password";
    $_SESSION['active_form'] = 'login';
    header("Location: login.php");
    exit();
}

header("Location: login.php");
exit();
?>
