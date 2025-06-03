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

    $action = $_GET['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ten_uu_dai = trim($_POST['ten_uu_dai'] ?? '');
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        $phan_tram_giam = $_POST['phan_tram_giam'] ?? '';
        $ngay_bat_dau = $_POST['ngay_bat_dau'] ?? '';
        $ngay_ket_thuc = $_POST['ngay_ket_thuc'] ?? '';
        $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;

        $errors = [];

        if ($ten_uu_dai === '') $errors[] = "Tên ưu đãi không được để trống.";
        if (!is_numeric($phan_tram_giam) || $phan_tram_giam < 0 || $phan_tram_giam > 100) $errors[] = "Phần trăm giảm phải là số từ 0 đến 100.";
        if ($ngay_bat_dau !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_bat_dau)) $errors[] = "Ngày bắt đầu không đúng định dạng YYYY-MM-DD.";
        if ($ngay_ket_thuc !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_ket_thuc)) $errors[] = "Ngày kết thúc không đúng định dạng YYYY-MM-DD.";
        if ($ngay_bat_dau !== '' && $ngay_ket_thuc !== '' && $ngay_ket_thuc < $ngay_bat_dau) $errors[] = "Ngày kết thúc phải sau ngày bắt đầu.";

        if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO uu_dai (ten_uu_dai, mo_ta, phan_tram_giam, ngay_bat_dau, ngay_ket_thuc, trang_thai) VALUES (:ten_uu_dai, :mo_ta, :phan_tram_giam, :ngay_bat_dau, :ngay_ket_thuc, :trang_thai)");
                $stmt->execute([
                    ':ten_uu_dai' => $ten_uu_dai,
                    ':mo_ta' => $mo_ta,
                    ':phan_tram_giam' => $phan_tram_giam,
                    ':ngay_bat_dau' => $ngay_bat_dau ?: null,
                    ':ngay_ket_thuc' => $ngay_ket_thuc ?: null,
                    ':trang_thai' => $trang_thai,
                ]);
                header("Location: discounts.php");
                exit;
            }
            if ($action === 'edit') {
                $id = (int)($_GET['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE uu_dai SET ten_uu_dai=:ten_uu_dai, mo_ta=:mo_ta, phan_tram_giam=:phan_tram_giam, ngay_bat_dau=:ngay_bat_dau, ngay_ket_thuc=:ngay_ket_thuc, trang_thai=:trang_thai WHERE uu_dai_id=:id");
                    $stmt->execute([
                        ':ten_uu_dai' => $ten_uu_dai,
                        ':mo_ta' => $mo_ta,
                        ':phan_tram_giam' => $phan_tram_giam,
                        ':ngay_bat_dau' => $ngay_bat_dau ?: null,
                        ':ngay_ket_thuc' => $ngay_ket_thuc ?: null,
                        ':trang_thai' => $trang_thai,
                        ':id' => $id,
                    ]);
                    header("Location: discounts.php");
                    exit;
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM uu_dai WHERE uu_dai_id=:id");
            $stmt->execute([':id' => $id]);
            header("Location: discounts.php");
            exit;
        }
    }

    // Tìm kiếm theo tên ưu đãi
    $search = trim($_GET['search'] ?? '');

    $where = '';
    $params = [];

    if ($search !== '') {
        $where = "WHERE ten_uu_dai LIKE :search";
        $params[':search'] = "%$search%";
    }

    $stmt = $pdo->prepare("SELECT * FROM uu_dai $where ORDER BY uu_dai_id DESC");
    $stmt->execute($params);
    $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy thông tin để sửa
    $editDiscount = null;
    if ($action === 'edit') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM uu_dai WHERE uu_dai_id=:id");
            $stmt->execute([':id' => $id]);
            $editDiscount = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quản lý ưu đãi</title>
<link rel="stylesheet" href="./style/bootstrap/css/sb-admin-2.min.css" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
<link rel="stylesheet" href="./style/dist/output.css" />
<style>
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
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 0 5px rgb(0 0 0 / 0.1);
        margin-bottom: 1rem;
        max-width: 600px;
    }
    .form-add-edit label {
        display: block;
        margin-top: 0.5rem;
        font-weight: 600;
    }
    .form-add-edit input[type="text"],
    .form-add-edit input[type="number"],
    .form-add-edit input[type="date"],
    .form-add-edit textarea {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        resize: vertical;
        box-sizing: border-box;
    }
    .form-add-edit textarea {
        min-height: 80px;
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
     .btn-edit {
            background-color: #007bff;
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
        <img src="/Hethongbansach/image/book_logo.jpg" height="150" width="150" alt="Logo" />
        <h1>CHÀO MỪNG</h1>
    </div>
    <?php
    $col = [
        ["label" => "Trang chủ", "route" => "home(ad).php"],
        ["label" => "Quản lý sách", "route" => "books.php"],
        ["label" => "Quản lý danh mục", "route" => "categories.php"],
        ["label" => "Quản lý nhân viên", "route" => "employees.php"],
        ["label" => "Quản lý ưu đãi", "route" => "discounts.php"],
        ["label" => "Báo cáo", "route" => "reports.php"],
        ["label" => "Đăng xuất", "route" => "login.php"],
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
    <h1>Quản lý ưu đãi</h1>

    <form id="search-form" method="GET" action="discounts.php">
        <input type="text" name="search" placeholder="Tìm theo tên ưu đãi" value="<?= htmlspecialchars($search) ?>" />
        <button type="submit">Tìm kiếm</button>
        <?php if ($search !== ''): ?>
            <a href="discounts.php" style="margin-left:10px; color:#A0522D;">Xóa tìm kiếm</a>
        <?php endif; ?>
    </form>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <form class="form-add-edit" method="POST" action="discounts.php?action=<?= $action ?><?= $action === 'edit' ? '&id=' . (int)$_GET['id'] : '' ?>">
            <?php if (!empty($errors)): ?>
                <div class="errors" style="background:#ffcccc; border:1px solid #cc0000; color:#900; padding:10px; border-radius:6px; margin-bottom:1rem;">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <label for="ten_uu_dai">Tên ưu đãi *</label>
            <input type="text" id="ten_uu_dai" name="ten_uu_dai" required value="<?= htmlspecialchars($_POST['ten_uu_dai'] ?? $editDiscount['ten_uu_dai'] ?? '') ?>" />

            <label for="mo_ta">Mô tả</label>
            <textarea id="mo_ta" name="mo_ta"><?= htmlspecialchars($_POST['mo_ta'] ?? $editDiscount['mo_ta'] ?? '') ?></textarea>

            <label for="phan_tram_giam">Phần trăm giảm (%) *</label>
            <input type="number" id="phan_tram_giam" name="phan_tram_giam" min="0" max="100" step="0.01" required value="<?= htmlspecialchars($_POST['phan_tram_giam'] ?? $editDiscount['phan_tram_giam'] ?? '') ?>" />

            <label for="ngay_bat_dau">Ngày bắt đầu</label>
            <input type="date" id="ngay_bat_dau" name="ngay_bat_dau" value="<?= htmlspecialchars($_POST['ngay_bat_dau'] ?? $editDiscount['ngay_bat_dau'] ?? '') ?>" />

            <label for="ngay_ket_thuc">Ngày kết thúc</label>
            <input type="date" id="ngay_ket_thuc" name="ngay_ket_thuc" value="<?= htmlspecialchars($_POST['ngay_ket_thuc'] ?? $editDiscount['ngay_ket_thuc'] ?? '') ?>" />

            <label for="trang_thai">
                <input type="checkbox" id="trang_thai" name="trang_thai" value="1" <?= ((isset($_POST['trang_thai']) && $_POST['trang_thai']) || (!isset($_POST['trang_thai']) && !empty($editDiscount) && $editDiscount['trang_thai'])) ? 'checked' : '' ?> />
                Đang hoạt động
            </label>

            <button type="submit" class="btn-save"><?= $action === 'add' ? 'Thêm mới' : 'Lưu thay đổi' ?></button>
            <a href="discounts.php" class="btn-add" style="background:#ccc; color:#333; margin-left:10px; text-decoration:none; padding:8px 16px; border-radius:6px;">Hủy</a>
        </form>
    <?php else: ?>
        <a href="discounts.php?action=add" class="btn-add">+ Thêm ưu đãi mới</a>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên ưu đãi</th>
                <th>Mô tả</th>
                <th>Phần trăm giảm (%)</th>
                <th>Ngày bắt đầu</th>
                <th>Ngày kết thúc</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($discounts): ?>
                <?php foreach ($discounts as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['uu_dai_id']) ?></td>
                        <td><?= htmlspecialchars($d['ten_uu_dai']) ?></td>
                        <td><?= htmlspecialchars(mb_strimwidth($d['mo_ta'], 0, 50, '...')) ?></td>
                        <td><?= number_format($d['phan_tram_giam'], 2) ?>%</td>
                        <td><?= htmlspecialchars($d['ngay_bat_dau']) ?></td>
                        <td><?= htmlspecialchars($d['ngay_ket_thuc']) ?></td>
                        <td><?= $d['trang_thai'] ? 'Đang hoạt động' : 'Không hoạt động' ?></td>
                        <td>
                            <a href="discounts.php?action=edit&id=<?= $d['uu_dai_id'] ?>" class="btn-edit" style="margin-right:10px;">Sửa</a>
                            <a href="discounts.php?action=delete&id=<?= $d['uu_dai_id'] ?>" onclick="return confirm('Bạn chắc chắn muốn xóa ưu đãi này?');" class="btn-delete">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;">Không tìm thấy ưu đãi phù hợp</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
