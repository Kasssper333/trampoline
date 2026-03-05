<?php
 
session_start();
require_once('db.php');

// Проверка авторизации
if (!isset($_SESSION['f']) or !isset($_SESSION['n']) or !isset($_SESSION['p'])) {
    echo "<script>alert('Пожалуйста, сначала авторизуйтесь.'); window.location.href='Авторизация.php';</script>";
    exit;
}
$user_login = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id_user FROM users WHERE login = ?");
$stmt->bind_param("s", $user_login);
$stmt->execute();
$stmt->bind_result($id_user);
$stmt->fetch();
$stmt->close();

$data = date("Y-m-d H:i:s");

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $text = trim($_POST['text']);


     //Удаление заявки
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_app_id'])) {
                $del_id = (int)$_POST['delete_app_id'];
                $stmt = $conn->prepare("DELETE FROM applications WHERE id_application = ? AND id_user = ?");
                $stmt->bind_param("ii", $del_id, $id_user);
                $stmt->execute();
                echo "<script>alert('Заявка удалена!'); window.location.href = '/Личный_кабинет.php';</script>";
                exit;
            }

    if (empty($text)) {
        echo "<script>alert('Введите текст отзыва');window.location.href = '/Личный_кабинет.php';</script>";
        exit;
    } elseif (mb_strlen($text) < 10) {
        echo "<script>alert('Отзыв слишком короткий! Минимум 10 символов.');window.location.href = '/Личный_кабинет.php';</script>";
        exit;
    } elseif (mb_strlen($text) > 1000) {
        echo "<script>alert('Отзыв слишком длинный! Максимум 500 символов.');window.location.href = '/Личный_кабинет.php';</script>";
        exit;
    } else {
        $sql = "INSERT INTO reviews (status,text, id_user, date, reason_del) VALUES ('Новый',?, ?, ?, null)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo "<script>alert('Ошибка базы данных: " . addslashes($conn->error) . "');window.location.href = '/Личный_кабинет.php';</script>";
            exit;
        }
        $stmt->bind_param("sis", $text, $id_user,$data);
        if ($stmt->execute()) {
            echo "<script>alert('Отзыв добавлен!');window.location.href = '/Личный_кабинет.php';</script>";
            exit;
        } else {
            echo "<script>alert('Ошибка при добавлении отзыва!'); window.location.href = '/Личный_кабинет.php';</script>";
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampoline</title>
    <link rel="stylesheet" href="css/Личный_кабинет.css?v=3">
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
                <p class="lk">Личный кабинет</p>
                <div class="blok1">
                    <img class="img" src="img/user2.svg" alt="Пользователь">
                    <p class="fio"><?php echo htmlspecialchars($_SESSION['f'])." ".htmlspecialchars($_SESSION['n'])." ".htmlspecialchars($_SESSION['p']); ?></p>
                </div>

                <div class="blok2">
                    <p class="applications">Мои заявки</p>
                    <?php
             $sql = "SELECT 
                app.id_application AS app_id,
                a.name AS attraction_name,
                a.foto1,
                app.rental_date,
                app.rental_time,
                app.total_price,
                app.status,
                app.reason_del
                FROM applications app
                JOIN attractions a ON app.id_attraction = a.id_attraction
                WHERE app.id_user = ?
                ORDER BY app.id_application DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo '<p style="font-size: 20px; font-family: M-M; color: #4A0A77;">У вас пока нет заявок.</p>';
    } else {
        echo '<div class="my_applications">';
while ($row = $result->fetch_assoc()) {
    // Фото
    $img = '';
    if (!empty($row['foto1'])) {
        $img = '<img class="app-img" src="data:image/jpeg;base64,' . base64_encode($row['foto1']) . '" alt="Фото">';
    }
    // Количество часов
    $hours = round($row['rental_time'] / 60, 2);

    $date = date('d.m.Y', strtotime($row['rental_date']));

    echo '<div class="application-item">';
    echo $img;
    echo '<div class="app-info">';
    echo '<div class="app-title">' . htmlspecialchars($row['attraction_name']) . '</div>';
    echo '<div><span class="app-label">Дата:</span> <span class="app-date">' . htmlspecialchars($date) . '</span></div>';
    echo '<div><span class="app-label">Длительность:</span> <span class="app-hours">' . $hours . ' ч.</span></div>';
    echo '<div><span class="app-label">Стоимость:</span> <span class="app-price">' . htmlspecialchars($row['total_price']) . ' ₽</span></div>';
    echo '<div><span class="app-label">Статус:</span> <span class="app-status">' . htmlspecialchars($row['status']) . '</span></div>';
    if ($row['status'] === 'Отменённая' && !empty($row['reason_del'])) {
        echo '<div><span class="app-label">Причина отмены:</span> <span class="app-reason">' . htmlspecialchars($row['reason_del']) . '</span></div>';
    }
    echo '</div>';
    // Кнопка удаления только для статусов "Новая" или "Отменённая"
    if ($row['status'] === 'Новая' || $row['status'] === 'Отменённая') {
        echo '<form method="post" class="app-delete-form" onsubmit="return confirm(\'Вы уверены, что хотите удалить заявку?\');">
            <input type="hidden" name="delete_app_id" value="' . $row['app_id'] . '">
            <button type="submit" class="app-delete-btn">Удалить</button>
            </form>';
    } else {
        echo '<button class="app-delete-btn app-delete-btn-disabled" onclick="alert(\'Вы не можете удалить подтверждённую заявку!\');return false;">Удалить</button>';
    }
    echo '</div>';
}
    }
    ?>
                </div>

                <div class="blok3">
                    <p class="blok3_text">Весело провели время на наших батутах! Поделитесь своими впечатлениями!</p>
                    <form class="form" action="" method="post">
                        <textarea class="review" name="text" placeholder="Ваш отзыв"></textarea>
                        <button class="btn" type="submit">Оставить отзыв</button>
                    </form>
                </div>

                <form class="form2" action="exit.php" method="post">
                    <a href="exit.php" class="exit" onclick="this.parentNode.submit(); return false;">Выйти</a>
                </form>
            </div>

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
    
</body>
</html>