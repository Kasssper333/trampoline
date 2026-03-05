<?php
session_start();
require_once('db.php');

// --- Обработка изменения статуса ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['new_status'])) {
    $review_id = (int)$_POST['review_id'];
    $new_status = $_POST['new_status'];
    $reason_del = isset($_POST['reason_del']) ? trim($_POST['reason_del']) : null;

    $stmt = $conn->prepare("UPDATE reviews SET status = ?, reason_del = ? WHERE id_review = ?");
    $stmt->bind_param("ssi", $new_status, $reason_del, $review_id);
    $stmt->execute();
    echo "<script>window.location.href='Админ_отзывы.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampoline</title>
    <link rel="stylesheet" href="css/Админ_отзывы.css?v=7">
</head>
<body>

    <div class="wrapper">
        
            <header>
                <div style="display: flex; align-items: center;">
                    <img class="header_logo" src="img/logo.svg" alt="Логотип">
                    <a class="header_name" href="index.php">Trampoline</a>
                </div>
                <nav>
                    <a class="nav1" href="index.php">О нас</a>
                    <a class="nav2" href="Каталог.php">Каталог</a>
                    <a class="nav2" href="Условия_аренды.php">Условия аренды</a>
                    <a class="nav2" href="Где_нас_найти.php">Где нас найти?</a>
                    <?php
            require_once('db.php');

            // Проверка, авторизован ли пользователь и есть ли информация о пользователе в сессии
            if (isset($_SESSION['user'])) {
                $user = $_SESSION['user'];

                // Запрос к базе данных, чтобы получить роль пользователя
                // Добавлены кавычки для защиты от SQL-инъекций (хотя лучше использовать prepared statements)
                $sql = "SELECT admin FROM users WHERE login = '" . mysqli_real_escape_string($conn, $user) . "'";

                $result = $conn->query($sql);

                if ($result) { // Проверяем, что запрос выполнился успешно
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $is_admin = (bool)$row['admin']; // Преобразуем bit(1) в boolean

                        // Отображение ссылки администратора (если пользователь - администратор)
                        if ($is_admin) {
                            echo '<a class="nav2" href="Админ.php" class="btn2">Админ-панель</a>';
                        }
                    } else {
                        echo "Ошибка: Не удалось получить информацию о пользователе. Пользователь не найден.";
                    }
                } else {
                    echo "Ошибка: Ошибка при выполнении запроса: " . $conn->error; // Выводим сообщение об ошибке
                }
            } else {
                echo "";
            }
            ?>
                </nav>

                <div style="display: flex;align-items: center;">
                    <div style="display: flex; flex-direction: column;">
                        <a class="auth" href="Авторизация.php">Авторизация</a>
                        <a class="reg" href="Регистрация.php">Регистрация</a>
                    </div>
                    <a href="Личный_кабинет.php"><img class="header_lk" src="img/user.svg" alt="Личный кабинет"></a>
                </div>
            </header>



            <div class="container">

            <div class="filter" style="margin-bottom:20px;">
                <form method="get" style="display:inline;">
                    <label for="status_filter">Сортировать по статусу:</label>
                    <select name="status_filter" id="status_filter" onchange="this.form.submit()">
                        <option value="">Все</option>
                        <option value="Новый" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Новый') ? 'selected' : '' ?>>Новый</option>
                        <option value="Подтверждён" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Подтверждён') ? 'selected' : '' ?>>Подтверждён</option>
                        <option value="Отменён" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Отменён') ? 'selected' : '' ?>>Отменён</option>
                    </select>
                </form>
            </div>


            <div class="blok">
<?php
// Получаем отзывы с именем пользователя
$status_filter = isset($_GET['status_filter']) && $_GET['status_filter'] !== '' ? $_GET['status_filter'] : null;

