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

// Функция загрузки изображения
function uploadImage($file) {
    $targetDir = "../uploads/";
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Проверяем ошибки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Ошибка загрузки файла'];
    }
    
    // Проверяем тип файла
    $fileType = $file['type'];
    if (!in_array($fileType, $allowedTypes)) {
        return ['error' => 'Разрешены только JPG, PNG изображения'];
    }
    
    // Проверяем размер
    if ($file['size'] > $maxSize) {
        return ['error' => 'Размер файла не должен превышать 5MB'];
    }
    
    // Создаем уникальное имя файла
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . strtolower($extension);
    $targetPath = $targetDir . $fileName;
    
    // Загружаем файл
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => 'uploads/' . $fileName];
    } else {
        return ['error' => 'Ошибка сохранения файла'];
    }
}

// Добавление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // CSRF проверка
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Ошибка безопасности (CSRF)';
    } else {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $stock = intval($_POST['stock'] ?? 0);
        
        // Валидация
        if (empty($name) || $price <= 0) {
            $error = 'Название и цена обязательны';
        } else {
            $image_path = '';
            
            // Обрабатываем загруженное изображение
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImage($_FILES['product_image']);
                if (isset($uploadResult['error'])) {
                    $error = $uploadResult['error'];
                } else {
                    $image_path = $uploadResult['path'];
                }
            } elseif (!empty($_POST['image_url'])) {
                $image_path = trim($_POST['image_url']);
            }
            
            if (!isset($error)) {
                $stmt = $db->prepare("INSERT INTO products (name, price, category, description, image_url, stock) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sdsssi", $name, $price, $category, $description, $image_path, $stock);
                
                if ($stmt->execute()) {
                    $success = "✅ Товар успешно добавлен!";
                } else {
                    $error = "❌ Ошибка добавления товара";
                }
            }
        }
    }
}

// Редактирование товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    // CSRF проверка
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Ошибка безопасности (CSRF)';
    } else {
        $id = intval($_POST['id']);
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $stock = intval($_POST['stock'] ?? 0);
        
        // Валидация
        if (empty($name) || $price <= 0) {
            $error = 'Название и цена обязательны';
        } else {
            // Получаем текущее изображение
            $stmt = $db->prepare("SELECT image_url FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_product = $result->fetch_assoc();
            $image_path = $current_product['image_url'];
            
            // Обрабатываем новое изображение
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImage($_FILES['product_image']);
                if (isset($uploadResult['error'])) {
                    $error = $uploadResult['error'];
                } else {
                    // Удаляем старое изображение
                    if ($image_path && strpos($image_path, 'uploads/') === 0 && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    $image_path = $uploadResult['path'];
                }
            } elseif (!empty($_POST['image_url'])) {
                // Если загружен новый URL, используем его
                $image_path = trim($_POST['image_url']);
            }
            
            if (!isset($error)) {
                $stmt = $db->prepare("UPDATE products SET name=?, price=?, category=?, description=?, image_url=?, stock=? WHERE id=?");
                $stmt->bind_param("sdsssi", $name, $price, $category, $description, $image_path, $stock, $id);
                
                if ($stmt->execute()) {
                    $success = "✅ Товар успешно обновлен!";
                } else {
                    $error = "❌ Ошибка обновления товара";
                }
            }
        }
    }
}

// Удаление товара
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Получаем изображение перед удалением
    $stmt = $db->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    // Удаляем файл изображения
    if ($product && $product['image_url'] && strpos($product['image_url'], 'uploads/') === 0) {
        $file_path = '../' . $product['image_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: products.php');
    exit();
}

