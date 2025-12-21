<?php
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['can_create_poll'] == FALSE) {
    header("Location: profile.php"); // Заблокирован админом
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $options_array = $_POST['options'] ?? []; // Теперь это будет массив
    $user_id = $_SESSION['user_id'];
    var_dump("Пытаюсь вставить опрос для User ID:", $user_id); // Добавить эту строку

    // Фильтруем и проверяем, что есть минимум 2 варианта
    $valid_options = array_filter(array_map('trim', $options_array));
    
    if (empty($title) || count($valid_options) < 1) {
        $message = "Заголовок обязателен, и должен быть указан хотя бы один вариант ответа.";
    } else {
        // Преобразуем массив опций в JSON для хранения в БД
        $options_json = json_encode($valid_options);

        try {
            $stmt = $pdo->prepare("INSERT INTO polls (user_id, title, description, options) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $description, $options_json]);
            $message = "Опрос успешно создан!";
            // Очистка после успеха
            $_POST = array();
        } catch (Exception $e) {
            $message = "Ошибка при создании опроса: " . $e->getMessage();
        }
    }
}

require 'includes/header.php';
?>
<h2>Создать новый опрос</h2>
<?php if ($message): ?><p style="color: blue;"><?= $message ?></p><?php endif; ?>

<form method="POST" id="pollForm">
    <p><label for="title">Заголовок опроса:</label><br>
    <input type="text" name="title" required style="width: 100%;" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"></p>
    
    <p><label for="description">Описание (необязательно):</label><br>
    <textarea name="description" rows="3" style="width: 100%;"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea></p>
    
    <p><label>Варианты ответа:</label></p>
    
    <!-- Контейнер для динамических полей -->
    <div id="options-container">
        <!-- Начальные поля (Добавляются JS ниже или тут) -->
    </div>
    
    <button type="button" id="add-option-btn">+ Добавить вариант</button>
    
    <p style="margin-top: 20px;"><button type="submit" name="create">Опубликовать опрос</button></p>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('options-container');
    const addButton = document.getElementById('add-option-btn');
    let optionCount = 0;

    // Функция для добавления нового поля
    function addOptionField(value = '') {
        const groupDiv = document.createElement('div');
        groupDiv.className = 'option-group';
        groupDiv.dataset.index = optionCount++;

        // 1. Поле ввода
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'options[]'; 
        input.placeholder = 'Вариант ответа';
        input.value = value;
        input.required = true;

        groupDiv.appendChild(input);

        // 2. Кнопка удаления (скрыта, пока не будет 3 поля)
        if (container.children.length >= 2) {
             const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'remove-option-btn';
            removeButton.textContent = 'X';
            removeButton.onclick = function() {
                container.removeChild(groupDiv);
                updateRemoveButtons();
            };
            groupDiv.appendChild(removeButton);
        }

        container.appendChild(groupDiv);
        updateRemoveButtons();
    }
    
    // Функция, которая показывает/скрывает кнопки удаления
    function updateRemoveButtons() {
        const groups = container.querySelectorAll('.option-group');
        
        groups.forEach((group, index) => {
            const removeBtn = group.querySelector('.remove-option-btn');
            // Показываем кнопку "X", если вариантов 2 или больше
            if (groups.length > 1) {
                 if (!removeBtn) {
                    // Если кнопки нет (было только 2 поля), нужно ее добавить
                    const addButtonBack = document.createElement('button');
                    addButtonBack.type = 'button';
                    addButtonBack.className = 'remove-option-btn';
                    addButtonBack.textContent = 'X';
                    addButtonBack.onclick = function() {
                        container.removeChild(group);
                        updateRemoveButtons();
                    };
                    group.appendChild(addButtonBack);
                }
            } else if (removeBtn) {
                // Если вариантов всего 2, удаляем кнопку "X"
                group.removeChild(removeBtn);
            }
        });
    }

    // Инициализация: добавляем 2 стартовых поля
    addOptionField();
    
    // Обработчик кнопки добавления
    addButton.addEventListener('click', function() {
        addOptionField();
    });
});
</script>

<?php require 'includes/footer.php'; ?>