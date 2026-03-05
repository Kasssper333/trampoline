<?php
session_start();
require_once('db.php'); // Подключение к базе данных

// Получаем ID товара из параметра URL
if (isset($_GET['id_attraction']) && is_numeric($_GET['id_attraction'])) {
    $id_attraction = (int)$_GET['id_attraction']; // Преобразуем к integer для безопасности

    // Запрос к базе данных для получения информации о товаре
    $sql = "SELECT * FROM attractions WHERE id_attraction = $id_attraction";
    $result = $conn->query($sql);

    if ($result) { // Проверка на успешное выполнение запроса
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $name = htmlspecialchars($row['name']);
            $description = htmlspecialchars($row['description']);
            $price = $row['price'];


            $image_data1 = $row['foto1'];
            $image_data2 = $row['foto2'];
            $image_data3 = $row['foto3'];

            function getImageSrc($data) {
                if (!$data) return '';
                $type = @exif_imagetype('data://image/jpeg;base64,' . base64_encode($data));
                $mime = $type ? image_type_to_mime_type($type) : 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }

            $img1 = getImageSrc($image_data1);
            $img2 = getImageSrc($image_data2);
            $img3 = getImageSrc($image_data3);;
            ?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trampoline</title>
    <link rel="stylesheet" href="css/Аттракцион.css?v=6">
</head>
<body>
    <script>
window.onload = function() {
    let slideIndex = 0;
    const slides = document.querySelectorAll('.slide');
    showSlide(slideIndex);

    function showSlide(n) {
        slides.forEach((slide, i) => {
            slide.style.display = (i === n) ? 'block' : 'none';
        });
    }
    window.plusSlide = function(n) {
        slideIndex += n;
        if (slideIndex >= slides.length) slideIndex = 0;
        if (slideIndex < 0) slideIndex = slides.length - 1;
        showSlide(slideIndex);
    }
}
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

        <div class='blok1'>
             <div class="slider">
                <button class="slider-btn prev" onclick="plusSlide(-1)"><</button>
                <div class="slider-images">
                    <?php if ($img1): ?>
                        <img class="slide" src="<?= $img1 ?>" alt="Фото 1">
                    <?php endif; ?>
                    <?php if ($img2): ?>
                        <img class="slide" src="<?= $img2 ?>" alt="Фото 2">
                    <?php endif; ?>
                    <?php if ($img3): ?>
                        <img class="slide" src="<?= $img3 ?>" alt="Фото 3">
                    <?php endif; ?>
                </div>
                <button class="slider-btn next" onclick="plusSlide(1)">></button>
            </div>

        <?php  echo " 
                        <div class='blok'>
                            <h1 class='name' >$name</h1>
                            <p class='discr'>$description</p>
                        </div>";
        ?>
        </div>


<form id="orderForm" action="Заказ.php" method="get" >
        <div class="calculator">
    <input type="hidden" name="id_attraction" value="<?= $id_attraction ?>">
    
    <label class='text'>Выберите дату проката</label><br/>
    <input class='input' type="date" id="rental_date" name="rental_date" /><br/>

    <label class='text'>Выберите время начала проката</label><br/>
    <input class='input' type="time" id="start_time" name="start_time" required><br/>

    <label class='text'>Выберите время окончания проката</label><br/>
    <input class='input' type="time" id="end_time" name="end_time" required><br/>

    <input type="hidden" id="hidden_start_time" name="hidden_start_time">
    <input type="hidden" id="hidden_end_time" name="hidden_end_time">

    <label class='text'>Тарифная ставка батута за час (₽)</label><br/>
    <input class='input' type="number" id="rate_per_hour" min="1" value="<?= htmlspecialchars($price) ?>" readonly /><br/>

    <label class='text'>Скидка (%)</label><br/>
    <input class='input' type="text" id="discount" readonly /><br/>

    <label class='text'>Итоговая стоимость (₽)</label><br/>
    <input class='input' type="text" id="total_cost" name="total_cost" readonly /><br/>

    <button class='btn_r' type="button" onclick="calculate()">Рассчитать</button>
    </div>

    <button type="submit" class="btn_z" onclick="sendOrder()">Заказать</button>
    </form>


<script>
   function calculate() {
  const start = document.getElementById("start_time").value;
  const end = document.getElementById("end_time").value;
  const rate = parseFloat(document.getElementById("rate_per_hour").value);

  if (!start || !end) {
    alert("Выберите время начала и окончания проката.");
    return;
  }

  // Переводим время в минуты
  const [startH, startM] = start.split(":").map(Number);
  const [endH, endM] = end.split(":").map(Number);
  let minutes = (endH * 60 + endM) - (startH * 60 + startM);

  if (minutes < 60) {
    alert("Минимальное время аренды — 60 минут.");
    return;
  }

  const hours = minutes / 60;
  let discountPercent = 0;

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

  document.getElementById("discount").value = discountPercent + "%";
  document.getElementById("total_cost").value = Math.round(finalCost) + " руб.";
}

    function sendOrder() {
        document.getElementById('hidden_start_time').value = document.getElementById('start_time').value;
        document.getElementById('hidden_end_time').value = document.getElementById('end_time').value;
        document.getElementById('orderForm').submit();
    }
  </script>

        </div>



<?php  } else {
            echo "<p>Товар не найден.</p>";
        }
    } else {
        echo "<p>Ошибка при выполнении запроса: " . $conn->error . "</p>"; // Выводим ошибку SQL
    }
} else {
    echo "<p>Неверный ID товара.</p>";
    if(isset($_GET['id_product'])){
    } else {
        echo "<p>Параметр id_product отсутствует в URL.</p>";
    }
}
?>

    


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