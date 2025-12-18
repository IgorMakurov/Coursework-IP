<?php
// header.php - Должен быть включен после сессии и подключения к БД
// Проверяем, авторизован ли пользователь
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = $is_logged_in && ($_SESSION['role'] == 'admin');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сайт Опросов</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1><a href="index.php">Опросник</a></h1>
        <nav>
            <?php if ($is_logged_in): ?>
                <span>Привет, <?= htmlspecialchars($_SESSION['username']) ?>! (<?= $is_admin ? 'Админ' : 'Пользователь' ?>)</span>
                <a href="create_poll.php">Создать опрос</a>
                <a href="polls.php">Все опросы</a>
                <a href="profile.php">Личный кабинет</a>
                <a href="logout.php">Выход</a>
            <?php else: ?>
                <a href="login.php">Вход</a>
                <a href="register.php">Регистрация</a>
                <a href="polls.php">Все опросы</a>
            <?php endif; ?>
        </nav>
    </header>
    <div class="container">