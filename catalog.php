<?php
require_once 'config/database.php';
require_once 'auth.php';

$user = getCurrentUser();
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

// Построение запроса с поиском и фильтрацией
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= 's';
}

// Сортировка
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    default: // newest
        $sql .= " ORDER BY created_at DESC";
}

// Выполнение запроса
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Получение уникальных категорий
$categories_result = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог - SportShop PRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .catalog-container { display: flex; gap: 20px; padding: 60px 30px; max-width: 1400px; margin: 0 auto; }
        .catalog-sidebar { width: 250px; }
        .filter-section { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .filter-section h3 { margin-top: 0; font-size: 1.1rem; }
        .filter-section input, .filter-section select { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .filter-section button { width: 100%; padding: 10px; background: #ff6b6b; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .filter-section button:hover { background: #ff5252; }
        .categories-list { list-style: none; padding: 0; }
        .categories-list li { margin-bottom: 8px; }
        .categories-list a { text-decoration: none; color: #333; padding: 8px; display: block; border-radius: 5px; transition: 0.3s; }
        .categories-list a:hover, .categories-list a.active { background: #ff6b6b; color: white; }
        .catalog-main { flex: 1; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .toolbar select { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .product { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; }
        .product:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .product img { width: 100%; height: 200px; object-fit: cover; }
        .product-info { padding: 15px; }
        .product h3 { margin: 0 0 10px 0; font-size: 1rem; }
        .price { font-size: 1.3rem; font-weight: bold; color: #ff6b6b; margin-bottom: 10px; }
        .product-desc { font-size: 0.85rem; color: #666; margin-bottom: 10px; }
        .no-results { text-align: center; padding: 60px 20px; }
        @media (max-width: 768px) { .catalog-container { flex-direction: column; } .catalog-sidebar { width: 100%; } .products-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); } }
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
    <h1>🛍️ Каталог товаров</h1>
    <p>Выберите лучшие товары для спорта и фитнеса</p>
</section>

<div class="catalog-container">
    <!-- Боковая панель фильтров -->
    <aside class="catalog-sidebar">
        <!-- Поиск -->
        <div class="filter-section">
            <h3>🔍 Поиск</h3>
            <form method="GET" action="catalog.php">
                <input type="text" name="search" placeholder="Искать товар..." value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <button type="submit">Найти</button>
            </form>
        </div>

        <!-- Категории -->
        <div class="filter-section">
            <h3>📂 Категории</h3>
            <ul class="categories-list">
                <li><a href="catalog.php" <?php echo empty($category) ? 'class="active"' : ''; ?>>Все товары</a></li>
                <?php while ($cat = $categories_result->fetch_assoc()): ?>
                <li>
                    <a href="catalog.php?category=<?php echo urlencode($cat['category']); ?>&search=<?php echo urlencode($search); ?>" 
                       <?php echo ($category === $cat['category']) ? 'class="active"' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </a>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </aside>

    <!-- Основной контент -->
    <div class="catalog-main">
        <!-- Панель инструментов -->
        <div class="toolbar">
            <div style="flex: 1; display: flex; gap: 10px;">
                <label style="display: flex; align-items: center; gap: 5px;">
                    <span>Сортировка:</span>
                    <form method="GET" action="catalog.php" style="display: flex; gap: 5px;">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <select name="sort" onchange="this.form.submit();">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Новые</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Старые</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Цена: по возрастанию</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Цена: по убыванию</option>
                        </select>
                    </form>
                </label>
            </div>
        </div>

        <!-- Сетка товаров -->
        <?php if ($products->num_rows > 0): ?>
            <div class="products-grid">
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
                            echo 'https://via.placeholder.com/220x200?text=No+Image';
                        }
                    ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://via.placeholder.com/220x200'">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-desc"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 60)); ?></div>
                        <div class="price">€<?php echo number_format($product['price'], 2); ?></div>
                        <button class="btn" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo addslashes($product['image_url'] ?? ''); ?>')">
                            🛒 В корзину
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h2>😔 Товары не найдены</h2>
                <p>Попробуйте изменить фильтры или условия поиска</p>
                <a href="catalog.php" class="btn" style="margin-top: 20px;">Вернуться к каталогу</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>© 2026 SPORT PRO — лидер спортивной экипировки.</p>
</footer>

<div id="toastMsg" class="toast-msg"></div>

<script src="js/cart.js"></script>

</body>
</html>