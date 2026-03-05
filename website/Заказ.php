<?php
session_start();
require_once('db.php');

// Проверка авторизации
if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Сначала нужно авторизироваться!'); window.location.href='Авторизация.php';</script>";
    exit;
}

// Если форма отправлена (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['id_user'];
    $id_attraction = (int)$_POST['id_attraction'];
    $date = $_POST['rental_date'];
    $time = (int)$_POST['rental_time'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Получаем данные о батуте
    $res = $conn->query("SELECT a.name, a.id_category FROM attractions a WHERE a.id_attraction = $id_attraction");
    if (!$res || $res->num_rows === 0) {
        echo "<script>alert('Батут не найден'); history.back();</script>";
        exit;
    }
    $batut = $res->fetch_assoc();
    $name = $batut['name'];
    $id_category = $batut['id_category'];

   // Проверяем, занят ли батут в выбранный интервал времени
$stmt = $conn->prepare("SELECT * FROM applications WHERE rental_date = ? AND id_attraction = ?");
$stmt->bind_param("si", $date, $id_attraction);
$stmt->execute();
$result = $stmt->get_result();
$is_busy = false;

$start_minutes = (int)explode(':', $start_time)[0] * 60 + (int)explode(':', $start_time)[1];
$end_minutes = (int)explode(':', $end_time)[0] * 60 + (int)explode(':', $end_time)[1];

while ($row = $result->fetch_assoc()) {
    $exist_start = isset($row['start_time']) ? $row['start_time'] : null;
    $exist_end = isset($row['end_time']) ? $row['end_time'] : null;
    if ($exist_start && $exist_end) {
        $exist_start_minutes = (int)explode(':', $exist_start)[0] * 60 + (int)explode(':', $exist_start)[1];
        $exist_end_minutes = (int)explode(':', $exist_end)[0] * 60 + (int)explode(':', $exist_end)[1];
        // Проверка на пересечение интервалов
        if ($start_minutes < $exist_end_minutes && $end_minutes > $exist_start_minutes) {
            $is_busy = true;
            break;
        }
    }
}

if ($is_busy) {
    echo "<script>alert('Этот батут уже занят в выбранный промежуток времени! Пожалуйста, выберите другое время или батут.'); history.back();</script>";
    exit;
}

    // Запись в БД
    $stmt = $conn->prepare("INSERT INTO applications 
    (status, reason_del, id_category, id_attraction, name, rental_date, rental_time, start_time, end_time, total_price, id_user, address, date_of_birth, passport_series, passport_number, date_of_issue, issued_by_whom) 
    VALUES ('Новая',null, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iississiissssss",
    $id_category,
    $id_attraction,
    $name,
    $_POST['rental_date'],
    $_POST['rental_time'],
    $start_time,
    $end_time,
    $_POST['total_price'],
    $id_user,
    $_POST['address'],
    $_POST['birth_date'],
    $_POST['passport_series'],
    $_POST['passport_number'],
    $_POST['passport_issue_date'],
    $_POST['passport_issued_by']
);
    if ($stmt->execute()) {
        echo "<script>alert('Заявка успешно отправлена!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Ошибка при отправке заявки: " . addslashes($stmt->error) . "'); history.back();</script>";
    }
    exit;
}

// Если просто открыли страницу (GET)
$batut = null;
if (!isset($_GET['id_attraction']) || !is_numeric($_GET['id_attraction'])) {
    echo "<script>alert('Некорректный доступ: отсутствует ID батута'); window.location.href = 'Каталог.php';</script>";
    exit;
}
$id = (int)$_GET['id_attraction'];
$res = $conn->query("SELECT a.name, a.price, c.name as category FROM attractions a JOIN categories c ON a.id_category = c.id_category WHERE a.id_attraction = $id");
if ($res && $row = $res->fetch_assoc()) {
    $batut = $row;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampоline</title>
    <link rel="stylesheet" href="css/Заказ.css?v=3">
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
    <div class="blok">
        <p class="authorization">Оформление заявки</p>
        <form action="" method="post">
            <label class="form-label">Дата рождения:</label>
            <input class="input" type="date" name="birth_date" required>

            <label class="form-label">Адрес доставки:</label>
            <input class="input" type="text" name="address" required>

            <label class="form-label">Серия паспорта:</label>
            <input class="input" type="text" name="passport_series" maxlength="10" required>

            <label class="form-label">Номер паспорта:</label>
            <input class="input" type="text" name="passport_number" maxlength="10" required>

            <label class="form-label">Дата выдачи паспорта:</label>
            <input class="input" type="date" name="passport_issue_date" required>

            <label class="form-label">Кем выдан паспорт:</label>
            <input class="input" type="text" name="passport_issued_by" required>

            <label class="form-label">Категория батута:</label>
            <input class="input" type="text" name="batut_category" value="<?= htmlspecialchars($batut['category'] ?? '') ?>" readonly>

            <label class="form-label">Название батута:</label>
            <input class="input" type="text" name="batut_name" value="<?= htmlspecialchars($batut['name'] ?? '') ?>" readonly>

            <label class="form-label">Дата проката:</label>
            <input class="input" type="date" name="rental_date" value="<?= htmlspecialchars($_GET['rental_date'] ?? '') ?>" required>

            <label class="form-label">Время начала аренды:</label>
            <input class="input" type="time" name="start_time" id="start_time" required onchange="calculateRentalTime()">

            <label class="form-label">Время окончания аренды:</label>
            <input class="input" type="time" name="end_time" id="end_time" required onchange="calculateRentalTime()">

            <label class="form-label">Время проката (в минутах):</label>
            <input class="input" type="number" name="rental_time" id="rental_time" min="1" value="<?= htmlspecialchars($_GET['rental_time'] ?? '') ?>" required readonly>

            <label class="form-label">Итоговая цена со скидкой:</label>
            <input class="input" type="text" id="total_price" name="total_price" value="<?= htmlspecialchars($_GET['total_cost'] ?? '') ?>" readonly>
            <input type="hidden" id="price_per_hour" value="<?= (int)($batut['price'] ?? 0) ?>">

            <input type="hidden" name="id_attraction" value="<?= $id ?>">

            <button class="btn" type="submit">Отправить заявку</button>
        </form>

        <script>
function updateTotalPrice() {
    const pricePerHour = parseFloat(document.getElementById('price_per_hour').value) || 0;
    const rentalTimeInput = document.getElementById('rental_time');
    const totalPriceInput = document.getElementById('total_price');
    const minutes = parseInt(rentalTimeInput.value) || 0;
    const rate = pricePerHour;

    if (isNaN(minutes) || minutes < 60 || isNaN(rate) || rate <= 0) {
        totalPriceInput.value = '';
        return;
    }

    const hours = minutes / 60;
    let discountPercent = 0;

    // Расчет скидки 
    if (hours >= 3 && hours <= 4.9) {
        discountPercent = 5;
    } else if (hours >= 5 && hours <= 7.9) {
        discountPercent = 10;
    } else if (hours >= 8 && hours <= 11.9) {
        discountPercent = 15;
    } else if (hours >= 12) {
        discountPercent = 20;
    }

    const costBeforeDiscount = hours * rate;
    const finalCost = costBeforeDiscount * (1 - discountPercent / 100);

    totalPriceInput.value = Math.round(finalCost) + ' руб.';
}

function calculateRentalTime() {
    const start = document.getElementById('start_time').value;
    const end = document.getElementById('end_time').value;
    if (!start || !end) {
        document.getElementById('rental_time').value = '';
        updateTotalPrice();
        return;
    }
    const [startH, startM] = start.split(':').map(Number);
    const [endH, endM] = end.split(':').map(Number);
    let minutes = (endH * 60 + endM) - (startH * 60 + startM);
    if (minutes < 1) {
        document.getElementById('rental_time').value = '';
        updateTotalPrice();
        return;
    }
    document.getElementById('rental_time').value = minutes;
    updateTotalPrice();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('start_time').addEventListener('change', calculateRentalTime);
    document.getElementById('end_time').addEventListener('change', calculateRentalTime);
    document.getElementById('rental_time').addEventListener('input', updateTotalPrice);
    updateTotalPrice();
});
</script>
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
    </div>
</body>
</html>