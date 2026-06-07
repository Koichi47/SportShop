<?php
require_once '../config/database.php';
require_once '../auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_admin'])) {
    $stmt = $db->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
    $stmt->bind_param("ii", $_POST['is_admin'], $_POST['user_id']);
    $stmt->execute();
    header('Location: users.php');
    exit();
}

if (isset($_GET['delete'])) {
    if ($_GET['delete'] != $_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $_GET['delete']);
        $stmt->execute();
    }
    header('Location: users.php');
    exit();
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
        .admin-sidebar a { display: block; padding: 12px 24px; color: #ccc; text-decoration: none; }
        .admin-sidebar a:hover, .admin-sidebar a.active { background: #ff6b6b; color: white; }
        .admin-main { flex: 1; padding: 30px; background: #f8f9fa; }
        .users-table { width: 100%; background: white; border-radius: 12px; overflow-x: auto; }
        .users-table th, .users-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .admin-badge { background: #28a745; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; display: inline-block; }
        .user-badge { background: #6c757d; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; display: inline-block; }
        .btn-sm { padding: 5px 12px; font-size: 0.85rem; margin: 2px; }
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
        <h1>Управление пользователями</h1>
        <div style="overflow-x: auto;">
            <table class="users-table">
                <thead><tr><th>ID</th><th>Логин</th><th>Email</th><th>Имя</th><th>Телефон</th><th>Статус</th><th>Дата</th><th>Действия</th></tr></thead>
                <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['full_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                    <td><?php echo $u['is_admin'] ? '<span class="admin-badge">Админ</span>' : '<span class="user-badge">Пользователь</span>'; ?></td>
                    <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="is_admin" value="<?php echo $u['is_admin'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_admin" class="btn-sm"><?php echo $u['is_admin'] ? 'Снять админа' : 'Сделать админом'; ?></button>
                        </form>
                        <a href="?delete=<?php echo $u['id']; ?>" class="btn-sm" onclick="return confirm('Удалить пользователя?')" style="background:#dc3545; color:white;">🗑️</a>
                        <?php else: ?>
                        <span style="color:#999;">(Вы)</span>
                        <?php endif; ?>
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