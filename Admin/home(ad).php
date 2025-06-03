<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['quyen'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$curPageName = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Trang quản lý Admin</title>
    <!-- Link CSS bạn dùng -->
    <link rel="stylesheet" href="./style/bootstrap/css/sb-admin-2.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="./style/dist/output.css" />
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: white;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 300px;
            height: 100vh;
            background-color: #FFE4B5;
            color: #A0522D;
            padding: 2rem 1rem;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .sidebar .logo-container {
            display: flex;
            gap: 1rem;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }
        .sidebar h1 {
            color: #A0522D;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            color: #CD853F;
            text-decoration: none;
            transition: color 0.3s ease;
            border-radius: 6px;
        }
        .sidebar a:hover,
        .sidebar a.active {
            color: white;
            font-style: italic;
            background-color: #A0522D;
            position: relative;
        }
        .sidebar a.active::after {
            content: "";
            position: absolute;
            width: 1.5rem;
            height: 1.5rem;
            background: white;
            top: 50%;
            right: -0.75rem;
            transform: translateY(-50%) rotate(45deg);
            z-index: 1;
        }
        main.content {
            flex-grow: 1;
            padding: 2rem;
            overflow-y: auto;
            background-color: #f8fafc;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo-container">
            <img src="\Hethongbansach\image\book_logo.jpg" height="150" width="150" alt="Logo" />
            <h1>CHÀO MỪNG</h1>
        </div>
        <?php
        $col = [
            ["label" => "Trang chủ", "route" => "\Hethongbansach\Admin\home(ad).php"],
            ["label" => "Quản lý sách", "route" => "\Hethongbansach\Admin\books.php"],
            ["label" => "Quản lý danh mục", "route" => "\Hethongbansach\Admin\categories.php"],
            ["label" => "Quản lý nhân viên", "route" => "\Hethongbansach\Admin\Employees.php"],
            ["label" => "Quản lý ưu đãi", "route" => "\Hethongbansach\Admin\discounts.php"],
            ["label" => "Báo cáo", "route" => "\Hethongbansach\Admin\Reports.php"],
            ["label" => "Đăng xuất", "route" => "\Hethongbansach\login.php"],
        ];
        ?>

        <?php foreach ($col as $item):
            $url = $item['route'];
            $isActive = strstr($url, $curPageName);
        ?>
            <a href="<?php echo $url; ?>" class="<?php echo $isActive ? 'active' : '' ?>">
                <?php echo $isActive ? '- ' : '' ?>
                <?php echo htmlspecialchars($item["label"]); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <main class="content">
        <h2>Trang quản lý Admin</h2>
        <p>Chào mừng bạn, <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong> đến trang quản trị hệ thống. Chúc bạn một ngày tốt lành !!!</p>
        <h3>TIN TỨC</h3>   
        <!-- Bạn thêm nội dung quản lý ở đây -->
    </main>

</body>
</html>
