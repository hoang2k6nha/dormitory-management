<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Xử lý Thêm Phòng Mới
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'them_phong') {
    $khu_nha = $_POST['khu_nha'] ?? '';
    $so_phong = $_POST['so_phong'] ?? '';
    $loai_phong = $_POST['loai_phong'] ?? 'Thuong';
    $so_giuong = $_POST['so_giuong'] ?? 4;
    $gia_phong = $_POST['gia_phong'] ?? 0;

    if (!empty($khu_nha) && !empty($so_phong)) {
        try {
            $stmt = $conn->prepare("INSERT INTO Phong (khu_nha, so_phong, loai_phong, so_giuong_toi_da, gia_phong, trang_thai) VALUES (?, ?, ?, ?, ?, 'Trong')");
            $stmt->execute([$khu_nha, $so_phong, $loai_phong, $so_giuong, $gia_phong]);
            $phong_id = $conn->lastInsertId();
            
            // Tự động thêm thiết bị mặc định cho phòng mới
            $so_giuong_tang = ceil($so_giuong / 2);
            $conn->prepare("INSERT INTO ThietBi (phong_id, ten_thiet_bi, so_luong, tinh_trang) VALUES (?, 'Giường tầng', ?, 'Tot')")->execute([$phong_id, $so_giuong_tang]);
            $conn->prepare("INSERT INTO ThietBi (phong_id, ten_thiet_bi, so_luong, tinh_trang) VALUES (?, 'Tủ cá nhân', ?, 'Tot')")->execute([$phong_id, $so_giuong]);
            $conn->prepare("INSERT INTO ThietBi (phong_id, ten_thiet_bi, so_luong, tinh_trang) VALUES (?, 'Quạt trần', 2, 'Tot')")->execute([$phong_id]);
            $conn->prepare("INSERT INTO ThietBi (phong_id, ten_thiet_bi, so_luong, tinh_trang) VALUES (?, 'Đèn tuýp LED', 2, 'Tot')")->execute([$phong_id]);
            
            $thong_bao = "Thêm phòng $khu_nha - $so_phong và cấp phát thiết bị thành công!";
        } catch (PDOException $e) {
            $loi = "Lỗi khi thêm phòng (Có thể phòng đã tồn tại): " . $e->getMessage();
        }
    } else {
        $loi = "Vui lòng nhập đầy đủ thông tin phòng.";
    }
}

// Xử lý Xóa/Cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    if ($_POST['action'] == 'xoa') {
        try {
            $conn->prepare("DELETE FROM Phong WHERE id = ?")->execute([$id]);
            $thong_bao = "Đã xóa phòng thành công!";
        } catch (PDOException $e) {
            $loi = "Không thể xóa phòng này (Có thể đang có người ở).";
        }
    } elseif ($_POST['action'] == 'baotri') {
        $conn->prepare("UPDATE Phong SET trang_thai = 'DangBaoTri' WHERE id = ?")->execute([$id]);
        $thong_bao = "Đã chuyển phòng sang trạng thái bảo trì!";
    } elseif ($_POST['action'] == 'trong') {
        $conn->prepare("UPDATE Phong SET trang_thai = 'Trong' WHERE id = ?")->execute([$id]);
        $thong_bao = "Đã chuyển phòng sang trạng thái trống!";
    }
}

