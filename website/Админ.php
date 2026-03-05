<?php
session_start();
require_once('db.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Обработка добавления категории ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["category_name"]) && !empty($_POST["category_name"])) {
    $category_name = trim($_POST["category_name"]);
    // Экранируем строку
    $category_name = mysqli_real_escape_string($conn, $category_name);


    // Добавляем категорию
    $sql = "INSERT INTO categories (name) VALUES ('$category_name')";
    if ($conn->query($sql)) {
        echo "<script>alert('Категория добавлена!');window.location.href = '/Админ.php';</script>";
        exit;
    } else {
        echo "<script>alert('Ошибка при добавлении категории: " . addslashes($conn->error) . "');window.location.href = '/Админ.php';</script>";
        exit;
    }
}


// --- Обработка удаления категории ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_category_id"]) && !empty($_POST["delete_category_id"])) {
    $delete_category_id = (int)$_POST["delete_category_id"];

    // Проверяем, нет ли товаров с этой категорией
    $check = $conn->prepare("SELECT COUNT(*) FROM attractions WHERE id_category = ?");
    $check->bind_param("i", $delete_category_id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        echo "<script>alert('Нельзя удалить категорию, к которой привязаны товары!');window.location.href = '/Админ.php';</script>";
        exit;
    }

    // Удаляем категорию
    $stmt = $conn->prepare("DELETE FROM categories WHERE id_category = ?");
    $stmt->bind_param("i", $delete_category_id);
    if ($stmt->execute()) {
        echo "<script>alert('Категория удалена!');window.location.href = '/Админ.php';</script>";
        exit;
    } else {
        echo "<script>alert('Ошибка при удалении категории: " . addslashes($stmt->error) . "');window.location.href = '/Админ.php';</script>";
        exit;
    }
}


// Получаем список категорий
$categories = [];
$cat_result = $conn->query("SELECT id_category, name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// --- Обработка удаления товара ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_attraction"]) && !empty($_POST["id_attraction"])) {
    $id_attraction = $_POST["id_attraction"];


    // Удаляем связанные записи из категорий
    $del_c = $conn->prepare("DELETE FROM categories WHERE id_attractiont = ?");
    $del_c->bind_param("i", $id_attraction);
    $del_c->execute();
    $del_c->close();

    // Удаляем связанные записи из заявок
    $del_c = $conn->prepare("DELETE FROM applications WHERE id_attractiont = ?");
    $del_c->bind_param("i", $id_attraction);
    $del_c->execute();
    $del_c->close();

    // Теперь удаляем
    $sql = "DELETE FROM attractions WHERE id_attraction = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_attraction);

    if ($stmt->execute()) {
        echo "<script>alert('Аттракцион успешно удален!');window.location.href = '/Админ.php';</script>";
        exit;
    } else {
        echo "<script>alert('Ошибка при удалении: " . $stmt->error . "');window.location.href = '/Админ.php';</script>";
        exit;
    }
    $stmt->close();
}

