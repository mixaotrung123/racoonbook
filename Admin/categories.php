<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['quyen'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$curPageName = basename($_SERVER["SCRIPT_NAME"]);

$host = 'localhost';
$dbname = 'hethongbansach';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// Xử lý các hành động thêm, sửa, xóa
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $ten = trim($_POST['ten_danh_muc'] ?? '');
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        if ($ten !== '') {
            $stmt = $pdo->prepare("INSERT INTO danh_muc (ten_danh_muc, mo_ta) VALUES (:ten, :mo_ta)");
            $stmt->execute([':ten' => $ten, ':mo_ta' => $mo_ta]);
            header("Location: categories.php");
            exit;
        }
    }
    if ($action === 'edit') {
        $id = (int)($_GET['id'] ?? 0);
        $ten = trim($_POST['ten_danh_muc'] ?? '');
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        if ($id > 0 && $ten !== '') {
            $stmt = $pdo->prepare("UPDATE danh_muc SET ten_danh_muc = :ten, mo_ta = :mo_ta WHERE danh_muc_id = :id");
            $stmt->execute([':ten' => $ten, ':mo_ta' => $mo_ta, ':id' => $id]);
            header("Location: categories.php");
            exit;
        }
    }
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM danh_muc WHERE danh_muc_id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: categories.php");
        exit;
    }
}

// Tìm kiếm
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE ten_danh_muc LIKE :search";
    $params[':search'] = "%$search%";
}

// Lấy dữ liệu danh mục
$stmt = $pdo->prepare("SELECT * FROM danh_muc $where ORDER BY danh_muc_id DESC");
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nếu đang sửa thì lấy dữ liệu cụ thể
$editCat = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM danh_muc WHERE danh_muc_id = :id");
        $stmt->execute([':id' => $id]);
        $editCat = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Quản lý danh mục</title>
    <link rel="stylesheet" href="./style/bootstrap/css/sb-admin-2.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="./style/dist/output.css" />
    <style>
        /* Giữ nguyên style sidebar từ books.php */
        body {
            font-family: 'Nunito', sans-serif;
            background-color: white;
            margin: 0; padding: 0;
            display: flex; height: 100vh; overflow: hidden;
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
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
            box-shadow: 0 0 5px rgb(0 0 0 / 0.1);
            border-radius: 6px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f0f0f0;
            font-weight: 700;
        }
        .btn-add, .btn-save {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 8px 16px;
            background-color: #A0522D;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        .btn-add:hover, .btn-save:hover {
            background-color: #7B3F1A;
        }
        form#search-form {
            margin-bottom: 1rem;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 0 5px rgb(0 0 0 / 0.1);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        form#search-form input[type="text"] {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            min-width: 200px;
        }
        form#search-form button {
            padding: 8px 20px;
            background-color: #A0522D;
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form#search-form button:hover {
            background-color: #7B3F1A;
        }
        .form-add-edit {
            background: white;
            padding: 1.5rem 2rem;  /* tăng padding */
            border-radius: 8px;
            box-shadow: 0 0 5px rgb(0 0 0 / 0.1);
            margin-bottom: 1rem;
            max-width: 600px;
            box-sizing: border-box;
        }
        .form-add-edit label {
            display: block;
            margin-top: 1rem;   /* tăng khoảng cách trên */
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-add-edit input[type="text"],
        .form-add-edit textarea {
            width: 100%;
            padding: 8px 12px;  /* tăng padding trong textbox */
            border: 1px solid #ccc;
            border-radius: 6px;
            resize: vertical;
            box-sizing: border-box;  /* tránh tràn chiều ngang */
        }
        .form-add-edit textarea {
            min-height: 100px;
        }
        .btn-edit {
            background-color: #007bff; /* màu xanh dương chuẩn nút sửa */
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-edit:hover {
            background-color: #0056b3;
        }

        .btn-delete {
            background-color: #cc3333;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-delete:hover {
            background-color: #992222;
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
        foreach ($col as $item):
            $url = $item['route'];
            $isActive = strstr($url, $curPageName);
        ?>
        <a href="<?= $url ?>" class="<?= $isActive ? 'active' : '' ?>">
            <?= $isActive ? '- ' : '' ?>
            <?= htmlspecialchars($item["label"]) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <main class="content">
        <h1>Quản lý danh mục</h1>

        <form id="search-form" method="GET" action="categories.php">
            <input type="text" name="search" placeholder="Tìm theo tên danh mục" value="<?= htmlspecialchars($search) ?>" />
            <button type="submit">Tìm kiếm</button>
            <?php if ($search !== ''): ?>
                <a href="categories.php" style="margin-left:10px; color:#A0522D;">Xóa tìm kiếm</a>
            <?php endif; ?>
        </form>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <form class="form-add-edit" method="POST" action="categories.php?action=<?= $action ?><?= $action === 'edit' ? '&id=' . (int)$_GET['id'] : '' ?>">
                <label for="ten_danh_muc">Tên danh mục:</label>
                <input type="text" name="ten_danh_muc" id="ten_danh_muc" required value="<?= htmlspecialchars($editCat['ten_danh_muc'] ?? '') ?>" />

                <label for="mo_ta">Mô tả:</label>
                <textarea name="mo_ta" id="mo_ta"><?= htmlspecialchars($editCat['mo_ta'] ?? '') ?></textarea>

                <button type="submit" class="btn-save"><?= $action === 'add' ? 'Thêm mới' : 'Lưu thay đổi' ?></button>
                <a href="categories.php" class="btn-add" style="background:#ccc; color:#333; margin-left:10px; text-decoration:none; padding:8px 16px; border-radius:6px;">Hủy</a>
            </form>
        <?php else: ?>
            <a href="categories.php?action=add" class="btn-add">+ Thêm danh mục mới</a>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['danh_muc_id']) ?></td>
                            <td><?= htmlspecialchars($cat['ten_danh_muc']) ?></td>
                            <td class="mo_ta" title="<?= htmlspecialchars($cat['mo_ta']) ?>"><?= htmlspecialchars(mb_strimwidth($cat['mo_ta'], 0, 90, '...')) ?></td>
                            <td>
                                <a href="categories.php?action=edit&id=<?= $cat['danh_muc_id'] ?>" class="btn-edit">Sửa</a>
                                   |
                                <a href="categories.php?action=delete&id=<?= $cat['danh_muc_id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');" class="btn-delete">Xóa</a>
                            </td>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;">Không tìm thấy danh mục phù hợp</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
