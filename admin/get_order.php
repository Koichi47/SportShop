<?php
require_once '../config/database.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['error' => 'Доступ запрещен']);
    exit();
}

$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    echo json_encode(['error' => 'Неверный ID']);
    exit();
}

$stmt = $db->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['error' => 'Заказ не найден']);
    exit();
}

$stmt = $db->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
?>