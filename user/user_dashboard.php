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
$stmt = $conn->prepare("SELECT s.*, p.so_phong, p.khu_nha, h.id as hop_dong_id, h.ngay_bat_dau, h.ngay_ket_thuc, h.trang_thai as hd_trang_thai 
                        FROM SinhVien s 
                        LEFT JOIN HopDong h ON s.id = h.sinh_vien_id AND h.trang_thai = 'HieuLuc' 
                        LEFT JOIN Phong p ON h.phong_id = p.id 
                        WHERE s.tai_khoan_id = ?");
$stmt->execute([$user_id]);
$sinh_vien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sinh_vien) {
    $sinh_vien = ['id' => 0, 'ho_ten' => 'Sinh viên', 'mssv' => 'N/A', 'so_phong' => 'Chưa xếp', 'khu_nha' => ''];
}

$tien_no = $conn->prepare("SELECT SUM(so_tien) FROM HoaDon WHERE sinh_vien_id = ? AND trang_thai = 'ChuaThanhToan'");
$tien_no->execute([$sinh_vien['id']]);
$tong_no = $tien_no->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - KTX Student</title>
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
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-home" style="font-size: 1.5rem;"></i> 
                <span style="font-size: 1.1rem;">KÝ TÚC XÁ</span>
            </div>
            
            <div class="sidebar-section">USER</div>
            
            <nav class="sidebar-nav">
                <a href="user_dashboard.php" class="nav-item active"><i class="fas fa-home" style="width:20px;"></i> Trang chủ</a>
                <a href="user_thong_tin_ca_nhan.php" class="nav-item"><i class="fas fa-user" style="width:20px;"></i> Thông tin cá nhân</a>
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Trang chủ</div>
                
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
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($sinh_vien['ho_ten']); ?>&background=107c41&color=fff" alt="User" style="width:36px; height:36px; border-radius:50%; margin-left:0.5rem;">
                </div>
            </header>

            <div class="content-area">
                <!-- Top Row -->
                <div class="grid grid-cols-4" style="margin-bottom: 1.5rem;">
                    <!-- Info Room -->
                    <div class="card">
                        <div class="card-header" style="font-size: 1rem; border:none; padding-bottom:0;">Thông tin phòng</div>
                        <div class="card-body">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Phòng</span>
                                <strong><?php echo $sinh_vien['so_phong'] ? ($sinh_vien['khu_nha'] . ' - ' . $sinh_vien['so_phong']) : 'Chưa xếp'; ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Tòa nhà</span>
                                <strong><?php echo htmlspecialchars($sinh_vien['khu_nha'] ?? '-'); ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Ngày bắt đầu</span>
                                <strong><?php echo !empty($sinh_vien['ngay_bat_dau']) ? date('d/m/Y', strtotime($sinh_vien['ngay_bat_dau'])) : '-'; ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size: 0.875rem;">
                                <span class="text-muted">Ngày kết thúc</span>
                                <strong><?php echo !empty($sinh_vien['ngay_ket_thuc']) ? date('d/m/Y', strtotime($sinh_vien['ngay_ket_thuc'])) : '-'; ?></strong>
                            </div>
                            <div style="text-align: center; margin-top: 1rem;">
                                <a href="user_thong_tin_phong.php" class="btn btn-outline" style="width:100%; border-color:var(--border-color); color:var(--user-primary);">Xem chi tiết</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Debt -->
                    <div class="card">
                        <div class="card-header" style="font-size: 1rem; border:none; padding-bottom:0;">Công nợ hiện tại</div>
                        <div class="card-body">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Tổng nợ</span>
                                <strong class="text-danger"><?php echo number_format($tong_no); ?> đ</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Tiền phòng</span>
                                <strong>0 đ</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Tiền điện nước</span>
                                <strong><?php echo number_format($tong_no); ?> đ</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size: 0.875rem;">
                                <span class="text-muted">Tiền phạt</span>
                                <strong>0 đ</strong>
                            </div>
                            <div style="text-align: center; margin-top: 1rem;">
                                <a href="user_hoa_don.php" class="btn btn-outline" style="width:100%; border-color:var(--border-color); color:var(--user-primary);">Xem hóa đơn</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contract -->
                    <div class="card">
                        <div class="card-header" style="font-size: 1rem; border:none; padding-bottom:0;">Hợp đồng</div>
                        <div class="card-body">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Số hợp đồng</span>
                                <strong><?php echo $sinh_vien['hop_dong_id'] ? 'HD2024-' . str_pad($sinh_vien['hop_dong_id'], 4, '0', STR_PAD_LEFT) : '-'; ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Ngày ký</span>
                                <strong><?php echo !empty($sinh_vien['ngay_bat_dau']) ? date('d/m/Y', strtotime($sinh_vien['ngay_bat_dau'])) : '-'; ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.75rem; font-size: 0.875rem;">
                                <span class="text-muted">Ngày hết hạn</span>
                                <strong><?php echo !empty($sinh_vien['ngay_ket_thuc']) ? date('d/m/Y', strtotime($sinh_vien['ngay_ket_thuc'])) : '-'; ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size: 0.875rem;">
                                <span class="text-muted">Trạng thái</span>
                                <span class="text-success" style="font-weight: 500;"><?php echo $sinh_vien['hd_trang_thai'] == 'HieuLuc' ? 'Còn hiệu lực' : '-'; ?></span>
                            </div>
                            <div style="text-align: center; margin-top: 1rem;">
                                <a href="xem_hop_dong.php" class="btn btn-outline" style="width:100%; border-color:var(--border-color); color:var(--user-primary);">Xem hợp đồng</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notifications -->
                    <div class="card">
                        <div class="card-header" style="font-size: 1rem; border:none; padding-bottom:0;">Thông báo mới</div>
                        <div class="card-body" style="padding-top: 0.5rem;">
                            <?php
                            $tbs = $conn->query("SELECT * FROM ThongBao ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                            foreach($tbs as $tb):
                            ?>
                            <div style="display:flex; gap: 0.75rem; margin-bottom: 1rem;">
                                <i class="fas fa-bell text-warning" style="margin-top: 0.25rem;"></i>
                                <div>
                                    <div style="font-size: 0.875rem; font-weight: 500;"><?php echo htmlspecialchars($tb['tieu_de']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('d/m/Y', strtotime($tb['ngay_dang'])); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div style="text-align: center; margin-top: 1rem;">
                                <a href="user_thong_bao.php" class="btn btn-outline" style="width:100%; border-color:var(--border-color); color:var(--user-primary);">Xem tất cả</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="card">
                    <div class="card-header" style="font-size: 1.1rem; border:none;">Lịch sử thanh toán gần đây</div>
                    <div class="card-body" style="padding-top:0;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="background:transparent;">Ngày thanh toán</th>
                                    <th style="background:transparent;">Nội dung</th>
                                    <th style="background:transparent; text-align:right;">Số tiền</th>
                                    <th style="background:transparent;">Trạng thái</th>
                                    <th style="background:transparent;">Hóa đơn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $hoadon_ls = $conn->prepare("SELECT * FROM HoaDon WHERE sinh_vien_id = ? ORDER BY id DESC LIMIT 3");
                                $hoadon_ls->execute([$sinh_vien['id']]);
                                $ds_hoadon = $hoadon_ls->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($ds_hoadon) > 0) {
                                    foreach ($ds_hoadon as $hd) {
                                        echo '<tr>';
                                        echo '<td>' . date('d/m/Y', strtotime($hd['ngay_tao'])) . '</td>';
                                        echo '<td>' . htmlspecialchars($hd['noi_dung']) . '</td>';
                                        echo '<td style="text-align:right;">' . number_format($hd['so_tien']) . ' đ</td>';
                                        if ($hd['trang_thai'] == 'DaThanhToan') {
                                            echo '<td><span style="color: var(--success); font-weight: 500;">Đã thanh toán</span></td>';
                                        } else {
                                            echo '<td><span style="color: var(--danger); font-weight: 500;">Chưa thanh toán</span></td>';
                                        }
                                        echo '<td><a href="#" style="color: var(--user-primary); text-decoration:none;">HD' . str_pad($hd['id'], 5, '0', STR_PAD_LEFT) . '</a></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center text-muted">Không có lịch sử thanh toán.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                        <div style="text-align: right; margin-top: 1rem;">
                            <a href="user_hoa_don.php" style="color: var(--user-primary); text-decoration:none; font-size: 0.875rem;">Xem tất cả <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></a>
                        </div>
                    </div>
                </div>

            </div>
        </main>
        
        <!-- Bottom Nav for Mobile -->
        <nav class="bottom-nav">
            <a href="user_dashboard.php" class="bottom-nav-item active">
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
    
    // Hiện chấm đỏ
    let dot = document.getElementById('notificationDot');
    if(!dot) {
        let bellIcon = document.querySelector('.fa-bell');
        if(bellIcon) {
            dot = document.createElement('span');
            dot.id = 'notificationDot';
            dot.style.cssText = 'position:absolute; top:-2px; right:-2px; background:var(--danger); width:8px; height:8px; border-radius:50%;';
            bellIcon.appendChild(dot);
        }
    } else {
        dot.style.display = 'block';
    }

    // Thêm vào danh sách dropdown
    let dropdownContent = document.querySelector('#notificationDropdown > div:nth-child(2)');
    if (dropdownContent) {
        let emptyMsg = dropdownContent.querySelector('div[style*="text-align: center"]');
        if (emptyMsg) emptyMsg.remove();
        
        let newNoti = document.createElement('div');
        newNoti.style.cssText = 'padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; cursor: pointer; background: #f0fdf4; transition: 0.2s;';
        newNoti.onmouseover = function() { this.style.backgroundColor = '#f9fafb'; };
        newNoti.onmouseout = function() { this.style.backgroundColor = '#f0fdf4'; };
        newNoti.innerHTML = '<div style="font-size: 0.85rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem;">' + data.tieu_de + '</div><div style="font-size: 0.75rem; color: #15803d; margin-bottom: 0.25rem; font-weight: 500;"><i class="fas fa-sparkles"></i> Mới nhận</div>';
        dropdownContent.prepend(newNoti);
    }
  });
</script>
</body>
</html>
