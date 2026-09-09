<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
$orderId = $_SESSION['last_order_id'] ?? null;
if (!$orderId) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | Luzano Spear Master</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Montserrat', Arial, sans-serif; background: #05090c; color: #fff; }
        .success-header { height: 92px; background: #05090c; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid rgba(56, 130, 255, 0.5); }
        .success-logo img { width: 130px; display: block; }
        .success-container { min-height: calc(100vh - 92px); display: flex; align-items: center; justify-content: center; padding: 50px 20px; }
        .success-box { width: 100%; max-width: 650px; background: #0b1217; border: 1px solid rgba(56, 130, 255, 0.2); border-radius: 6px; padding: 55px 45px; text-align: center; box-shadow: 0 24px 70px rgba(0, 0, 0, 0.55); }
        .success-icon { width: 75px; height: 75px; margin: 0 auto 25px; border-radius: 50%; background: #3c82ff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 38px; font-weight: bold; }
        .success-box h1 { font-size: 32px; letter-spacing: 2px; margin-bottom: 15px; }
        .success-message { color: #9db2c2; font-size: 15px; line-height: 1.7; margin-bottom: 25px; }
        .success-message strong { color: #fff; }
        .order-number { background: rgba(56, 130, 255, 0.1); border: 1px solid rgba(56, 130, 255, 0.25); border-radius: 4px; padding: 18px; margin: 25px 0; }
        .order-number span { display: block; font-size: 11px; letter-spacing: 1.5px; color: #9db2c2; margin-bottom: 8px; text-transform: uppercase; }
        .order-number strong { font-size: 22px; letter-spacing: 1px; color: #3c82ff; }
        .payment-note { font-size: 13px; color: #9db2c2; line-height: 1.6; margin-bottom: 30px; }
        .payment-note strong { color: #fff; }
        .success-buttons { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; }
        .button { display: inline-block; text-decoration: none; padding: 15px 25px; font-size: 12px; font-weight: bold; letter-spacing: 1px; border-radius: 4px; transition: 0.2s; }
        .shop-button { background: rgba(255, 255, 255, 0.06); color: #d8e2e8; border: 1px solid rgba(255, 255, 255, 0.12); }
        .shop-button:hover { background: rgba(255, 255, 255, 0.1); }
        .account-button { background: #3c82ff; color: #fff; }
        .account-button:hover { background: #1e5ef0; }
        .footer-note { margin-top: 35px; font-size: 11px; color: #7c93a1; letter-spacing: 0.5px; }
        @media (max-width: 600px) {
            .success-box { padding: 40px 25px; }
            .success-box h1 { font-size: 26px; }
            .success-buttons { flex-direction: column; }
            .button { width: 100%; }
        }
    </style>
</head>
<body>
<header class="success-header">
    <a href="index.php" class="success-logo">
        <img src="images/viclogo.png" alt="Luzano Spear Master">
    </a>
</header>
<main class="success-container">
    <div class="success-box">
        <div class="success-icon">✓</div>
        <h1>ORDER CONFIRMED</h1>
        <p class="success-message">
            Thank you for shopping with <strong>Luzano Spear Master</strong>!
            <br>Your order has been successfully placed and is now being processed.
        </p>
        <div class="order-number">
            <span>Order Number</span>
            <strong>LUZANO-<?= date('Y') ?>-<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <p class="payment-note">
            Payment Method: <strong>Cash on Delivery</strong>
            <br>Please prepare the exact amount when your order arrives.
        </p>
        <div class="success-buttons">
            <a href="index.php" class="button shop-button">CONTINUE SHOPPING</a>
            <a href="user_page.php" class="button account-button">MY ACCOUNT</a>
        </div>
        <p class="footer-note">Thank you for choosing Luzano Spear Master.</p>
    </div>
</main>
</body>
</html>