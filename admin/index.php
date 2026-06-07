<?php
require_once '../config/database.php';
require_once '../auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

$stats = [
    'users' => $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'],
    'orders' => $db->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'],
    'products' => $db->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'],
    'revenue' => $db->query("SELECT SUM(total_amount) as s FROM orders WHERE status != 'cancelled'")->fetch_assoc()['s'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - SportShop PRO</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { display: flex; min-height: calc(100vh - 70px); }
        .admin-sidebar { width: 250px; background: #1a1a2e; padding: 30px 0; }
        .admin-sidebar a { display: block; padding: 12px 24px; color: #ccc; text-decoration: none; transition: 0.3s; }
        .admin-sidebar a:hover, .admin-sidebar a.active { background: #ff6b6b; color: white; }
        .admin-main { flex: 1; padding: 30px; background: #f8f9fa; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #ff6b6b; }
        .stat-label { color: #666; margin-top: 5px; }
    </style>
</head>
<body>
<header>
    <div class="logo">⚡ SPORT PRO ADMIN</div>
    <nav>
        <a href="../index.php">На сайт</a>
        <a href="../logout.php">Выйти</a>
    </nav>
</header>
<div class="admin-container">
    <div class="admin-sidebar">
        <a href="index.php" class="active">📊 Главная</a>
        <a href="orders.php">📦 Заказы</a>
        <a href="products.php">🏷️ Товары</a>
        <a href="users.php">👥 Пользователи</a>
    </div>
    <div class="admin-main">
        <h1>Добро пожаловать в админ-панель!</h1>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['users']; ?></div>
                <div class="stat-label">Пользователей</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['orders']; ?></div>
                <div class="stat-label">Заказов</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['products']; ?></div>
                <div class="stat-label">Товаров</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">€<?php echo number_format($stats['revenue'], 2); ?></div>
                <div class="stat-label">Выручка</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>