<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../login/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: storefront.php');
    exit;
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if (!$order_id) {
    $order_id = $_SESSION['last_order_id'] ?? null;
}

if (!$order_id) {
    header('Location: page.php');
    exit;
}

try {
    
    $stmt = $pdo->prepare("
        SELECT id, order_reference, total_price, status, created_at
        FROM orders
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: page.php');
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT p.name AS product_name, oi.quantity, oi.price_at_purchase
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $stmt->execute([$order['id']]);
    $order_items = $stmt->fetchAll();

} catch (PDOException $e) {
    header('Location: page.php');
    exit;
}

$order_ref    = $order['order_reference'];
$order_date   = date('F j, Y, g:i a', strtotime($order['created_at']));
$order_total  = $order['total_price'];
$order_status = $order['status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Smash Pro Storefront</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f3f7fc 0%, #e6effa 100%);
            --text-main: #2d3748;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --card-border: rgba(227, 238, 252, 0.9);
            --header-bg: rgba(255, 255, 255, 0.85);
            --title-color: #1a202c;
            --badge-bg: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --card-border: rgba(51, 65, 85, 0.5);
            --header-bg: rgba(15, 23, 42, 0.85);
            --title-color: #ffffff;
            --badge-bg: #334155;
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            transition: background 0.3s, color 0.3s;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: var(--header-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 1.2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(47, 199, 52, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #1e8a4b;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            color: #5e3b96;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
        }

        [data-theme="dark"] .nav-links a {
            color: #a78bfa;
        }

        .theme-toggle {
            background: rgba(94, 59, 150, 0.1);
            color: #5e3b96;
            border: none;
            padding: 8px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
        }

        .container {
            max-width: 650px;
            margin: auto;
            padding: 2rem 1.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        .success-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--card-border);
        }

        .success-icon-wrapper {
            width: 90px;
            height: 90px;
            background: rgba(47, 199, 52, 0.15);
            color: #2fc734;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            margin: 0 auto 1.5rem auto;
            animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes scaleIn {
            from { transform: scale(0.6); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .section-title {
            font-size: 30px;
            font-weight: 800;
            color: var(--title-color);
            margin: 0 0 10px 0;
        }

        .desc {
            font-size: 15px;
            color: var(--text-muted);
            margin: 0 0 2.5rem 0;
            line-height: 1.6;
        }

        .order-meta-table {
            background: rgba(94, 59, 150, 0.03);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--card-border);
            text-align: left;
        }

        [data-theme="dark"] .order-meta-table {
            background: rgba(255, 255, 255, 0.02);
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .meta-row:not(:last-child) {
            border-bottom: 1px solid var(--card-border);
        }

        .meta-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .meta-value {
            color: var(--title-color);
            font-weight: 700;
        }

        .order-items-box {
            background: rgba(94, 59, 150, 0.03);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 2.5rem;
            border: 1px solid var(--card-border);
            text-align: left;
        }

        [data-theme="dark"] .order-items-box {
            background: rgba(255, 255, 255, 0.02);
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .item-row:not(:last-child) {
            border-bottom: 1px dashed var(--card-border);
        }

        .item-name { color: var(--text-main); font-weight: 600; }
        .item-qty { color: var(--text-muted); font-size: 13px; }
        .item-price { color: var(--title-color); font-weight: 700; }

        .btn-action {
            display: inline-block;
            text-decoration: none;
            width: 100%;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-sizing: border-box;
            transition: all 0.2s;
            background: linear-gradient(135deg, #2fc734 0%, #1e8a4b 100%);
            color: #ffffff;
            margin-bottom: 1rem;
        }

        .btn-action:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 15px rgba(47, 199, 52, 0.2);
        }

        .btn-secondary {
            background: transparent;
            color: #5e3b96;
            border: 2px solid rgba(94, 59, 150, 0.2);
            margin-bottom: 0;
        }

        [data-theme="dark"] .btn-secondary {
            color: #a78bfa;
            border-color: rgba(167, 139, 250, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(94, 59, 150, 0.05);
            transform: none;
            box-shadow: none;
        }
    </style>
</head>
<body>

    <header>
        <a href="home.php" class="logo">SmashPro Badminton</a>
        <div class="nav-links">
            <button class="theme-toggle" id="themeBtn">🌓 Mode</button>
            <a href="../login/dashboard.php">My Account</a>
        </div>
    </header>

    <div class="container">
        <div class="success-card">
            <div class="success-icon-wrapper">✓</div>

            <h2 class="section-title">Order Confirmed!</h2>
            <p class="desc">Thank you for your purchase, <strong><?= htmlspecialchars($username) ?></strong>. Your professional-grade gear processing is now underway and moving to our validation queues.</p>

            <div class="order-meta-table">
                <div class="meta-row">
                    <span class="meta-label">Receipt Identifier</span>
                    <span class="meta-value"><?= htmlspecialchars($order_ref) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Transaction Date</span>
                    <span class="meta-value"><?= htmlspecialchars($order_date) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Order Total</span>
                    <span class="meta-value">$<?= htmlspecialchars(number_format($order_total, 2)) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Fulfillment Status</span>
                    <span class="meta-value" style="color: #2fc734;"><?= htmlspecialchars($order_status) ?></span>
                </div>
            </div>

            <?php if (!empty($order_items)): ?>
            <div class="order-items-box">
                <?php foreach ($order_items as $item): ?>
                    <div class="item-row">
                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?> <span class="item-qty">×<?= htmlspecialchars($item['quantity']) ?></span></span>
                        <span class="item-price">$<?= htmlspecialchars(number_format($item['price_at_purchase'] * $item['quantity'], 2)) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <a href="page.php" class="btn-action">Purchase Done</a>
            <a href="../login/dashboard.php" class="btn-action btn-secondary">View Order History</a>
        </div>
    </div>

    <script>
        const themeBtn = document.getElementById('themeBtn');
        if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');

        themeBtn.addEventListener('click', () => {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>