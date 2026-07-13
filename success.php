<?php

function getOrCreateCartId($pdo, $user_id) {

    $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();

    if ($cart) {
        return $cart['id'];
    }

    // Otherwise, create a new cart entry automatically
    $stmt = $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    return $pdo->lastInsertId();
}

function addProductToDbCart($pdo, $user_id, $product_id, $quantity = 1) {
    $cart_id = getOrCreateCartId($pdo, $user_id);

    // Upsert pattern: Insert item, if product exists in this cart, increment quantity instead
    $sql = "INSERT INTO cart_items (cart_id, product_id, quantity)
            VALUES (:cart_id, :product_id, :quantity)
            ON DUPLICATE KEY UPDATE quantity = quantity + :quantity_update";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'cart_id' => $cart_id,
        'product_id' => $product_id,
        'quantity' => $quantity,
        'quantity_update' => $quantity
    ]);
}

function getDbCartCount($pdo, $user_id) {
    $sql = "SELECT SUM(ci.quantity) as total_items
            FROM cart_items ci
            JOIN carts c ON ci.cart_id = c.id
            WHERE c.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $res = $stmt->fetch();
    return $res['total_items'] ?? 0;
}
?>