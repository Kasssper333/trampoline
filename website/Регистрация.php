<?php
    session_start();
    $errors = $_SESSION['reg_errors'] ?? [];
    $values = $_SESSION['reg_values'] ?? [];
    unset($_SESSION['reg_errors'], $_SESSION['reg_values']);

require_once('db.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
// Получаем данные из формы
$lastname = $_POST['lastname'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$middlename = $_POST['middlename'] ?? '';
$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';
$repeatpassword = $_POST['repeatpassword'] ?? '';
$number = $_POST['number'] ?? '';
$email = $_POST['email'] ?? '';
$agree = $_POST['agree'] ?? '';

$errors = [
    'lastname' => '',
    'firstname' => '',
    'middlename' => '',
    'login' => '',
    'password' => '',
    'repeatpassword' => '',
    'number' => '',
    'email' => '',
    'common' => '',
    'agree' => ''
];

// Регулярные выражения
$loginRegex = '/^[а-яА-Яa-zA-Z0-9\-]{5,20}$/u';
$passwordRegex = '/^.{6,}$/u';
$numberRegex = '/^\+?\d{11,12}$/u';
$emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/u';

if (empty($lastname)) {
    $errors['lastname'] = "Заполните фамилию";
}
if (empty($firstname)) {
    $errors['firstname'] = "Заполните имя";
}

if (empty($login)) {
    $errors['login'] = "Заполните логин";
} elseif (!preg_match($loginRegex, $login)) {
    $errors['login'] = "Логин должен содержать от 5 до 20 символов, только буквы и цифры";
}

if (empty($password)) {
    $errors['password'] = "Заполните пароль";
} elseif (!preg_match($passwordRegex, $password)) {
    $errors['password'] = "Пароль должен содержать не менее 6 символов";
}

if (empty($repeatpassword)) {
    $errors['repeatpassword'] = "Повторите пароль";
} elseif ($password !== $repeatpassword) {
    $errors['repeatpassword'] = "Пароли не совпадают";
}

if (empty($number)) {
    $errors['number'] = "Заполните номер телефона";
} elseif (!preg_match($numberRegex, $number)) {
    $errors['number'] = "Введите корректный номер телефона";
}

if (empty($email)) {
    $errors['email'] = "Заполните email";
} elseif (!preg_match($emailRegex, $email)) {
    $errors['email'] = "Введите корректный адрес электронной почты";
}

if (empty($agree)) {
    $errors['agree'] = "Вы должны согласиться с правилами регистрации";
}

// Проверка уникальности только если нет ошибок валидации
if (!array_filter($errors)) {
    $loginCheckSql = "SELECT COUNT(*) FROM users WHERE login = '$login'";
    $emailCheckSql = "SELECT COUNT(*) FROM users WHERE email = '$email'";
    $numberCheckSql = "SELECT COUNT(*) FROM users WHERE number = '$number'";

    $loginResult = $conn->query($loginCheckSql);
    $emailResult = $conn->query($emailCheckSql);
    $numberResult = $conn->query($numberCheckSql);

    if ($loginResult->fetch_row()[0] > 0) {
        $errors['login'] = "Пользователь с таким логином уже существует";
    }
    if ($emailResult->fetch_row()[0] > 0) {
        $errors['email'] = "Пользователь с такой почтой уже существует";
    }
    if ($numberResult->fetch_row()[0] > 0) {
        $errors['number'] = "Пользователь с таким номером телефона уже существует";
    }
}

// Если есть ошибки — сохраняем их в сессию и возвращаем значения полей
if (array_filter($errors)) {
    session_start();
    $_SESSION['reg_errors'] = $errors;
    $_SESSION['reg_values'] = [
        'lastname' => $lastname,
        'firstname' => $firstname,
        'middlename' => $middlename,
        'login' => $login,
        'number' => $number,
        'email' => $email,
        'agree' => $agree
    ];
    header("Location: /Регистрация.php");
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Если всё хорошо — регистрируем пользователя
$sql = "INSERT INTO users (name, surname, patronymic, login, password, number, email) VALUES ('$lastname', '$firstname', '$middlename', '$login', '$hashedPassword', '$number', '$email')";
if ($conn->query($sql)) {
   // Получаем только что зарегистрированного пользователя
    $result = $conn->query("SELECT * FROM users WHERE login = '$login'");
    if ($result && $row = $result->fetch_assoc()) {
        $_SESSION['user'] = $row['login'];
        $_SESSION['f'] = $row['surname'];
        $_SESSION['n'] = $row['name'];
        $_SESSION['p'] = $row['patronymic'];
        $_SESSION['id_user'] = $row['id_user'];
    }
    echo "<script>alert('Вы успешно зарегистрировались!'); window.location.href = '/index.php';</script>";
    exit;
} else {
    session_start();
    $_SESSION['reg_errors'] = ['common' => "Ошибка регистрации! Повторите попытку позже."];
    echo "<script>alert('Произошла ошибка, пожалуйста повторите попытку позже!'); window.location.href = '/Регистрация.php';</script>";
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
    <link rel="stylesheet" href="css/Регистрация.css?v=3">
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
                <p class="register">Регистрация</p>
                <form action="" method="post">

                    <input class="input lastname" type="text" name="lastname" placeholder="Имя"  value="<?= htmlspecialchars($values['lastname'] ?? '') ?>">
                    <?php if (!empty($errors['lastname'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['lastname']) ?></div>
                    <?php endif; ?>

                    <input class="input firstname" type="text" name="firstname" placeholder="Фамилия" value="<?= htmlspecialchars($values['firstname'] ?? '') ?>">
                    <?php if (!empty($errors['firstname'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['firstname']) ?></div>
                    <?php endif; ?>

                    <input class="input middlename" type="text" name="middlename" placeholder="Отчество" value="<?= htmlspecialchars($values['middlename'] ?? '') ?>">
                    <?php if (!empty($errors['middlename'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['middlename']) ?></div>
                    <?php endif; ?>

                    <input class="input login" type="text" name="login" placeholder="Логин" value="<?= htmlspecialchars($values['login'] ?? '') ?>">
                    <div class="error errlogin"></div>
                    <?php if (!empty($errors['login'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['login']) ?></div>
                    <?php endif; ?>

                    <input class="input email" type="text" name="email" placeholder="Электронная почта" value="<?= htmlspecialchars($values['email'] ?? '') ?>">
                    <div class="error erremail"></div>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>

                    <input class="input number" type="text" name="number" placeholder="Телефон" value="<?= htmlspecialchars($values['number'] ?? '') ?>">
                    <div class="error errnumber"></div>
                    <?php if (!empty($errors['number'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['number']) ?></div>
                    <?php endif; ?>

                    <input class="input password" type="text" name="password" placeholder="Пароль" value="<?= htmlspecialchars($values['password'] ?? '') ?>">
                    <div class="error errpassword"></div>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>

                    <input class="input repeatpassword" type="text" name="repeatpassword" placeholder="Повторите пароль" value="<?= htmlspecialchars($values['repeatpassword'] ?? '') ?>">
                    <div class="error errrpassword"></div>
                    <?php if (!empty($errors['repeatpassword'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['repeatpassword']) ?></div>
                    <?php endif; ?>

                    <label class="label">
                        <input class="checkbox" type="checkbox" name="agree" value="1" <?= isset($values['agree']) ? 'checked' : '' ?> style="margin-right: 8px;">
                       <p class="agree"> Я согласен с <a href="https://мвд.рф/dejatelnost/emvd/guvm/регистрационный-учет" target="_blank" style="color: #4A0A77; text-decoration: underline; margin-left: 4px;">правилами регистрации</a></p>
                    </label>
                    <?php if (!empty($errors['agree'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['agree']) ?></div>
                    <?php endif; ?>

                    <button class="btn" type="submit">Зарегистрироваться</button>
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