<?php
require_once 'config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Требуется авторизация']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $user_id = $_SESSION['user_id'];
    $full_name = $db->escape($_POST['full_name'] ?? '');
    $phone = $db->escape($_POST['phone'] ?? '');
    $address = $db->escape($_POST['address'] ?? '');
    $cart = json_decode($_POST['cart'], true);
    $total = floatval($_POST['total'] ?? 0);
    
    if (empty($cart)) {
        echo json_encode(['error' => 'Корзина пуста']);
        exit();
    }
    
    $db->getConnection()->begin_transaction();
    
    try {
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, phone) VALUES (?, ?, 'pending', ?, ?)");
        $stmt->bind_param("idss", $user_id, $total, $address, $phone);
        $stmt->execute();
        $order_id = $db->getLastId();
        
        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        
        $db->getConnection()->commit();
        echo json_encode(['success' => true, 'order_id' => $order_id]);
        
    } catch (Exception $e) {
        $db->getConnection()->rollback();
        echo json_encode(['error' => 'Ошибка оформления заказа']);
    }
} else {
    echo json_encode(['error' => 'Неверный запрос']);
}
?>