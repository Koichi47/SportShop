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

// Блокировка пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token invalid');
    }
    
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'toggle_status') {
        $stmt = $db->prepare("UPDATE users SET is_banned = NOT is_banned WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        header('Location: users.php');
        exit();
    } elseif ($action === 'make_admin') {
        $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        header('Location: users.php');
        exit();
    } elseif ($action === 'remove_admin') {
        $stmt = $db->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        header('Location: users.php');
        exit();
    } elseif ($action === 'delete_user') {
        // Удаляем заказы пользователя
        $stmt = $db->prepare("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id = ?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $stmt = $db->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Удаляем самого пользователя
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        header('Location: users.php');
        exit();
    }
}

$users = $db->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - Админ-панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { display: flex; }
        .admin-sidebar { width: 250px; background: #1a1a2e; padding: 30px 0; }
        .admin-sidebar a { display: block; padding: 12px 24px; color: #ccc; text-decoration: none; transition: 0.3s; }
        .admin-sidebar a:hover, .admin-sidebar a.active { background: #ff6b6b; color: white; }
        .admin-main { flex: 1; padding: 30px; background: #f8f9fa; }
        .users-table { width: 100%; background: white; border-radius: 12px; overflow-x: auto; }
        .users-table th, .users-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; }
        .status-active { background: #d4edda; color: #155724; }
        .status-banned { background: #f8d7da; color: #721c24; }
        .admin-badge { background: #cce5ff; color: #004085; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
        .btn-sm { padding: 5px 12px; font-size: 0.85rem; cursor: pointer; border: none; border-radius: 4px; margin: 2px; transition: 0.3s; }
        .btn-action { background: #007bff; color: white; }
        .btn-action:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
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
        <a href="orders.php">📦 Заказы</a>
        <a href="products.php">🏷️ Товары</a>
        <a href="users.php" class="active">👥 Пользователи</a>
    </div>
    <div class="admin-main">
        <h1>👥 Управление пользователями</h1>
        <div style="overflow-x: auto;">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Email</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Статус</th>
                        <th>Роль</th>
                        <th>Дата регистрации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['full_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                    <td>
                        <span class="status-badge <?php echo ($u['is_banned'] ?? 0) ? 'status-banned' : 'status-active'; ?>">
                            <?php echo ($u['is_banned'] ?? 0) ? '🔒 Заблокирован' : '✅ Активен'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['is_admin'] ?? 0): ?>
                            <span class="admin-badge">👑 Администратор</span>
                        <?php else: ?>
                            <span style="color: #666;">👤 Пользователь</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <form method="POST" style="display: inline-flex; gap: 2px; flex-wrap: wrap;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            
                            <!-- Блокировка/Разблокировка -->
                            <button type="submit" name="action" value="toggle_status" class="btn-sm btn-action" onclick="return confirm('<?php echo ($u['is_banned'] ?? 0) ? 'Разблокировать' : 'Заблокировать'; ?> этого пользователя?');">
                                <?php echo ($u['is_banned'] ?? 0) ? '🔓 Разблокировать' : '🔒 Заблокировать'; ?>
                            </button>
                            
                            <!-- Выдача/Отзыв админа -->
                            <?php if ($u['is_admin'] ?? 0): ?>
                                <button type="submit" name="action" value="remove_admin" class="btn-sm btn-danger" onclick="return confirm('Отозвать права админа?');">👤 Обычный юзер</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="make_admin" class="btn-sm btn-success">👑 Сделать админом</button>
                            <?php endif; ?>
                            
                            <!-- Удаление -->
                            <button type="submit" name="action" value="delete_user" class="btn-sm btn-danger" onclick="return confirm('Удалить пользователя и все его заказы? Это действие необратимо!');" style="background: #6f42c1;">🗑️ Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>