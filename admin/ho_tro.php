<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Xử lý POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['ticket_id'])) {
        $ticket_id = $_POST['ticket_id'];
        $trang_thai = $_POST['trang_thai'];
        try {
            $conn->prepare("UPDATE HoTro SET trang_thai = ? WHERE id = ?")->execute([$trang_thai, $ticket_id]);
            $thong_bao = "Cập nhật tiến độ xử lý thành công!";
        } catch (PDOException $e) {
            $loi = "Lỗi cập nhật: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'phat') {
        $sinh_vien_id = $_POST['sinh_vien_id'];
        $noi_dung = $_POST['noi_dung'];
        $hinh_thuc = $_POST['hinh_thuc'];
        $tien_phat = $_POST['tien_phat'] ?: 0;
        $ngay_vi_pham = date('Y-m-d');
        
        try {
            $conn->beginTransaction();
            $conn->prepare("INSERT INTO ViPham (sinh_vien_id, noi_dung_vi_pham, hinh_thuc_ky_luat, tien_phat, ngay_vi_pham) VALUES (?, ?, ?, ?, ?)")->execute([$sinh_vien_id, $noi_dung, $hinh_thuc, $tien_phat, $ngay_vi_pham]);
            
            if ($tien_phat > 0) {
                $thang = date('m');
                $nam = date('Y');
                $conn->prepare("INSERT INTO HoaDon (sinh_vien_id, loai_hoa_don, so_tien, noi_dung, thang, nam, trang_thai) VALUES (?, 'Phat', ?, ?, ?, ?, 'ChuaThanhToan')")->execute([$sinh_vien_id, $tien_phat, "Phạt: " . $noi_dung, $thang, $nam]);
            }
            $conn->commit();
            $thong_bao = "Ghi nhận vi phạm thành công!";
        } catch (PDOException $e) {
            $conn->rollBack();
            $loi = "Lỗi ghi nhận vi phạm: " . $e->getMessage();
        }
    }
}

// Lấy danh sách ticket
$tu_khoa_ht = $_GET['q_ht'] ?? '';
$sql_ht = "SELECT h.*, s.ho_ten, s.mssv, p.khu_nha, p.so_phong 
           FROM HoTro h 
           JOIN SinhVien s ON h.sinh_vien_id = s.id 
           LEFT JOIN HopDong hd ON s.id = hd.sinh_vien_id AND hd.trang_thai = 'HieuLuc' 
           LEFT JOIN Phong p ON hd.phong_id = p.id ";
if (!empty($tu_khoa_ht)) {
    $sql_ht .= " WHERE h.id LIKE :q OR s.ho_ten LIKE :q OR s.mssv LIKE :q OR p.so_phong LIKE :q ";
}
$sql_ht .= " ORDER BY FIELD(h.trang_thai, 'ChoXuLy', 'DangXuLy', 'DaXuLy'), h.id DESC";

