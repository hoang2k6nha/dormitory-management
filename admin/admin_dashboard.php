<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Thống kê tổng quan (Dữ liệu thực từ Database)
$tong_phong = $conn->query("SELECT COUNT(*) FROM Phong")->fetchColumn() ?: 0;
$phong_bao_tri = $conn->query("SELECT COUNT(*) FROM Phong WHERE trang_thai = 'DangBaoTri'")->fetchColumn() ?: 0;

$phong_da_o = $conn->query("
    SELECT COUNT(DISTINCT phong_id) 
    FROM HopDong 
    WHERE trang_thai = 'HieuLuc' 
      AND phong_id IN (SELECT id FROM Phong WHERE trang_thai != 'DangBaoTri')
")->fetchColumn() ?: 0;

$phong_trong = $tong_phong - $phong_bao_tri - $phong_da_o;

$ty_le_trong = $tong_phong > 0 ? round(($phong_trong / $tong_phong) * 100) : 0;
$ty_le_da_o = $tong_phong > 0 ? round(($phong_da_o / $tong_phong) * 100) : 0;
$ty_le_bao_tri = $tong_phong > 0 ? round(($phong_bao_tri / $tong_phong) * 100) : 0;

$tong_sinh_vien = $conn->query("SELECT COUNT(*) FROM SinhVien")->fetchColumn() ?: 0;
$doanh_thu = $conn->query("SELECT SUM(so_tien) FROM HoaDon WHERE trang_thai = 'DaThanhToan' AND thang = MONTH(CURRENT_DATE()) AND nam = YEAR(CURRENT_DATE())")->fetchColumn() ?: 0;

// Doanh thu 6 tháng gần nhất
$doanh_thu_6_thang = [];
$labels_6_thang = [];
for ($i = 5; $i >= 0; $i--) {
    $t = date('n', strtotime("-$i months"));
    $y = date('Y', strtotime("-$i months"));
    $dt = $conn->query("SELECT SUM(so_tien) FROM HoaDon WHERE trang_thai = 'DaThanhToan' AND thang = $t AND nam = $y")->fetchColumn() ?: 0;
    $doanh_thu_6_thang[] = $dt / 1000000; // Đổi ra đơn vị Triệu (M)
    $labels_6_thang[] = "'T$t'";
}
$chart_labels = implode(',', $labels_6_thang);
$chart_data = implode(',', $doanh_thu_6_thang);

// Nợ / Chưa đóng phạt
$sv_no_hoa_don = $conn->query("SELECT COUNT(DISTINCT sinh_vien_id) FROM HoaDon WHERE trang_thai = 'ChuaThanhToan' AND loai_hoa_don != 'Phat'")->fetchColumn() ?: 0;
$tien_no_hoa_don = $conn->query("SELECT SUM(so_tien) FROM HoaDon WHERE trang_thai = 'ChuaThanhToan' AND loai_hoa_don != 'Phat'")->fetchColumn() ?: 0;

$sv_chua_phat = $conn->query("SELECT COUNT(DISTINCT sinh_vien_id) FROM HoaDon WHERE trang_thai = 'ChuaThanhToan' AND loai_hoa_don = 'Phat'")->fetchColumn() ?: 0;
$tien_chua_phat = $conn->query("SELECT SUM(so_tien) FROM HoaDon WHERE trang_thai = 'ChuaThanhToan' AND loai_hoa_don = 'Phat'")->fetchColumn() ?: 0;

$tong_sv_no = $conn->query("SELECT COUNT(DISTINCT sinh_vien_id) FROM HoaDon WHERE trang_thai = 'ChuaThanhToan'")->fetchColumn() ?: 0;
$tong_tien_no = $tien_no_hoa_don + $tien_chua_phat;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quản Lý Ký Túc Xá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        // Khôi phục Dark Mode nếu đã bật
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body class="admin-theme">
    <div class="dashboard-layout">
        <!-- Sidebar -->
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
                <a href="admin_dashboard.php" class="nav-item active"><i class="fas fa-home" style="width:20px;"></i> Dashboard</a>
                <a href="quan_ly_phong.php" class="nav-item"><i class="fas fa-folder-open" style="width:20px;"></i> Quản lý Phòng</a>
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
                <i class="fas fa-ellipsis-v" style="margin-left: auto; cursor:pointer;"></i>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Dashboard Tổng quan</div>
                
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
                <!-- KPI Cards -->
                <div class="grid grid-cols-4" style="margin-bottom: 1.5rem;">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h4>Tổng số phòng</h4>
                            <p><?php echo $tong_phong; ?></p>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Phòng</span>
                        </div>
                        <div class="stat-icon icon-blue"><i class="fas fa-home"></i></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <h4>Tỷ lệ phòng trống</h4>
                            <p><?php echo $ty_le_trong; ?>%</p>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $phong_trong; ?> phòng trống</span>
                        </div>
                        <div class="stat-icon icon-green"><i class="fas fa-chart-pie"></i></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <h4>Tổng số sinh viên</h4>
                            <p><?php echo $tong_sinh_vien; ?></p>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Sinh viên</span>
                        </div>
                        <div class="stat-icon icon-blue" style="color: #1e40af; background: #dbeafe;"><i class="fas fa-users"></i></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <h4>Doanh thu tháng</h4>
                            <p><?php echo number_format($doanh_thu); ?> đ</p>
                            <span style="font-size: 0.75rem; color: var(--success);"><i class="fas fa-arrow-up"></i> 12.5% so với tháng trước</span>
                        </div>
                        <div class="stat-icon icon-green"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-3" style="margin-bottom: 1.5rem;">
                    <div class="card">
                        <div class="card-header" style="border:none; padding-bottom: 0;">Tỷ lệ phòng</div>
                        <div class="card-body" style="display:flex; justify-content:center; align-items:center;">
                            <div style="width: 200px; height: 200px;">
                                <canvas id="roomChart"></canvas>
                            </div>
                            <div style="margin-left: 1rem; font-size: 0.875rem;">
                                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                                    <span style="width:12px; height:12px; background:#3b82f6; border-radius:2px;"></span>
                                    <span>Phòng đã ở<br><strong><?php echo $phong_da_o; ?> (<?php echo $ty_le_da_o; ?>%)</strong></span>
                                </div>
                                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                                    <span style="width:12px; height:12px; background:#10b981; border-radius:2px;"></span>
                                    <span>Phòng trống<br><strong><?php echo $phong_trong; ?> (<?php echo $ty_le_trong; ?>%)</strong></span>
                                </div>
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <span style="width:12px; height:12px; background:#ef4444; border-radius:2px;"></span>
                                    <span>Phòng bảo trì<br><strong><?php echo $phong_bao_tri; ?> (<?php echo $ty_le_bao_tri; ?>%)</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" style="border:none; padding-bottom: 0;">Doanh thu 6 tháng gần nhất</div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="180"></canvas>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" style="border:none; padding-bottom: 0;">Danh sách nợ / chưa đóng phạt</div>
                        <div class="card-body" style="padding-top: 0.5rem;">
                            <table style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="background:transparent; border-bottom: 1px solid #e5e7eb; padding: 0.5rem;">Loại</th>
                                        <th style="background:transparent; border-bottom: 1px solid #e5e7eb; padding: 0.5rem;">Số lượng</th>
                                        <th style="background:transparent; border-bottom: 1px solid #e5e7eb; padding: 0.5rem;">Số tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 0.75rem 0.5rem; border:none;">Nợ hóa đơn</td>
                                        <td style="padding: 0.75rem 0.5rem; border:none;"><?php echo $sv_no_hoa_don; ?> SV</td>
                                        <td style="padding: 0.75rem 0.5rem; border:none; font-weight: 500;"><?php echo number_format($tien_no_hoa_don); ?> đ</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem 0.5rem; border:none;">Chưa đóng phạt</td>
                                        <td style="padding: 0.75rem 0.5rem; border:none;"><?php echo $sv_chua_phat; ?> SV</td>
                                        <td style="padding: 0.75rem 0.5rem; border:none; font-weight: 500;"><?php echo number_format($tien_chua_phat); ?> đ</td>
                                    </tr>
                                    <tr style="border-top: 1px dashed var(--border-color);">
                                        <td style="padding: 0.75rem 0.5rem; border:none; font-weight: 600;">Tổng cộng</td>
                                        <td style="padding: 0.75rem 0.5rem; border:none; font-weight: 600;"><?php echo $tong_sv_no; ?> SV</td>
                                        <td style="padding: 0.75rem 0.5rem; border:none; font-weight: 600; color: var(--danger);"><?php echo number_format($tong_tien_no); ?> đ</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="grid grid-cols-2">
                    <div class="card">
                        <div class="card-header" style="border:none;">Sinh viên mới đăng ký</div>
                        <div class="card-body" style="padding-top:0;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="background:transparent;">Họ tên</th>
                                        <th style="background:transparent;">MSSV</th>
                                        <th style="background:transparent;">Ngày đăng ký</th>
                                        <th style="background:transparent;">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $dk_moi = $conn->query("SELECT * FROM DangKy WHERE trang_thai_duyet = 'ChoDuyet' LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach($dk_moi as $dk):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dk['ho_ten']); ?></td>
                                        <td><?php echo htmlspecialchars($dk['sdt']); // using sdt as mssv placeholder for guest ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($dk['ngay_dang_ky'])); ?></td>
                                        <td><span style="color: var(--warning); font-weight: 500;">Chờ duyệt</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div style="text-align: right; margin-top: 1rem;">
                                <a href="quan_ly_duyet.php" style="color: var(--admin-primary); text-decoration:none; font-size: 0.875rem;">Xem tất cả <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" style="border:none;">Thông báo mới</div>
                        <div class="card-body" style="padding-top:0;">
                            <?php
                            $tbs = $conn->query("SELECT * FROM ThongBao ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                            foreach($tbs as $tb):
                            ?>
                            <div style="display:flex; align-items:flex-start; gap: 1rem; margin-bottom: 1.25rem;">
                                <div style="color: var(--danger); background: #fee2e2; width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div style="flex:1;">
                                    <div style="display:flex; justify-content:space-between;">
                                        <span style="font-weight: 500; font-size: 0.875rem;"><?php echo htmlspecialchars($tb['tieu_de']); ?></span>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('d/m/Y H:i', strtotime($tb['ngay_dang'])); ?></span>
                                    </div>
                                    <p style="font-size: 0.875rem; color: var(--text-muted); margin:0; margin-top:0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 300px;"><?php echo htmlspecialchars($tb['noi_dung']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div style="text-align: right; margin-top: 1rem;">
                                <a href="thong_bao.php" style="color: var(--admin-primary); text-decoration:none; font-size: 0.875rem;">Xem tất cả <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
        
        <!-- Bottom Nav for Mobile -->
        <nav class="bottom-nav">
            <a href="admin_dashboard.php" class="bottom-nav-item active">
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
        // Room Chart
        const ctxRoom = document.getElementById('roomChart').getContext('2d');
        new Chart(ctxRoom, {
            type: 'doughnut',
            data: {
                labels: ['Phòng đã ở', 'Phòng trống', 'Phòng bảo trì'],
                datasets: [{
                    data: [<?php echo $phong_da_o; ?>, <?php echo $phong_trong; ?>, <?php echo $phong_bao_tri; ?>],
                    backgroundColor: ['#3b82f6', '#10b981', '#ef4444'],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                maintainAspectRatio: false
            }
        });

        // Revenue Chart
        const ctxRev = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRev, {
            type: 'bar',
            data: {
                labels: [<?php echo $chart_labels; ?>],
                datasets: [{
                    label: 'Doanh thu (Triệu VNĐ)',
                    data: [<?php echo $chart_data; ?>],
                    backgroundColor: '#3b82f6',
                    borderRadius: 4,
                    barThickness: 20
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function(value) { return value + 'M'; }
                        }
                    },
                    x: { grid: { display: false } }
                },
                maintainAspectRatio: false
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
