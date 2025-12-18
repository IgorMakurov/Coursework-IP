<?php
require_once 'config/db.php';
require 'includes/header.php';

// Получаем все активные опросы
$stmt = $pdo->query("SELECT p.*, u.username FROM polls p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$polls = $stmt->fetchAll();
?>

<h2>Все доступные опросы</h2>

<?php if (empty($polls)): ?>
    <p>Пока нет ни одного опроса.</p>
<?php else: ?>
    <?php foreach ($polls as $poll): ?>
        <div class="poll-card">
            <h3><?= htmlspecialchars($poll['title']) ?></h3>
            <p><em>Автор: <?= htmlspecialchars($poll['username']) ?></em></p>
            <p><?= htmlspecialchars($poll['description']) ?></p>
            
            <?php 
            $options = json_decode($poll['options'], true);
            
            // Проверка, голосовали ли мы в этом опросе (для авторизованных)
            $has_voted = false;
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $check_vote_stmt = $pdo->prepare("SELECT id FROM votes WHERE poll_id = ? AND user_id = ?");
                $check_vote_stmt->execute([$poll['id'], $user_id]);
                if ($check_vote_stmt->fetch()) {
                    $has_voted = true;
                }
            }
            ?>

            <?php if ($has_voted): ?>
                <p style="color: green;">Вы уже проголосовали в этом опросе.</p>
                <a href="poll_view.php?id=<?= $poll['id'] ?>">Посмотреть результаты</a>
            <?php else: ?>
                <a href="poll_view.php?id=<?= $poll['id'] ?>">Проголосовать</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>