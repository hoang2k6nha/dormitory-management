<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Thêm thông báo mới
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tieu_de'])) {
    $tieu_de = $_POST['tieu_de'];
    $noi_dung = $_POST['noi_dung'];
    try {
        $conn->prepare("INSERT INTO ThongBao (tieu_de, noi_dung) VALUES (?, ?)")->execute([$tieu_de, $noi_dung]);
        require_once "../vendor/autoload.php";
        try {
            $options = array('cluster' => 'ap1', 'useTLS' => true);
            $pusher = new Pusher\Pusher('d567ab30e7aa1832d4f4', 'f99b314dc5af5d3ac548', '2173806', $options);
            $data['tieu_de'] = $tieu_de;
            $pusher->trigger('ktx-channel', 'new-notification', $data);
        } catch (Exception $e) {}
        $thong_bao = "Phát thông báo thành công!";
    } catch (PDOException $e) {
        $loi = "Lỗi khi tạo thông báo: " . $e->getMessage();
    }
}

// Lấy danh sách thông báo
$ds_thong_bao = $conn->query("SELECT * FROM ThongBao ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gửi Thông Báo - KTX Admin</title>
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
                <a href="ho_tro.php" class="nav-item"><i class="fas fa-exclamation-triangle" style="width:20px;"></i> Hỗ trợ & Phạt</a>
                <a href="thong_bao.php" class="nav-item active"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
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
                <div style="font-weight: 600; font-size: 1.25rem;">Phát Sóng Thông Báo</div>
                
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

                <div class="grid grid-cols-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 style="margin:0;"><i class="fas fa-edit text-primary"></i> Viết Thông Báo Mới</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="thong_bao.php">
                                <div class="form-group">
                                    <label class="form-label">Tiêu đề</label>
                                    <input type="text" name="tieu_de" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nội dung</label>
                                    <textarea name="noi_dung" class="form-control" rows="8" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-bullhorn"></i> Phát Thông Báo</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 style="margin:0;"><i class="fas fa-history text-primary"></i> Lịch sử thông báo</h3>
                        </div>
                        <div class="card-body">
                            <div style="max-height: 500px; overflow-y: auto;">
                                <?php if(count($ds_thong_bao) == 0): ?>
                                    <p class="text-center text-muted-light">Chưa có thông báo nào được tạo.</p>
                                <?php else: ?>
                                    <?php foreach ($ds_thong_bao as $tb): ?>
                                    <div style="border-bottom: 1px solid var(--border-light); padding-bottom: 1rem; margin-bottom: 1rem;">
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color);"><?php echo htmlspecialchars($tb['tieu_de']); ?></h4>
                                        <small style="color: var(--text-muted-light); display: block; margin-bottom: 0.5rem;"><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($tb['ngay_dang'])); ?></small>
                                        <p style="margin: 0; font-size: 0.9rem;"><?php echo nl2br(htmlspecialchars($tb['noi_dung'])); ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
            <a href="thong_bao.php" class="bottom-nav-item active">
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
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
  function showToast(message) {
      let toastContainer = document.getElementById('toast-container');
      if (!toastContainer) {
          toastContainer = document.createElement('div');
          toastContainer.id = 'toast-container';
          toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;';
          document.body.appendChild(toastContainer);
      }
      let toast = document.createElement('div');
      toast.style.cssText = 'background: white; border-left: 4px solid #3b82f6; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-radius: 8px; padding: 16px 20px; display: flex; align-items: center; gap: 12px; transform: translateX(120%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); min-width: 300px; max-width: 400px;';
      toast.innerHTML = `
          <div style="background: #eff6ff; color: #3b82f6; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
              <i class="fas fa-bell"></i>
          </div>
          <div style="flex: 1;">
              <div style="font-weight: 600; font-size: 0.95rem; color: #111827; margin-bottom: 2px;">Thông báo KTX mới</div>
              <div style="font-size: 0.85rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${message}</div>
          </div>
          <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 1.1rem; padding: 4px;">&times;</button>
      `;
      toastContainer.appendChild(toast);
      requestAnimationFrame(() => toast.style.transform = 'translateX(0)');
      setTimeout(() => {
          toast.style.transform = 'translateX(120%)';
          setTimeout(() => toast.remove(), 300);
      }, 5000);
  }

  var pusher = new Pusher('d567ab30e7aa1832d4f4', { cluster: 'ap1' });
  var channel = pusher.subscribe('ktx-channel');
  channel.bind('new-notification', function(data) {
    showToast(data.tieu_de);
    let dot = document.getElementById('notificationDot');
    if(dot) dot.style.display = 'block';
  });
</script>
</body>
</html>
