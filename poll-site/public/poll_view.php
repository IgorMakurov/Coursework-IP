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
$user_can_vote = !isset($_SESSION['user_id']) || $_SESSION['can_create_poll']; // Если заблокирован, голосовать нельзя

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote_submit']) && $user_can_vote) {
    $option_index = (int)$_POST['vote_option'];

    if (array_key_exists($option_index, $options)) {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $voted_ip = $_SERVER['REMOTE_ADDR'];

        try {
            // Пытаемся вставить голос
            $insert_stmt = $pdo->prepare("INSERT INTO votes (poll_id, user_id, option_index, voted_ip) VALUES (?, ?, ?, ?)");
            
            // Если user_id NULL, UNIQUE по user_id не сработает, сработает по IP (для гостей)
            if (isset($_SESSION['user_id'])) {
                // Для авторизованных: используем user_id
                $insert_stmt->execute([$poll_id, $user_id, $option_index, '']);
            } else {
                // Для гостей: используем IP, user_id=NULL
                $insert_stmt->execute([$poll_id, null, $option_index, $voted_ip]);
            }
            
            $vote_message = "Ваш голос учтен!";
            // Перезагрузка для отображения результатов
            header("Refresh: 0");
            exit;

        } catch (\PDOException $e) {
            // Если ошибка UNIQUE, значит, уже голосовали
            if ($e->getCode() == 23000) {
                $vote_message = "Вы уже голосовали в этом опросе.";
            } else {
                $vote_message = "Ошибка при голосовании: " . $e->getMessage();
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
    <p style="color: <?= strpos($vote_message, 'успех') !== false || strpos($vote_message, 'учтен') !== false ? 'green' : 'red' ?>;">
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
    $check_stmt = $pdo->prepare("SELECT id FROM votes WHERE poll_id = ? AND voted_ip = ?");
    $check_stmt->execute([$poll_id, $_SERVER['REMOTE_ADDR']]);
    if ($check_stmt->fetch()) $already_voted = true;
}
?>

<?php if (!$already_voted && $user_can_vote): ?>
    <h3>Ваш голос</h3>
    <form method="POST">
        <?php foreach ($options as $index => $option): ?>
            <p>
                <input type="radio" id="opt_<?= $index ?>" name="vote_option" value="<?= $index ?>" required>
                <label for="opt_<?= $index ?>"><?= htmlspecialchars($option) ?></label>
            </p>
        <?php endforeach; ?>
        <button type="submit" name="vote_submit">Проголосовать</button>
    </form>
<?php elseif ($already_voted): ?>
    <p style="color: blue;">Вы уже приняли участие в этом опросе.</p>
<?php elseif (!$user_can_vote): ?>
    <p style="color: red;">Вы заблокированы администратором и не можете голосовать.</p>
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