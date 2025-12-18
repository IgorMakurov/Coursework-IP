<?php
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Введите имя пользователя и пароль.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Вход успешен
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['can_create_poll'] = $user['can_create_poll'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Неверное имя пользователя или пароль.";
        }
    }
}

require 'includes/header.php';
?>
<h2>Авторизация</h2>
<?php if ($error): ?><p style="color: red;"><?= $error ?></p><?php endif; ?>

<form method="POST">
    <p><label for="username">Имя пользователя:</label>
    <input type="text" name="username" required></p>
    
    <p><label for="password">Пароль:</label>
    <input type="password" name="password" required></p>
    
    <p><button type="submit" name="login">Войти</button></p>
</form>

<?php require 'includes/footer.php'; ?>