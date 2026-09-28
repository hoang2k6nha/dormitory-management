<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();
$user_id = $_SESSION['user_id'];

// Get student info
$stmt = $conn->prepare("SELECT s.*, p.so_phong, p.khu_nha 
                        FROM SinhVien s 
                        LEFT JOIN HopDong h ON s.id = h.sinh_vien_id AND h.trang_thai = 'HieuLuc' 
                        LEFT JOIN Phong p ON h.phong_id = p.id 
                        WHERE s.tai_khoan_id = ?");
$stmt->execute([$user_id]);
$sinh_vien = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lop = $_POST['lop'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';
    $action = $_POST['action'] ?? 'cap_nhat_thong_tin';
    
    if ($action == 'doi_mat_khau') {
        $mat_khau_cu = $_POST['mat_khau_cu'];
        $mat_khau_moi = $_POST['mat_khau_moi'];
        $xac_nhan_mk = $_POST['xac_nhan_mk'];
        
        // Kiểm tra mật khẩu cũ
        $stmt_check = $conn->prepare("SELECT password FROM TaiKhoan WHERE id = ?");
        $stmt_check->execute([$user_id]);
        $hash_cu = $stmt_check->fetchColumn();
        
        if (!password_verify($mat_khau_cu, $hash_cu)) {
            $loi = "Mật khẩu cũ không chính xác!";
        } elseif ($mat_khau_moi !== $xac_nhan_mk) {
            $loi = "Mật khẩu xác nhận không khớp!";
        } else {
            $hash_moi = password_hash($mat_khau_moi, PASSWORD_DEFAULT);
            $conn->prepare("UPDATE TaiKhoan SET password = ? WHERE id = ?")->execute([$hash_moi, $user_id]);
            $thong_bao = "Đổi mật khẩu thành công!";
        }
    } else {
        try {
            $conn->prepare("UPDATE SinhVien SET lop = ?, sdt = ?, email = ? WHERE id = ?")->execute([$lop, $sdt, $email, $sinh_vien['id']]);
            $thong_bao = "Cập nhật thông tin cá nhân thành công!";
            // Reload data
            $stmt->execute([$user_id]);
            $sinh_vien = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $loi = "Lỗi khi cập nhật: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Cá Nhân - KTX Student</title>
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
<body class="user-theme">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-home" style="font-size: 1.5rem;"></i> 
                <span style="font-size: 1.1rem;">KÝ TÚC XÁ</span>
            </div>
            
            <div class="sidebar-section">USER</div>
            
            <nav class="sidebar-nav">
                <a href="user_dashboard.php" class="nav-item"><i class="fas fa-home" style="width:20px;"></i> Trang chủ</a>
                <a href="user_thong_tin_ca_nhan.php" class="nav-item active"><i class="fas fa-user" style="width:20px;"></i> Thông tin cá nhân</a>
                <a href="user_phong.php" class="nav-item"><i class="fas fa-bed" style="width:20px;"></i> Đăng ký ở KTX</a>
                <a href="user_thong_tin_phong.php" class="nav-item"><i class="fas fa-info-circle" style="width:20px;"></i> Thông tin phòng</a>
                <a href="xem_hop_dong.php" class="nav-item"><i class="fas fa-file-signature" style="width:20px;"></i> Hợp đồng</a>
                <a href="user_hoa_don.php" class="nav-item"><i class="fas fa-credit-card" style="width:20px;"></i> Thanh toán</a>
                <a href="user_thong_bao.php" class="nav-item"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
                <a href="user_ho_tro.php" class="nav-item"><i class="fas fa-headset" style="width:20px;"></i> Hỗ trợ</a>
                <a href="../logout.php" class="nav-item mt-4"><i class="fas fa-sign-out-alt" style="width:20px;"></i> Đăng xuất</a>
            </nav>
            
            <div class="user-profile-sidebar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($sinh_vien['ho_ten']); ?>&background=ffffff&color=107c41" alt="User" style="width:40px; height:40px; border-radius:50%;">
                <div>
                    <div style="font-weight: 600; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;"><?php echo htmlspecialchars($sinh_vien['ho_ten']); ?></div>
                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.7);">MSSV: <?php echo htmlspecialchars($sinh_vien['mssv']); ?></div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Thông Tin Cá Nhân</div>
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
                        <div id="notificationDropdown" style="display: none; position: absolute; right: -10px; top: 35px; width: 320px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 1000; text-align: left;">
                            <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #111827;">Thông báo mới</span>
                                <a href="user_thong_bao.php" style="font-size: 0.8rem; color: #3b82f6; text-decoration: none;">Xem tất cả</a>
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
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($sinh_vien['ho_ten']); ?>&background=107c41&color=fff" alt="User" style="width:36px; height:36px; border-radius:50%; margin-left:0.5rem;">
                </div>
            </header>

            <div class="content-area">
                <?php if (isset($thong_bao)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($thong_bao); ?></div>
                <?php endif; ?>
                <?php if (isset($loi)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-2">
                    <div class="card">
                        <div class="card-header" style="border:none;">Thông tin sinh viên (Chỉ đọc)</div>
                        <div class="card-body">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Họ và tên</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 600; text-align:right;"><?php echo htmlspecialchars($sinh_vien['ho_ten'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Mã số SV</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 500; text-align:right;"><?php echo htmlspecialchars($sinh_vien['mssv'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Lớp</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 500; text-align:right;"><?php echo htmlspecialchars($sinh_vien['lop'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Phòng đang ở</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 500; text-align:right;">
                                        <?php echo $sinh_vien['so_phong'] ? ($sinh_vien['khu_nha'] . ' - ' . $sinh_vien['so_phong']) : 'Chưa xếp'; ?>
                                    </td>
                                </tr>
                            </table>
                            <p class="text-muted" style="font-size: 0.75rem; margin-top: 1rem;">* Để thay đổi các thông tin cơ bản này, vui lòng liên hệ Ban Quản Lý.</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header" style="border:none;">Thông tin liên lạc</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="cap_nhat_thong_tin">
                                <div class="form-group">
                                    <label class="form-label">Lớp</label>
                                    <input type="text" name="lop" class="form-control" value="<?php echo htmlspecialchars($sinh_vien['lop'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="text" name="sdt" class="form-control" value="<?php echo htmlspecialchars($sinh_vien['sdt'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($sinh_vien['email'] ?? ''); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary" style="margin-top: 1rem; width: 100%;"><i class="fas fa-save"></i> Cập nhật liên lạc</button>
                            </form>
                        </div>
                    </div>

                    <!-- Block Đổi Mật Khẩu -->
                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header" style="border:none;">Đổi Mật Khẩu</div>
                        <div class="card-body">
                            <form method="POST" style="max-width: 400px;">
                                <input type="hidden" name="action" value="doi_mat_khau">
                                <div class="form-group">
                                    <label class="form-label">Mật khẩu cũ</label>
                                    <input type="password" name="mat_khau_cu" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Mật khẩu mới</label>
                                    <input type="password" name="mat_khau_moi" class="form-control" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Xác nhận mật khẩu mới</label>
                                    <input type="password" name="xac_nhan_mk" class="form-control" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-warning" style="margin-top: 1rem;"><i class="fas fa-key"></i> Đổi Mật Khẩu</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <nav class="bottom-nav">
            <a href="user_dashboard.php" class="bottom-nav-item">
                <i class="fas fa-home"></i>
                <span>Trang chủ</span>
            </a>
            <a href="user_phong.php" class="bottom-nav-item">
                <i class="fas fa-bed"></i>
                <span>Đăng ký</span>
            </a>
            <a href="user_thong_bao.php" class="bottom-nav-item">
                <i class="fas fa-bell"></i>
                <span>Thông báo</span>
            </a>
            <a href="user_thong_tin_ca_nhan.php" class="bottom-nav-item active">
                <i class="fas fa-user"></i>
                <span>Cá nhân</span>
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