$products = $db->query("SELECT * FROM products ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товары - Админ-панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { display: flex; }
        .admin-sidebar { width: 250px; background: #1a1a2e; padding: 30px 0; }
        .admin-sidebar a { display: block; padding: 12px 24px; color: #ccc; text-decoration: none; transition: 0.3s; }
        .admin-sidebar a:hover, .admin-sidebar a.active { background: #ff6b6b; color: white; }
        .admin-main { flex: 1; padding: 30px; background: #f8f9fa; }
        .products-table { width: 100%; background: white; border-radius: 12px; overflow-x: auto; }
        .products-table th, .products-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .btn-sm { padding: 5px 12px; font-size: 0.85rem; margin: 2px; cursor: pointer; border: none; border-radius: 4px; background: #007bff; color: white; transition: 0.3s; }
        .btn-sm:hover { background: #0056b3; }
        .btn-sm.delete { background: #dc3545; }
        .btn-sm.delete:hover { background: #c82333; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .form-group input[type="file"] { padding: 5px; }
        .add-btn { margin-bottom: 20px; }
        .current-image { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 8px; text-align: center; }
        .current-image img { max-width: 100px; max-height: 100px; border-radius: 8px; }
        .image-preview { margin-top: 10px; text-align: center; }
        .image-preview img { max-width: 150px; max-height: 150px; border-radius: 8px; border: 1px solid #ddd; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
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
        <a href="products.php" class="active">🏷️ Товары</a>
        <a href="users.php">👥 Пользователи</a>
    </div>
    <div class="admin-main">
        <h1>🏷️ Управление товарами</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="add-btn">
            <button class="btn" onclick="openAddModal()">➕ Добавить товар</button>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Изображение</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Категория</th>
                        <th>Наличие</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($p = $products->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td>
                        <?php if ($p['image_url']): ?>
                            <img src="../<?php echo htmlspecialchars($p['image_url']); ?>" class="product-img" onerror="this.src='https://via.placeholder.com/60'">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/60" class="product-img">
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                    <td>€<?php echo number_format($p['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($p['category'] ?? '-'); ?></td>
                    <td><?php echo $p['stock']; ?> шт.</td>
                    <td>
                        <button class="btn-sm" onclick="editProduct(<?php echo htmlspecialchars(json_encode($p)); ?>)">✏️ Редактировать</button>
                        <a href="?delete=<?php echo $p['id']; ?>" class="btn-sm delete" onclick="return confirm('Удалить товар? Это действие необратимо!');">🗑️ Удалить</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно добавления товара -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <h2>➕ Добавить новый товар</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label>Название товара *</label>
                <input type="text" name="name" required placeholder="Введите название">
            </div>
            
            <div class="form-group">
                <label>Цена (€) *</label>
                <input type="number" step="0.01" name="price" required placeholder="0.00" min="0">
            </div>
            
            <div class="form-group">
                <label>Категория</label>
                <input type="text" name="category" placeholder="Пример: Обувь, Одежда, Оборудование">
            </div>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" rows="3" placeholder="Подробное описание товара"></textarea>
            </div>
            
            <div class="form-group">
                <label>Изображение (JPG, PNG)</label>
                <input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png" onchange="previewImage(this, 'addPreview')">
                <div id="addPreview" class="image-preview"></div>
                <small style="color: #666;">📁 Максимум 5MB. Форматы: JPG, PNG</small>
            </div>
            
            <div class="form-group">
                <label>Или URL изображения</label>
                <input type="url" name="image_url" placeholder="https://example.com/image.jpg">
            </div>
            
            <div class="form-group">
                <label>Количество на складе</label>
                <input type="number" name="stock" value="10" min="0">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="add_product" class="btn">💾 Сохранить</button>
                <button type="button" class="btn" onclick="closeAddModal()" style="background:#6c757d">❌ Отмена</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно редактирования товара -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h2>✏️ Редактировать товар</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Название товара *</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            
            <div class="form-group">
                <label>Цена (€) *</label>
                <input type="number" step="0.01" name="price" id="edit_price" required min="0">
            </div>
            
            <div class="form-group">
                <label>Категория</label>
                <input type="text" name="category" id="edit_category">
            </div>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" id="edit_description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Текущее изображение</label>
                <div id="currentImagePreview" class="current-image"></div>
            </div>
            
            <div class="form-group">
                <label>Новое изображение (JPG, PNG)</label>
                <input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png" onchange="previewImage(this, 'editPreview')">
                <div id="editPreview" class="image-preview"></div>
                <small style="color: #666;">Оставьте пустым для сохранения текущего изображения</small>
            </div>
            
            <div class="form-group">
                <label>Или новый URL изображения</label>
                <input type="url" name="image_url" id="edit_image_url" placeholder="https://...">
            </div>
            
            <div class="form-group">
                <label>Количество на складе</label>
                <input type="number" name="stock" id="edit_stock" min="0">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="edit_product" class="btn">💾 Сохранить изменения</button>
                <button type="button" class="btn" onclick="closeEditModal()" style="background:#6c757d">❌ Отмена</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() { 
    document.getElementById('addModal').classList.add('active');
    document.getElementById('addPreview').innerHTML = '';
}

function closeAddModal() { 
    document.getElementById('addModal').classList.remove('active');
}

function editProduct(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_price').value = p.price;
    document.getElementById('edit_category').value = p.category || '';
    document.getElementById('edit_description').value = p.description || '';
    document.getElementById('edit_image_url').value = p.image_url || '';
    document.getElementById('edit_stock').value = p.stock || 10;
    
    // Показываем текущее изображение
    const currentPreview = document.getElementById('currentImagePreview');
    if (p.image_url) {
        currentPreview.innerHTML = `<img src="../${p.image_url}" onerror="this.src='https://via.placeholder.com/150'">`;
    } else {
        currentPreview.innerHTML = '<p style="color:#999;">❌ Нет изображения</p>';
    }
    
    document.getElementById('editPreview').innerHTML = '';
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() { 
    document.getElementById('editModal').classList.remove('active');
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}">`;
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '';
    }
}
</script>
</body>
</html>