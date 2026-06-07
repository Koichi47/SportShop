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
        .cart-page {
            max-width: 1000px;
            margin: 60px auto;
            padding: 0 20px;
        }
        .cart-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .cart-table th, .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .cart-table th {
            background: #f8f9fa;
        }
        .cart-item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .quantity-input {
            width: 60px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .cart-summary {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }
        .summary-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ff6b6b;
            border-top: 2px solid #f0f0f0;
            padding-top: 15px;
            margin-top: 10px;
        }
        .empty-cart {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 12px;
        }
        .btn-sm {
            padding: 5px 12px;
            font-size: 0.85rem;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        @media (max-width: 768px) {
            .cart-table th, .cart-table td {
                padding: 10px;
            }
            .cart-item-img {
                width: 40px;
                height: 40px;
            }
        }
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
            <?php if (isset($user['is_admin']) && $user['is_admin'] == 1): ?>
                <a href="admin/index.php" style="color: #ff5e5e;">Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Войти</a>
            <a href="register.php">Регистрация</a>
        <?php endif; ?>
    </nav>
    <div class="cart" onclick="window.location.href='cart_page.php'">
        🛒 <span id="cart-count">0</span>
    </div>
</header>

<section class="page-header">
    <h1>🛍️ Корзина</h1>
    <p>Ваши выбранные товары</p>
</section>

<div class="cart-page">
    <div id="cart-content">
        <div class="empty-cart">
            <h2>Корзина пуста</h2>
            <p>Добавьте товары в корзину, чтобы оформить заказ</p>
            <a href="catalog.php" class="btn" style="margin-top: 20px;">Перейти в каталог</a>
        </div>
    </div>
</div>

<footer>
    © 2026 SPORT PRO — лидер спортивной экипировки. Все права защищены.
</footer>

<div id="toastMsg" class="toast-msg"></div>

<script src="simple_cart.js"></script>
<script>
let currentCart = [];

function loadAndDisplayCart() {
    currentCart = CartManager.getCart();
    displayCart();
    CartManager.updateCounter();
}

function displayCart() {
    const container = document.getElementById('cart-content');
    
    if (!currentCart || currentCart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <h2>🛒 Корзина пуста</h2>
                <p>Добавьте товары в корзину, чтобы оформить заказ</p>
                <a href="catalog.php" class="btn" style="margin-top: 20px;">Перейти в каталог</a>
            </div>
        `;
        return;
    }
    
    let total = 0;
    let itemsHtml = `
        <table class="cart-table">
            <thead>
                 <tr>
                    <th>Товар</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
    `;
    
    currentCart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        itemsHtml += `
            <tr>
                <td><img src="${item.image || 'https://via.placeholder.com/60'}" class="cart-item-img" alt="${escapeHtml(item.name)}"></td>
                <td><strong>${escapeHtml(item.name)}</strong></td>
                <td>€${item.price}</td>
                <td>
                    <input type="number" class="quantity-input" value="${item.quantity}" min="1" 
                           onchange="updateQuantity(${item.id}, this.value)">
                </td>
                <td>€${itemTotal.toFixed(2)}</td>
                <td><button class="btn btn-sm" onclick="removeItem(${item.id})">🗑️ Удалить</button></td>
            </tr>
        `;
    });
    
    itemsHtml += `
            </tbody>
        </table>
        
        <div class="cart-summary">
            <div class="summary-row">
                <span>Товары:</span>
                <span>€${total.toFixed(2)}</span>
            </div>
            <div class="summary-row">
                <span>Доставка:</span>
                <span>Бесплатно</span>
            </div>
            <div class="summary-total">
                <span>Итого:</span>
                <span>€${total.toFixed(2)}</span>
            </div>
            <button class="btn" onclick="clearCartAndReload()" style="background: #6c757d; margin-top: 15px;">🗑️ Очистить корзину</button>
    `;
    
    // Добавляем форму оформления заказа
    <?php if ($user): ?>
    itemsHtml += `
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h3>Данные для доставки</h3>
                <form id="checkout-form">
                    <div class="form-group">
                        <label>Ваше имя</label>
                        <input type="text" id="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>" required>
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
            </div>
    `;
    <?php else: ?>
    itemsHtml += `
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                    <p>Для оформления заказа необходимо <a href="login.php">войти</a> или <a href="register.php">зарегистрироваться</a></p>
                </div>
            </div>
    `;
    <?php endif; ?>
    
    itemsHtml += `</div>`;
    container.innerHTML = itemsHtml;
}

function updateQuantity(id, quantity) {
    const newQuantity = parseInt(quantity);
    if (isNaN(newQuantity) || newQuantity < 1) {
        alert('Количество должно быть не менее 1');
        loadAndDisplayCart();
        return;
    }
    CartManager.updateQuantity(id, newQuantity);
    loadAndDisplayCart();
}

function removeItem(id) {
    if (confirm('Удалить товар из корзины?')) {
        CartManager.removeItem(id);
        loadAndDisplayCart();
    }
}

function clearCartAndReload() {
    if (confirm('Очистить корзину?')) {
        CartManager.clearCart();
        loadAndDisplayCart();
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Оформление заказа
document.addEventListener('submit', async function(e) {
    if (e.target.id === 'checkout-form') {
        e.preventDefault();
        
        if (!currentCart.length) {
            CartManager.showMessage('Корзина пуста', true);
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'checkout');
        formData.append('full_name', document.getElementById('full_name').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('address', document.getElementById('address').value);
        formData.append('cart', JSON.stringify(currentCart));
        
        const total = currentCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        formData.append('total', total);
        
        try {
            const response = await fetch('process_order.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                CartManager.showMessage('✅ Заказ успешно оформлен!');
                localStorage.removeItem('sportshop_cart');
                setTimeout(() => {
                    window.location.href = 'profile.php';
                }, 1500);
            } else {
                CartManager.showMessage(result.error || 'Ошибка оформления заказа', true);
            }
        } catch (error) {
            CartManager.showMessage('Ошибка соединения', true);
        }
    }
});

// Инициализация
document.addEventListener('DOMContentLoaded', loadAndDisplayCart);
</script>
</body>
</html>