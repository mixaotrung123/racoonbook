<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['quyen'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$curPageName = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1);

$host = 'localhost';
$dbname = 'hethongbansach';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lấy danh mục để đổ dropdown tìm kiếm
    $stmt_dm = $pdo->query("SELECT danh_muc_id, ten_danh_muc FROM danh_muc ORDER BY ten_danh_muc");
    $danh_mucs = $stmt_dm->fetchAll(PDO::FETCH_ASSOC);

    // Xử lý tìm kiếm
    $where = [];
    $params = [];

    if (!empty($_GET['ten_sach'])) {
        $where[] = "s.ten_sach LIKE :ten_sach";
        $params[':ten_sach'] = '%' . $_GET['ten_sach'] . '%';
    }
    if (!empty($_GET['tac_gia'])) {
        $where[] = "s.tac_gia LIKE :tac_gia";
        $params[':tac_gia'] = '%' . $_GET['tac_gia'] . '%';
    }
    if (!empty($_GET['danh_muc_id']) && $_GET['danh_muc_id'] !== '0') {
        $where[] = "s.danh_muc_id = :danh_muc_id";
        $params[':danh_muc_id'] = (int)$_GET['danh_muc_id'];
    }
    if (isset($_GET['gia_min']) && $_GET['gia_min'] !== '') {
        $where[] = "s.gia >= :gia_min";
        $params[':gia_min'] = (float)$_GET['gia_min'];
    }
    if (isset($_GET['gia_max']) && $_GET['gia_max'] !== '') {
        $where[] = "s.gia <= :gia_max";
        $params[':gia_max'] = (float)$_GET['gia_max'];
    }

    $sql = "
        SELECT s.*, u.ten_uu_dai, d.ten_danh_muc
        FROM sach s
        LEFT JOIN uu_dai u ON s.uu_dai_id = u.uu_dai_id
        LEFT JOIN danh_muc d ON s.danh_muc_id = d.danh_muc_id
    ";

    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY s.sach_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// Hàm chuyển đường dẫn Windows-style \ thành URL chuẩn web dùng /
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
    <title>Quản lý sách</title>
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
        td.mo_ta {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-add {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 8px 16px;
            background-color: #A0522D;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-add:hover {
            background-color: #7B3F1A;
        }
        /* Form tìm kiếm */
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
        form#search-form input[type="text"],
        form#search-form select,
        form#search-form input[type="number"] {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            min-width: 150px;
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
        td.img-col img {
            max-width: 80px;
            max-height: 100px;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
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
        <h1>Quản lý sách</h1>
        <a href="/Hethongbansach/Admin/add_books.php" class="btn-add">+ Thêm sách mới</a>

        <form id="search-form" method="GET" action="books.php">
            <input type="text" name="ten_sach" placeholder="Tìm theo tên sách" value="<?= htmlspecialchars($_GET['ten_sach'] ?? '') ?>" />
            <input type="text" name="tac_gia" placeholder="Tìm theo tác giả" value="<?= htmlspecialchars($_GET['tac_gia'] ?? '') ?>" />
            <select name="danh_muc_id">
                <option value="0">-- Chọn danh mục --</option>
                <?php foreach ($danh_mucs as $dm): ?>
                    <option value="<?= $dm['danh_muc_id'] ?>" <?= (isset($_GET['danh_muc_id']) && $_GET['danh_muc_id'] == $dm['danh_muc_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dm['ten_danh_muc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="gia_min" placeholder="Giá từ" min="0" value="<?= htmlspecialchars($_GET['gia_min'] ?? '') ?>" />
            <input type="number" name="gia_max" placeholder="Giá đến" min="0" value="<?= htmlspecialchars($_GET['gia_max'] ?? '') ?>" />
            <button type="submit">Tìm kiếm</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ảnh</th>
                    <th>Tên sách</th>
                    <th>Tác giả</th>
                    <th>Nhà xuất bản</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Mô tả</th>
                    <th>Ưu đãi</th>
                    <th>Danh mục</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($books): ?>
                    <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= htmlspecialchars($book['sach_id']) ?></td>
                        <td class="img-col">
                            <?php if (!empty($book['img'])): ?>
                                <?php
                                    $img_url = str_replace('\\', '/', $book['img']);
                                ?>
                                <img src="<?= htmlspecialchars($img_url) ?>" alt="Ảnh sách" />
                            <?php else: ?>
                                Không có ảnh
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($book['ten_sach']) ?></td>
                        <td><?= htmlspecialchars($book['tac_gia']) ?></td>
                        <td><?= htmlspecialchars($book['nha_xuat_ban']) ?></td>
                        <td><?= number_format($book['gia'], 2, ',', '.') ?>₫</td>
                        <td><?= (int)$book['so_luong'] ?></td>
                        <td class="mo_ta" title="<?= htmlspecialchars($book['mo_ta']) ?>"><?= htmlspecialchars($book['mo_ta']) ?></td>
                        <td><?= htmlspecialchars($book['ten_uu_dai'] ?? 'Không') ?></td>
                        <td><?= htmlspecialchars($book['ten_danh_muc'] ?? 'Không') ?></td>
                        <td>
                            <a href="/Hethongbansach/Admin/edit_books.php?id=<?= $book['sach_id'] ?>" class="btn-edit" style="margin-right:10px;">Sửa</a>
                            <a href="/Hethongbansach/Admin/delete_books.php?id=<?= $book['sach_id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa sách này?');" class="btn-delete">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="text-align:center;">Không tìm thấy sách phù hợp</td></tr>
                <?php endif; ?>
            </tbody>    
        </table>
    </main>

</body>
</html>
