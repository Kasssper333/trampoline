<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampoline</title>
    <link rel="stylesheet" href="css/Условия_аренды.css?v=3">
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
                   <img class="img" src="img/icon1.svg" alt="Иконка">
                    <p class="text1">Условия доставки</p>
                    <p class="text2">Мы осуществляем доставку нашего оборудования по Москве и области по следующим условиям: доставка по Москве в пределах МКАД 2000 руб., далее +50 руб. за каждый километр. (Оплачивается доставка только в одну сторону) Доставка в другие районы оговаривается отдельно.</p>
                </div>

                <div class="blok">
                   <img class="img" src="img/icon2.svg" alt="Иконка">
                    <p class="text1">Условия оплаты</p>
                    <p class="text2">Наличными или безналичным переводом на расчетный счет компании. При наличном способе оплаты осуществляется минимальная предоплата до приезда на монтаж (предоплата равна стоимости доставки), далее после доставки и монтажа батута Вы оплачиваете оставшуюся сумму. Предоплату можно перевести на карту Сбербанка, ВТБ, Альфа или привезти к нам в офис. При безналичной форме оплаты 100% предоплата и надбавка в размере 7% к стоимости аренды.</p>
                </div>

                <div class="blok">
                   <img class="img" src="img/icon3.svg" alt="Иконка">
                    <p class="text1">Состояние батутов</p>
                    <p class="text2">Мы тщательно следим за сохранностью нашего оборудования. Все батуты в идеальном состоянии, т.е. прошли мало циклов эксплуатации. А большинство и вовсе только с завода - мы постоянно следим за обновлениями и стараемся радовать заказчиков новинками.</p>
                </div>

                <div class="blok">
                   <img class="img" src="img/icon4.svg" alt="Иконка">
                    <p class="text1">Дезинфекция батутов и оборудования</p>
                    <p class="text2">Перед выездом на площадку наше оборудование проходит антивирусную обработку, специальными средствами, убивающими все микробы и вирусы. Такую же услугу мы можем провести и перед эксплуатацией аттракционов на площадке.</p>
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