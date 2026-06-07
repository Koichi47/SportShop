<?php
require_once 'config/database.php';
require_once 'auth.php';

echo "<h2>Проверка прав администратора</h2>";

// Проверка сессии
echo "<h3>Сессия:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Проверка пользователя в базе данных
if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "<h3>Текущий пользователь:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // Прямой запрос к БД для проверки is_admin
    $db = new Database();
    $stmt = $db->prepare("SELECT id, username, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $db_user = $result->fetch_assoc();
    
    echo "<h3>Данные из БД:</h3>";
    echo "<pre>";
    print_r($db_user);
    echo "</pre>";
    
    echo "<h3>Результат isAdmin():</h3>";
    echo isAdmin() ? "true (есть права админа)" : "false (нет прав админа)";
} else {
    echo "Пользователь не авторизован";
}

echo "<br><br><a href='index.php'>Вернуться на главную</a>";
?>