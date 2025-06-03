<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['quyen'] !== 'user') {
  header('Location: /Hethongbansach/login.php');
  exit;
}
$tenNguoiDung = $_SESSION['user']['ho_ten'] ?? 'Người dùng';

// Kết nối database
try {
  $pdo = new PDO("mysql:host=localhost;dbname=hethongbansach;charset=utf8", "root", "");
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Lỗi kết nối DB: " . $e->getMessage());
}

// Lấy 10 sách mới nhất
$stmt = $pdo->query("SELECT * FROM sach ORDER BY sach_id DESC LIMIT 10");
$sach_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>Trang người dùng</title>
  <style>
    /* Giữ nguyên toàn bộ CSS của bạn */
    body {
      font-family: 'Nunito', sans-serif;
      margin: 0;
      background-color: #f9fafb;
    }
    header {
      background-color: #FFE4B5;
      padding: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      position: relative;
    }
    .logo-title {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }
    .logo-title strong {
      font-size: 1.3rem;
      color: #8B4513;
    }
    .menu-dropdown {
      position: relative;
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .menu-icon {
      background-color: #A0522D;
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .menu-dropdown:hover .submenu-content {
      display: flex !important;
    }
    .submenu-content {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 8px;
      width: 1000px;
      max-width: 90vw;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      z-index: 9999;
    }
    .menu-panel {
      width: 160px;
      background-color: #fff9e6;
      border-right: 1px solid #ddd;
    }
    .menu-panel a {
      display: block;
      padding: 10px 14px;
      font-size: 14px;
      font-weight: bold;
      color: #5c4033;
      text-decoration: none;
      transition: background-color 0.2s;
    }
    .menu-panel a:hover {
      background-color: #f1d9b5;
    }
    .submenu-wrapper {
      flex: 1;
      display: none;
      flex-wrap: wrap;
      gap: 16px;
      padding: 20px;
      background-color: #fff;
    }
    .submenu-wrapper div {
      flex: 1 1 200px;
    }
    .submenu-wrapper a {
      display: block;
      padding: 4px 0;
      text-decoration: none;
      color: #333;
    }
    .submenu-wrapper div > div {
      font-weight: bold;
      margin-bottom: 6px;
      font-size: 15px;
    }
    .search-box {
      display: flex;
      align-items: center;
      max-width: 600px;
      height: 50px;
      margin-left: 1rem;
    }
    .search-box input {
      flex: 1;
      padding: 0 10px;
      border: 1px solid #ccc;
      border-radius: 6px 0 0 6px;
      height: 100%;
      font-size: 16px;
      box-sizing: border-box;
      max-width: 800px;
      width: 1000px;
      min-width: 0;
    }
    .search-box button {
      background-color: #A0522D;
      color: white;
      border: none;
      width: 50px;
      border-radius: 0 6px 6px 0;
      cursor: pointer;
      height: 100%;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .category-bar {
      background-color: #fff;
      border-radius: 12px;
      margin: 20px auto;
      max-width: 1200px;
      padding: 16px 24px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .category-bar h3 {
      font-size: 18px;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 16px;
      color: #333;
    }
    .category-bar .category-list {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-start;
      gap: 24px;
    }
    .category-bar .category-item {
      text-align: center;
      width: 100px;
      flex-shrink: 0;
    }
    .category-bar .category-item a {
      text-decoration: none;
      display: block;
      color: #333;
    }
    .category-bar .category-item img {
      width: 100%;
      max-width: 80px;
      height: auto;
      margin-bottom: 8px;
      border-radius: 8px;
      transition: transform 0.2s;
    }
    .category-bar .category-item img:hover {
      transform: scale(1.05);
    }
    .category-bar .category-item span {
      font-size: 14px;
      color: #333;
      display: block;
      margin-top: 4px;
    }
    .suggestion-bar {
      max-width: 1200px;
      margin: 20px auto;
      padding: 1rem 1.5rem;
      background-color: #e6f7e6;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .suggestion-bar h3 {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 1rem;
      color: #1a7f1a;
      text-align: center;
    }
    .suggestion-list {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      justify-content: flex-start;
    }
    .suggestion-item {
      width: 120px;
      text-decoration: none;
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: transform 0.2s;
    }
    .suggestion-item:hover {
      transform: scale(1.05);
    }
    .suggestion-item img {
      width: 100%;
      height: auto;
      border-radius: 6px;
      margin-bottom: 0.5rem;
    }
    .book-title {
      font-size: 14px;
      font-weight: 600;
      text-align: center;
      margin-bottom: 0.3rem;
    }
    .price {
      font-size: 13px;
      color: #d64545;
      font-weight: 600;
    }
    .discount {
      background-color: #d64545;
      color: white;
      padding: 0 4px;
      margin-left: 4px;
      border-radius: 4px;
      font-size: 11px;
    }
  </style>
</head>
<body>
<header>
  <a href="home.php" class="logo-title">
    <img src="/Hethongbansach/image/book_logo.jpg" height="48" />
    <strong>RACOON BOOKS</strong>
  </a>
  <div class="menu-dropdown">
    <button class="menu-icon">📚 Danh mục</button>
    <form action="timkiem.php" method="get" class="search-box">
      <input type="text" name="q" placeholder="Tìm sách, danh mục...">
      <button type="submit">🔍</button>
    </form>
    <div class="submenu-content">
      <div class="menu-panel">
        <a href="#" data-category="vietnam">Sách trong nước</a>
        <a href="#" data-category="foreign">Sách nước ngoài</a>
        <a href="#" data-category="vpp">VPP</a>
      </div>
      <div class="submenu-wrapper" data-category="vietnam">
        <div><div>Văn học</div><a href="#">Tiểu thuyết</a><a href="#">Truyện ngắn</a><a href="#">Ngôn tình</a></div>
        <div><div>Kinh tế</div><a href="#">Nhân vật - bài học kinh doanh</a><a href="#">Marketing</a><a href="#">Quản trị - lãnh đạo</a><a href="#">Phân tích kinh tế</a></div>
        <div><div>Thể thao</div><a href="#">Thể Thao</a></div>
        <div><div>Tâm lý - Kỹ năng</div><a href="#">Kỹ năng sống</a><a href="#">Tâm lý</a><a href="#">Sách cho tuổi mới lớn</a></div>
        <div><div>Sách thiếu nhi</div><a href="#">Manga - Comic</a><a href="#">Kiến thức bách khoa</a><a href="#">Sách tranh</a></div>
        <div><div>Sách giáo khoa</div><a href="#">Sách giáo khoa</a><a href="#">Sách tham khảo</a><a href="#">Luyện thi THPT Quốc gia</a></div>
        <div><div>Sách ngoại ngữ</div><a href="#">Tiếng Anh</a><a href="#">Tiếng Hàn</a><a href="#">Tiếng Nhật</a><a href="#">Tiếng Hoa</a></div>
      </div>
      <div class="submenu-wrapper" data-category="foreign">
        <div><div>Fiction</div><a href="#">Contemporary Fiction</a><a href="#">Romance</a><a href="#">Fantasy</a><a href="#">Classics</a></div>
        <div><div>Bussiness & Management</div><a href="#">Bussiness & Management</a><a href="#">Economics</a><a href="#">Finance & Accouting</a></div>
        <div><div>Personal Development</div><a href="#">Popular Psychology</a></div>
        <div><div>Childrent's Books</div><a href="#">Picture & Activity books</a><a href="#">Education</a><a href="#">Fiction & Non-Fiction</a></div>
        <div><div>Dictionary & Language</div><a href="#">ELT</a><a href="#">Dictionaries</a></div>
      </div>
      <div class="submenu-wrapper" data-category="vpp">
        <div><div>Bút - Viết</div><a href="#">Bút Bi - Ruột Bút Bi</a><a href="#">Bút Gel - Bút Nước</a><a href="#">Bút Mực - Bút Máy</a><a href="#">Bút Dạ Quang</a><a href="#">Bút Chì - Ruột Bút Chì</a></div>
        <div><div>Sản phẩm về giấy</div><a href="#">Tập - Vở</a><a href="#">Sổ Tay</a><a href="#">Giấy Photo</a><a href="#">Giấy Note</a></div>
        <div><div>Dụng cụ học sinh</div><a href="#">Gôm - Tẩy</a><a href="#">Gọt Bút Chì</a><a href="#">Thước</a><a href="#">Bóp Viết - Hộp Bút</a><a href="#">Bộ Dụng Cụ Học Tập</a></div>
      </div>
    </div>
  </div>
  <div style="display: flex; gap: 1rem; align-items: center;">
    <form action="thongbao.php" method="get">
      <button type="submit" style="background-color: #A0522D; color: white; border: none; padding: 6px 12px; border-radius: 4px;">🔔 Thông báo</button>
    </form>
    <form action="giohang.php" method="get">
      <button type="submit" style="background-color: #A0522D; color: white; border: none; padding: 6px 12px; border-radius: 4px;">🛒 Giỏ hàng</button>
    </form>
    <form action="taikhoan.php" method="get">
      <button type="submit" style="background-color: #A0522D; color: white; border: none; padding: 6px 12px; border-radius: 4px;">👤 Tài khoản</button>
    </form>
  </div>
</header>
<main style="padding: 2rem;">
  <div class="category-bar">
    <h3>📂 Best Seller</h3>
    <div class="category-list">
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/bo_mau_ve1.webp" alt="Màu Vẽ" />
          <span>Bộ Màu Vẽ</span>
        </a>
      </div>
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/3900000245517.webp" alt="Bản Đồ" />
          <span>Bản Đồ</span>
        </a>
      </div>
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/8934974182375.webp" alt="Văn học" />
          <span>Sách Văn Học</span>
        </a>
      </div>
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/bup-sen-xanh-100x100.webp" alt="Thiếu nhi" />
          <span>Sách Thiếu Nhi</span>
        </a>
      </div>
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/hsk100x100.webp" alt="Ngoại ngữ" />
          <span>Sách Ngoại Ngữ</span>
        </a>
      </div>
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/34884-pisen-pro-eva-desktop-magstation-kf25-eva-red-asuka-philong.webp" alt="Thiết bị số" />
          <span>Thiết Bị Số</span>
        </a>
      </div>
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/8935244874389.webp" alt="Lịch sử" />
          <span>Lịch sử Việt Nam</span>
        </a>
      </div>
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/ngoai-van-t1-24(1).webp" alt="Ngoại văn" />
          <span>Ngoại Văn</span>
        </a>
      </div>
      <div class="category-item">
        <a href="">
          <img src="/Hethongbansach/image/atomichabit100x100.webp" alt="Tâm lý" />
          <span>Tâm lý</span>
        </a>
      </div>
    </div>
  </div>
  <div class="suggestion-bar">
    <h3>⭐ Gợi ý cho bạn</h3>
    <div class="suggestion-list">
      <?php
// Kết nối DB
try {
  $pdo = new PDO("mysql:host=localhost;dbname=hethongbansach;charset=utf8", "root", "");
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Lỗi kết nối DB: " . $e->getMessage());
}
// Lấy 10 sách mới nhất
$stmt = $pdo->query("SELECT * FROM sach ORDER BY sach_id DESC LIMIT 10");
$sach_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php foreach ($sach_list as $sach): ?>
  <a href="sanpham.php?id=<?= htmlspecialchars($sach['sach_id']) ?>" class="suggestion-item">
    <img src="<?= htmlspecialchars(trim($sach['img'])) ?>" alt="<?= htmlspecialchars(trim($sach['ten_sach'])) ?>" />
    <div class="book-title"><?= htmlspecialchars(trim($sach['ten_sach'])) ?></div>
    <div class="price">
      <?= number_format($sach['gia']) ?> đ
      <?php if (!empty($sach['giam_gia']) && $sach['giam_gia'] > 0): ?>
        <span class="discount">-<?= htmlspecialchars($sach['giam_gia']) ?>%</span>
      <?php endif; ?>
    </div>
  </a>
<?php endforeach; ?>
    </div>
  </div>
</main>
<script>
  document.querySelectorAll('.menu-panel a').forEach(link => {
    link.addEventListener('mouseenter', () => {
      const category = link.getAttribute('data-category');
      document.querySelectorAll('.submenu-wrapper').forEach(sub => {
        sub.style.display = sub.dataset.category === category ? 'flex' : 'none';
      });
    });
  });
</script>
</body>
</html>
