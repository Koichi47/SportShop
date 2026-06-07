<?php
require_once 'config/database.php';
require_once 'auth.php';
requireLogin();

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssi", $full_name, $phone, $user['id']);
    $stmt->execute();
    $_SESSION['user']['full_name'] = $full_name;
    $_SESSION['user']['phone'] = $phone;
    $success = "✅ Профиль успешно обновлен!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    if (password_verify($current, $userData['password'])) {
        if ($new === $confirm && strlen($new) >= 6) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user['id']);
            $stmt->execute();
            $password_success = "✅ Пароль успешно изменен!";
        } else {
            $password_error = "Новый пароль должен быть не менее 6 символов и совпадать с подтверждением";
        }
    } else {
        $password_error = "❌ Неверный текущий пароль";
    }
}

// Получение заказов
$orders = [];
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

while ($order = $result->fetch_assoc()) {
    $stmt2 = $db->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt2->bind_param("i", $order['id']);
    $stmt2->execute();
    $order['items'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $orders[] = $order;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - SportShop PRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container { max-width: 1200px; margin: 60px auto; padding: 0 20px; display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
        .profile-sidebar { background: white; border-radius: 16px; padding: 30px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: fit-content; }
        .profile-avatar { width: 100px; height: 100px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2.5rem; font-weight: bold; color: white; }
        .profile-menu { margin-top: 20px; }
        .menu-tab { width: 100%; padding: 12px; text-align: left; background: none; border: none; cursor: pointer; border-radius: 8px; margin-bottom: 5px; font-size: 1rem; transition: 0.3s; }
        .menu-tab:hover { background: #f0f0f0; }
        .menu-tab.active { background: #ff6b6b; color: white; }
        .profile-main { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .order-card { border: 1px solid #eee; border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
        .order-header { background: #f8f9fa; padding: 15px 20px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .order-status { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .order-items { padding: 15px 20px; }
        .order-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .order-footer { background: #fafafa; padding: 12px 20px; text-align: right; font-weight: bold; }
        @media (max-width: 768px) { .profile-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<header>
    <div class="logo">⚡ SPORT PRO</div>
    <nav>
        <a href="index.php">Главная</a>
        <a href="catalog.php">Каталог</a>
        <a href="about.php">О нас</a>
        <a href="delivery.php">Доставка</a>
        <a href="contacts.php">Контакты</a>
        <a href="profile.php">Личный кабинет</a>
        <?php if (isAdmin()): ?>
            <a href="admin/index.php" style="color: #ff5e5e;">Админ-панель</a>
        <?php endif; ?>
        <a href="logout.php">Выйти</a>
    </nav>
    <div class="cart" onclick="location.href='cart.php'">
        🛒 <span id="cart-count">0</span>
    </div>
</header>

<section class="page-header">
    <h1>Личный кабинет</h1>
    <p>Управляйте своими данными и заказами</p>
</section>

<div class="profile-container">
    <div class="profile-sidebar">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
        </div>
        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
        <p style="color: #666;"><?php echo htmlspecialchars($user['email']); ?></p>
        
        <div class="profile-menu">
            <button class="menu-tab active" data-tab="info">👤 Личные данные</button>
            <button class="menu-tab" data-tab="orders">📦 Мои заказы</button>
            <button class="menu-tab" data-tab="password">🔐 Смена пароля</button>
        </div>
    </div>
    
    <div class="profile-main">
        <!-- Вкладка Личные данные -->
        <div class="tab-content active" id="tab-info">
            <h2>Личные данные</h2>
            <?php if (isset($success)): ?>
                <div class="alert alert-success" style="background: #d4edda; padding: 10px; border-radius: 8px; margin-bottom: 20px;"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Имя пользователя</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Полное имя</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Введите полное имя">
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+7 (999) 123-45-67">
                </div>
                <div class="form-group">
                    <label>Дата регистрации</label>
                    <input type="text" value="<?php echo date('d.m.Y', strtotime($user['created_at'])); ?>" disabled style="background: #f5f5f5;">
                </div>
                <button type="submit" name="update_profile" class="btn">💾 Сохранить изменения</button>
            </form>
        </div>
        
        <!-- Вкладка Мои заказы -->
        <div class="tab-content" id="tab-orders">
            <h2>Мои заказы</h2>
            <?php if (empty($orders)): ?>
                <div style="text-align: center; padding: 60px;">
                    <p style="color: #666;">У вас пока нет заказов</p>
                    <a href="catalog.php" class="btn" style="margin-top: 20px;">🛍️ Перейти в каталог</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>Заказ #<?php echo $order['id']; ?></strong>
                            <span style="margin-left: 10px; color: #666;">от <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="order-status status-<?php echo $order['status']; ?>">
                            <?php
                            $statuses = ['pending' => 'Ожидает обработки', 'processing' => 'В обработке', 'completed' => 'Выполнен', 'cancelled' => 'Отменен'];
                            echo $statuses[$order['status']];
                            ?>
                        </div>
                    </div>
                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                            <span>€<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-footer">
                        Итого: €<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Вкладка Смена пароля -->
        <div class="tab-content" id="tab-password">
            <h2>Смена пароля</h2>
            <?php if (isset($password_success)): ?>
                <div class="alert alert-success" style="background: #d4edda; padding: 10px; border-radius: 8px; margin-bottom: 20px;"><?php echo $password_success; ?></div>
            <?php endif; ?>
            <?php if (isset($password_error)): ?>
                <div class="alert alert-error" style="background: #f8d7da; padding: 10px; border-radius: 8px; margin-bottom: 20px;"><?php echo $password_error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Текущий пароль</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>Новый пароль (мин. 6 символов)</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Подтверждение пароля</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn">🔒 Изменить пароль</button>
            </form>
        </div>
    </div>
</div>

<footer>
    <p>© 2026 SPORT PRO — лидер спортивной экипировки.</p>
</footer>

<div id="toastMsg" class="toast-msg"></div>

<script src="js/cart.js"></script>
<script>
document.querySelectorAll('.menu-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.menu-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});
</script>
</body>
</html>