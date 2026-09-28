<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

$ds_thong_bao = $conn->query("SELECT * FROM ThongBao ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Báo - KTX Student</title>
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
                <a href="user_thong_bao.php" class="nav-item active"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
                <a href="user_ho_tro.php" class="nav-item"><i class="fas fa-headset" style="width:20px;"></i> Hỗ trợ</a>
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

                <div class="card" style="max-width: 800px; margin: 0 auto;">
                    <div class="card-header">
                        <h3 style="margin:0;"><i class="fas fa-bullhorn text-warning"></i> Tin tức từ Ban Quản Lý</h3>
                    </div>
                    <div class="card-body">
                        <?php if(count($ds_thong_bao) == 0): ?>
                            <div class="text-center text-muted-light" style="padding: 3rem 0;">
                                <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem; display: block;"></i>
                                Không có thông báo nào.
                            </div>
                        <?php else: ?>
                            <?php foreach ($ds_thong_bao as $tb): ?>
                            <div style="border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1rem; background-color: #f8fafc;">
                                <h3 style="margin: 0 0 0.5rem 0; color: var(--primary-color); display: flex; justify-content: space-between; align-items: center;">
                                    <?php echo htmlspecialchars($tb['tieu_de']); ?>
                                    <span class="badge badge-primary" style="font-size: 0.75rem;"><i class="fas fa-info-circle"></i> Mới</span>
                                </h3>
                                <p style="margin: 0 0 1rem 0; font-size: 0.875rem; color: var(--text-muted-light);"><i class="fas fa-clock"></i> Đăng lúc: <?php echo date('H:i - d/m/Y', strtotime($tb['ngay_dang'])); ?></p>
                                <div style="font-size: 0.95rem; line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($tb['noi_dung'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
            <a href="user_thong_bao.php" class="bottom-nav-item active">
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