// --- Обработка изменения товара ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id_attraction'])) {
    $id = (int)$_POST['update_id_attraction'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $descr = $_POST['descr'];
    $price = $_POST['price'];
    $power = $_POST['power'];
    $length = $_POST['length'];
    $height = $_POST['height'];
    $width = $_POST['width'];
    $id_category = $_POST['id_category'];

    // Обработка изображений (если загружены новые)
    $foto1_sql = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto1_data = process_image_upload($conn, $_FILES['foto']);
        if ($foto1_data !== false) {
            $foto1_sql = ", foto1 = '$foto1_data'";
        }
    }
    $foto2_sql = "";
    if (isset($_FILES['foto2']) && $_FILES['foto2']['error'] === UPLOAD_ERR_OK) {
        $foto2_data = process_image_upload($conn, $_FILES['foto2']);
        if ($foto2_data !== false) {
            $foto2_sql = ", foto2 = '$foto2_data'";
        }
    }
    $foto3_sql = "";
    if (isset($_FILES['foto3']) && $_FILES['foto3']['error'] === UPLOAD_ERR_OK) {
        $foto3_data = process_image_upload($conn, $_FILES['foto3']);
        if ($foto3_data !== false) {
            $foto3_sql = ", foto3 = '$foto3_data'";
        }
    }

    // Экранирование
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);
    $descr = mysqli_real_escape_string($conn, $descr);
    $price = mysqli_real_escape_string($conn, $price);
    $power = mysqli_real_escape_string($conn, $power);
    $length = mysqli_real_escape_string($conn, $length);
    $height = mysqli_real_escape_string($conn, $height);
    $width = mysqli_real_escape_string($conn, $width);
    $id_category = mysqli_real_escape_string($conn, $id_category);

    $sql = "UPDATE attractions SET 
        id_category = '$id_category',
        name = '$name',
        descr = '$descr',
        description = '$description',
        price = '$price',
        power = '$power',
        length = '$length',
        height = '$height',
        width = '$width'
        $foto1_sql
        $foto2_sql
        $foto3_sql
        WHERE id_attraction = $id";

    if ($conn->query($sql)) {
        echo "<script>alert('Товар обновлен!');window.location.href = '/Админ.php';</script>";
        exit;
    } else {
        echo "<script>alert('Ошибка при обновлении: " . addslashes($conn->error) . "');window.location.href = '/Админ.php';</script>";
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampoline</title>
<link rel="stylesheet" href="css/Админ.css?v=5">
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
        
            <?php

// Проверяем, что форма была отправлена методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn'])) {

    // Получаем данные из формы
    $name = $_POST['name'];
    $description = $_POST['description'];
    $descr = $_POST['descr'];
    $price = $_POST['price'];
    $power = $_POST['power'];
    $length = $_POST['length'];
    $height = $_POST['height'];
    $width = $_POST['width'];
    $id_category = $_POST['id_category'];

    // --- Обработка первого изображения (foto) ---
    $foto1_data = null; // Инициализация
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto1_data = process_image_upload($conn, $_FILES['foto']); // Функция для обработки загрузки
        if ($foto1_data === false) {
            exit; // Прерываем выполнение, если произошла ошибка
        }
    } else {
        echo "<script>alert('Ошибка загрузки первого изображения.');window.location.href = '/Админ.php';</script>";
        exit;
    }

    // --- Обработка второго изображения (foto2) ---
    $foto2_data = null; // Инициализация
    if (isset($_FILES['foto2']) && $_FILES['foto2']['error'] === UPLOAD_ERR_OK) {
        $foto2_data = process_image_upload($conn, $_FILES['foto2']);  // Функция для обработки загрузки
        if ($foto2_data === false) {
            exit; // Прерываем выполнение, если произошла ошибка
        }
    } else {
        echo "<script>alert('Ошибка загрузки второго изображения.');window.location.href = '/Админ.php';</script>";
        exit;
    }

    // --- Обработка второго изображения (foto3) ---
    $foto3_data = null; // Инициализация
    if (isset($_FILES['foto3']) && $_FILES['foto3']['error'] === UPLOAD_ERR_OK) {
        $foto3_data = process_image_upload($conn, $_FILES['foto3']);  // Функция для обработки загрузки
        if ($foto3_data === false) {
            exit; // Прерываем выполнение, если произошла ошибка
        }
    } else {
        echo "<script>alert('Ошибка загрузки третьего изображения.');window.location.href = '/Админ.php';</script>";
        exit;
    }

    // Экранируем данные для безопасной вставки в SQL-запрос
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);
    $descr = mysqli_real_escape_string($conn, $descr);
    $price = mysqli_real_escape_string($conn, $price);
    $power = mysqli_real_escape_string($conn, $power);
    $length = mysqli_real_escape_string($conn, $length);
    $height = mysqli_real_escape_string($conn, $height);
    $width = mysqli_real_escape_string($conn, $width);
    $id_category = mysqli_real_escape_string($conn, $id_category);

    // Проверяем, что все поля заполнены
    if (empty($name)|| empty($descr) || empty($description) || empty($price) || empty($power) || empty($length) || empty($height) || empty($width) || empty($id_category)) {
        echo "<script>alert('Заполните все текстовые поля.');window.location.href = '/Админ.php';</script>";
        exit;
    }

    // Создаем SQL-запрос
    $sql = "INSERT INTO attractions (id_category, name, descr, description, price, power, length, height, width, foto1, foto2, foto3) VALUES ('$id_category', '$name', '$descr', '$description', '$price', '$power', '$length', '$height', '$width', '$foto1_data', '$foto2_data', '$foto3_data')";

    // Выполняем запрос
    if ($conn->query($sql)) {
        echo "<script>alert('Аттракцион добавлен!');window.location.href = '/Админ.php';</script>";
        exit;
    } else {
        echo "<script>alert('Ошибка! Повторите попытку позже: " . $conn->error . "'); window.location.href = '/Админ.php';</script>";
        exit;
    }

} 

