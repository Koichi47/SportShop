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
    <title>Доставка - SportShop PRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .delivery-content { max-width: 1000px; margin: 60px auto; padding: 0 20px; }
        .method-card { background: white; border-radius: 16px; padding: 25px; margin-bottom: 20px; display: flex; gap: 20px; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; }
        .method-card:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .method-icon { font-size: 3rem; min-width: 80px; text-align: center; }
        .method-info h3 { color: #ff6b6b; margin-bottom: 10px; }
        .method-info p { color: #555; line-height: 1.5; }
        .payment-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; }
        .payment-item { background: white; padding: 20px; text-align: center; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .payment-icon { font-size: 2.5rem; margin-bottom: 10px; }
        .info-box { background: #e3f2fd; padding: 20px; border-radius: 12px; margin-top: 30px; }
        .info-box ul { margin-top: 15px; padding-left: 20px; }
        .info-box li { padding: 5px 0; }
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
    <h1>Доставка и оплата</h1>
    <p>Удобные способы получения заказа</p>
</section>

<div class="delivery-content">
    <h2 style="margin-bottom: 30px;">🚚 Способы доставки</h2>
    
    <div class="method-card">
        <div class="method-icon">🚚</div>
        <div class="method-info">
            <h3>Курьерская доставка</h3>
            <p>Доставка по городу в течение 1-2 дней. Курьер свяжется с вами за час до приезда.</p>
            <p><strong>Стоимость:</strong> 300 руб. <strong>Бесплатно</strong> при заказе от 3000 руб.</p>
            <p><small>Время доставки: с 10:00 до 20:00</small></p>
        </div>
    </div>
    
    <div class="method-card">
        <div class="method-icon">📦</div>
        <div class="method-info">
            <h3>Почтовая доставка</h3>
            <p>Доставка по всей России через Почту России. Сроки: 3-10 рабочих дней.</p>
            <p><strong>Стоимость:</strong> от 200 руб. (зависит от веса и региона)</p>
            <p><small>Трек-номер для отслеживания отправляется на email</small></p>
        </div>
    </div>
    
    <div class="method-card">
        <div class="method-icon">🏪</div>
        <div class="method-info">
            <h3>Самовывоз</h3>
            <p>Бесплатно. Вы можете забрать заказ самостоятельно из нашего магазина.</p>
            <p><strong>Адрес:</strong> г. Улан-Удэ, ул. Спортивная, д. 15, ТЦ "Peopls", 3 этаж</p>
            <p><small>Время работы: Пн-Пт с 9:00 до 19:00, Сб-Вс с 10:00 до 17:00</small></p>
        </div>
    </div>
    
    <h2 style="margin: 50px 0 30px;">💳 Способы оплаты</h2>
    <div class="payment-grid">
        <div class="payment-item">
            <div class="payment-icon">💳</div>
            <h3>Банковские карты</h3>
            <p>Visa, MasterCard, МИР</p>
            <small>Оплата онлайн при оформлении</small>
        </div>
        <div class="payment-item">
            <div class="payment-icon">📱</div>
            <h3>Электронные кошельки</h3>
            <p>СБП, Apple Pay, Google Pay</p>
            <small>Мгновенная оплата</small>
        </div>
        <div class="payment-item">
            <div class="payment-icon">💰</div>
            <h3>Наличными</h3>
            <p>При получении заказа</p>
            <small>Только для курьерской доставки</small>
        </div>
    </div>
    
    <div class="info-box">
        <h3>ℹ️ Важная информация</h3>
        <ul>
            <li>Бесплатная доставка при заказе от 3000 руб.</li>
            <li>При заказе до 12:00 - доставка на следующий день</li>
            <li>Возврат товара в течение 14 дней при сохранении товарного вида</li>
            <li>Подарочная упаковка бесплатно по запросу</li>
            <li>Скидка 5% на первый заказ при подписке на новости</li>
        </ul>
    </div>
</div>

<footer>
    <p>© 2026 SPORT PRO — лидер спортивной экипировки.</p>
</footer>

<div id="toastMsg" class="toast-msg"></div>

<script src="js/cart.js"></script>
</body>
</html>