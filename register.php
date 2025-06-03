<?php
session_start();
require 'config.php'; // file kết nối DB của bạn

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!$username || !$email || !$password) {
        $message = 'Vui lòng điền đầy đủ thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email không hợp lệ.';
    } elseif ($password !== $password_confirm) {
        $message = 'Mật khẩu không khớp.';
    } else {
        // Kiểm tra trùng tên đăng nhập hoặc email
        $stmt = $pdo->prepare("SELECT tai_khoan_id FROM tai_khoan WHERE ten_dang_nhap = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $message = 'Tên đăng nhập hoặc Email đã tồn tại.';
        } else {
            // Mã hóa mật khẩu
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO tai_khoan (ten_dang_nhap, mat_khau, email, quyen, trang_thai) VALUES (?, ?, ?, 'user', 1)");
            if ($stmt->execute([$username, $hash, $email])) {
                $_SESSION['success'] = "Đăng ký thành công, vui lòng đăng nhập.";
                header('Location: login.php');
                exit;
            } else {
                $message = 'Đăng ký thất bại, vui lòng thử lại.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Đăng ký tài khoản</title>
    <link rel="stylesheet" href="./style/bootstrap/css/bootstrap.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
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
        font-weight: 500;
      }
      .form-control {
        width: 90%;
        margin-bottom: 1rem;
        transition: border-color 0.3s ease;
        border-radius: 6px;
      }
      input[type="text"],
      input[type="email"],
      input[type="password"] {
        height: 40px;
        font-size: 1.1rem;
        background-color: #fff8dc;
        border: 1px solid #deb887;
        color: #5a3e1b;
        padding: 0.5rem 1rem;
      }
      input[type="text"]:focus,
      input[type="email"]:focus,
      input[type="password"]:focus {
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
        cursor: pointer;
      }
      .btn-link:hover {
        color: #FFDEAD;
        text-decoration: underline;
      }
      .error {
        background-color: #b91c1c;
        color: white;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 10px;
      }
      .success {
        background-color: #166534;
        color: white;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 10px;
      }
    </style>
</head>
<body>

<div class="container">
    <h2>Đăng ký tài khoản</h2>

    <?php if ($message): ?>
        <div class="error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="username">Tên đăng nhập</label><br/>
        <input type="text" id="username" name="username" class="form-control" required value="<?=htmlspecialchars($_POST['username'] ?? '')?>" /><br/>

        <label for="email">Email</label><br/>
        <input type="email" id="email" name="email" class="form-control" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>" /><br/>

        <label for="password">Mật khẩu</label><br/>
        <input type="password" id="password" name="password" class="form-control" required /><br/>

        <label for="password_confirm">Nhập lại mật khẩu</label><br/>
        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required /><br/>

        <button type="submit" class="btn btn-primary">Đăng ký</button><br/><br/>
        <a href="login.php" class="btn-link">Đăng nhập</a>
    </form>
</div>

<script src="./style/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
