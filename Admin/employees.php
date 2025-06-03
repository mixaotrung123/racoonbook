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

    // Xử lý các hành động thêm, sửa, xóa
    $action = $_GET['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ho_ten = trim($_POST['ho_ten'] ?? '');
        $chuc_vu = trim($_POST['chuc_vu'] ?? '');
        $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $dia_chi = trim($_POST['dia_chi'] ?? '');
        $ngay_bat_dau_lam = $_POST['ngay_bat_dau_lam'] ?? '';
        $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;

        $errors = [];

        if ($ho_ten === '') $errors[] = "Họ tên không được để trống.";
        if ($chuc_vu === '') $errors[] = "Chức vụ không được để trống.";
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
        if ($ngay_bat_dau_lam !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_bat_dau_lam)) $errors[] = "Ngày bắt đầu làm không đúng định dạng YYYY-MM-DD.";

        if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO nhan_vien (ho_ten, chuc_vu, so_dien_thoai, email, dia_chi, ngay_bat_dau_lam, trang_thai) VALUES (:ho_ten, :chuc_vu, :so_dien_thoai, :email, :dia_chi, :ngay_bat_dau_lam, :trang_thai)");
                $stmt->execute([
                    ':ho_ten' => $ho_ten,
                    ':chuc_vu' => $chuc_vu,
                    ':so_dien_thoai' => $so_dien_thoai,
                    ':email' => $email,
                    ':dia_chi' => $dia_chi,
                    ':ngay_bat_dau_lam' => $ngay_bat_dau_lam ?: null,
                    ':trang_thai' => $trang_thai,
                ]);
                header("Location: employees.php");
                exit;
            }
            if ($action === 'edit') {
                $id = (int)($_GET['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE nhan_vien SET ho_ten=:ho_ten, chuc_vu=:chuc_vu, so_dien_thoai=:so_dien_thoai, email=:email, dia_chi=:dia_chi, ngay_bat_dau_lam=:ngay_bat_dau_lam, trang_thai=:trang_thai WHERE nhan_vien_id=:id");
                    $stmt->execute([
                        ':ho_ten' => $ho_ten,
                        ':chuc_vu' => $chuc_vu,
                        ':so_dien_thoai' => $so_dien_thoai,
                        ':email' => $email,
                        ':dia_chi' => $dia_chi,
                        ':ngay_bat_dau_lam' => $ngay_bat_dau_lam ?: null,
                        ':trang_thai' => $trang_thai,
                        ':id' => $id,
                    ]);
                    header("Location: employees.php");
                    exit;
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM nhan_vien WHERE nhan_vien_id=:id");
            $stmt->execute([':id' => $id]);
            header("Location: employees.php");
            exit;
        }
    }

    // Tìm kiếm
    $search_ho_ten = trim($_GET['ho_ten'] ?? '');
    $search_chuc_vu = trim($_GET['chuc_vu'] ?? '');

    $where = [];
    $params = [];

    if ($search_ho_ten !== '') {
        $where[] = "ho_ten LIKE :ho_ten";
        $params[':ho_ten'] = "%$search_ho_ten%";
    }
    if ($search_chuc_vu !== '') {
        $where[] = "chuc_vu LIKE :chuc_vu";
        $params[':chuc_vu'] = "%$search_chuc_vu%";
    }

    $sql = "SELECT * FROM nhan_vien";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY nhan_vien_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy thông tin nhân viên khi sửa
    $editEmp = null;
    if ($action === 'edit') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM nhan_vien WHERE nhan_vien_id=:id");
            $stmt->execute([':id' => $id]);
            $editEmp = $stmt->fetch(PDO::FETCH_ASSOC);
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
<title>Quản lý nhân viên</title>
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
    background-color: #007bff; /* xanh dương */
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
        <h1>Quản lý nhân viên</h1>

        <form id="search-form" method="GET" action="employees.php">
            <input type="text" name="ho_ten" placeholder="Tìm theo họ tên" value="<?= htmlspecialchars($search_ho_ten) ?>" />
            <input type="text" name="chuc_vu" placeholder="Tìm theo chức vụ" value="<?= htmlspecialchars($search_chuc_vu) ?>" />
            <button type="submit">Tìm kiếm</button>
            <?php if ($search_ho_ten !== '' || $search_chuc_vu !== ''): ?>
                <a href="employees.php" style="margin-left:10px; color:#A0522D;">Xóa tìm kiếm</a>
            <?php endif; ?>
        </form>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <form class="form-add-edit" method="POST" action="employees.php?action=<?= $action ?><?= $action === 'edit' ? '&id=' . (int)$_GET['id'] : '' ?>">
                <?php if (!empty($errors)): ?>
                    <div class="errors" style="background:#ffcccc; border:1px solid #cc0000; color:#900; padding:10px; border-radius:6px; margin-bottom:1rem;">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <label for="ho_ten">Họ tên *</label>
                <input type="text" id="ho_ten" name="ho_ten" required value="<?= htmlspecialchars($_POST['ho_ten'] ?? $editEmp['ho_ten'] ?? '') ?>" />

                <label for="chuc_vu">Chức vụ *</label>
                <select id="chuc_vu" name="chuc_vu" required>
                 <?php
                 $positions = ['Nhân viên', 'Quản lý', 'Giám sát'];
                 $selectedPosition = $_POST['chuc_vu'] ?? $editEmp['chuc_vu'] ?? '';
                    ?>
                    <option value="" disabled <?= $selectedPosition === '' ? 'selected' : '' ?>>-- Chọn chức vụ --</option>
                    <?php foreach ($positions as $pos): ?>
                     <option value="<?= $pos ?>" <?= $pos === $selectedPosition ? 'selected' : '' ?>><?= $pos ?></option>
                <?php endforeach; ?>
                        </select>

                <label for="so_dien_thoai">Số điện thoại</label>
                <input type="text" id="so_dien_thoai" name="so_dien_thoai" value="<?= htmlspecialchars($_POST['so_dien_thoai'] ?? $editEmp['so_dien_thoai'] ?? '') ?>" />

                <label for="email">Email</label>
                <input type="text" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $editEmp['email'] ?? '') ?>" />

                <label for="dia_chi">Địa chỉ</label>
                <textarea id="dia_chi" name="dia_chi"><?= htmlspecialchars($_POST['dia_chi'] ?? $editEmp['dia_chi'] ?? '') ?></textarea>

                <label for="ngay_bat_dau_lam">Ngày bắt đầu làm</label>
                <input type="date" id="ngay_bat_dau_lam" name="ngay_bat_dau_lam" 
    value="<?= 
        htmlspecialchars(
            $_POST['ngay_bat_dau_lam'] 
            ?? ($action === 'add' ? date('Y-m-d') : ($editEmp['ngay_bat_dau_lam'] ?? ''))
        ) 
    ?>" 
