<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Xử lý tạo hóa đơn điện nước
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tao_hoa_don') {
    $phong_id = $_POST['phong_id'] ?? '';
    $thang = $_POST['thang'] ?? date('m');
    $nam = $_POST['nam'] ?? date('Y');
    $chi_so_dien_cu = $_POST['chi_so_dien_cu'] ?? 0;
    $chi_so_dien_moi = $_POST['chi_so_dien_moi'] ?? 0;
    $chi_so_nuoc_cu = $_POST['chi_so_nuoc_cu'] ?? 0;
    $chi_so_nuoc_moi = $_POST['chi_so_nuoc_moi'] ?? 0;
    $don_gia_dien = 3500;
    $don_gia_nuoc = 15000;

    if ($chi_so_dien_moi >= $chi_so_dien_cu && $chi_so_nuoc_moi >= $chi_so_nuoc_cu) {
        $conn->beginTransaction();
        try {
            // 1. Lưu chỉ số Điện nước
            $stmt = $conn->prepare("INSERT INTO DienNuoc (phong_id, thang, nam, chi_so_dien_cu, chi_so_dien_moi, chi_so_nuoc_cu, chi_so_nuoc_moi, don_gia_dien, don_gia_nuoc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$phong_id, $thang, $nam, $chi_so_dien_cu, $chi_so_dien_moi, $chi_so_nuoc_cu, $chi_so_nuoc_moi, $don_gia_dien, $don_gia_nuoc]);
            
            $tien_dien = ($chi_so_dien_moi - $chi_so_dien_cu) * $don_gia_dien;
            $tien_nuoc = ($chi_so_nuoc_moi - $chi_so_nuoc_cu) * $don_gia_nuoc;
            $tong_tien = $tien_dien + $tien_nuoc;

            // 2. Chia tiền cho sinh viên đang ở phòng này (hoặc lập hóa đơn chung cho phòng)
            // Trong thực tế có thể thu theo phòng. Theo CSDL, hóa đơn gắn với sinh_vien_id.
            // Lấy danh sách SV đang ở phòng này
            $svs = $conn->prepare("SELECT sinh_vien_id FROM HopDong WHERE phong_id = ? AND trang_thai = 'HieuLuc'");
            $svs->execute([$phong_id]);
            $danh_sach_sv = $svs->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($danh_sach_sv) > 0) {
                $tien_moi_nguoi = round($tong_tien / count($danh_sach_sv)); // Làm tròn để tránh số lẻ
                $so_nguoi = count($danh_sach_sv);
                foreach ($danh_sach_sv as $sv) {
                    $noi_dung = "Tiền điện nước T$thang/$nam. Tổng hóa đơn phòng: " . number_format($tong_tien) . "đ (Chia đều cho $so_nguoi người)";
                    $conn->prepare("INSERT INTO HoaDon (sinh_vien_id, loai_hoa_don, so_tien, noi_dung, thang, nam) VALUES (?, 'DienNuoc', ?, ?, ?, ?)")
                         ->execute([$sv['sinh_vien_id'], $tien_moi_nguoi, $noi_dung, $thang, $nam]);
                }
                $conn->commit();
                $thong_bao = "Đã chốt chỉ số điện nước và phát hành hóa đơn thành công cho " . count($danh_sach_sv) . " sinh viên trong phòng!";
            } else {
                $conn->rollBack();
                $loi = "Phòng này hiện tại không có sinh viên nào đang ở.";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $loi = "Lỗi xử lý cơ sở dữ liệu: " . $e->getMessage();
        }
    } else {
        $loi = "Chỉ số mới không được nhỏ hơn chỉ số cũ.";
    }
}