$sql = "SELECT r.id_review, r.status, r.text, r.date, r.reason_del, 
               CONCAT(u.surname, ' ', u.name, ' ', u.patronymic) AS fio
        FROM reviews r 
        JOIN users u ON r.id_user = u.id_user";
if ($status_filter) {
    $sql .= " WHERE r.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= " ORDER BY r.date DESC";
$res = $conn->query($sql);

if ($res->num_rows === 0) {
    echo '<p>Нет отзывов.</p>';
} else {
    while ($row = $res->fetch_assoc()) {
        echo '<div class="review-item">';
        echo '<b>Пользователь:</b> <span class="review-fio">' . htmlspecialchars($row['fio']) . '</span><br>';
        $date = date('d.m.Y', strtotime($row['date']));
        echo '<b>Дата:</b> <span class="review-date">' . htmlspecialchars($date) . '</span><br>';
        echo '<b>Текст:</b> <span class="review-text">' . nl2br(htmlspecialchars($row['text'])) . '</span><br>';
        echo '<b>Статус:</b> ';
        echo '<select name="new_status" class="status-select" data-review-id="' . $row['id_review'] . '" onchange="changeStatus(this)">';
        $statuses = ['Новый', 'Подтверждён', 'Отменён'];
        foreach ($statuses as $status) {
            $selected = ($row['status'] === $status) ? 'selected' : '';
            echo "<option value=\"$status\" $selected>$status</option>";
        }
        echo '</select>';
        // Поле для причины отмены
        $showReason = ($row['status'] === 'Отменён') ? '' : 'style="display:none"';
        echo '<input type="text" name="reason_del" class="reason-del" id="reason_del_' . $row['id_review'] . '" placeholder="Причина отмены" value="' . htmlspecialchars($row['reason_del']) . '" ' . $showReason . ' onblur="changeStatus(this, true)" data-review-id="' . $row['id_review'] . '">';
        echo '</div>';
    }
    }
?>
</div>
<script>
function toggleReason(select, id) {
    var reasonInput = document.getElementById('reason_del_' + id);
    if (select.value === 'Отменён') {
        reasonInput.style.display = '';
    } else {
        reasonInput.style.display = 'none';
        reasonInput.value = '';
    }
}

function changeStatus(selectOrInput, isReasonInput = false) {
    let reviewId = selectOrInput.getAttribute('data-review-id');
    let select, reasonInput;

    if (isReasonInput) {
        reasonInput = selectOrInput;
        select = document.querySelector('select[data-review-id="' + reviewId + '"]');
    } else {
        select = selectOrInput;
        reasonInput = document.getElementById('reason_del_' + reviewId);
    }

    let newStatus = select.value;
    if (newStatus === 'Отменён') {
        reasonInput.style.display = '';
    } else {
        reasonInput.style.display = 'none';
        reasonInput.value = '';
    }

    // Отправляем AJAX-запрос
    fetch(window.location.pathname, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'review_id=' + encodeURIComponent(reviewId) +
              '&new_status=' + encodeURIComponent(newStatus) +
              '&reason_del=' + encodeURIComponent(reasonInput.value)
    })
    .then(response => {
        if (!response.ok) throw new Error('Ошибка сети');
        // Можно добавить уведомление об успехе
    })
    .catch(error => {
        alert('Ошибка при сохранении статуса: ' + error.message);
    });
}
</script>

           


             </div>



            <footer>
                <div class="footer_blok1">
                    <div style="display: flex; flex-direction: column;">
                        <p class="footer_name">Trampoline</p>
                        <p class="footer_text">Ваши дети будут в восторге! успевайте забронировать батут</p>
                    </div>
                    <div>
                        <p class="footer_text">адрес: Россия, Москва, Автозаводская 5 телефон: +7-918-845-45-45 email:  info@tramp.ru</p>
                    </div>
                </div>
                <div class="footer_blok2">
                    <p class="footer_blok2_text">©2025 Все права защищены</p>
                </div>
            </footer>
    </div>
</body>
</html>