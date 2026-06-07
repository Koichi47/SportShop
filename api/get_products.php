<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$result = $db->query("SELECT id, name, description, price, image_url, category FROM products ORDER BY created_at DESC");
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>