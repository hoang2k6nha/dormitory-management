<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo - KTX Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <a href="ho_tro.php" class="nav-item"><i class="fas fa-exclamation-triangle" style="width:20px;"></i> Hỗ trợ & Phạt</a>
                <a href="thong_bao.php" class="nav-item"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
                <a href="bao_cao.php" class="nav-item active"><i class="fas fa-chart-bar" style="width:20px;"></i> Báo cáo</a>
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

        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Báo Cáo Thống Kê</div>
            </header>
            
<?php
// Lấy danh sách hóa đơn quá hạn 2 ngày
$ds_no_qua_han = $conn->query("
    SELECT hd.id, sv.ho_ten, sv.mssv, hd.loai_hoa_don, hd.so_tien, hd.ngay_tao, 
           DATEDIFF(NOW(), hd.ngay_tao) as so_ngay_tre
    FROM HoaDon hd
    JOIN SinhVien sv ON hd.sinh_vien_id = sv.id
    WHERE hd.trang_thai = 'ChuaThanhToan' AND DATEDIFF(NOW(), hd.ngay_tao) > 2
    ORDER BY so_ngay_tre DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy báo cáo doanh thu tiền phòng dự kiến
$ds_doanh_thu_phong = $conn->query("
    SELECT p.khu_nha, p.so_phong, p.gia_phong, COUNT(h.sinh_vien_id) as so_nguoi_o, 
           p.gia_phong as tong_tien_du_kien, (p.gia_phong / COUNT(h.sinh_vien_id)) as don_gia_sv
    FROM Phong p
    JOIN HopDong h ON p.id = h.phong_id AND h.trang_thai = 'HieuLuc'
    GROUP BY p.id
    HAVING COUNT(h.sinh_vien_id) > 0
    ORDER BY p.khu_nha, p.so_phong
")->fetchAll(PDO::FETCH_ASSOC);

$tong_thu_du_kien = array_sum(array_column($ds_doanh_thu_phong, 'tong_tien_du_kien'));
?>
                <div class="grid grid-cols-1 gap-4">
                    <!-- Danh sách nợ quá hạn -->
                    <div class="card">
                        <div class="card-header" style="border-bottom: 2px solid var(--danger);">
                            <h3 style="margin:0; color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> Danh Sách Nợ Quá Hạn (Trên 2 Ngày)</h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Mã HĐ</th>
                                            <th>Sinh Viên</th>
                                            <th>Ngày Tạo</th>
                                            <th>Số Ngày Trễ</th>
                                            <th>Loại Hóa Đơn</th>
                                            <th>Số Tiền Nợ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($ds_no_qua_han) == 0): ?>
                                            <tr><td colspan="6" class="text-center text-muted-light">Hiện không có hóa đơn nào quá hạn.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($ds_no_qua_han as $hd): ?>
                                            <tr>
                                                <td style="font-weight: 500;">HD<?php echo str_pad($hd['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($hd['ho_ten']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($hd['mssv']); ?></small>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($hd['ngay_tao'])); ?></td>
                                                <td><span class="badge badge-danger"><?php echo $hd['so_ngay_tre']; ?> ngày</span></td>
                                                <td>
                                                    <?php 
                                                    if ($hd['loai_hoa_don'] == 'TienPhong') echo '<span class="badge badge-primary">Tiền Phòng</span>';
                                                    elseif ($hd['loai_hoa_don'] == 'DienNuoc') echo '<span class="badge badge-warning">Điện Nước</span>';
                                                    elseif ($hd['loai_hoa_don'] == 'Phat') echo '<span class="badge badge-danger">Tiền Phạt</span>';
                                                    else echo '<span class="badge badge-secondary">Tổng Hợp</span>';
                                                    ?>
                                                </td>
                                                <td style="font-weight: 600; color: var(--danger);"><?php echo number_format($hd['so_tien']); ?> đ</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Báo cáo doanh thu tiền phòng -->
                    <div class="card mt-4">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--success);">
                            <h3 style="margin:0; color: var(--success);"><i class="fas fa-chart-line"></i> Báo Cáo Doanh Thu Tiền Phòng Theo Tháng</h3>
                            <strong style="font-size: 1.1rem; color: var(--success);">Tổng dự kiến: <?php echo number_format($tong_thu_du_kien); ?> đ</strong>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Phòng</th>
                                            <th class="text-center">Số SV Đang Ở</th>
                                            <th class="text-right">Đơn Giá / SV</th>
                                            <th class="text-right">Tổng Tiền Thu Được</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($ds_doanh_thu_phong) == 0): ?>
                                            <tr><td colspan="4" class="text-center text-muted-light">Chưa có dữ liệu sinh viên ở phòng.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($ds_doanh_thu_phong as $phong): ?>
                                            <tr>
                                                <td style="font-weight: 600;">
                                                    <i class="fas fa-door-closed text-muted"></i> 
                                                    <?php echo htmlspecialchars($phong['khu_nha']) . ' - ' . htmlspecialchars($phong['so_phong']); ?>
                                                </td>
                                                <td class="text-center"><span class="badge badge-info"><?php echo $phong['so_nguoi_o']; ?> người</span></td>
                                                <td class="text-right text-muted"><?php echo number_format($phong['don_gia_sv']); ?> đ</td>
                                                <td class="text-right" style="font-weight: bold; color: var(--primary);"><?php echo number_format($phong['tong_tien_du_kien']); ?> đ</td>
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
    </div>
</body>
</html>