$tu_khoa_phong = $_GET['q_phong'] ?? '';
$sql_phong = "SELECT p.*, (SELECT COUNT(*) FROM HopDong h WHERE h.phong_id = p.id AND h.trang_thai = 'HieuLuc') as so_nguoi_dang_o FROM Phong p ";
if (!empty($tu_khoa_phong)) {
    $sql_phong .= " WHERE p.khu_nha LIKE :q OR p.so_phong LIKE :q ";
}
$sql_phong .= " ORDER BY khu_nha, so_phong";
$stmt = $conn->prepare($sql_phong);
if (!empty($tu_khoa_phong)) {
    $stmt->execute([':q' => "%$tu_khoa_phong%"]);
} else {
    $stmt->execute();
}
$danh_sach_phong = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Phòng - KTX Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
        <script>
        // Khôi phục Dark Mode nếu đã bật
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body class="admin-theme">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-building" style="background: rgba(255,255,255,0.2); padding: 8px; border-radius: 8px;"></i> 
                <div>
                    <div style="font-size: 0.85rem; font-weight: 600;">QUẢN LÝ</div>
                    <div style="font-size: 1.1rem; line-height: 1;">KÝ TÚC XÁ</div>
                </div>
            </div>
            
            <div class="sidebar-section">ADMIN</div>
            
                        <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-home" style="width:20px;"></i> Dashboard</a>
                <a href="quan_ly_phong.php" class="nav-item active"><i class="fas fa-folder-open" style="width:20px;"></i> Quản lý Phòng</a>
                <a href="quan_ly_duyet_phong.php" class="nav-item"><i class="fas fa-check-square" style="width:20px;"></i> Duyệt ĐK & Chuyển</a>
                <a href="quan_ly_sinh_vien.php" class="nav-item"><i class="fas fa-users" style="width:20px;"></i> Sinh viên & HĐ</a>
                <a href="quan_ly_dich_vu.php" class="nav-item"><i class="fas fa-bolt" style="width:20px;"></i> Điện Nước</a>
                <a href="quan_ly_hoa_don.php" class="nav-item"><i class="fas fa-file-invoice-dollar" style="width:20px;"></i> Hóa đơn</a>
                <a href="quan_ly_thiet_bi.php" class="nav-item"><i class="fas fa-desktop" style="width:20px;"></i> Thiết bị</a>
                <a href="ho_tro.php" class="nav-item"><i class="fas fa-exclamation-triangle" style="width:20px;"></i> Hỗ trợ & Phạt</a>
                <a href="thong_bao.php" class="nav-item"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
                <a href="bao_cao.php" class="nav-item"><i class="fas fa-chart-bar" style="width:20px;"></i> Báo cáo</a>
                <a href="cai_dat.php" class="nav-item"><i class="fas fa-cog" style="width:20px;"></i> Cài đặt</a>
                <a href="../logout.php" class="nav-item mt-4"><i class="fas fa-sign-out-alt" style="width:20px;"></i> Đăng xuất</a>
            </nav>
            
            <div class="user-profile-sidebar">
                <img src="https://ui-avatars.com/api/?name=Admin&background=ffffff&color=154388" alt="Admin" style="width:40px; height:40px; border-radius:50%;">
                <div>
                    <div style="font-weight: 600; font-size: 0.875rem;">Admin</div>
                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.7);">Quản trị hệ thống</div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Quản Lý Phòng Ở</div>
                
                <div style="position: relative; display: none;" class="d-md-block">
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" class="search-bar" placeholder="Tìm kiếm nhanh..." style="padding-left: 2.5rem; background: #f8fafc;">
                </div>
                
                <div class="topbar-icons">
                <div style="position: relative; display: flex; align-items: center;" id="notificationBellContainer">
                    <a href="javascript:void(0)" onclick="toggleNotificationDropdown()" style="color: inherit; text-decoration: none;" title="Xem thông báo">
                        <i class="fas fa-bell" style="position: relative; font-size: 1.1rem;">
                            <?php 
                            $latest_tb_id = $conn->query("SELECT MAX(id) FROM ThongBao")->fetchColumn() ?: 0;
                            if (!isset($_COOKIE['last_viewed_tb_id']) || $_COOKIE['last_viewed_tb_id'] < $latest_tb_id): 
                            ?>
                            <span id="notificationDot" style="position:absolute; top:-2px; right:-2px; background:var(--danger); width:8px; height:8px; border-radius:50%;"></span>
                            <?php endif; ?>
                        </i>
                    </a>
                    <!-- Dropdown Mess-style -->
                    <div id="notificationDropdown" style="display: none; position: absolute; right: -10px; top: 35px; width: 320px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 1000; text-align: left;">
                        <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #111827;">Thông báo mới</span>
                            <a href="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'thong_bao.php' : '../user/user_thong_bao.php'; ?>" style="font-size: 0.8rem; color: #3b82f6; text-decoration: none;">Xem tất cả</a>
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php
                            $top_tbs = $conn->query("SELECT * FROM ThongBao ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                            if (count($top_tbs) > 0) {
                                foreach($top_tbs as $tb) {
                                    echo '<div style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; cursor: pointer;" onmouseover="this.style.backgroundColor=\'#f9fafb\'" onmouseout="this.style.backgroundColor=\'white\'">';
                                    echo '<div style="font-size: 0.85rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem;">' . htmlspecialchars($tb['tieu_de']) . '</div>';
                                    echo '<div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . htmlspecialchars($tb['noi_dung']) . '</div>';
                                    echo '<div style="font-size: 0.7rem; color: #9ca3af;">' . date('d/m/Y H:i', strtotime($tb['ngay_dang'])) . '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div style="padding: 1rem; text-align: center; font-size: 0.85rem; color: #6b7280;">Không có thông báo nào</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <span style="font-size: 0.9rem; font-weight: 500; margin-left: 0.5rem; margin-right: 0.5rem; color: var(--text-muted);" title="Hôm nay"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></span>
                <img src="https://ui-avatars.com/api/?name=Admin&background=154388&color=fff" alt="User" style="width:36px; height:36px; border-radius:50%; margin-left:0.5rem;">
                </div>
            </header>
            <div class="content-area">

                <?php if (isset($thong_bao)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($thong_bao); ?></div>
                <?php endif; ?>
                <?php if (isset($loi)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-3">
                    <div class="card" style="grid-column: span 1;">
                        <div class="card-header">
                            <h3 style="margin:0; font-size: 1.1rem;"><i class="fas fa-plus-circle text-primary"></i> Thêm Phòng Mới</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="quan_ly_phong.php">
                                <input type="hidden" name="action" value="them_phong">
                                <div class="form-group">
                                    <label class="form-label">Khu Nhà</label>
                                    <input type="text" name="khu_nha" class="form-control" placeholder="VD: Khu A" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Số Phòng</label>
                                    <input type="text" name="so_phong" class="form-control" placeholder="VD: 101" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Loại Phòng</label>
                                    <select name="loai_phong" class="form-control">
                                        <option value="Thuong">Thường</option>
                                        <option value="VIP">VIP</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Số Giường Tối Đa</label>
                                    <input type="number" name="so_giuong" class="form-control" value="4" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Giá Phòng (VNĐ/Tháng)</label>
                                    <input type="number" name="gia_phong" class="form-control" value="1200000" min="0" required>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Thêm Phòng</button>
                            </form>
                        </div>
                    </div>

                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: 1rem;">
                            <h3 style="margin:0; font-size: 1.1rem;"><i class="fas fa-list text-primary"></i> Danh sách phòng</h3>
                            
                            <form method="GET" action="quan_ly_phong.php" style="display:flex; gap: 0.5rem; margin:0;">
                                <input type="text" name="q_phong" class="form-control" placeholder="Tìm theo Khu nhà, Số phòng..." value="<?php echo htmlspecialchars($tu_khoa_phong); ?>" style="width: 220px; padding: 0.4rem 0.75rem;">
                                <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 1rem;"><i class="fas fa-search"></i> Tìm</button>
                                <?php if($tu_khoa_phong): ?>
                                    <a href="quan_ly_phong.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Xóa</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Phòng</th>
                                            <th>Loại</th>
                                            <th>Chỗ Trống</th>
                                            <th>Giá (VNĐ)</th>
                                            <th>Trạng Thái</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($danh_sach_phong) == 0): ?>
                                            <tr><td colspan="6" class="text-center text-muted-light">Chưa có dữ liệu phòng.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($danh_sach_phong as $phong): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($phong['khu_nha'] . ' - ' . $phong['so_phong']); ?></td>
                                                <td><span class="badge <?php echo $phong['loai_phong'] == 'VIP' ? 'badge-warning' : 'badge-primary'; ?>"><?php echo $phong['loai_phong']; ?></span></td>
                                                <td>
                                                    <?php 
                                                        $so_cho_trong = $phong['so_giuong_toi_da'] - $phong['so_nguoi_dang_o'];
                                                        echo $phong['so_nguoi_dang_o'] . '/' . $phong['so_giuong_toi_da']; 
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($phong['gia_phong']); ?></td>
                                                <td>
                                                    <?php 
                                                        if ($phong['trang_thai'] == 'DangBaoTri') echo '<span class="badge badge-warning">Bảo trì</span>';
                                                        elseif ($phong['so_nguoi_dang_o'] == 0) echo '<span class="badge badge-success">Phòng trống</span>';
                                                        elseif ($so_cho_trong > 0) echo '<span class="badge badge-primary">Đang ở</span>';
                                                        else echo '<span class="badge badge-danger">Đã đầy</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <div style="display: flex; gap: 0.25rem;">
                                                        <?php if ($phong['trang_thai'] != 'DangBaoTri'): ?>
                                                            <form method="POST" action="quan_ly_phong.php" style="margin:0; padding:0; display:inline;">
                                                                <input type="hidden" name="action" value="baotri">
                                                                <input type="hidden" name="id" value="<?php echo $phong['id']; ?>">
                                                                <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Bảo trì"><i class="fas fa-tools"></i></button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" action="quan_ly_phong.php" style="margin:0; padding:0; display:inline;">
                                                                <input type="hidden" name="action" value="trong">
                                                                <input type="hidden" name="id" value="<?php echo $phong['id']; ?>">
                                                                <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: var(--success); border-color: var(--success);" title="Mở lại"><i class="fas fa-check"></i></button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" action="quan_ly_phong.php" style="margin:0; padding:0; display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng này?');">
                                                            <input type="hidden" name="action" value="xoa">
                                                            <input type="hidden" name="id" value="<?php echo $phong['id']; ?>">
                                                            <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: var(--danger); border-color: var(--danger);" title="Xóa"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                </main>
        
        <!-- Bottom Nav for Mobile -->
        <nav class="bottom-nav">
            <a href="admin_dashboard.php" class="bottom-nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="quan_ly_phong.php" class="bottom-nav-item active">
                <i class="fas fa-folder"></i>
                <span>Quản lý</span>
            </a>
            <a href="thong_bao.php" class="bottom-nav-item">
                <i class="fas fa-bell"></i>
                <span>Thông báo</span>
            </a>
            <a href="cai_dat.php" class="bottom-nav-item">
                <i class="fas fa-cog"></i>
                <span>Cài đặt</span>
            </a>
        </nav>
    </div>

<script>
function toggleNotificationDropdown() {
    var dropdown = document.getElementById('notificationDropdown');
    var dot = document.getElementById('notificationDot');
    
    if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        dropdown.style.display = 'block';
        if (dot) dot.style.display = 'none';
        document.cookie = "last_viewed_tb_id=<?php echo $latest_tb_id ?? 0; ?>; path=/; max-age=" + (30*24*60*60);
    } else {
        dropdown.style.display = 'none';
    }
}

document.addEventListener('click', function(event) {
    var container = document.getElementById('notificationBellContainer');
    var dropdown = document.getElementById('notificationDropdown');
    if (container && !container.contains(event.target)) {
        if(dropdown) dropdown.style.display = 'none';
    }
});
</script>
</body>
</html>
