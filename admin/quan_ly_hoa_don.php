<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Xử lý thanh toán
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'thanh_toan') {
    $hoa_don_id = $_POST['hoa_don_id'];
    try {
        $conn->prepare("UPDATE HoaDon SET trang_thai = 'DaThanhToan' WHERE id = ?")->execute([$hoa_don_id]);
        $thong_bao = "Đã xác nhận thanh toán hóa đơn thành công!";
    } catch (PDOException $e) {
        $loi = "Lỗi xử lý: " . $e->getMessage();
    }
}

// Lấy danh sách hóa đơn
$tu_khoa_hd = $_GET['q_hd'] ?? '';
$sql_hd = "SELECT hd.*, s.ho_ten, s.mssv, p.khu_nha, p.so_phong 
           FROM HoaDon hd 
           JOIN SinhVien s ON hd.sinh_vien_id = s.id 
           LEFT JOIN HopDong hop ON s.id = hop.sinh_vien_id AND hop.trang_thai = 'HieuLuc'
           LEFT JOIN Phong p ON hop.phong_id = p.id ";
if (!empty($tu_khoa_hd)) {
    $sql_hd .= " WHERE hd.id LIKE :q OR s.ho_ten LIKE :q OR s.mssv LIKE :q OR p.so_phong LIKE :q ";
}
$sql_hd .= " ORDER BY FIELD(hd.trang_thai, 'ChuaThanhToan', 'DaThanhToan'), hd.id DESC";
$stmt = $conn->prepare($sql_hd);
if (!empty($tu_khoa_hd)) {
    $stmt->execute([':q' => "%$tu_khoa_hd%"]);
} else {
    $stmt->execute();
}
$hoa_dons = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tong_tien_phong = $conn->query("SELECT SUM(so_tien) FROM HoaDon WHERE trang_thai='ChuaThanhToan' AND loai_hoa_don='TienPhong'")->fetchColumn() ?: 0;
$tong_dien_nuoc = $conn->query("SELECT SUM(so_tien) FROM HoaDon WHERE trang_thai='ChuaThanhToan' AND loai_hoa_don='DienNuoc'")->fetchColumn() ?: 0;
$tong_tien_phat = $conn->query("SELECT SUM(so_tien) FROM HoaDon WHERE trang_thai='ChuaThanhToan' AND loai_hoa_don='Phat'")->fetchColumn() ?: 0;
$tong_du_no = $tong_tien_phong + $tong_dien_nuoc + $tong_tien_phat;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Hóa Đơn - KTX Admin</title>
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
                <a href="quan_ly_hoa_don.php" class="nav-item active"><i class="fas fa-file-invoice-dollar" style="width:20px;"></i> Hóa đơn</a>
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

        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Quản Lý Tài Chính & Hóa Đơn</div>
            </header>
            
            <div class="content-area">
                <?php if (isset($thong_bao)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($thong_bao); ?></div>
                <?php endif; ?>
                <?php if (isset($loi)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>
                <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
                    <div class="card" style="border-left: 4px solid var(--danger); text-align: center;">
                        <h4 class="text-muted" style="margin:0 0 0.5rem 0; font-size: 0.875rem;">Tổng Tiền Phòng (Chưa thu)</h4>
                        <strong class="text-danger" style="font-size: 1.5rem;"><?php echo number_format($tong_tien_phong); ?> đ</strong>
                    </div>
                    <div class="card" style="border-left: 4px solid var(--warning); text-align: center;">
                        <h4 class="text-muted" style="margin:0 0 0.5rem 0; font-size: 0.875rem;">Điện Nước (Chưa thu)</h4>
                        <strong class="text-warning" style="font-size: 1.5rem;"><?php echo number_format($tong_dien_nuoc); ?> đ</strong>
                    </div>
                    <div class="card" style="border-left: 4px solid var(--danger); text-align: center;">
                        <h4 class="text-muted" style="margin:0 0 0.5rem 0; font-size: 0.875rem;">Tiền Phạt (Chưa thu)</h4>
                        <strong class="text-danger" style="font-size: 1.5rem;"><?php echo number_format($tong_tien_phat); ?> đ</strong>
                    </div>
                    <div class="card" style="border-left: 4px solid var(--primary); text-align: center; background-color: var(--primary); color: white;">
                        <h4 style="margin:0 0 0.5rem 0; font-size: 0.875rem; color: rgba(255,255,255,0.8);">TỔNG DƯ NỢ CẦN THU</h4>
                        <strong style="font-size: 1.5rem; color: white;"><?php echo number_format($tong_du_no); ?> đ</strong>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: 1rem;">
                        <h3 style="margin:0;"><i class="fas fa-file-invoice-dollar text-primary"></i> Tất Cả Hóa Đơn</h3>
                        
                        <form method="GET" action="quan_ly_hoa_don.php" style="display:flex; gap: 0.5rem; margin:0;">
                            <input type="text" name="q_hd" class="form-control" placeholder="Tìm Mã HĐ, Tên, MSSV, Phòng..." value="<?php echo htmlspecialchars($tu_khoa_hd); ?>" style="width: 250px; padding: 0.4rem 0.75rem;">
                            <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 1rem;"><i class="fas fa-search"></i> Tìm</button>
                            <?php if($tu_khoa_hd): ?>
                                <a href="quan_ly_hoa_don.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Xóa</a>
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
                                        <th>Loại Hóa Đơn</th>
                                        <th>Nội Dung</th>
                                        <th>Số Tiền</th>
                                        <th>Tháng</th>
                                        <th>Trạng Thái</th>
                                        <th>Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hoa_dons as $hd): ?>
                                    <tr>
                                        <td>HD<?php echo str_pad($hd['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($hd['ho_ten']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($hd['mssv']); ?> - <?php echo $hd['so_phong'] ? $hd['khu_nha'].$hd['so_phong'] : ''; ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($hd['loai_hoa_don'] == 'TienPhong') echo '<span class="badge badge-primary">Tiền Phòng</span>';
                                            elseif ($hd['loai_hoa_don'] == 'DienNuoc') echo '<span class="badge badge-warning">Điện Nước</span>';
                                            elseif ($hd['loai_hoa_don'] == 'Phat') echo '<span class="badge badge-danger">Đóng Phạt</span>';
                                            else echo '<span class="badge badge-secondary">Tổng Hợp</span>';
                                            ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($hd['noi_dung'] ?? ''); ?></small></td>
                                        <td><strong class="text-danger"><?php echo number_format($hd['so_tien']); ?> đ</strong></td>
                                        <td><?php echo $hd['thang'].'/'.$hd['nam']; ?></td>
                                        <td>
                                            <?php if ($hd['trang_thai'] == 'DaThanhToan'): ?>
                                                <span class="badge badge-success">Đã thu</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Chưa thu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hd['trang_thai'] == 'ChuaThanhToan'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Xác nhận đã thu tiền?');">
                                                <input type="hidden" name="action" value="thanh_toan">
                                                <input type="hidden" name="hoa_don_id" value="<?php echo $hd['id']; ?>">
                                                <button class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"><i class="fas fa-check"></i> Xác nhận thu</button>
                                            </form>
                                            <?php else: ?>
                                                <i class="fas fa-check-circle text-success" style="font-size: 1.2rem;"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
