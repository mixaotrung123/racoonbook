<?php
    $curPageName = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1); // lấy tên file hiện tại
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Quản lý trang</title>
    <!-- Link đúng file CSS SB Admin 2 -->
    <link rel="stylesheet" href="./style/bootstrap/css/sb-admin-2.min.css" />
    <!-- Google fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <!-- Tailwind (nếu cần) -->
    <link rel="stylesheet" href="./style/dist/output.css" />
    <style>
      /* Nếu muốn chỉnh CSS thêm cho sidebar hoặc phần text: */
      body {
          font-family: 'Nunito', sans-serif; /* SB Admin 2 mặc định */
          background-color: white; /* slate-900 */
          margin: 0;
          padding: 0;
      }
      .sidebar {
          width: 300px;
          height: 100vh;
          background-color: #FFE4B5; /* darker slate */
          color: #A0522D; /* gray-400 */
          padding: 2rem 1rem;
      }
      .sidebar a {
          display: flex;
          align-items: center;
          padding: 0.75rem 1rem;
          margin-bottom: 0.5rem;
          color: #CD853F; /* gray-400 */
          text-decoration: none;
          transition: color 0.3s ease;
      }
      .sidebar a:hover,
      .sidebar a.active {
          color: white;
          font-style: italic;
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="flex gap-4 items-center justify-center">
            <img src="./image/book_logo.jpg" height="200" width="200" alt="Logo" />
            <h1 class="text-2xl text-white">CHÀO MỪNG</h1>
        </div>
        <div class="text-left mt-8">
            <?php
            $col = [
                ["label" => "Đăng nhập", "route" => "http://localhost/QuanLyKyTucXa01/login.php"],
            ];
            ?>
            <?php foreach ($col as $item):
                $url = $item['route'];
                $isActive = strstr($url, $curPageName);
            ?>
            <a href="<?php echo $url; ?>" class="<?php echo $isActive ? 'active' : '' ?>">
                <?php echo $isActive ? '- ' : '' ?>
                <?php echo $item["label"]; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
