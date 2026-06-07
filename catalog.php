<?php
require_once 'config/database.php';
require_once 'auth.php';

$user = getCurrentUser();
$products = $db->query("SELECT * FROM products ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог - SportShop PRO</title>
    <link rel="stylesheet" href="css/style.css">
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
        <?php if ($user): ?>
            <a href="profile.php">Личный кабинет</a>
            <?php if (isAdmin()): ?>
                <a href="admin/index.php" style="color: #ff5e5e;">Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Войти</a>
            <a href="register.php">Регистрация</a>
        <?php endif; ?>
    </nav>
    <div class="cart" onclick="location.href='cart.php'">
        🛒 <span id="cart-count">0</span>
    </div>
</header>

<section class="page-header">
    <h1>Каталог товаров</h1>
    <p>Выберите лучшие товары для спорта и фитнеса</p>
</section>

<section class="section">
    <div class="products">
        <?php while ($product = $products->fetch_assoc()): ?>
        <div class="product">
            <img src="<?php 
                if ($product['image_url'] && file_exists($product['image_url'])) {
                    echo htmlspecialchars($product['image_url']);
                } elseif ($product['image_url'] && strpos($product['image_url'], 'http') === 0) {
                    echo htmlspecialchars($product['image_url']);
                } elseif ($product['image_url']) {
                    echo htmlspecialchars($product['image_url']);
                } else {
                    echo 'https://via.placeholder.com/280x200?text=No+Image';
                }
            ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <p style="padding: 0 16px; color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 80)); ?></p>
            <div class="price">€<?php echo number_format($product['price'], 2); ?></div>
            <button class="btn" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo addslashes($product['image_url']); ?>')">
                В корзину
            </button>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<footer>
    <p>© 2026 SPORT PRO — лидер спортивной экипировки.</p>
</footer>

<div id="toastMsg" class="toast-msg"></div>

<script src="js/cart.js"></script>
</body>
</html>