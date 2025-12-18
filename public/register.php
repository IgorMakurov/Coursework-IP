<?php
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Заполните все поля.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некорректный email.";
    } else {
        // !!! ВНИМАНИЕ: Для продакшена используйте password_hash() !!!
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            $success = "Регистрация прошла успешно! Вы можете войти в систему.";
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Пользователь с таким именем или email уже существует.";
            } else {
                $error = "Ошибка базы данных: " . $e->getMessage();
            }
        }
    }
}

require 'includes/header.php';
?>
<h2>Регистрация</h2>
<?php if ($error): ?><p style="color: red;"><?= $error ?></p><?php endif; ?>
<?php if ($success): ?><p style="color: green;"><?= $success ?></p><?php endif; ?>

<form method="POST">
    <p><label for="username">Имя пользователя:</label>
    <input type="text" name="username" required></p>
    
    <p><label for="email">Email:</label>
    <input type="email" name="email" required></p>
    
    <p><label for="password">Пароль:</label>
    <input type="password" name="password" required></p>
    
    <p><button type="submit" name="register">Зарегистрироваться</button></p>
</form>

<?php require 'includes/footer.php'; ?>