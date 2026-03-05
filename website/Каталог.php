<?php

require_once('db.php');


// Получаем категории для фильтра
$categories = [];
$res = $conn->query("SELECT id_category, name FROM categories ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Формируем условия фильтрации
$where = [];
$params = [];
$types = "";

// Цена
if (!empty($_GET['price_min'])) {
    $where[] = "price >= ?";
    $params[] = $_GET['price_min'];
    $types .= "i";
}
if (!empty($_GET['price_max'])) {
    $where[] = "price <= ?";
    $params[] = $_GET['price_max'];
    $types .= "i";
}
// Длина
if (!empty($_GET['length_min'])) {
    $where[] = "length >= ?";
    $params[] = $_GET['length_min'];
    $types .= "i";
}
if (!empty($_GET['length_max'])) {
    $where[] = "length <= ?";
    $params[] = $_GET['length_max'];
    $types .= "i";
}
// Ширина
if (!empty($_GET['width_min'])) {
    $where[] = "width >= ?";
    $params[] = $_GET['width_min'];
    $types .= "i";
}
if (!empty($_GET['width_max'])) {
    $where[] = "width <= ?";
    $params[] = $_GET['width_max'];
    $types .= "i";
}
// Высота
if (!empty($_GET['height_min'])) {
    $where[] = "height >= ?";
    $params[] = $_GET['height_min'];
    $types .= "i";
}
if (!empty($_GET['height_max'])) {
    $where[] = "height <= ?";
    $params[] = $_GET['height_max'];
    $types .= "i";
}
// Мощность
if (!empty($_GET['power_min'])) {
    $where[] = "power >= ?";
    $params[] = $_GET['power_min'];
    $types .= "i";
}
if (!empty($_GET['power_max'])) {
    $where[] = "power <= ?";
    $params[] = $_GET['power_max'];
    $types .= "i";
}
// Категория
if (!empty($_GET['category'])) {
    $where[] = "id_category = ?";
    $params[] = $_GET['category'];
    $types .= "i";
}

// Собираем SQL-запрос
$sql = "SELECT * FROM attractions";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id_attraction DESC";

// Готовим и выполняем запрос
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampoline</title>
    <link rel="stylesheet" href="css/Каталог.css?v=8">
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
                    <?php while ($row = $result->fetch_assoc()){ 

                        //Загрузка фото чтобы можно было загружать фотки разных типов
                        $image_data = $row['foto1'];
                        $image_type = exif_imagetype('data://image/jpeg;base64,' . base64_encode($image_data));
                        $image_mime_type = image_type_to_mime_type($image_type);
                        $show_img = base64_encode($image_data);
                        //
                        $id_attraction = (int)$row['id_attraction'];
                        $name_attraction = htmlspecialchars($row["name"]);
                        $discr = htmlspecialchars($row["descr"]);
                        $price = htmlspecialchars($row["price"]);
                        echo '<div class="box">
                                <a href="Аттракцион.php?id_attraction=' . trim($id_attraction) . '"><img class="img" src="data:' . $image_mime_type . ';base64,' . $show_img . '" alt="Фото товара"></a>
                                <a href="Аттракцион.php?id_attraction=' . trim($id_attraction) . '" class="title">' . $name_attraction . '</a>
                                <a href="Аттракцион.php?id_attraction=' . trim($id_attraction) . '" class="discr">' . $discr . '</a>
                                <div class="box2">
                                    <p class="price">' . $price . ' руб/ч.</p>
                                   
                                    <a style="text-decoration: none; color:#ffffff;" href="Заказ.php?id_attraction=' . $row['id_attraction'] . '"><button class="btn">Заказать</button></a>
                                </div>
                            </div>';
                    }
                    ?>
                </div>



                <div class="filter">
                        <form method="get" action="">
                            <h3 class="title_f">Фильтр</h3>
                            <div>
                                <label class="text">Цена:</label><br>
                                <input class='input' type="number" name="price_min" placeholder="от" value="<?= isset($_GET['price_min']) ? htmlspecialchars($_GET['price_min']) : '' ?>"> 
                                <input class='input' type="number" name="price_max" placeholder="до" value="<?= isset($_GET['price_max']) ? htmlspecialchars($_GET['price_max']) : '' ?>">
                            </div>

                            <div>
                                <label class="text">Длина (м):</label><br>
                                <input class='input' type="number" name="length_min" placeholder="от" value="<?= isset($_GET['length_min']) ? htmlspecialchars($_GET['length_min']) : '' ?>"> 
                                <input class='input' type="number" name="length_max" placeholder="до" value="<?= isset($_GET['length_max']) ? htmlspecialchars($_GET['length_max']) : '' ?>">
                            </div>

                            <div>
                                <label class="text">Ширина (м):</label><br>
                                <input class='input' type="number" name="width_min" placeholder="от" value="<?= isset($_GET['width_min']) ? htmlspecialchars($_GET['width_min']) : '' ?>"> 
                                <input class='input' type="number" name="width_max" placeholder="до" value="<?= isset($_GET['width_max']) ? htmlspecialchars($_GET['width_max']) : '' ?>">
                            </div>

                            <div>
                                <label class="text">Высота (м):</label><br>
                                <input class='input' type="number" name="height_min" placeholder="от" value="<?= isset($_GET['height_min']) ? htmlspecialchars($_GET['height_min']) : '' ?>"> 
                                <input class='input' type="number" name="height_max" placeholder="до" value="<?= isset($_GET['height_max']) ? htmlspecialchars($_GET['height_max']) : '' ?>">
                            </div>

                            <div>
                                <label class="text">Пот. мощность (Вт):</label><br>
                                <input class='input' type="number" name="power_min" placeholder="от" value="<?= isset($_GET['power_min']) ? htmlspecialchars($_GET['power_min']) : '' ?>"> 
                                <input class='input' type="number" name="power_max" placeholder="до" value="<?= isset($_GET['power_max']) ? htmlspecialchars($_GET['power_max']) : '' ?>">
                            </div>

                            <div >
                                <label class="text">Категория:</label><br>
                                <select class='input' name="category" >
                                    <option value="">Все</option>
                                        <?php foreach ($categories as $cat): ?>
                                    <option  value="<?= $cat['id_category'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $cat['id_category']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                        <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <button type="submit" class="btn_f" >Показать</button><br>
                                    <button type="button" class="btn_f" onclick="window.location.href='Каталог.php'" >Сбросить фильтр</button>
                            </div>
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
    </div>
</body>
</html>