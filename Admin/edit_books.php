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

$errors = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lấy danh mục + ưu đãi để dropdown
    $stmt_dm = $pdo->query("SELECT danh_muc_id, ten_danh_muc FROM danh_muc ORDER BY ten_danh_muc");
    $danh_mucs = $stmt_dm->fetchAll(PDO::FETCH_ASSOC);

    $stmt_ud = $pdo->query("SELECT uu_dai_id, ten_uu_dai FROM uu_dai ORDER BY ten_uu_dai");
    $uu_dais = $stmt_ud->fetchAll(PDO::FETCH_ASSOC);

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        die("ID sách không hợp lệ.");
    }

    $stmt = $pdo->prepare("SELECT * FROM sach WHERE sach_id = :id");
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        die("Không tìm thấy sách cần sửa.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ten_sach = trim($_POST['ten_sach'] ?? '');
        $tac_gia = trim($_POST['tac_gia'] ?? '');
        $nha_xuat_ban = trim($_POST['nha_xuat_ban'] ?? '');
        $gia = $_POST['gia'] ?? '';
        $so_luong = $_POST['so_luong'] ?? '';
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        $uu_dai_id = $_POST['uu_dai_id'] ?? null;
        $danh_muc_id = $_POST['danh_muc_id'] ?? null;
        $img_path = trim($_POST['img_url'] ?? '');

        // Validate
        if ($ten_sach === '') {
            $errors[] = 'Tên sách không được để trống.';
        }
        if (!is_numeric($gia) || $gia < 0) {
            $errors[] = 'Giá phải là số dương hợp lệ.';
        }
        if (!is_numeric($so_luong) || $so_luong < 0 || (int)$so_luong != $so_luong) {
            $errors[] = 'Số lượng phải là số nguyên dương.';
        }

        if ($img_path === '') {
            $img_path = null; // nếu để trống thì lưu null
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE sach SET ten_sach = :ten_sach, tac_gia = :tac_gia, nha_xuat_ban = :nha_xuat_ban, gia = :gia, so_luong = :so_luong, mo_ta = :mo_ta, uu_dai_id = :uu_dai_id, danh_muc_id = :danh_muc_id, img = :img WHERE sach_id = :id");
            $stmt->execute([
                ':ten_sach' => $ten_sach,
                ':tac_gia' => $tac_gia,
                ':nha_xuat_ban' => $nha_xuat_ban,
                ':gia' => (float)$gia,
                ':so_luong' => (int)$so_luong,
                ':mo_ta' => $mo_ta,
                ':uu_dai_id' => $uu_dai_id !== '' ? $uu_dai_id : null,
                ':danh_muc_id' => $danh_muc_id !== '' ? $danh_muc_id : null,
                ':img' => $img_path,
                ':id' => $id
            ]);
            header('Location: books.php');
            exit;
        }
    }
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

