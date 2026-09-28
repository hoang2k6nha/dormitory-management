<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Handle Approval
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['dang_ky_id'])) {
    $dang_ky_id = $_POST['dang_ky_id'];
    $action = $_POST['action'];
    
    if ($action == 'duyet') {
        // Fetch dang ky
        $stmt = $conn->prepare("SELECT * FROM DangKy WHERE id = ?");
        $stmt->execute([$dang_ky_id]);
        $dk = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dk) {
            $conn->beginTransaction();
            try {
                // Update dang ky status
                $conn->prepare("UPDATE DangKy SET trang_thai_duyet = 'DaDuyet' WHERE id = ?")->execute([$dang_ky_id]);
                
                // Create account for student (username: mssv / sdt, pass: 123456)
                $username = "sv_" . $dk['sdt'];
                $password = password_hash("123456", PASSWORD_BCRYPT);
                
                $conn->prepare("INSERT INTO TaiKhoan (username, password, role) VALUES (?, ?, 'user')")->execute([$username, $password]);
                $tai_khoan_id = $conn->lastInsertId();
                
                // Create student record
                $conn->prepare("INSERT INTO SinhVien (mssv, ho_ten, email, sdt, tai_khoan_id) VALUES (?, ?, ?, ?, ?)")
                     ->execute([$dk['sdt'], $dk['ho_ten'], $dk['email'], $dk['sdt'], $tai_khoan_id]);
                
                $conn->commit();
                
                // Gửi email thông báo
                $to = $dk['email'];
                $subject = "=?UTF-8?B?" . base64_encode("Thông báo: Đăng ký lưu trú KTX thành công") . "?=";
                $message = "Chào " . $dk['ho_ten'] . ",\n\n";
                $message .= "Đơn đăng ký lưu trú ký túc xá của bạn đã được Ban quản lý phê duyệt thành công.\n\n";
                $message .= "Dưới đây là thông tin tài khoản đăng nhập của bạn vào hệ thống quản lý KTX:\n";
                $message .= "- Tên đăng nhập: " . $username . "\n";
                $message .= "- Mật khẩu: 123456\n\n";
                $message .= "Vui lòng đăng nhập vào hệ thống tại trang chủ và đổi mật khẩu để bảo mật tài khoản.\n\n";
                $message .= "Trân trọng,\nBan Quản Lý KTX";
                
                $headers = "From: banquanly@ktx.local\r\n";
                $headers .= "Reply-To: banquanly@ktx.local\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                @mail($to, $subject, $message, $headers);
                
                $thong_bao = "Đã duyệt và cấp tài khoản (TK: $username, MK: 123456) cho sinh viên " . $dk['ho_ten'] . ". Email thông báo đã được gửi đi.";
            } catch (Exception $e) {
                $conn->rollBack();
                $loi = "Lỗi khi duyệt: " . $e->getMessage();
            }
        }
    } elseif ($action == 'tu_choi') {
        $conn->prepare("UPDATE DangKy SET trang_thai_duyet = 'TuChoi' WHERE id = ?")->execute([$dang_ky_id]);
        $thong_bao = "Đã từ chối đơn đăng ký.";
    }
}

$danh_sach_dk = $conn->query("SELECT * FROM DangKy ORDER BY trang_thai_duyet ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt Đăng Ký - KTX Admin</title>
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
                <div style="font-weight: 600; font-size: 1.25rem;">Quản Lý Yêu Cầu Đăng Ký</div>
                
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
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $thong_bao; ?></div>
                <?php endif; ?>
                <?php if (isset($loi)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $loi; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 style="margin:0;">Danh sách Đơn đăng ký lưu trú</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Họ và Tên</th>
                                        <th>SĐT / Email</th>
                                        <th>Khoa - Khóa</th>
                                        <th>Loại Phòng</th>
                                        <th>Ngày ĐK</th>
                                        <th>Trạng Thái</th>
                                        <th>Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($danh_sach_dk as $dk): ?>
                                    <tr>
                                        <td>#<?php echo $dk['id']; ?></td>
                                        <td style="font-weight: 500;"><?php echo htmlspecialchars($dk['ho_ten']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($dk['sdt']); ?><br>
                                            <small style="color: var(--text-muted-light);"><?php echo htmlspecialchars($dk['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($dk['khoa']) . ' - ' . htmlspecialchars($dk['khoa_hoc']); ?></td>
                                        <td><span class="badge badge-primary"><?php echo $dk['loai_phong_mong_muon']; ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($dk['ngay_dang_ky'])); ?></td>
                                        <td>
                                            <?php if ($dk['trang_thai_duyet'] == 'ChoDuyet'): ?>
                                                <span class="badge badge-warning">Chờ duyệt</span>
                                            <?php elseif ($dk['trang_thai_duyet'] == 'DaDuyet'): ?>
                                                <span class="badge badge-success">Đã duyệt</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Từ chối</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($dk['trang_thai_duyet'] == 'ChoDuyet'): ?>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="duyet">
                                                        <input type="hidden" name="dang_ky_id" value="<?php echo $dk['id']; ?>">
                                                        <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"><i class="fas fa-check"></i> Duyệt</button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="tu_choi">
                                                        <input type="hidden" name="dang_ky_id" value="<?php echo $dk['id']; ?>">
                                                        <button type="submit" class="btn" style="background-color: var(--danger-color); color: white; padding: 0.25rem 0.5rem; font-size: 0.875rem;"><i class="fas fa-times"></i></button>
                                                    </form>
                                                </div>
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
