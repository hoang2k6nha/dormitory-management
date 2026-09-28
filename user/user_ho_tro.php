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

$stmt = $conn->prepare("SELECT id FROM SinhVien WHERE tai_khoan_id = ?");
$stmt->execute([$user_id]);
$sv = $stmt->fetch(PDO::FETCH_ASSOC);
$sinh_vien_id = $sv['id'] ?? 0;

// Gửi ticket
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tieu_de']) && $sinh_vien_id) {
    $tieu_de = $_POST['tieu_de'];
    $noi_dung = $_POST['noi_dung'];
    try {
        $conn->prepare("INSERT INTO HoTro (sinh_vien_id, tieu_de, noi_dung, trang_thai) VALUES (?, ?, ?, 'ChoXuLy')")->execute([$sinh_vien_id, $tieu_de, $noi_dung]);
        $thong_bao = "Gửi yêu cầu hỗ trợ thành công. Vui lòng chờ phản hồi từ Ban Quản Lý.";
    } catch (PDOException $e) {
        $loi = "Lỗi khi gửi: " . $e->getMessage();
    }
}

// Lấy danh sách ticket
$tickets = $conn->prepare("SELECT * FROM HoTro WHERE sinh_vien_id = ? ORDER BY id DESC");
$tickets->execute([$sinh_vien_id]);
$danh_sach_ticket = $tickets->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hỗ Trợ - KTX Student</title>
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
                <a href="user_thong_tin_ca_nhan.php" class="nav-item"><i class="fas fa-user" style="width:20px;"></i> Thông tin cá nhân</a>
                <a href="user_phong.php" class="nav-item"><i class="fas fa-bed" style="width:20px;"></i> Đăng ký ở KTX</a>
                <a href="user_thong_tin_phong.php" class="nav-item"><i class="fas fa-info-circle" style="width:20px;"></i> Thông tin phòng</a>
                <a href="xem_hop_dong.php" class="nav-item"><i class="fas fa-file-signature" style="width:20px;"></i> Hợp đồng</a>
                <a href="user_hoa_don.php" class="nav-item"><i class="fas fa-credit-card" style="width:20px;"></i> Thanh toán</a>
                <a href="user_thong_bao.php" class="nav-item"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
                <a href="user_ho_tro.php" class="nav-item active"><i class="fas fa-headset" style="width:20px;"></i> Hỗ trợ</a>
                <a href="../logout.php" class="nav-item mt-4"><i class="fas fa-sign-out-alt" style="width:20px;"></i> Đăng xuất</a>
            </nav>
            
            <div class="user-profile-sidebar">
                <img src="https://ui-avatars.com/api/?name=SV&background=ffffff&color=107c41" alt="User" style="width:40px; height:40px; border-radius:50%;">
                <div>
                    <div style="font-weight: 600; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">Sinh viên</div>
                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.7);">Hồ sơ KTX</div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;"></div>
                
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
                            <a href="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? '../admin/thong_bao.php' : 'user_thong_bao.php'; ?>" style="font-size: 0.8rem; color: #3b82f6; text-decoration: none;">Xem tất cả</a>
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
                <img src="https://ui-avatars.com/api/?name=SV&background=107c41&color=fff" alt="User" style="width:36px; height:36px; border-radius:50%; margin-left:0.5rem;">
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
                        <div class="card-header">
                            <h3 style="margin:0;"><i class="fas fa-plus text-primary"></i> Tạo Ticket Mới</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="user_ho_tro.php">
                                <div class="form-group">
                                    <label class="form-label">Tiêu đề (VD: Hỏng vòi nước phòng 101, Xin đổi phòng)</label>
                                    <input type="text" name="tieu_de" class="form-control" value="<?php echo isset($_GET['type']) && $_GET['type'] == 'doi_phong' ? 'Xin đổi phòng ở' : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Chi tiết sự cố</label>
                                    <textarea name="noi_dung" class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-paper-plane"></i> Gửi Yêu Cầu</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 style="margin:0;"><i class="fas fa-list text-primary"></i> Lịch sử yêu cầu</h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Tiêu đề</th>
                                            <th>Ngày gửi</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($danh_sach_ticket) == 0): ?>
                                            <tr><td colspan="3" class="text-center text-muted-light">Chưa có yêu cầu nào.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($danh_sach_ticket as $tk): ?>
                                            <tr>
                                                <td style="font-weight: 500;"><?php echo htmlspecialchars($tk['tieu_de']); ?></td>
                                                <td><small><?php echo date('d/m/Y', strtotime($tk['ngay_gui'])); ?></small></td>
                                                <td>
                                                    <?php if ($tk['trang_thai'] == 'ChoXuLy'): ?>
                                                        <span class="badge badge-warning">Chờ xử lý</span>
                                                    <?php elseif ($tk['trang_thai'] == 'DangXuLy'): ?>
                                                        <span class="badge badge-primary">Đang xử lý</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Đã hoàn thành</span>
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
            <a href="user_thong_tin_ca_nhan.php" class="bottom-nav-item">
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