$stmt = $conn->prepare($sql_ht);
if (!empty($tu_khoa_ht)) {
    $stmt->execute([':q' => "%$tu_khoa_ht%"]);
} else {
    $stmt->execute();
}
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách sinh viên cho form lập biên bản
$ds_sinh_vien = $conn->query("SELECT id, mssv, ho_ten FROM SinhVien ORDER BY ho_ten")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Hỗ Trợ - KTX Admin</title>
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
                <a href="quan_ly_phong.php" class="nav-item"><i class="fas fa-folder-open" style="width:20px;"></i> Quản lý Phòng</a>
                <a href="quan_ly_duyet_phong.php" class="nav-item"><i class="fas fa-check-square" style="width:20px;"></i> Duyệt ĐK & Chuyển</a>
                <a href="quan_ly_sinh_vien.php" class="nav-item"><i class="fas fa-users" style="width:20px;"></i> Sinh viên & HĐ</a>
                <a href="quan_ly_dich_vu.php" class="nav-item"><i class="fas fa-bolt" style="width:20px;"></i> Điện Nước</a>
                <a href="quan_ly_hoa_don.php" class="nav-item"><i class="fas fa-file-invoice-dollar" style="width:20px;"></i> Hóa đơn</a>
                <a href="quan_ly_thiet_bi.php" class="nav-item"><i class="fas fa-desktop" style="width:20px;"></i> Thiết bị</a>
                <a href="ho_tro.php" class="nav-item active"><i class="fas fa-exclamation-triangle" style="width:20px;"></i> Hỗ trợ & Phạt</a>
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
                <div style="font-weight: 600; font-size: 1.25rem;">Quản Lý Yêu Cầu Hỗ Trợ</div>
                
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
                    <div class="card" style="grid-column: span 2;">
                    <div class="card-header d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: 1rem;">
                        <h3 style="margin:0;"><i class="fas fa-clipboard-list text-primary"></i> Danh sách Ticket</h3>
                        
                        <form method="GET" action="ho_tro.php" style="display:flex; gap: 0.5rem; margin:0;">
                            <input type="text" name="q_ht" class="form-control" placeholder="Tìm ID, Tên, MSSV, Phòng..." value="<?php echo htmlspecialchars($tu_khoa_ht); ?>" style="width: 200px; padding: 0.4rem 0.75rem;">
                            <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 1rem;"><i class="fas fa-search"></i> Tìm</button>
                            <?php if($tu_khoa_ht): ?>
                                <a href="ho_tro.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Xóa</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>Người gửi (Phòng)</th>
                                        <th>Sự Cố</th>
                                        <th>Ngày tạo</th>
                                        <th>Trạng Thái</th>
                                        <th>Cập nhật</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($tickets) == 0): ?>
                                        <tr><td colspan="6" class="text-center text-muted-light">Không có yêu cầu hỗ trợ nào.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($tickets as $tk): ?>
                                        <tr <?php if($tk['trang_thai'] == 'DaXuLy') echo 'style="background-color: #f8fafc; opacity: 0.8;"'; ?>>
                                            <td>#<?php echo $tk['id']; ?></td>
                                            <td>
                                                <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($tk['ho_ten']); ?></strong><br>
                                                <small class="text-muted-light">Phòng: <?php echo $tk['so_phong'] ? ($tk['khu_nha'] . ' - ' . $tk['so_phong']) : 'Chưa xếp'; ?></small>
                                            </td>
                                            <td style="max-width: 300px;">
                                                <strong style="display:block; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($tk['tieu_de']); ?></strong>
                                                <small class="text-muted-light" style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($tk['noi_dung']); ?>"><?php echo htmlspecialchars($tk['noi_dung']); ?></small>
                                            </td>
                                            <td><small><?php echo date('d/m/Y H:i', strtotime($tk['ngay_gui'])); ?></small></td>
                                            <td>
                                                <?php if ($tk['trang_thai'] == 'ChoXuLy'): ?>
                                                    <span class="badge badge-warning">Chờ xử lý</span>
                                                <?php elseif ($tk['trang_thai'] == 'DangXuLy'): ?>
                                                    <span class="badge badge-primary">Đang sửa chữa</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Hoàn thành</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tk['trang_thai'] != 'DaXuLy'): ?>
                                                    <form method="POST" action="ho_tro.php" style="display:flex; gap: 0.5rem; align-items:center;">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $tk['id']; ?>">
                                                        <select name="trang_thai" class="form-control" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; width: auto;">
                                                            <option value="DangXuLy" <?php echo $tk['trang_thai'] == 'DangXuLy' ? 'selected' : ''; ?>>Đang xử lý</option>
                                                            <option value="DaXuLy">Hoàn thành</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"><i class="fas fa-save"></i></button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted-light"><i class="fas fa-check-circle text-secondary"></i> Xong</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                    <div class="card">
                        <div class="card-header" style="background-color: #fee2e2; border-color: #fca5a5;">
                            <h3 style="margin:0; color: #991b1b;"><i class="fas fa-exclamation-triangle"></i> Lập Biên Bản</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="ho_tro.php">
                                <input type="hidden" name="action" value="phat">
                                <div class="form-group">
                                    <label class="form-label">Sinh viên vi phạm</label>
                                    <select name="sinh_vien_id" class="form-control" required>
                                        <option value="">-- Chọn sinh viên --</option>
                                        <?php foreach($ds_sinh_vien as $sv): ?>
                                            <option value="<?php echo $sv['id']; ?>"><?php echo htmlspecialchars($sv['mssv'] . ' - ' . $sv['ho_ten']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Hình thức kỷ luật</label>
                                    <select name="hinh_thuc" class="form-control" required>
                                        <option value="Nhắc nhở">Nhắc nhở</option>
                                        <option value="Cảnh cáo">Cảnh cáo</option>
                                        <option value="Phạt tiền">Phạt tiền</option>
                                        <option value="Mời ra khỏi KTX">Mời ra khỏi KTX</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nội dung vi phạm</label>
                                    <textarea name="noi_dung" class="form-control" rows="3" required placeholder="Ví dụ: Nấu ăn trong phòng..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tiền phạt (nếu có)</label>
                                    <div style="position:relative;">
                                        <input type="number" name="tien_phat" class="form-control" value="0" min="0" step="1000">
                                        <span style="position:absolute; right:10px; top:10px; color:var(--text-muted);">VNĐ</span>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%; background-color: var(--danger);"><i class="fas fa-gavel"></i> Ghi nhận Vi Phạm</button>
                            </form>
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
            <a href="quan_ly_phong.php" class="bottom-nav-item">
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
