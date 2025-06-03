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

    $stmt_dm = $pdo->query("SELECT danh_muc_id, ten_danh_muc FROM danh_muc ORDER BY ten_danh_muc");
    $danh_mucs = $stmt_dm->fetchAll(PDO::FETCH_ASSOC);

    $stmt_books = $pdo->query("SELECT ten_sach, so_luong FROM sach");
    $books = $stmt_books->fetchAll(PDO::FETCH_ASSOC);

    $stmt_categories = $pdo->query("SELECT COUNT(*) as category_count FROM danh_muc");
    $category_count = $stmt_categories->fetch(PDO::FETCH_ASSOC)['category_count'] ?? 0;

    $stmt_orders = $pdo->query("SELECT COUNT(*) as order_count FROM don_hang");
    $order_count = $stmt_orders->fetch(PDO::FETCH_ASSOC)['order_count'] ?? 0;

} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

$col = [
    ["label" => "Trang chủ", "route" => "home(ad).php"],
    ["label" => "Quản lý sách", "route" => "books.php"],
    ["label" => "Quản lý danh mục", "route" => "categories.php"],
    ["label" => "Quản lý nhân viên", "route" => "employees.php"],
    ["label" => "Quản lý ưu đãi", "route" => "discounts.php"],
    ["label" => "Báo cáo", "route" => "reports.php"],
    ["label" => "Đăng xuất", "route" => ".\Hethongbansach\login.php"],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Trang quản lý Admin</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: 'Nunito', sans-serif; margin: 0; padding: 0; display: flex; height: 100vh; overflow: hidden; }
    .sidebar { width: 300px; background: #FFE4B5; color: #A0522D; padding: 2rem 1rem; display: flex; flex-direction: column; flex-shrink: 0; overflow-y: auto; }
    .sidebar h1 { font-size: 1.75rem; font-weight: bold; text-align: center; }
    .sidebar .logo-container {
            display: flex;
            gap: 1rem;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }
    .sidebar a { display: block; padding: 0.75rem 1rem; margin: 0.25rem 0; text-decoration: none; color: #CD853F; border-radius: 6px; transition: 0.3s; }
    .sidebar a:hover, .sidebar a.active { background: #A0522D; color: white; font-style: italic; position: relative; }
    .sidebar a.active::after { content: ""; position: absolute; width: 1.5rem; height: 1.5rem; background: white; right: -0.75rem; top: 50%; transform: translateY(-50%) rotate(45deg); }
    main.content { flex-grow: 1; padding: 2rem; overflow-y: auto; background-color: #f8fafc; }
    .chart-wrapper { height: 120px; max-width: 360px; margin: auto; position: relative; }
    .popup-table { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 1rem; border-radius: 8px; border: 1px solid #ccc; box-shadow: 0 0 10px rgba(0,0,0,0.25); max-height: 70vh; max-width: 90vw; overflow-y: auto; z-index: 9999; }
    .popup-table h2 { margin-top: 0; }
    .popup-table .close-btn { background: #A0522D; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; float: right; cursor: pointer; margin-bottom: 1rem; }
    .popup-table table { width: 100%; border-collapse: collapse; }
    .popup-table th, .popup-table td { border: 1px solid #ddd; padding: 8px; }
    .popup-table th { background-color: #f4f4f4; font-weight: 600; }
    .toggle-btn {
  display: flex;
  justify-content: center; /* Căn giữa ngang */
  gap: 10px;                /* Khoảng cách giữa các nút */
  margin-top: 10px;
  margin-bottom: 20px;
}
.toggle-btn button {
  background-color: #A0522D;
  color: white;
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s;
}
.toggle-btn button:hover {
  background-color: #7b3f1a;
}

  </style>
  <script>
    function showPopup(id) { document.getElementById(id).style.display = 'block'; }
    function hidePopup(id) { document.getElementById(id).style.display = 'none'; }
  </script>
</head>
<body>
<div class="sidebar">
        <div class="logo-container">
            <img src="\Hethongbansach\image\book_logo.jpg" height="150" width="150" alt="Logo" />
            <h1>CHÀO MỪNG</h1>
        </div>
  <?php foreach ($col as $item): $isActive = strstr($item['route'], $curPageName); ?>
    <a href="<?= $item['route'] ?>" class="<?= $isActive ? 'active' : '' ?>">
      <?= $isActive ? '- ' : '' ?><?= htmlspecialchars($item['label']) ?>
    </a>
  <?php endforeach; ?>
</div>
<main class="content">
  <h1 class="text-2xl font-bold mb-4">Báo cáo thống kê</h1>
  <div class="chart-container mb-6">
    <div class="chart-header font-semibold mb-2">Số lượng sách</div>
    <div class="chart-wrapper"><canvas id="bookChart"></canvas></div>
   <div class="toggle-btn">
  <button onclick="toggleChart('book')">Đổi biểu đồ</button>
  <button onclick="showPopup('popupBooks')">Xem bảng sách</button>
</div>

  </div>
  <div class="chart-container mb-6">
    <div class="chart-header font-semibold mb-2">Số lượng danh mục</div>
    <div class="chart-wrapper"><canvas id="categoryChart"></canvas></div>
    <div class="toggle-btn">
  <button onclick="toggleChart('category')">Đổi biểu đồ</button>
  <button onclick="showPopup('popupCategory')">Xem bảng danh mục</button>
</div>

  </div>
  <div class="chart-container mb-6">
    <div class="chart-header font-semibold mb-2">Số lượng đơn hàng</div>
    <div class="chart-wrapper"><canvas id="orderChart"></canvas></div>
    <div class="toggle-btn">
  <button onclick="toggleChart('order')">Đổi biểu đồ</button>
  <button onclick="showPopup('popupOrders')">Xem bảng đơn hàng</button>
</div>

  </div>
</main>

<!-- Popup Sách -->
<div id="popupBooks" class="popup-table">
  <button class="close-btn" onclick="hidePopup('popupBooks')">Đóng</button>
  <h2>Danh sách sách</h2>
  <table>
    <thead><tr><th>STT</th><th>Tên sách</th><th>Số lượng</th></tr></thead>
    <tbody>
      <?php foreach ($books as $index => $book): ?>
      <tr>
        <td><?= $index + 1 ?></td>
        <td><?= htmlspecialchars($book['ten_sach']) ?></td>
        <td><?= (int)$book['so_luong'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Popup Danh mục -->
<div id="popupCategory" class="popup-table">
  <button class="close-btn" onclick="hidePopup('popupCategory')">Đóng</button>
  <h2>Danh sách danh mục</h2>
  <table>
    <thead><tr><th>STT</th><th>Tên danh mục</th></tr></thead>
    <tbody>
      <?php foreach ($danh_mucs as $index => $dm): ?>
      <tr>
        <td><?= $index + 1 ?></td>
        <td><?= htmlspecialchars($dm['ten_danh_muc']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Popup Đơn hàng -->
<div id="popupOrders" class="popup-table">
  <button class="close-btn" onclick="hidePopup('popupOrders')">Đóng</button>
  <h2>Tổng đơn hàng</h2>
  <table>
    <thead><tr><th>Số lượng đơn hàng</th></tr></thead>
    <tbody>
      <tr><td><?= $order_count ?></td></tr>
    </tbody>
  </table>
</div>
<script>
let chartTypes = {
  book: 'bar',
  category: 'bar',
  order: 'bar'
};
let charts = {};

function renderChart(id, labels, data, label, color) {
  const ctx = document.getElementById(id + 'Chart').getContext('2d');
  if (charts[id]) charts[id].destroy();
  charts[id] = new Chart(ctx, {
    type: chartTypes[id],
    data: {
      labels: labels,
      datasets: [{ label, data, backgroundColor: color, borderWidth: 1 }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: chartTypes[id] === 'bar' ? { y: { beginAtZero: true } } : {}
    }
  });
}

function toggleChart(id) {
  chartTypes[id] = chartTypes[id] === 'bar' ? 'pie' : 'bar';
  if (id === 'book') renderChart('book', bookLabels, bookValues, 'Số lượng sách', '#f87171');
  else if (id === 'category') renderChart('category', ['Tổng danh mục'], [<?= $category_count ?>], 'Số danh mục', '#facc15');
  else if (id === 'order') renderChart('order', ['Tổng đơn hàng'], [<?= $order_count ?>], 'Đơn hàng', '#34d399');
}

const bookLabels = <?= json_encode(array_column($books, 'ten_sach')) ?>;
const bookValues = <?= json_encode(array_map('intval', array_column($books, 'so_luong'))) ?>;

renderChart('book', bookLabels, bookValues, 'Số lượng sách', '#f87171');
renderChart('category', ['Tổng danh mục'], [<?= $category_count ?>], 'Số danh mục', '#facc15');
renderChart('order', ['Tổng đơn hàng'], [<?= $order_count ?>], 'Đơn hàng', '#34d399');
</script>

</body>
</html>