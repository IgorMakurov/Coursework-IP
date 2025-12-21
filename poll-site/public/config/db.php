<?php
// config/db.php

$host = 'localhost'; // Или хост, предоставленный Render
$db   = 'site_db'; // Замените на имя вашей БД
$user = 'root';       // Замените на ваше имя пользователя БД
$pass = '';   // Замените на ваш пароль БД
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // В реальном приложении не выводить ошибку напрямую, а логировать
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Запуск сессии
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Функция для быстрой проверки роли пользователя
 */
function check_role($role_needed) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    return $_SESSION['role'] === $role_needed;
}
?>