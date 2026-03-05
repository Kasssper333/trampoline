<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "Trampline";

$conn = mysqli_connect($servername,$username,$password,$dbname);

if(!$conn){
    die("Нет подключения к БД" . mysqli_connect_error());
} else {
     "Успешное подключение к БД";
}

mysqli_set_charset($conn, "utf8"); 
?>