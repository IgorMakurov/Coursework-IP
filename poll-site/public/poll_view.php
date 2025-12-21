<?php
require_once 'config/db.php';
$poll_id = $_GET['id'] ?? 0;

if (!$poll_id || !is_numeric($poll_id)) {
    header("Location: polls.php");
    exit;
}

// 1. Получаем данные опроса
$stmt = $pdo->prepare("SELECT p.*, u.username FROM polls p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$poll_id]);
$poll = $stmt->fetch();

if (!$poll) {
    header("Location: polls.php");
    exit;
}

$options = json_decode($poll['options'], true);
$total_votes = 0;

// 2. Обработка голоса
$vote_message = '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Проверяем, голосовал ли уже этот пользователь (по user_id ИЛИ по IP, если user_id отсутствует)
$check_vote_stmt = $pdo->prepare("
    SELECT id FROM votes 
    WHERE poll_id = ? 
    AND (
        (user_id IS NOT NULL AND user_id = ?) 
    )
");
$check_vote_stmt->execute([$poll_id, $user_id]);

if ($check_vote_stmt->fetch()) {
    $vote_message = "✓ Вы уже проголосовали в этом опросе.";
    $already_voted = true; // Устанавливаем флаг для отображения результатов
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote_submit'])) {
    $option_index = (int)$_POST['vote_option'];

    if (array_key_exists($option_index, $options)) {
        
        try {
            // Вставляем голос. Если пользователь авторизован, user_id будет NOT NULL.
            // Если гость, user_id будет NULL, и UNIQUE будет проверяться по voted_ip.
            $insert_stmt = $pdo->prepare("INSERT INTO votes (poll_id, user_id, option_index) VALUES (?, ?, ?)");
            
            $insert_stmt->execute([$poll_id, $user_id, $option_index]);
            
            $vote_message = "Ваш голос учтен!";
            // После успешной вставки, перенаправляем или обновляем страницу, чтобы показать результаты
            header("Location: poll_view.php?id=" . $poll_id);
            exit;

        } catch (\PDOException $e) {
            // Если мы дошли сюда, значит, по какой-то причине не сработала предварительная проверка (например, гость сменил IP)
            if ($e->getCode() == 23000) {
                 $vote_message = "Вы уже голосовали в этом опросе (повторная проверка).";
            } else {
                $vote_message = "Критическая ошибка при голосовании: " . $e->getMessage();
            }
        }
    } else {
        $vote_message = "Выбран неверный вариант.";
    }
}

// 3. Подсчет результатов для отображения
$vote_counts = array_fill(0, count($options), 0);
$votes_stmt = $pdo->prepare("SELECT option_index FROM votes WHERE poll_id = ?");
$votes_stmt->execute([$poll_id]);
$all_votes = $votes_stmt->fetchAll();

foreach ($all_votes as $vote) {
    $vote_counts[$vote['option_index']]++;
}
$total_votes = count($all_votes);

require 'includes/header.php';
?>
<h2><?= htmlspecialchars($poll['title']) ?></h2>
<p><em>Автор: <?= htmlspecialchars($poll['username']) ?></em></p>
<p><?= htmlspecialchars($poll['description']) ?></p>

<?php if ($vote_message): ?>
    <p style="color: <?= strpos($vote_message, 'успех') !== false || strpos($vote_message, 'учтен') !== false ? 'green' : 'green' ?>;">
        <?= $vote_message ?>
    </p>
<?php endif; ?>

<?php 
// Проверка, голосовал ли пользователь (или гость)
$already_voted = false;
if (isset($_SESSION['user_id'])) {
    $check_stmt = $pdo->prepare("SELECT id FROM votes WHERE poll_id = ? AND user_id = ?");
    $check_stmt->execute([$poll_id, $_SESSION['user_id']]);
    if ($check_stmt->fetch()) $already_voted = true;
} elseif (isset($_SERVER['REMOTE_ADDR'])) {
    // Проверка для гостей
    $check_stmt = $pdo->prepare("SELECT id FROM votes WHERE poll_id = ?");
    $check_stmt->execute([$poll_id, $_SERVER['REMOTE_ADDR']]);
    if ($check_stmt->fetch()) $already_voted = true;
}
?>

<?php if (!$already_voted): ?>
    <h3>Ваш голос</h3>
    <form method="POST">
        <?php foreach ($options as $index => $option): ?>
            <p>
                <input type="radio" id="opt_<?= $index ?>" name="vote_option" value="<?= $index ?>" required>
                <label for="opt_<?= $index ?>"><?= htmlspecialchars($option) ?></label>
            </p>
        <?php endforeach; ?>
        <button type="submit" name="vote_submit" class="btn-primary">Проголосовать</button>
    </form>
<?php endif; ?>

<hr>

<h3>Результаты (<?= $total_votes ?> голосов)</h3>
<?php if ($total_votes > 0): ?>
    <?php foreach ($options as $index => $option): 
        $count = $vote_counts[$index];
        $percentage = ($count / $total_votes) * 100;
    ?>
        <div style="margin-bottom: 10px;">
            <strong><?= htmlspecialchars($option) ?></strong> 
            (<?= $count ?>) - <?= number_format($percentage, 1) ?>%
            <div style="background: #eee; height: 15px; border-radius: 3px;">
                <div style="width: <?= $percentage ?>%; background-color: #007bff; height: 15px; border-radius: 3px;"></div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Голосование еще не началось.</p>
<?php endif; ?>


<?php require 'includes/footer.php'; ?>