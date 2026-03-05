<?php
session_start();
require_once('db.php');

$reviews = [];
$sql = "SELECT r.text, r.date, u.name, u.surname 
        FROM reviews r 
        JOIN users u ON r.id_user = u.id_user 
        WHERE r.status = 'Подтверждён'
        ORDER BY RAND() 
        LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampoline</title>
    <link rel="stylesheet" href="css/Главная.css?v=6">
</head>
<body>
    <script>
        //слайдер
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.slider-track');
    const items = document.querySelectorAll('.slider-item');
    const prevBtn = document.querySelector('.slider-btn.prev');
    const nextBtn = document.querySelector('.slider-btn.next');
    const itemsToShow = 3;
    const itemWidth = items[0].offsetWidth + 20; // 20px — ваш отступ между блоками
    let position = itemsToShow; // начинаем с первого "настоящего" слайда

    // Клонируем последние и первые элементы
    for (let i = 0; i < itemsToShow; i++) {
        track.appendChild(items[i].cloneNode(true)); // клон первых в конец
        track.insertBefore(items[items.length - 1 - i].cloneNode(true), track.firstChild); // клон последних в начало
    }

    // Обновляем список после клонирования
    const allItems = track.querySelectorAll('.slider-item');

    // Устанавливаем стартовую позицию
    track.style.transform = `translateX(-${position * itemWidth}px)`;

    prevBtn.addEventListener('click', () => {
        if (position <= 0) {
            position = allItems.length - itemsToShow * 2 - 1;
            track.style.transition = 'none';
            track.style.transform = `translateX(-${position * itemWidth}px)`;
            setTimeout(() => {
                track.style.transition = 'transform 0.3s';
                position--;
                track.style.transform = `translateX(-${position * itemWidth}px)`;
            }, 20);
        } else {
            position--;
            track.style.transform = `translateX(-${position * itemWidth}px)`;
        }
    });

    nextBtn.addEventListener('click', () => {
        if (position >= allItems.length - itemsToShow) {
            position = itemsToShow;
            track.style.transition = 'none';
            track.style.transform = `translateX(-${position * itemWidth}px)`;
            setTimeout(() => {
                track.style.transition = 'transform 0.3s';
                position++;
                track.style.transform = `translateX(-${position * itemWidth}px)`;
            }, 20);
        } else {
            position++;
            track.style.transform = `translateX(-${position * itemWidth}px)`;
        }
    });
});
</script>

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

                <div class="blok1">
                    <div>
                        <p class="blok1_name">Trampoline</p>
                        <p class="blok1_text">Ваши дети будут в восторге! Успевайте забронировать батут</p>
                    </div>
                <img class='blok1_img' src="img/blok1_fon.svg" alt="Батут">
                </div>

                <div class="blok2">
                    <p class="blok2_text1">О нас</p>
                    <p class="blok2_text2">Trampoline - это аренда аттракционов для корпоративных, уличных и семейных праздников. Аренда аттракционов - самый распространенный способ привнести в Ваш праздник оригинальность, интерактивность и веселье. Арендованные у нас аттракционы могут выступать в качестве декораций, оформления места праздника, не говоря уже об их непосредственном использовании. Аренда надувных батутов, горок, каруселей делает услуги аренды и проката аттракционов применимой для любого праздника!</p>
                </div>

                <div class="blok3">
                    <button class="slider-btn prev"> < </button>
                    <div class="slider-window">
                        <div class="slider-track">
                            <?php foreach ($reviews as $review): ?>
                                <div class="slider-item">
                                    <p><b><?= htmlspecialchars($review['name'] . ' ' . $review['surname']) ?></b></p>
                                    <p><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                                    <p>
                                <?php
                                // Преобразуем дату
                                $date = date('d.m.Y', strtotime($review['date']));
                                 echo htmlspecialchars($date);
                                ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="slider-btn next"> > </button>
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