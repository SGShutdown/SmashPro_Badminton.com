<?php
session_start();
require_once __DIR__ . '/../login/config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_POST['product_id'])) {
    header("Location: page.php");
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity   = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

try {

    $stmt = $pdo->prepare("SELECT id, name, category, price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        die("Error: Target Equipment Node Not Found.");
    }

    $line_total = round($product['price'] * $quantity, 2);
    $order_ref  = 'SMP-' . strtoupper(bin2hex(random_bytes(4)));



    $pdo->beginTransaction();

    try {
        $insert_order = $pdo->prepare("
            INSERT INTO orders (order_reference, user_id, total_price, status)
            VALUES (?, ?, ?, 'Preparing Shipment')
        ");
        $insert_order->execute([$order_ref, $user_id, $line_total]);
        $order_id = $pdo->lastInsertId();

        $insert_item = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
            VALUES (?, ?, ?, ?)
        ");
        $insert_item->execute([$order_id, $product['id'], $quantity, $product['price']]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    $_SESSION['last_order_id'] = $order_id;

    header("Location: success.php?order_id=" . urlencode($order_id));
    exit;

} catch (PDOException $e) {
    die("System Core Relational Exception: " . htmlspecialchars($e->getMessage()));
}