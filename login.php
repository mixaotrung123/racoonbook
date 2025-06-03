<?php
session_start();
require 'config.php'; // file kết nối DB, tạo biến $pdo

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = "Vui lòng nhập tên đăng nhập và mật khẩu.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tai_khoan WHERE ten_dang_nhap = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['mat_khau'])) {
            $errors[] = "Tên đăng nhập hoặc mật khẩu không đúng.";
        } else if ($user['trang_thai'] == 0) {
            $errors[] = "Tài khoản đã bị khóa.";
        } else {
            // Lưu session
            $_SESSION['user'] = [
                'id' => $user['tai_khoan_id'],
                'username' => $user['ten_dang_nhap'],
                'email' => $user['email'],
                'quyen' => $user['quyen'],
            ];

            // Phân quyền redirect
            if ($user['quyen'] === 'admin') {
                header("Location: \Hethongbansach\Admin\home(ad).php");
                exit;
            } else {
                header("Location: \Hethongbansach\User\home.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="./style/bootstrap/css/bootstrap.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
      body {
        background: #FFE4B5;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
        line-height: 1.4;
      }
      label, input {
        font-weight: 500;
      }
      .container {
        background-color: #CD853F;
        padding: 2.5rem 2rem;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgb(0 0 0 / 0.4);
        color: white;
        max-width: 400px;
        width: 100%;
        margin: 20px;
      }
      h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #8B4513;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
      }
      label {
        color: #8B4513;
      }
      .form-control {
        width: 90%;
        margin-bottom: 1rem;
        transition: border-color 0.3s ease;
        border-radius: 6px;
      }
      .form-username, .form-password {
        height: 40px;
        font-size: 1.1rem;
        background-color: #fff8dc;
        border: 1px solid #deb887;
        color: #5a3e1b;
        padding: 0.5rem 1rem;
      }
      .form-username:focus, .form-password:focus {
        border-color: #8b4513;
        box-shadow: 0 0 6px rgba(139, 69, 19, 0.6);
        outline: none;
        color: #3b2f1a;
      }
      .btn-primary {
        height: 60px;
        width: 140px;
        background-color: #8B4513;
        border: none;
        font-weight: 600;
        margin-left: 120px;
        transition: background-color 0.3s ease;
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
        line-height: 1.4;
        cursor: pointer;
      }
      .btn-primary:hover {
        background-color: #FFDEAD;
      }
      .btn-link {
        margin-left: 150px;
        color: #8B4513;
      }
      .btn-link:hover {
        color: #FFDEAD;
        text-decoration: underline;
      }
      .alert-danger {
        background-color: #b91c1c;
        color: white;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 10px;
      }
      .alert-success {
        background-color: #166534;
        color: white;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 10px;
      }
      ul {
        padding-left: 20px;
        margin: 0;
      }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Đăng nhập tài khoản</h2>
    <img src="./image/book_logo.jpg" height="200" width="200" alt="Logo" />

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="username">Tên đăng nhập</label>
            <input type="text" name="username" id="username" class="form-control form-username" required value="<?=htmlspecialchars($_POST['username'] ?? '')?>" />
        </div>
        <div class="mb-3">
            <label for="password">Mật khẩu</label>
            <input type="password" name="password" id="password" class="form-control form-password" required />
        </div>
        <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
        <a href="register.php" class="btn btn-link d-block text-center mt-3">Đăng ký</a>
    </form>
</div>

<script src="./style/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
