<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prevent the browser from serving a cached copy of this page. Without this,
// after delete.php redirects back here the browser can show the OLD (stale)
// order list from cache instead of fetching the real, updated data — making
// a successful delete look like it silently failed until a manual refresh.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Make sure PDO actually throws on errors instead of silently returning false.
// If config.php doesn't set this, a failed query never hits our catch blocks —
// it just quietly returns an empty result, which looks identical to "no orders".
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// CSRF token for state-changing actions (e.g. deleting an order)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// One-time flash message from delete_order.php (success/error banner)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// 1. Fetch User Data
try {
    $stmt = $pdo->prepare("SELECT username, email, DATE_FORMAT(created_at, '%M %Y') as join_date FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = ['username' => '', 'email' => '', 'join_date' => ''];
}

// 2. Fetch Orders
try {
    $stmt = $pdo->prepare("
        SELECT id, created_at, total_price, status
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_orders = [];
}

// 3. Fetch every line item (product) belonging to those orders in a single query,
//    then group them by order_id in PHP so each order can list all of its products
//    instead of just a single rolled-up total.
//    This is wrapped in its OWN try/catch so that if order_items is missing or named
//    differently, it only means "no line items shown" — it must never wipe out
//    $recent_orders, which was already fetched successfully above.
$order_items_by_order = [];
if (!empty($recent_orders)) {
    try {
        $order_ids = array_column($recent_orders, 'id');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));

        $stmt = $pdo->prepare("
            SELECT
                oi.order_id,
                p.name AS product_name,
                oi.quantity,
                oi.price_at_purchase AS unit_price
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.id ASC
        ");
        $stmt->execute($order_ids);

        foreach ($stmt->fetchAll() as $item) {
            $order_items_by_order[$item['order_id']][] = $item;
        }
    } catch (PDOException $e) {

        $order_items_by_order = [];
    }
}

function statusBadgeClass(string $status): string
{
    switch (strtolower($status)) {
        case 'shipped':
        case 'shipping':
        case 'in transit':
            return 'status-shipping';
        case 'delivered':
        case 'completed':
            return 'status-delivered';
        case 'cancelled':
        case 'canceled':
        case 'refunded':
            return 'status-cancelled';
        case 'pending':
        case 'processing':
            return 'status-pending';
        default:
            return 'status-shipping';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Smash Pro Badminton</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f3f7fc 0%, #e6effa 100%);
            --text-main: #2d3748;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --card-border: rgba(227, 238, 252, 0.9);
            --header-bg: rgba(255, 255, 255, 0.85);
            --title-color: #1a202c;
            --profile-avatar-bg: rgba(94, 59, 150, 0.1);
            --profile-avatar-color: #5e3b96;
        }

        [data-theme="dark"] {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --card-border: rgba(51, 65, 85, 0.5);
            --header-bg: rgba(15, 23, 42, 0.85);
            --title-color: #ffffff;
            --profile-avatar-bg: rgba(167, 139, 250, 0.2);
            --profile-avatar-color: #a78bfa;
        }

        body {
            background: var(--bg-gradient); color: var(--text-main);
            font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0;
            transition: background 0.3s, color 0.3s; min-height: 100vh;
            display: flex; flex-direction: column;
        }

        header {
            background: var(--header-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            padding: 1.2rem 3rem; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(47, 199, 52, 0.2); position: sticky; top: 0; z-index: 100;
        }
        .logo { font-size: 24px; font-weight: 800; color: #1e8a4b; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: #5e3b96; text-decoration: none; font-weight: 700; font-size: 15px; }
        [data-theme="dark"] .nav-links a { color: #a78bfa; }

        .theme-toggle {
            background: rgba(94, 59, 150, 0.1); color: #5e3b96; border: none;
            padding: 8px 14px; border-radius: 12px; cursor: pointer; font-weight: 700;
        }

        .container { max-width: 1200px; width: 100%; margin: 3rem auto; padding: 0 1.5rem; box-sizing: border-box; flex-grow: 1; }
        .account-layout { display: grid; grid-template-columns: 320px 1fr; gap: 2rem; align-items: flex-start; }
        @media (max-width: 768px) { .account-layout { grid-template-columns: 1fr; } }

        /* PROFILE CARD SIDEBAR */
        .profile-sidebar {
            background: var(--card-bg); border-radius: 24px; border: 1px solid var(--card-border);
            padding: 2.5rem 1.5rem; text-align: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }
        .avatar {
            width: 90px; height: 90px; border-radius: 50%; background: var(--profile-avatar-bg);
            color: var(--profile-avatar-color); font-size: 36px; font-weight: 800;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;
        }
        .profile-name { font-size: 22px; font-weight: 800; color: var(--title-color); margin: 0 0 4px 0; }
        .profile-email { font-size: 14px; color: var(--text-muted); margin: 0 0 1.5rem 0; }
        .profile-meta { font-size: 13px; color: var(--text-muted); border-top: 1px solid var(--card-border); padding-top: 1rem; margin-bottom: 2rem; }

        /* BUTTON ACTIONS */
        .btn-logout {
            width: 100%; padding: 14px; border: 2px solid #ef4444; color: #ef4444;
            background: transparent; font-size: 15px; font-weight: 700; border-radius: 14px;
            cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-logout:hover { background: #ef4444; color: #ffffff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); }

        /* ACCOUNT MAIN CONTENT AREA */
        .account-main { display: flex; flex-direction: column; gap: 2rem; }

        .panel-card {
            background: var(--card-bg); border-radius: 24px; border: 1px solid var(--card-border);
            padding: 2rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }
        .panel-title { font-size: 20px; font-weight: 800; color: var(--title-color); margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 10px; }

        /* ORDER GROUP (order header row + its product line items) */
        .order-group { border-bottom: 1px solid var(--card-border); }
        .order-group:last-child { border-bottom: none; }
        .order-group.removing {
            transition: opacity 0.35s ease, max-height 0.35s ease, transform 0.35s ease, margin 0.35s ease, padding 0.35s ease;
            opacity: 0; transform: scale(0.98); max-height: 0; overflow: hidden;
            margin: 0; padding: 0; border-bottom: none;
        }
        .btn-delete-order[disabled] { opacity: 0.5; cursor: not-allowed; }

        .toast {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(20px);
            padding: 14px 22px; border-radius: 14px; font-size: 14px; font-weight: 700;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); z-index: 1000; opacity: 0;
            transition: opacity 0.25s ease, transform 0.25s ease; pointer-events: none;
        }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .toast.toast-success { background: #1e8a4b; color: #ffffff; }
        .toast.toast-error { background: #ef4444; color: #ffffff; }
        .order-header {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 12px; padding: 16px; background: rgba(94, 59, 150, 0.03);
        }
        [data-theme="dark"] .order-header { background: rgba(167, 139, 250, 0.05); }
        .order-header-left { display: flex; align-items: center; gap: 14px; }
        .order-ref { font-weight: 800; color: var(--title-color); }
        .order-date { font-size: 13px; color: var(--text-muted); }
        .order-header-right { display: flex; align-items: center; gap: 14px; }
        .order-total { font-weight: 800; color: var(--title-color); }

        .order-items-table { width: 100%; border-collapse: collapse; }
        .order-items-table th {
            font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em;
            color: var(--text-muted); font-weight: 700; padding: 8px 16px 8px 40px;
            border-bottom: 1px solid var(--card-border);
        }
        .order-items-table td { padding: 10px 16px 10px 40px; font-size: 14px; color: var(--text-main); }
        .order-items-table tr:not(:last-child) td { border-bottom: 1px dashed var(--card-border); }
        .item-qty { color: var(--text-muted); }
        .item-line-total { text-align: right; font-weight: 700; }
        .no-items-note { padding: 10px 16px 14px 40px; font-size: 13px; color: var(--text-muted); font-style: italic; }

        .btn-delete-order {
            background: transparent; border: 1px solid rgba(239, 68, 68, 0.4); color: #ef4444;
            font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 10px;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-delete-order:hover { background: #ef4444; color: #ffffff; }

        .flash-banner {
            padding: 14px 18px; border-radius: 14px; font-size: 14px; font-weight: 600; margin-bottom: 1.5rem;
        }
        .flash-success { background: rgba(47, 199, 52, 0.12); color: #1e8a4b; border: 1px solid rgba(47, 199, 52, 0.3); }
        .flash-error { background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }

        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 30px; font-size: 12px; font-weight: 700; }
        .status-shipping { background: rgba(47, 199, 52, 0.15); color: #2fc734; }
        .status-delivered { background: rgba(37, 99, 235, 0.15); color: #2563eb; }
        .status-cancelled { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .status-pending { background: rgba(245, 158, 11, 0.15); color: #d97706; }

        .empty-history { text-align: center; color: var(--text-muted); padding: 2rem 0; font-size: 15px; }

        footer { background: var(--card-bg); text-align: center; padding: 2.5rem 1.5rem; border-top: 1px solid var(--card-border); color: var(--text-muted); font-size: 14px; margin-top: auto; }
    </style>
</head>
<body>

    <header>
        <a href="../store/home.php" class="logo">SmashPro Badminton</a>
        <div class="nav-links">
            <button class="theme-toggle" id="themeBtn">🌓 Mode</button>
            <a href="../store/home.php">Home</a>
            <a href="../store/page.php">Shop Equipment</a>
        </div>
    </header>

    <div class="container">
        <div class="account-layout">


            <aside class="profile-sidebar">
                <div class="avatar">
                    <?= htmlspecialchars(strtoupper(substr($user['username'], 0, 1))); ?>
                </div>
                <h3 class="profile-name"><?= htmlspecialchars($user['username']) ?></h3>
                <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>

                <div class="profile-meta">
                    Member Since: <strong><?= htmlspecialchars($user['join_date']) ?></strong>
                </div>


                <form action="logout.php" method="POST">
                    <button type="submit" class="btn-logout">
                        <span></span> Sign Out of Account
                    </button>
                </form>
            </aside>


            <main class="account-main">

                <?php if ($flash): ?>
                    <div class="flash-banner flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($flash['message']) ?>
                    </div>
                <?php endif; ?>


                <div class="panel-card">
                    <h3 class="panel-title"> Welcome Back, <?= htmlspecialchars($user['username']) ?>!</h3>
                    <p style="margin: 0; line-height: 1.6; color: var(--text-muted); font-size: 15px;">
                        From your account dashboard panel, you can instantly review pending product deliveries, manage string setup data profiles, and check out active item configurations purchased across our catalog.
                    </p>
                </div>


                <div class="panel-card">
                    <h3 class="panel-title">Recent Order Deployments</h3>
                    <div style="overflow-x: auto;">
                        <?php if (empty($recent_orders)): ?>
                            <div class="empty-history">
                                You haven't ordered any premium equipment yet!
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <?php $items = $order_items_by_order[$order['id']] ?? []; ?>
                                <div class="order-group" data-order-id="<?= htmlspecialchars($order['id']) ?>">
                                    <div class="order-header">
                                        <div class="order-header-left">
                                            <span class="order-ref">#<?= htmlspecialchars($order['id']) ?></span>
                                            <span class="order-date"><?= htmlspecialchars(date('m/d/Y', strtotime($order['created_at']))) ?></span>
                                        </div>
                                        <div class="order-header-right">
                                            <span class="status-badge <?= statusBadgeClass($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
                                            <span class="order-total">$<?= htmlspecialchars(number_format($order['total_price'], 2)) ?></span>
                                            <form action="delete.php" method="POST" class="delete-order-form" data-order-ref="<?= htmlspecialchars($order['id']) ?>">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <button type="submit" class="btn-delete-order"> Delete</button>
                                            </form>
                                        </div>
                                    </div>

                                    <?php if (empty($items)): ?>
                                        <p class="no-items-note">No itemized products found for this order.</p>
                                    <?php else: ?>
                                        <table class="order-items-table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Qty</th>
                                                    <th>Unit Price</th>
                                                    <th style="text-align:right;">Line Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                        <td class="item-qty">×<?= htmlspecialchars($item['quantity']) ?></td>
                                                        <td>$<?= htmlspecialchars(number_format($item['unit_price'], 2)) ?></td>
                                                        <td class="item-line-total">$<?= htmlspecialchars(number_format($item['unit_price'] * $item['quantity'], 2)) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Smash Pro Badminton Equipment. All Rights Reserved.</p>
    </footer>

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


        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type === 'success' ? 'success' : 'error'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3200);
        }

        document.querySelectorAll('.delete-order-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const ref = form.dataset.orderRef;
                const confirmed = window.confirm(`Delete order #${ref} from your history? This can't be undone.`);
                if (!confirmed) return;

                const button = form.querySelector('button');
                const orderGroup = form.closest('.order-group');
                button.disabled = true;
                button.textContent = 'Deleting…';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(form),
                    });
                    const result = await response.json();

                    if (result.success) {
                        
                        orderGroup.classList.add('removing');
                        orderGroup.addEventListener('transitionend', () => orderGroup.remove(), { once: true });
                        showToast(result.message, 'success');
                    } else {
                        showToast(result.message || 'Could not delete that order.', 'error');
                        button.disabled = false;
                        button.textContent = '🗑 Delete';
                    }
                } catch (err) {
                    showToast('Network error — please try again.', 'error');
                    button.disabled = false;
                    button.textContent = '🗑 Delete';
                }
            });
        });
    </script>
</body>
</html>