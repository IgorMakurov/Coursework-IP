<?php
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['can_create_poll'] == FALSE) {
    header("Location: index.php"); // Заблокирован админом
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $options_raw = $_POST['options'];
    $user_id = $_SESSION['user_id'];

    // Разделяем варианты ответа по строкам
    $options_array = array_filter(array_map('trim', explode("\n", $options_raw)));
    
    if (empty($title) || count($options_array) < 2) {
        $message = "Заголовок обязателен, и должно быть минимум два варианта ответа.";
    } else {
        // Преобразуем массив опций в JSON для хранения в БД
        $options_json = json_encode($options_array);

        try {
            $stmt = $pdo->prepare("INSERT INTO polls (user_id, title, description, options) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $description, $options_json]);
            $message = "Опрос успешно создан!";
        } catch (Exception $e) {
            $message = "Ошибка при создании опроса: " . $e->getMessage();
        }
    }
}

require 'includes/header.php';
?>
<h2>Создать новый опрос</h2>
<?php if ($message): ?><p style="color: blue;"><?= $message ?></p><?php endif; ?>

<form method="POST">
    <p><label for="title">Заголовок опроса:</label><br>
    <input type="text" name="title" required style="width: 100%;"></p>
    
    <p><label for="description">Описание (необязательно):</label><br>
    <textarea name="description" rows="3" style="width: 100%;"></textarea></p>
    
    <p><label for="options">Варианты ответа (каждый вариант на новой строке):</label><br>
    <textarea name="options" required rows="6" style="width: 100%;"></textarea></p>
    
    <p><button type="submit" name="create">Опубликовать опрос</button></p>
</form>

<?php require 'includes/footer.php'; ?>