/>

                <label for="trang_thai">
                    <input type="checkbox" id="trang_thai" name="trang_thai" value="1" <?= ((isset($_POST['trang_thai']) && $_POST['trang_thai']) || (!isset($_POST['trang_thai']) && !empty($editEmp) && $editEmp['trang_thai'])) ? 'checked' : '' ?> />
                    Đang làm việc
                </label>

                <button type="submit" class="btn-save"><?= $action === 'add' ? 'Thêm mới' : 'Lưu thay đổi' ?></button>
                <a href="employees.php" class="btn-add" style="background:#ccc; color:#333; margin-left:10px; text-decoration:none; padding:8px 16px; border-radius:6px;">Hủy</a>
            </form>
        <?php else: ?>
            <a href="employees.php?action=add" class="btn-add">+ Thêm nhân viên mới</a>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Chức vụ</th>
                    <th>Số điện thoại</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th>Ngày bắt đầu làm</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($employees): ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?= htmlspecialchars($emp['nhan_vien_id']) ?></td>
                            <td><?= htmlspecialchars($emp['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($emp['chuc_vu']) ?></td>
                            <td><?= htmlspecialchars($emp['so_dien_thoai']) ?></td>
                            <td><?= htmlspecialchars($emp['email']) ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($emp['dia_chi'], 0, 40, '...')) ?></td>
                            <td><?= htmlspecialchars($emp['ngay_bat_dau_lam']) ?></td>
                            <td><?= $emp['trang_thai'] ? 'Đang làm việc' : 'Nghỉ' ?></td>
                            <td>
                                <a href="categories.php?action=edit&id=<?= $cat['danh_muc_id'] ?>" class="btn-edit">Sửa</a>
                                <a href="employees.php?action=delete&id=<?= $emp['nhan_vien_id'] ?>" onclick="return confirm('Bạn chắc chắn muốn sa thải nhân viên này?');" class="btn-delete">Sa thải</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;">Không tìm thấy nhân viên phù hợp</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
