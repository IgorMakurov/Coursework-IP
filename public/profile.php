<?php
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем опросы пользователя
$stmt = $pdo->prepare("SELECT * FROM polls WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_polls = $stmt->fetchAll();

require 'includes/header.php';
?>
<h2>Личный кабинет (<?= htmlspecialchars($_SESSION['username']) ?>)</h2>

<?php if ($_SESSION['role'] == 'admin'): ?>
    <p><a href="admin.php">Перейти в панель администратора</a></p>
<?php endif; ?>

<?php if ($_SESSION['can_create_poll'] == false): ?>
    <p style="color: red; border: 1px solid red; padding: 10px;">
        Внимание! Администратор заблокировал вам возможность создавать новые опросы.
    </p>
<?php endif; ?>


<h3>Ваши опросы</h3>

<?php if (empty($my_polls)): ?>
    <p>Вы еще не создали ни одного опроса.</p>
<?php else: ?>
    <?php foreach ($my_polls as $poll): ?>
        <div class="poll-card">
            <h4><?= htmlspecialchars($poll['title']) ?></h4>
            <p>Создан: <?= $poll['created_at'] ?></p>
            <a href="poll_view.php?id=<?= $poll['id'] ?>">Посмотреть (<?= $poll['id'] ?>)</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>