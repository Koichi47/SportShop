<?php
require_once '../config/database.php';
require_once '../auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

// Генерируем CSRF токен если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Защита от CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token invalid');
    }
    
    if (isset($_POST['update_status'])) {
        $order_id = intval($_POST['order_id']);
        $status = $_POST['status'];
        
        // Валидация статуса
        $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            header('Location: orders.php');
            exit();
        }
        
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        header('Location: orders.php');
        exit();
    }
}

$filter = $_GET['status'] ?? '';

// Валидация фильтра
$valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
if ($filter && !in_array($filter, $valid_statuses)) {
    $filter = '';
}

// Безопасный запрос с prepared statement
$sql = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id";
if ($filter) {
    $sql .= " WHERE o.status = ?";
}
$sql .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
if ($filter) {
    $stmt->bind_param("s", $filter);
}
$stmt->execute();
$orders = $stmt->get_result();
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
        .admin-sidebar a { display: block; padding: 12px 24px; color: #ccc; text-decoration: none; transition: 0.3s; }
        .admin-sidebar a:hover, .admin-sidebar a.active { background: #ff6b6b; color: white; }
        .admin-main { flex: 1; padding: 30px; background: #f8f9fa; }
        .filter-links { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-links a { padding: 8px 16px; background: white; border-radius: 20px; text-decoration: none; color: #333; transition: 0.3s; }
        .filter-links a.active { background: #ff6b6b; color: white; }
        .orders-table { width: 100%; background: white; border-radius: 12px; overflow: hidden; }
        .orders-table th, .orders-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; display: inline-block; font-weight: 500; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .btn-sm { padding: 5px 12px; font-size: 0.85rem; cursor: pointer; }
        select { padding: 5px; border-radius: 5px; border: 1px solid #ddd; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .order-item { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        @media (max-width: 768px) { .admin-container { flex-direction: column; } .admin-sidebar { width: 100%; } }
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
        <h1>📦 Управление заказами</h1>
        <div class="filter-links">
            <a href="orders.php" <?php echo !$filter ? 'class="active"' : ''; ?>>📋 Все</a>
            <a href="orders.php?status=pending" <?php echo $filter == 'pending' ? 'class="active"' : ''; ?>>⏳ Ожидают</a>
            <a href="orders.php?status=processing" <?php echo $filter == 'processing' ? 'class="active"' : ''; ?>>⚙️ В обработке</a>
            <a href="orders.php?status=completed" <?php echo $filter == 'completed' ? 'class="active"' : ''; ?>>✅ Выполнены</a>
            <a href="orders.php?status=cancelled" <?php echo $filter == 'cancelled' ? 'class="active"' : ''; ?>>❌ Отменены</a>
        </div>
        <div style="overflow-x: auto;">
            <table class="orders-table">
                <thead><tr><th>ID</th><th>Покупатель</th><th>Сумма</th><th>Статус</th><th>Телефон</th><th>Адрес</th><th>Дата</th><th>Действия</th></tr></thead>
                <tbody>
                <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                    <td><strong>€<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                    <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></td>
                    <td><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(substr($order['address'] ?? '-', 0, 40)); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                    <td>
                        <button class="btn-sm" onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)">👁️ Просмотр</button>
                        <form method="POST" style="display: inline-flex; gap: 5px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="">Изменить</option>
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Выполнен</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                            </select>
                            <button type="submit" name="update_status" style="display:none;"></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно просмотра заказа -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <h2>📋 Детали заказа</h2>
        <div id="orderDetails"></div>
        <button class="btn" onclick="closeModal()" style="background:#6c757d; margin-top:20px;">Закрыть</button>
    </div>
</div>

<script>
async function viewOrder(order) {
    try {
        const response = await fetch('../api/get_order_details.php?id=' + order.id);
        const items = await response.json();
        
        let html = `
            <div style="margin-bottom: 20px;">
                <p><strong>Заказ:</strong> #${order.id}</p>
                <p><strong>Покупатель:</strong> ${order.username}</p>
                <p><strong>Телефон:</strong> ${order.phone || '-'}</p>
                <p><strong>Адрес доставки:</strong> ${order.address || '-'}</p>
                <p><strong>Сумма:</strong> €${parseFloat(order.total_amount).toFixed(2)}</p>
                <p><strong>Статус:</strong> <span class="status-badge status-${order.status}">${order.status}</span></p>
                <p><strong>Дата:</strong> ${new Date(order.created_at).toLocaleString('ru-RU')}</p>
            </div>
            <h3>Товары в заказе:</h3>
        `;
        
        items.forEach(item => {
            html += `
                <div class="order-item">
                    <span>${item.name} x ${item.quantity}</span>
                    <span>€${(item.price * item.quantity).toFixed(2)}</span>
                </div>
            `;
        });
        
        document.getElementById('orderDetails').innerHTML = html;
        document.getElementById('viewModal').classList.add('active');
    } catch (e) {
        alert('Ошибка загрузки деталей заказа');
    }
}

function closeModal() {
    document.getElementById('viewModal').classList.remove('active');
}
</script>
</body>
</html>