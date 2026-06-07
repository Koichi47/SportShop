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
    <title>Корзина - SportShop PRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-page { max-width: 1000px; margin: 60px auto; padding: 0 20px; }
        .cart-table { width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .cart-table th, .cart-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .cart-table th { background: #f8f9fa; }
        .cart-item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .quantity-input { width: 60px; padding: 5px; text-align: center; border: 1px solid #ddd; border-radius: 5px; }
        .cart-summary { background: white; border-radius: 12px; padding: 20px; margin-top: 20px; }
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; }
        .summary-total { font-size: 1.3rem; font-weight: bold; color: #ff6b6b; border-top: 2px solid #f0f0f0; padding-top: 15px; margin-top: 10px; }
        .empty-cart { text-align: center; padding: 60px; background: white; border-radius: 12px; }
        .btn-sm { padding: 5px 12px; font-size: 0.85rem; background: #6c757d; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        @media (max-width: 768px) { .cart-table th, .cart-table td { padding: 10px; } .cart-item-img { width: 40px; height: 40px; } }
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
    <h1>🛍️ Корзина</h1>
    <p>Ваши выбранные товары</p>
</section>

<div class="cart-page" id="cart-container">
    <div class="empty-cart">Загрузка...</div>
</div>

<footer>
    <p>© 2026 SPORT PRO — лидер спортивной экипировки.</p>
</footer>

<div id="toastMsg" class="toast-msg"></div>

<script src="js/cart.js"></script>
<script>
let currentCart = [];

function renderCart() {
    currentCart = Cart.get();
    const container = document.getElementById('cart-container');
    
    if (!currentCart.length) {
        container.innerHTML = `<div class="empty-cart"><h2>🛒 Корзина пуста</h2><p>Добавьте товары в корзину, чтобы оформить заказ</p><a href="catalog.php" class="btn">Перейти в каталог</a></div>`;
        return;
    }
    
    let total = 0;
    let html = `<table class="cart-table"><thead><tr><th>Товар</th><th>Название</th><th>Цена</th><th>Кол-во</th><th>Сумма</th><th></th></tr></thead><tbody>`;
    
    currentCart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        html += `<tr>
            <td><img src="${item.image || 'https://via.placeholder.com/60'}" class="cart-item-img" onerror="this.src='https://via.placeholder.com/60'"></td>
            <td><strong>${escapeHtml(item.name)}</strong></td>
            <td>€${item.price}</td>
            <td><input type="number" class="quantity-input" value="${item.quantity}" min="1" onchange="updateQty(${item.id}, this.value)"></td>
            <td>€${itemTotal.toFixed(2)}</td>
            <td><button class="btn btn-sm" onclick="removeItem(${item.id})">🗑️ Удалить</button></td>
        </tr>`;
    });
    
    html += `</tbody></table>
    <div class="cart-summary">
        <div class="summary-row"><span>Товары:</span><span>€${total.toFixed(2)}</span></div>
        <div class="summary-row"><span>Доставка:</span><span>Бесплатно</span></div>
        <div class="summary-total"><span>Итого:</span><span>€${total.toFixed(2)}</span></div>
        <button class="btn" onclick="clearCartAndReload()" style="background:#6c757d; margin-top:15px;">🗑️ Очистить корзину</button>`;
    
    <?php if ($user): ?>
    html += `<div style="margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
        <h3>📋 Данные для доставки</h3>
        <form id="checkoutForm">
            <div class="form-group">
                <label>Ваше имя</label>
                <input type="text" id="fullName" value="<?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Адрес доставки</label>
                <textarea id="address" rows="3" required placeholder="Введите ваш полный адрес"></textarea>
            </div>
            <button type="submit" class="btn">✅ Оформить заказ</button>
        </form>
    </div>`;
    <?php else: ?>
    html += `<div style="margin-top:30px; padding:20px; background:#e3f2fd; border-radius:12px; text-align:center;">
        <p>🔐 Для оформления заказа необходимо <a href="login.php">войти</a> или <a href="register.php">зарегистрироваться</a></p>
    </div>`;
    <?php endif; ?>
    
    html += `</div>`;
    container.innerHTML = html;
}

function updateQty(id, qty) {
    Cart.update(id, parseInt(qty));
    renderCart();
    Cart.updateCounter();
}

function removeItem(id) {
    if (confirm('Удалить товар из корзины?')) {
        Cart.remove(id);
        renderCart();
        Cart.updateCounter();
    }
}

function clearCartAndReload() {
    if (confirm('Очистить корзину?')) {
        Cart.clear();
        renderCart();
        Cart.updateCounter();
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
}

<?php if ($user): ?>
document.addEventListener('submit', async function(e) {
    if (e.target.id === 'checkoutForm') {
        e.preventDefault();
        const cart = Cart.get();
        if (!cart.length) { 
            Cart.showMessage('Корзина пуста', true); 
            return; 
        }
        
        const formData = new FormData();
        formData.append('action', 'checkout');
        formData.append('full_name', document.getElementById('fullName').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('address', document.getElementById('address').value);
        formData.append('cart', JSON.stringify(cart));
        formData.append('total', Cart.getTotal());
        
        try {
            const res = await fetch('process_order.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                Cart.showMessage('✅ Заказ успешно оформлен!');
                localStorage.removeItem('sportshop_cart');
                setTimeout(() => location.href = 'profile.php', 1500);
            } else {
                Cart.showMessage(data.error || 'Ошибка оформления заказа', true);
            }
        } catch(e) { 
            Cart.showMessage('Ошибка соединения с сервером', true); 
        }
    }
});
<?php endif; ?>

renderCart();
Cart.updateCounter();
</script>
</body>
</html>