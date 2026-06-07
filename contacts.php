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
    <title>Контакты - SportShop PRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .contacts-content { max-width: 1000px; margin: 60px auto; padding: 0 20px; }
        .contacts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 50px; }
        .contact-card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .contact-icon { font-size: 2rem; margin-bottom: 15px; }
        .contact-card h3 { color: #ff6b6b; margin-bottom: 10px; }
        .contact-card p { color: #555; line-height: 1.6; margin: 5px 0; }
        .feedback-form { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .feedback-form input, .feedback-form textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        .map-container { margin-top: 40px; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .map-container iframe { width: 100%; height: 400px; border: none; }
        @media (max-width: 768px) { .contacts-grid { grid-template-columns: 1fr; } }
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
    <h1>Контакты</h1>
    <p>Свяжитесь с нами любым удобным способом</p>
</section>

<div class="contacts-content">
    <div class="contacts-grid">
        <div>
            <div class="contact-card">
                <div class="contact-icon">📍</div>
                <h3>Адрес</h3>
                <p>г. Улан-Удэ, ул. Спортивная, д. 15</p>
                <p>ТЦ "Peopls", 3 этаж</p>
            </div>
            <div class="contact-card" style="margin-top: 20px;">
                <div class="contact-icon">📞</div>
                <h3>Телефоны</h3>
                <p>+7 (495) 123-45-67</p>
                <p>+7 (800) 555-35-35 (бесплатно по России)</p>
                <p>Режим работы: Ежедневно с 9:00 до 21:00</p>
            </div>
            <div class="contact-card" style="margin-top: 20px;">
                <div class="contact-icon">✉️</div>
                <h3>Email</h3>
                <p>info@gmail.com - общие вопросы</p>
                <p>support@gmail.com - поддержка</p>
                <p>wholesale@gmail.com - оптовые заказы</p>
            </div>
        </div>
        
        <div>
            <div class="feedback-form">
                <h3 style="margin-bottom: 20px;">📝 Напишите нам</h3>
                <form id="feedbackForm">
                    <input type="text" id="feedbackName" placeholder="Ваше имя *" required>
                    <input type="email" id="feedbackEmail" placeholder="Email *" required>
                    <input type="text" id="feedbackSubject" placeholder="Тема">
                    <textarea id="feedbackMessage" rows="5" placeholder="Сообщение *" required></textarea>
                    <button type="submit" class="btn">Отправить сообщение</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="map-container">
        <iframe 
            src="https://yandex.ru/map-widget/v1/?um=constructor%3A123456789&source=constructor" 
            frameborder="0"
            allowfullscreen="true">
        </iframe>
    </div>
</div>

<footer>
    <p>© 2026 SPORT PRO — лидер спортивной экипировки.</p>
</footer>

<div id="toastMsg" class="toast-msg"></div>

<script src="js/cart.js"></script>
<script>
document.getElementById('feedbackForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const name = document.getElementById('feedbackName').value;
    Cart.showMessage(`Спасибо, ${name}! Ваше сообщение отправлено. Мы ответим в течение 24 часов.`);
    this.reset();
});
</script>
</body>
</html>