// Lấy danh sách phòng để chọn, kèm theo chỉ số điện nước mới nhất để làm chỉ số cũ cho lần chốt này
$danh_sach_phong = $conn->query("
    SELECT p.id, p.khu_nha, p.so_phong, 
           COALESCE((SELECT chi_so_dien_moi FROM DienNuoc dn WHERE dn.phong_id = p.id ORDER BY nam DESC, thang DESC, id DESC LIMIT 1), 0) as chi_so_dien_cu_moi,
           COALESCE((SELECT chi_so_nuoc_moi FROM DienNuoc dn WHERE dn.phong_id = p.id ORDER BY nam DESC, thang DESC, id DESC LIMIT 1), 0) as chi_so_nuoc_cu_moi
    FROM Phong p 
    ORDER BY p.khu_nha, p.so_phong
")->fetchAll(PDO::FETCH_ASSOC);

$tu_khoa_hd = $_GET['q_hd'] ?? '';
$sql_hd = "SELECT h.*, s.ho_ten, s.mssv FROM HoaDon h JOIN SinhVien s ON h.sinh_vien_id = s.id ";
if (!empty($tu_khoa_hd)) {
    $sql_hd .= " WHERE h.id LIKE :q OR s.ho_ten LIKE :q OR s.mssv LIKE :q ";
}
$sql_hd .= " ORDER BY h.id DESC LIMIT 50";
$stmt = $conn->prepare($sql_hd);
if (!empty($tu_khoa_hd)) {
    $stmt->execute([':q' => "%$tu_khoa_hd%"]);
} else {
    $stmt->execute();
}
$ds_hoadon = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điện Nước & Phí - KTX Admin</title>
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
                <a href="quan_ly_dich_vu.php" class="nav-item active"><i class="fas fa-bolt" style="width:20px;"></i> Điện Nước</a>
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
                <div style="font-weight: 600; font-size: 1.25rem;">Quản Lý Điện Nước & Lập Hóa Đơn</div>
                
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
                            <h3 style="margin:0; font-size: 1.1rem;"><i class="fas fa-file-invoice text-primary"></i> Chốt Số Điện Nước</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="quan_ly_dich_vu.php">
                                <input type="hidden" name="action" value="tao_hoa_don">
                                <div class="form-group">
                                    <label class="form-label">Chọn Phòng</label>
                                    <select name="phong_id" class="form-control" required>
                                        <?php foreach ($danh_sach_phong as $phong): ?>
                                            <option value="<?php echo $phong['id']; ?>"><?php echo $phong['khu_nha'] . ' - ' . $phong['so_phong']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="form-group">
                                        <label class="form-label">Tháng</label>
                                        <input type="number" name="thang" class="form-control" value="<?php echo date('m'); ?>" min="1" max="12" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Năm</label>
                                        <input type="number" name="nam" class="form-control" value="<?php echo date('Y'); ?>" required>
                                    </div>
                                </div>
                                
                                <label class="form-label mt-2" style="font-weight: 600; color: var(--text-light);">Chỉ số Điện (kWh)</label>
                                <div class="grid grid-cols-2" style="margin-bottom: 0;">
                                    <div class="form-group">
                                        <input type="number" name="chi_so_dien_cu" class="form-control" placeholder="Cũ" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="number" name="chi_so_dien_moi" class="form-control" placeholder="Mới" required>
                                    </div>
                                </div>

                                <label class="form-label mt-2" style="font-weight: 600; color: var(--text-light);">Chỉ số Nước (m³)</label>
                                <div class="grid grid-cols-2" style="margin-bottom: 0;">
                                    <div class="form-group">
                                        <input type="number" name="chi_so_nuoc_cu" class="form-control" placeholder="Cũ" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="number" name="chi_so_nuoc_moi" class="form-control" placeholder="Mới" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3" style="width: 100%;">Tạo Hóa Đơn</button>
                            </form>
                        </div>
                    </div>

                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: 1rem;">
                            <h3 style="margin:0; font-size: 1.1rem;"><i class="fas fa-list text-primary"></i> Hóa đơn gần đây</h3>
                            
                            <form method="GET" action="quan_ly_dich_vu.php" style="display:flex; gap: 0.5rem; margin:0;">
                                <input type="text" name="q_hd" class="form-control" placeholder="Tìm Mã HĐ, Tên, MSSV..." value="<?php echo htmlspecialchars($tu_khoa_hd); ?>" style="width: 220px; padding: 0.4rem 0.75rem;">
                                <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 1rem;"><i class="fas fa-search"></i> Tìm</button>
                                <?php if($tu_khoa_hd): ?>
                                    <a href="quan_ly_dich_vu.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Xóa</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Mã HĐ</th>
                                            <th>Sinh Viên</th>
                                            <th>Tháng/Năm</th>
                                            <th>Loại</th>
                                            <th>Số Tiền (VNĐ)</th>
                                            <th>Trạng Thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($ds_hoadon) == 0): ?>
                                            <tr><td colspan="6" class="text-center text-muted-light">Chưa có dữ liệu hóa đơn.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($ds_hoadon as $hd): ?>
                                            <tr>
                                                <td style="font-weight: 500;">#<?php echo $hd['id']; ?></td>
                                                <td><?php echo htmlspecialchars($hd['ho_ten']); ?><br><small class="text-muted-light"><?php echo htmlspecialchars($hd['mssv']); ?></small></td>
                                                <td><?php echo $hd['thang'] . '/' . $hd['nam']; ?></td>
                                                <td><span class="badge badge-primary"><?php echo $hd['loai_hoa_don']; ?></span></td>
                                                <td style="font-weight: 600;"><?php echo number_format($hd['so_tien']); ?></td>
                                                <td>
                                                    <?php if ($hd['trang_thai'] == 'DaThanhToan'): ?>
                                                        <span class="badge badge-success">Đã thanh toán</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Chưa thu</span>
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
    // Dữ liệu phòng và chỉ số điện nước cũ được lấy từ PHP
    const phongData = <?php echo json_encode($danh_sach_phong); ?>;
    
    document.addEventListener('DOMContentLoaded', function() {
        const phongSelect = document.querySelector('select[name="phong_id"]');
        const dienCuInput = document.querySelector('input[name="chi_so_dien_cu"]');
        const nuocCuInput = document.querySelector('input[name="chi_so_nuoc_cu"]');
        
        function updateOldIndices() {
            const selectedId = phongSelect.value;
            const selectedPhong = phongData.find(p => p.id == selectedId);
            if (selectedPhong) {
                // Tự động điền số điện/nước cũ
                dienCuInput.value = selectedPhong.chi_so_dien_cu_moi;
                nuocCuInput.value = selectedPhong.chi_so_nuoc_cu_moi;
            } else {
                dienCuInput.value = '';
                nuocCuInput.value = '';
            }
        }
        
        if (phongSelect && dienCuInput && nuocCuInput) {
            // Cập nhật khi trang vừa load
            updateOldIndices();
            // Cập nhật khi đổi phòng
            phongSelect.addEventListener('change', updateOldIndices);
        }
    });
    </script>

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