function imgPathToUrl($img_path) {
    if (!$img_path) return '';
    return str_replace('\\', '/', $img_path);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sửa sách</title>
    <link rel="stylesheet" href="./style/bootstrap/css/sb-admin-2.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="./style/dist/output.css" />
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: white;
            margin: 0; padding: 0;
            display: flex;
            min-height: 100vh;
            overflow: auto;
        }
        .sidebar {
            width: 300px; height: 100vh;
            background-color: #FFE4B5;
            color: #A0522D;
            padding: 2rem 1rem;
            flex-shrink: 0;
            display: flex; flex-direction: column; overflow-y: auto;
        }
        .sidebar .logo-container {
            display: flex; gap: 1rem; flex-direction: column; align-items: center; margin-bottom: 2rem;
        }
        .sidebar h1 {
            color: #A0522D;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }
        .sidebar a {
            display: flex; align-items: center;
            padding: 0.75rem 1rem; margin-bottom: 0.5rem;
            color: #CD853F; text-decoration: none;
            transition: color 0.3s ease; border-radius: 6px;
        }
        .sidebar a:hover,
        .sidebar a.active {
            color: white; font-style: italic;
            background-color: #A0522D; position: relative;
        }
        .sidebar a.active::after {
            content: "";
            position: absolute;
            width: 1.5rem; height: 1.5rem;
            background: white;
            top: 50%; right: -0.75rem;
            transform: translateY(-50%) rotate(45deg);
            z-index: 1;
        }
        main.content {
            flex-grow: 1; padding: 2rem;
            overflow-y: auto;
            background-color: #f8fafc;
            max-width: 800px;
            margin: auto;
        }
        form.add-book-form {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            box-shadow: 0 0 8px rgb(0 0 0 / 0.1);
        }
        form.add-book-form label {
            display: block;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        form.add-book-form input[type="text"],
        form.add-book-form input[type="number"],
        form.add-book-form textarea,
        form.add-book-form select {
            width: 100%;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            resize: vertical;
        }
        form.add-book-form textarea {
            min-height: 100px;
        }
        button.btn-submit {
            margin-top: 1.5rem;
            background-color: #A0522D;
            color: white;
            padding: 10px 20px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button.btn-submit:hover {
            background-color: #7B3F1A;
        }
        .errors {
            background: #ffcccc;
            border: 1px solid #cc0000;
            color: #900;
            padding: 10px;
            margin-bottom: 1rem;
            border-radius: 6px;
        }
        .current-img {
            margin-top: 10px;
            max-width: 150px;
            max-height: 180px;
            border: 1px solid #ccc;
            padding: 3px;
            border-radius: 6px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="/Hethongbansach/image/book_logo.jpg" height="150" width="150" alt="Logo" />
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
        foreach ($col as $item):
            $url = $item['route'];
            $isActive = strstr($url, basename($_SERVER["SCRIPT_NAME"]));
        ?>
        <a href="<?= $url ?>" class="<?= $isActive ? 'active' : '' ?>">
            <?= $isActive ? '- ' : '' ?>
            <?= htmlspecialchars($item["label"]) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <main class="content">
        <h1>Sửa sách</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="add-book-form" method="POST" action="edit_books.php?id=<?= $id ?>">
            <label for="ten_sach">Tên sách *</label>
            <input type="text" id="ten_sach" name="ten_sach" required value="<?= htmlspecialchars($_POST['ten_sach'] ?? $book['ten_sach']) ?>" />

            <label for="tac_gia">Tác giả</label>
            <input type="text" id="tac_gia" name="tac_gia" value="<?= htmlspecialchars($_POST['tac_gia'] ?? $book['tac_gia']) ?>" />

            <label for="nha_xuat_ban">Nhà xuất bản</label>
            <input type="text" id="nha_xuat_ban" name="nha_xuat_ban" value="<?= htmlspecialchars($_POST['nha_xuat_ban'] ?? $book['nha_xuat_ban']) ?>" />

            <label for="gia">Giá *</label>
            <input type="number" id="gia" name="gia" min="0" step="0.01" required value="<?= htmlspecialchars($_POST['gia'] ?? $book['gia']) ?>" />

            <label for="so_luong">Số lượng *</label>
            <input type="number" id="so_luong" name="so_luong" min="0" required value="<?= htmlspecialchars($_POST['so_luong'] ?? $book['so_luong']) ?>" />

            <label for="mo_ta">Mô tả</label>
            <textarea id="mo_ta" name="mo_ta"><?= htmlspecialchars($_POST['mo_ta'] ?? $book['mo_ta']) ?></textarea>

            <label for="uu_dai_id">Ưu đãi</label>
            <select id="uu_dai_id" name="uu_dai_id">
                <option value="">-- Chọn ưu đãi --</option>
                <?php foreach($uu_dais as $ud): ?>
                    <option value="<?= $ud['uu_dai_id'] ?>" <?= ((isset($_POST['uu_dai_id']) && $_POST['uu_dai_id'] == $ud['uu_dai_id']) || (!isset($_POST['uu_dai_id']) && $book['uu_dai_id'] == $ud['uu_dai_id'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ud['ten_uu_dai']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="danh_muc_id">Danh mục</label>
            <select id="danh_muc_id" name="danh_muc_id">
                <option value="">-- Chọn danh mục --</option>
                <?php foreach($danh_mucs as $dm): ?>
                    <option value="<?= $dm['danh_muc_id'] ?>" <?= ((isset($_POST['danh_muc_id']) && $_POST['danh_muc_id'] == $dm['danh_muc_id']) || (!isset($_POST['danh_muc_id']) && $book['danh_muc_id'] == $dm['danh_muc_id'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dm['ten_danh_muc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="img_url">Đường dẫn ảnh sách (vd: \Hethongbansach\uploads\books\file.webp)</label>
            <input type="text" id="img_url" name="img_url" placeholder="Nhập đường dẫn ảnh" value="<?= htmlspecialchars($_POST['img_url'] ?? $book['img']) ?>" />
            
            <?php if (!empty($book['img'])): ?>
                <img src="<?= htmlspecialchars(imgPathToUrl($book['img'])) ?>" alt="Ảnh sách hiện tại" class="current-img" />
            <?php endif; ?>

            <button type="submit" class="btn-submit">Lưu thay đổi</button>
        </form>
    </main>
</body>
</html>