// --- Функция для обработки загрузки изображений ---
function process_image_upload($conn, $file) {
    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_type = $file['type'];

    // Проверяем тип файла (разрешаем только изображения)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Недопустимый тип файла. Разрешены только JPEG, PNG и GIF.');window.location.href = '/admin.php';</script>";
        return false; // Возвращаем false в случае ошибки
    }
    // Проверяем размер файла (например, не больше 2 МБ)
    if ($file_size > 2000000) {
        echo "<script>alert('Размер файла превышает 2 МБ.');window.location.href = '/admin.php';</script>";
        return false; // Возвращаем false в случае ошибки
    }

    // Читаем содержимое файла
    $image_data = file_get_contents($file_tmp_name);

    // Экранируем данные для безопасной вставки в SQL-запрос
    $image_data = mysqli_real_escape_string($conn, $image_data);

    return $image_data; // Возвращаем данные изображения
}
?>


        <div class="container">
    <div class="admin-panel">

        <div class="admin-section">
            <h1 class="admin-title">Админ-панель</h1>
            <div class="admin-links">
                <a href="Админ_отзывы.php" class="admin-link">Модерация отзывов</a>
                <a href="Админ_заявки.php" class="admin-link">Модерация заявок</a>
            </div>
        </div>

        <div class="admin-section">
            <h2 class="admin-subtitle">Добавление категории</h2>
            <form method="post">
                <label class="form-label">Название категории</label>
                <input class="input" type="text" name="category_name" required>
                <button class="btn" type="submit">Добавить категорию</button>
            </form>
        </div>

<div class="admin-section">
            <h2 class="admin-subtitle">Удаление категории</h2>
            <form method="post">
                <label class="form-label">Выберите категорию</label>
                <select class="input" name="delete_category_id" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id_category'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" type="submit" onclick="return confirm('Вы точно хотите удалить эту категорию?')">Удалить категорию</button>
            </form>
        </div>

        <div class="admin-section">
            <h2 class="admin-subtitle">Добавление товара</h2>
            <form method="post" enctype="multipart/form-data">
                <label class="form-label">Категория</label>
                <select class="input" name="id_category" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id_category'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label">Название</label>
                <input class="input" type="text" name="name">

                <label class="form-label">Описание для карточки</label>
                <textarea class="input2" name="description" required></textarea>

                <label class="form-label">Описание для каталога</label>
                <textarea class="input2" name="descr" required></textarea>

                <label class="form-label">Цена</label>
                <input class="input" type="number" name="price">

                <label class="form-label">Потребляемая мощность</label>
                <input class="input" type="text" name="power">

                <label class="form-label">Длина</label>
                <input class="input" type="text" name="length">

                <label class="form-label">Высота</label>
                <input class="input" type="text" name="height">

                <label class="form-label">Ширина</label>
                <input class="input" type="text" name="width">

                <label class="form-label">Картинка</label>
                <input class="input" type="file" name="foto">

                <label class="form-label">Картинка 2</label>
                <input class="input" type="file" name="foto2">

                <label class="form-label">Картинка 3</label>
                <input class="input" type="file" name="foto3">

                <button class="btn" type="submit" name="btn">Добавить</button>
            </form>
        </div>


