<?php
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';

// --- Действия Администратора ---

// 1. Удаление опроса
if (isset($_GET['action']) && $_GET['action'] == 'delete_poll' && isset($_GET['poll_id'])) {
    $poll_id_to_delete = (int)$_GET['poll_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM polls WHERE id = ?");
        $stmt->execute([$poll_id_to_delete]);
        $message = "Опрос ID {$poll_id_to_delete} был удален.";
    } catch (Exception $e) {
        $message = "Ошибка удаления: " . $e->getMessage();
    }
}

// 2. Блокировка/Разблокировка создания опросов
if (isset($_GET['action']) && ($_GET['action'] == 'block' || $_GET['action'] == 'unblock') && isset($_GET['user_id'])) {
    $user_id_to_toggle = (int)$_GET['user_id'];
    $new_state = ($_GET['action'] == 'block') ? 0 : 1;

    try {
        $stmt = $pdo->prepare("UPDATE users SET can_create_poll = ? WHERE id = ? AND role != 'admin'");
        $stmt->execute([$new_state, $user_id_to_toggle]);
        
        if ($stmt->rowCount()) {
            $message = "Пользователь ID {$user_id_to_toggle} теперь " . ($new_state ? "МОЖЕТ" : "НЕ МОЖЕТ") . " создавать опросы.";
        } else {
            $message = "Не удалось изменить статус (возможно, это другой администратор).";
        }
    } catch (Exception $e) {
        $message = "Ошибка изменения статуса: " . $e->getMessage();
    }
}


// Получение всех пользователей и опросов
$user_stmt = $pdo->query("SELECT id, username, role, can_create_poll FROM users WHERE role != 'admin' ORDER BY id");
$users = $user_stmt->fetchAll();

$poll_stmt = $pdo->query("SELECT p.*, u.username FROM polls p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$all_polls = $poll_stmt->fetchAll();

require 'includes/header.php';
?>
<h2>Панель Администратора</h2>

<?php if ($message): ?><p style="border: 1px solid #007bff; padding: 10px; background-color: #e7f3ff;"><?= $message ?></p><?php endif; ?>

<!-- Список пользователей и управление блокировкой -->
<h3>Управление пользователями</h3>
<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #eee;">
            <th style="border: 1px solid #ccc; padding: 8px;">ID</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Имя</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Статус</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Действие</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td style="border: 1px solid #ccc; padding: 8px;"><?= $user['id'] ?></td>
                <td style="border: 1px solid #ccc; padding: 8px;"><?= htmlspecialchars($user['username']) ?></td>
                <td style="border: 1px solid #ccc; padding: 8px; color: <?= $user['can_create_poll'] ? 'green' : 'red' ?>;">
                    <?= $user['can_create_poll'] ? 'Может создавать' : 'Заблокирован' ?>
                </td>
                <td style="border: 1px solid #ccc; padding: 8px;">
                    <?php if ($user['can_create_poll']): ?>
                        <a href="?action=block&user_id=<?= $user['id'] ?>">Блокировать</a>
                    <?php else: ?>
                        <a href="?action=unblock&user_id=<?= $user['id'] ?>">Разблокировать</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<hr>

<!-- Список опросов и удаление -->
<h3>Удаление опросов</h3>
<?php if (empty($all_polls)): ?>
    <p>Опросов пока нет.</p>
<?php else: ?>
    <?php foreach ($all_polls as $poll): ?>
        <div class="poll-card">
            <h4><?= htmlspecialchars($poll['title']) ?> (ID: <?= $poll['id'] ?>)</h4>
            <p>Автор: <?= htmlspecialchars($poll['username']) ?></p>
            <div class="admin-action">
                <a href="?action=delete_poll&poll_id=<?= $poll['id'] ?>" 
                   onclick="return confirm('Вы уверены, что хотите удалить этот опрос?')">
                    <button>Удалить опрос</button>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>