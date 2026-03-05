<?php
require_once('db.php');
session_start();

// --- Обработка смены статуса заявки (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_app_status'])) {
    $id_application = (int)$_POST['id_application'];
    $new_status = $_POST['new_status'];
    $reason_del = isset($_POST['reason_del']) ? trim($_POST['reason_del']) : null;

    $stmt = $conn->prepare("UPDATE applications SET status = ?, reason_del = ? WHERE id_application = ?");
    $stmt->bind_param("ssi", $new_status, $reason_del, $id_application);
    $stmt->execute();
    exit;
}

// --- Фильтр по статусу ---
$status_filter = isset($_GET['status_filter']) && $_GET['status_filter'] !== '' ? $_GET['status_filter'] : null;

// --- Получение заявок ---
$sql = "SELECT app.id_application, app.status, app.name, app.rental_date, app.reason_del, 
               CONCAT(u.surname, ' ', u.name, ' ', u.patronymic) AS fio
        FROM applications app
        JOIN users u ON app.id_user = u.id_user";
if ($status_filter) {
    $sql .= " WHERE app.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= " ORDER BY app.rental_date DESC";
$res = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampoline</title>
    <link rel="stylesheet" href="css/Админ_заявки.css?v=4">
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
 <!-- Фильтр по статусу -->
    <div class="filter" style="margin-bottom:20px;">
        <form method="get" style="display:inline;">
            <label for="status_filter">Сортировать по статусу:</label>
            <select name="status_filter" id="status_filter" onchange="this.form.submit()">
                <option value="">Все</option>
                <option value="Новая" <?= ($status_filter == 'Новая') ? 'selected' : '' ?>>Новая</option>
                <option value="Подтверждённая" <?= ($status_filter == 'Подтверждённая') ? 'selected' : '' ?>>Подтверждён</option>
                <option value="Отменённая" <?= ($status_filter == 'Отменённая') ? 'selected' : '' ?>>Отменён</option>
            </select>
        </form>
    </div>


<div class="blok">
    <h2 style="color: #8b2fc9; font-family: M-SB; margin-bottom: 30px;">Заявки</h2>

    <?php
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            echo '<div class="review-item">';
            echo '<b>ФИО:</b> <span class="review-fio">' . htmlspecialchars($row['fio']) . '</span><br>';
            echo '<b>Название батута:</b> <span class="review-text">' . htmlspecialchars($row['name']) . '</span><br>';
            echo '<b>Дата проката:</b> <span class="review-date">' . htmlspecialchars($row['rental_date']) . '</span><br>';
            echo '<b>Статус:</b> ';
            echo '<select class="status-select" name="new_status" data-app-id="' . $row['id_application'] . '" onchange="changeAppStatus(this)">';
            $statuses = ['Новая', 'Подтверждённая', 'Отменённая'];
            foreach ($statuses as $status) {
                $selected = ($row['status'] === $status) ? 'selected' : '';
                echo "<option value=\"$status\" $selected>$status</option>";
            }
            echo '</select>';
            // Поле для причины отмены
            $showReason = ($row['status'] === 'Отменённая') ? '' : 'style="display:none"';
            echo '<input type="text" name="reason_del" class="reason-del" id="reason_del_' . $row['id_application'] . '" placeholder="Причина отмены" value="' . htmlspecialchars($row['reason_del']) . '" ' . $showReason . ' onblur="changeAppStatus(this, true)" data-app-id="' . $row['id_application'] . '">';
            echo '</div>';
        }
    } else {
        echo '<p>Заявок нет.</p>';
    }
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // При загрузке страницы обработаем все селекты
    document.querySelectorAll('.status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            changeAppStatus(this);
            toggleReasonInput(this);
        });
        // И сразу выставим правильную видимость
        toggleReasonInput(select);
    });
});

function toggleReasonInput(select) {
    let appId = select.getAttribute('data-app-id');
    let reasonInput = document.getElementById('reason_del_' + appId);
    if (select.value === 'Отменённая') {
        reasonInput.style.display = '';
    } else {
        reasonInput.style.display = 'none';
        reasonInput.value = '';
    }
}


function changeAppStatus(selectOrInput, isReasonInput = false) {
    let appId = selectOrInput.getAttribute('data-app-id');
    let select, reasonInput;

    if (isReasonInput) {
        reasonInput = selectOrInput;
        select = document.querySelector('select[data-app-id="' + appId + '"]');
    } else {
        select = selectOrInput;
        reasonInput = document.getElementById('reason_del_' + appId);
    }

    let newStatus = select.value;
    if (newStatus === 'Отменённая') {
        reasonInput.style.display = '';
    } else {
        reasonInput.style.display = 'none';
        reasonInput.value = '';
    }

    // Отправляем AJAX-запрос
    fetch(window.location.pathname, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'change_app_status=1' +
              '&id_application=' + encodeURIComponent(appId) +
              '&new_status=' + encodeURIComponent(newStatus) +
              '&reason_del=' + encodeURIComponent(reasonInput.value)
    })
    .then(response => {
        if (!response.ok) throw new Error('Ошибка сети');
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