<div class="admin-section">
    <h2 class="admin-subtitle">Изменение товара</h2>
    <form method="post" enctype="multipart/form-data">
        <label class="form-label">Выберите товар</label>
        <select class="input" name="edit_id_attraction" required onchange="this.form.submit()">
            <option value="">Выберите товар</option>
            <?php
            $query = "SELECT id_attraction, name FROM attractions ORDER BY id_attraction DESC";
            $results = $conn->query($query);
            while ($row = $results->fetch_assoc()) {
                $selected = (isset($_POST['edit_id_attraction']) && $_POST['edit_id_attraction'] == $row['id_attraction']) ? 'selected' : '';
                echo "<option value='" . $row["id_attraction"] . "' $selected>" . htmlspecialchars($row["name"]) . "</option>";
            }
            ?>
        </select>
    </form>
    <?php
    // Если выбран товар, выводим форму для редактирования
    if (isset($_POST['edit_id_attraction']) && $_POST['edit_id_attraction']) {
        $edit_id = (int)$_POST['edit_id_attraction'];
        $res = $conn->query("SELECT * FROM attractions WHERE id_attraction = $edit_id");
        if ($res && $row = $res->fetch_assoc()) {
    ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="update_id_attraction" value="<?= $edit_id ?>">
        <label class="form-label">Категория</label>
        <select class="input" name="id_category" required>
            <option value="">Выберите категорию</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id_category'] ?>" <?= ($row['id_category'] == $cat['id_category']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label class="form-label">Название</label>
        <input class="input" type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
        <label class="form-label">Описание для карточки</label>
        <textarea class="input2" name="description" required><?= htmlspecialchars($row['description']) ?></textarea>
        <label class="form-label">Описание для каталога</label>
        <textarea class="input2" name="descr" required><?= htmlspecialchars($row['descr']) ?></textarea>
        <label class="form-label">Цена</label>
        <input class="input" type="number" name="price" value="<?= htmlspecialchars($row['price']) ?>" required>
        <label class="form-label">Потребляемая мощность</label>
        <input class="input" type="text" name="power" value="<?= htmlspecialchars($row['power']) ?>" required>
        <label class="form-label">Длина</label>
        <input class="input" type="text" name="length" value="<?= htmlspecialchars($row['length']) ?>" required>
        <label class="form-label">Высота</label>
        <input class="input" type="text" name="height" value="<?= htmlspecialchars($row['height']) ?>" required>
        <label class="form-label">Ширина</label>
        <input class="input" type="text" name="width" value="<?= htmlspecialchars($row['width']) ?>" required>
        <label class="form-label">Картинка (оставьте пустым, если не менять)</label>
        <input class="input" type="file" name="foto">
        <label class="form-label">Картинка 2 (оставьте пустым, если не менять)</label>
        <input class="input" type="file" name="foto2">
        <label class="form-label">Картинка 3 (оставьте пустым, если не менять)</label>
        <input class="input" type="file" name="foto3">
        <button class="btn" type="submit" name="update_btn">Сохранить изменения</button>
    </form>
    <?php
        }
    }
    ?>
</div>


        

        

        <div class="admin-section">
            <h2 class="admin-subtitle">Удаление товара</h2>
            <form method="post">
                <label class="form-label">Выберите товар</label>
                <select class="input" name="id_attraction">
                    <option value="">Выберите товар</option>
                    <?php
                    require_once('db.php');
                    $conn->query("SET NAMES utf8");
                    $query = "SELECT id_attraction, name FROM attractions ORDER BY id_attraction DESC";
                    $results = $conn->query($query);
                    while ($row = $results->fetch_assoc()) {
                        echo "<option value='" . $row["id_attraction"] . "'>" . $row["name"] . "</option>";
                    }
                    ?>
                </select>
                <button class="btn" type="submit" onclick="return confirm('Вы точно хотите удалить этот аттракцион?')">Удалить</button>
            </form>
        </div>

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