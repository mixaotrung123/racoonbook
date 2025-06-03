<?php
$host = "localhost";
$dbname = "hethongbansach";
$username = "root";   // đổi theo config của bạn
$password = "";       // đổi theo config của bạn

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Lỗi kết nối DB: " . $e->getMessage());
}
?>
