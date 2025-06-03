<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['quyen'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$host = 'localhost';
$dbname = 'hethongbansach';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        die("ID sách không hợp lệ.");
    }

    // Xóa sách theo id
    $stmt = $pdo->prepare("DELETE FROM sach WHERE sach_id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: books.php');
    exit;

} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
