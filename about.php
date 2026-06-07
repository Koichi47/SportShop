<?php
require_once 'config/database.php';
require_once 'auth.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нас - SportShop PRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .about-content { max-width: 900px; margin: 60px auto; padding: 0 20px; }
        .about-section { background: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .about-section h2 { color: #ff6b6b; margin-bottom: 15px; }
        .about-section p { line-height: 1.6; color: #555; }
        .values-list { list-style: none; padding: 0; }
        .values-list li { padding: 10px 0; border-bottom: 1px solid #eee; }
        .values-list li:before { content: "✓"; color: #ff6b6b; margin-right: 10px; font-weight: bold; }
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
    <h1>О нас</h1>
    <p>Узнайте больше о компании SportShop PRO</p>
</section>

<div class="about-content">
    <div class="about-section">
        <h2>🏆 Кто мы?</h2>
        <p>SportShop PRO - это современный интернет-магазин спортивных товаров, основанный в 2020 году. Мы предлагаем только качественную продукцию от ведущих мировых производителей. Наша команда состоит из профессиональных спортсменов и экспертов, которые лично тестируют каждый товар перед добавлением в ассортимент.</p>
    </div>
    
    <div class="about-section">
        <h2>🎯 Наша миссия</h2>
        <p>Сделать спорт доступным для каждого. Мы верим, что регулярные занятия спортом - это ключ к здоровой, счастливой и полноценной жизни. Наша цель - вдохновлять людей на активный образ жизни и помогать им достигать новых вершин.</p>
    </div>
    
    <div class="about-section">
        <h2>✨ Почему выбирают нас?</h2>
        <ul class="values-list">
            <li>Только оригинальная продукция от проверенных производителей</li>
            <li>Быстрая доставка по всей стране (1-3 дня)</li>
            <li>Профессиональная консультация от экспертов</li>
            <li>Гарантия качества на все товары (до 12 месяцев)</li>
            <li>Удобные способы оплаты: карты, наличные, онлайн</li>
            <li>Программа лояльности для постоянных клиентов</li>
            <li>Бесплатная примерка перед покупкой</li>
        </ul>
    </div>
    
    <div class="about-section">
        <h2>📊 Наши достижения</h2>
        <p>За 5 лет работы мы помогли более 50 000 клиентов подобрать идеальную экипировку. Наш ассортимент насчитывает более 500 товаров, а уровень удовлетворенности клиентов составляет 98.5%.</p>
    </div>
</div>

<footer>
    <p>© 2026 SPORT PRO — лидер спортивной экипировки.</p>
</footer>

<div id="toastMsg" class="toast-msg"></div>

<script src="js/cart.js"></script>
</body>
</html>