<?php
require_once '../config/database.php';
require_once '../auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $_POST['status'], $_POST['order_id']);
    $stmt->execute();
    header('Location: orders.php');
    exit();
}

$filter = $_GET['status'] ?? '';
$sql = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id";
if ($filter) $sql .= " WHERE o.status = '$filter'";
$sql .= " ORDER BY o.created_at DESC";
$orders = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы - Админ-панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { display: flex; }
        .admin-sidebar { width: 250px; background: #1a1a2e; padding: 30px 0; }
        .admin-sidebar a { display: block; padding: 12px 24px; color: #ccc; text-decoration: none; }
        .admin-sidebar a:hover, .admin-sidebar a.active { background: #ff6b6b; color: white; }
        .admin-main { flex: 1; padding: 30px; background: #f8f9fa; }
        .filter-links { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-links a { padding: 8px 16px; background: white; border-radius: 20px; text-decoration: none; color: #333; }
        .filter-links a.active { background: #ff6b6b; color: white; }
        .orders-table { width: 100%; background: white; border-radius: 12px; overflow: hidden; }
        .orders-table th, .orders-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .btn-sm { padding: 5px 12px; font-size: 0.85rem; }
        select { padding: 5px; border-radius: 5px; border: 1px solid #ddd; }
    </style>
</head>
<body>
<header>
    <div class="logo">⚡ SPORT PRO ADMIN</div>
    <nav><a href="../index.php">На сайт</a><a href="../logout.php">Выйти</a></nav>
</header>
<div class="admin-container">
    <div class="admin-sidebar">
        <a href="index.php">📊 Главная</a>
        <a href="orders.php" class="active">📦 Заказы</a>
        <a href="products.php">🏷️ Товары</a>
        <a href="users.php">👥 Пользователи</a>
    </div>
    <div class="admin-main">
        <h1>Управление заказами</h1>
        <div class="filter-links">
            <a href="orders.php" <?php echo !$filter ? 'class="active"' : ''; ?>>Все</a>
            <a href="orders.php?status=pending" <?php echo $filter == 'pending' ? 'class="active"' : ''; ?>>Ожидают</a>
            <a href="orders.php?status=processing" <?php echo $filter == 'processing' ? 'class="active"' : ''; ?>>В обработке</a>
            <a href="orders.php?status=completed" <?php echo $filter == 'completed' ? 'class="active"' : ''; ?>>Выполнены</a>
            <a href="orders.php?status=cancelled" <?php echo $filter == 'cancelled' ? 'class="active"' : ''; ?>>Отменены</a>
        </div>
        <table class="orders-table">
            <thead><tr><th>ID</th><th>Покупатель</th><th>Сумма</th><th>Статус</th><th>Телефон</th><th>Дата</th><th>Действия</th></tr></thead>
            <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['username']); ?></td>
                <td>€<?php echo number_format($order['total_amount'], 2); ?></td>
                <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></td>
                <td><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                <td>
                    <form method="POST" style="display: inline-flex; gap: 5px;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="status">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>В обработке</option>
                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Выполнен</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                        </select>
                        <button type="submit" name="update_status" class="btn-sm">Изменить</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>