<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Xử lý Xóa sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'xoa' && isset($_POST['id'])) {
    $id_xoa = $_POST['id'];
    try {
        $conn->beginTransaction();
        
        // Cần xóa các ràng buộc trước hoặc set null tùy thiết kế, 
        // nhưng theo script DB, xóa SinhVien sẽ gặp lỗi foreign key ở HopDong, HoaDon... nếu không ON DELETE CASCADE
        // Vì vậy ta xóa các bản ghi liên quan trước:
        $conn->prepare("DELETE FROM HoTro WHERE sinh_vien_id = ?")->execute([$id_xoa]);
        $conn->prepare("DELETE FROM ViPham WHERE sinh_vien_id = ?")->execute([$id_xoa]);
        $conn->prepare("DELETE FROM HoaDon WHERE sinh_vien_id = ?")->execute([$id_xoa]);
        $conn->prepare("DELETE FROM HopDong WHERE sinh_vien_id = ?")->execute([$id_xoa]);
        
        // Lấy tai_khoan_id để xóa tài khoản
        $stmt_tk = $conn->prepare("SELECT tai_khoan_id FROM SinhVien WHERE id = ?");
        $stmt_tk->execute([$id_xoa]);
        $tai_khoan_id = $stmt_tk->fetchColumn();

        // Xóa SinhVien
        $conn->prepare("DELETE FROM SinhVien WHERE id = ?")->execute([$id_xoa]);

        // Xóa TaiKhoan
        if ($tai_khoan_id) {
            $conn->prepare("DELETE FROM TaiKhoan WHERE id = ?")->execute([$tai_khoan_id]);
        }

        $conn->commit();
        $thong_bao = "Đã xóa hồ sơ sinh viên và các dữ liệu liên quan thành công!";
    } catch (PDOException $e) {
        $conn->rollBack();
        $loi = "Không thể xóa sinh viên này: " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'reset_pw' && isset($_POST['id'])) {
    $id_reset = $_POST['id'];
    try {
        $stmt_tk = $conn->prepare("SELECT tai_khoan_id, mssv FROM SinhVien WHERE id = ?");
        $stmt_tk->execute([$id_reset]);
        $sv_tk = $stmt_tk->fetch();
        if ($sv_tk && $sv_tk['tai_khoan_id']) {
            $default_pw = password_hash($sv_tk['mssv'], PASSWORD_DEFAULT);
            $conn->prepare("UPDATE TaiKhoan SET password = ? WHERE id = ?")->execute([$default_pw, $sv_tk['tai_khoan_id']]);
            $thong_bao = "Đã reset mật khẩu của sinh viên về MSSV thành công!";
        } else {
            $loi = "Không tìm thấy tài khoản để reset.";
        }
    } catch (PDOException $e) {
        $loi = "Lỗi reset: " . $e->getMessage();
    }
}

// Xử lý Thêm / Sửa Sinh viên
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $mssv = $_POST['mssv'] ?? '';
    $ho_ten = $_POST['ho_ten'] ?? '';
    $lop = $_POST['lop'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($_POST['action'] == 'them_sv') {
        if (!empty($mssv) && !empty($ho_ten)) {
            try {
                $conn->beginTransaction();
                $username = "sv_" . $mssv;
                $password = password_hash("123456", PASSWORD_BCRYPT);
                $conn->prepare("INSERT INTO TaiKhoan (username, password, role) VALUES (?, ?, 'user')")->execute([$username, $password]);
                $tai_khoan_id = $conn->lastInsertId();

                $conn->prepare("INSERT INTO SinhVien (mssv, ho_ten, lop, sdt, email, tai_khoan_id) VALUES (?, ?, ?, ?, ?, ?)")->execute([$mssv, $ho_ten, $lop, $sdt, $email, $tai_khoan_id]);
                
                $conn->commit();
                $thong_bao = "Thêm sinh viên thành công! Tài khoản: $username, MK: 123456";
            } catch (PDOException $e) {
                $conn->rollBack();
                $loi = "Lỗi khi thêm (MSSV/Email có thể bị trùng): " . $e->getMessage();
            }
        } else {
            $loi = "Vui lòng nhập MSSV và Họ tên.";
        }
    } elseif ($_POST['action'] == 'sua_sv') {
        $id_sua = $_POST['id'] ?? 0;
        if (!empty($mssv) && !empty($ho_ten) && $id_sua > 0) {
            try {
                $conn->prepare("UPDATE SinhVien SET mssv = ?, ho_ten = ?, lop = ?, sdt = ?, email = ? WHERE id = ?")->execute([$mssv, $ho_ten, $lop, $sdt, $email, $id_sua]);
                $thong_bao = "Cập nhật thông tin sinh viên thành công!";
            } catch (PDOException $e) {
                $loi = "Lỗi khi cập nhật: " . $e->getMessage();
            }
        }
    }
}

// Lấy thông tin sinh viên để sửa (nếu có req edit_id)
$sv_edit = null;
if (isset($_GET['edit_id'])) {
    $stmt_edit = $conn->prepare("SELECT * FROM SinhVien WHERE id = ?");
    $stmt_edit->execute([$_GET['edit_id']]);
    $sv_edit = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}

// Lấy danh sách SV
$tu_khoa = $_GET['q'] ?? '';
$sql = "SELECT s.*, p.khu_nha, p.so_phong 
        FROM SinhVien s 
        LEFT JOIN HopDong h ON s.id = h.sinh_vien_id AND h.trang_thai = 'HieuLuc' 
        LEFT JOIN Phong p ON h.phong_id = p.id ";

if (!empty($tu_khoa)) {
    $sql .= " WHERE s.ho_ten LIKE :q OR s.mssv LIKE :q OR s.sdt LIKE :q ";
}
$sql .= " ORDER BY s.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($tu_khoa)) {
    $q_param = "%$tu_khoa%";
    $stmt->bindParam(':q', $q_param);
}
$stmt->execute();
$danh_sach_sv = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sinh Viên - KTX Admin</title>
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
                <a href="quan_ly_sinh_vien.php" class="nav-item active"><i class="fas fa-users" style="width:20px;"></i> Sinh viên & HĐ</a>
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
                <div style="font-weight: 600; font-size: 1.25rem;">Hồ Sơ Sinh Viên</div>
                
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

                <div class="card mb-4" id="formContainer" <?php if(!$sv_edit) echo 'style="display:none;"'; ?>>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 style="margin:0; font-size: 1.1rem; color: var(--primary-color);">
                            <i class="fas <?php echo $sv_edit ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> 
                            <?php echo $sv_edit ? 'Cập Nhật Sinh Viên' : 'Thêm Sinh Viên Mới'; ?>
                        </h3>
                        <?php if($sv_edit): ?>
                            <a href="quan_ly_sinh_vien.php" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.875rem;"><i class="fas fa-times"></i> Hủy</a>
                        <?php else: ?>
                            <button class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.875rem; border: none;" onclick="document.getElementById('formContainer').style.display='none'; document.getElementById('btnShowForm').style.display='inline-block';"><i class="fas fa-times"></i> Đóng</button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="quan_ly_sinh_vien.php">
                            <input type="hidden" name="action" value="<?php echo $sv_edit ? 'sua_sv' : 'them_sv'; ?>">
                            <?php if($sv_edit): ?>
                                <input type="hidden" name="id" value="<?php echo $sv_edit['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-2">
                                <div class="form-group">
                                    <label class="form-label">MSSV *</label>
                                    <input type="text" name="mssv" class="form-control" value="<?php echo $sv_edit ? htmlspecialchars($sv_edit['mssv']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Họ và Tên *</label>
                                    <input type="text" name="ho_ten" class="form-control" value="<?php echo $sv_edit ? htmlspecialchars($sv_edit['ho_ten']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Lớp</label>
                                    <input type="text" name="lop" class="form-control" value="<?php echo $sv_edit ? htmlspecialchars($sv_edit['lop'] ?? '') : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="text" name="sdt" class="form-control" value="<?php echo $sv_edit ? htmlspecialchars($sv_edit['sdt'] ?? '') : ''; ?>">
                                </div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo $sv_edit ? htmlspecialchars($sv_edit['email'] ?? '') : ''; ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><?php echo $sv_edit ? 'Lưu Thay Đổi' : 'Thêm Sinh Viên'; ?></button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: 1rem;">
                        <h3 style="margin:0; font-size: 1.1rem;"><i class="fas fa-list text-primary"></i> Danh Sách Sinh Viên</h3>
                        
                        <div style="display:flex; gap: 1rem; flex-wrap: wrap;">
                            <!-- Form Tra Cứu -->
                            <form method="GET" action="quan_ly_sinh_vien.php" style="display:flex; gap: 0.5rem; margin:0;">
                                <input type="text" name="q" class="form-control" placeholder="Tra cứu MSSV, Họ tên, SĐT..." value="<?php echo htmlspecialchars($tu_khoa); ?>" style="width: 250px; padding: 0.4rem 0.75rem;">
                                <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 1rem;"><i class="fas fa-search"></i> Tìm</button>
                                <?php if($tu_khoa): ?>
                                    <a href="quan_ly_sinh_vien.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Xóa lọc</a>
                                <?php endif; ?>
                            </form>

                            <?php if(!$sv_edit): ?>
                                <button id="btnShowForm" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.875rem;" onclick="document.getElementById('formContainer').style.display='block'; this.style.display='none';"><i class="fas fa-plus"></i> Thêm mới</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>MSSV</th>
                                        <th>Họ và Tên</th>
                                        <th>Lớp</th>
                                        <th>SĐT / Email</th>
                                        <th>Phòng Ở</th>
                                        <th>Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($danh_sach_sv) == 0): ?>
                                        <tr><td colspan="6" class="text-center text-muted-light">Không tìm thấy sinh viên nào.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($danh_sach_sv as $sv): ?>
                                        <tr>
                                            <td style="font-weight: 600; color: var(--primary-color);"><?php echo htmlspecialchars($sv['mssv']); ?></td>
                                            <td style="font-weight: 500;"><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                                            <td><?php echo htmlspecialchars($sv['lop'] ?? ''); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($sv['sdt'] ?? ''); ?><br>
                                                <small style="color: var(--text-muted-light);"><?php echo htmlspecialchars($sv['email'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <?php if($sv['so_phong']): ?>
                                                    <span class="badge badge-success"><?php echo $sv['khu_nha'] . ' - ' . $sv['so_phong']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Chưa xếp</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display:flex; gap:0.25rem;">
                                                    <a href="xem_hop_dong.php?sv_id=<?php echo $sv['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Xem hợp đồng"><i class="fas fa-eye"></i></a>
                                                    <a href="quan_ly_sinh_vien.php?edit_id=<?php echo $sv['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Sửa"><i class="fas fa-edit"></i></a>
                                                    
                                                    <form method="POST" action="quan_ly_sinh_vien.php" style="margin:0; padding:0; display:inline;" onsubmit="return confirm('Reset mật khẩu của sinh viên này về mặc định (chính là MSSV)?');">
                                                        <input type="hidden" name="action" value="reset_pw">
                                                        <input type="hidden" name="id" value="<?php echo $sv['id']; ?>">
                                                        <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: var(--warning); border-color: var(--warning);" title="Cấp lại mật khẩu"><i class="fas fa-key"></i></button>
                                                    </form>

                                                    <form method="POST" action="quan_ly_sinh_vien.php" style="margin:0; padding:0; display:inline;" onsubmit="return confirm('CẢNH BÁO: Xóa sinh viên này sẽ xóa toàn bộ hợp đồng, hóa đơn, vé hỗ trợ liên quan. Bạn có chắc chắn?');">
                                                        <input type="hidden" name="action" value="xoa">
                                                        <input type="hidden" name="id" value="<?php echo $sv['id']; ?>">
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
