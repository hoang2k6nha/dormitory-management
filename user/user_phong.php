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

// Lấy thông tin sinh viên
$stmt = $conn->prepare("SELECT id, mssv FROM SinhVien WHERE tai_khoan_id = ?");
$stmt->execute([$user_id]);
$sv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sv) {
    die("Không tìm thấy hồ sơ sinh viên.");
}
$sinh_vien_id = $sv['id'];

// Kiểm tra xem sinh viên đã có hợp đồng hay chưa
$stmt_hd = $conn->prepare("SELECT h.*, p.khu_nha, p.so_phong FROM HopDong h JOIN Phong p ON h.phong_id = p.id WHERE h.sinh_vien_id = ? AND h.trang_thai IN ('HieuLuc', 'ChoDuyet') ORDER BY h.id DESC LIMIT 1");
$stmt_hd->execute([$sinh_vien_id]);
$hop_dong_hien_tai = $stmt_hd->fetch(PDO::FETCH_ASSOC);

// Xử lý chọn phòng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['phong_id']) && !$hop_dong_hien_tai) {
    $phong_id = $_POST['phong_id'];
    
    // Xử lý upload minh chứng
    $minh_chung_path = '';
    if (isset($_FILES['minh_chung']) && $_FILES['minh_chung']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES['minh_chung']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['minh_chung']['tmp_name'], $target_file)) {
            $minh_chung_path = $target_file;
        }
    }
    
    if (!$minh_chung_path) {
        $loi = "Vui lòng tải lên ảnh minh chứng chuyển khoản tiền cọc.";
    } else {
        // Kiểm tra xem phòng còn chỗ không
        $stmt_check = $conn->prepare("SELECT so_giuong_toi_da, (SELECT COUNT(*) FROM HopDong WHERE phong_id = ? AND trang_thai = 'HieuLuc') as dang_o FROM Phong WHERE id = ?");
        $stmt_check->execute([$phong_id, $phong_id]);
        $phong = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($phong && $phong['so_giuong_toi_da'] > $phong['dang_o']) {
            try {
                $ngay_bat_dau = date('Y-m-d');
                $ngay_ket_thuc = date('Y-m-d', strtotime('+1 year'));
                $conn->prepare("INSERT INTO HopDong (sinh_vien_id, phong_id, ngay_bat_dau, ngay_ket_thuc, trang_thai, minh_chung_coc) VALUES (?, ?, ?, ?, 'ChoDuyet', ?)")
                     ->execute([$sinh_vien_id, $phong_id, $ngay_bat_dau, $ngay_ket_thuc, $minh_chung_path]);
                $thong_bao = "Bạn đã gửi yêu cầu đăng ký phòng thành công! Vui lòng chờ Admin kiểm tra và duyệt hợp đồng.";
                // Tải lại hợp đồng
                $stmt_hd->execute([$sinh_vien_id]);
                $hop_dong_hien_tai = $stmt_hd->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $loi = "Lỗi khi đăng ký phòng: " . $e->getMessage();
            }
        } else {
            $loi = "Phòng này đã đầy hoặc không tồn tại.";
        }
    }
}

// Xử lý yêu cầu chuyển phòng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'chuyen_phong' && $hop_dong_hien_tai) {
    $phong_moi_id = $_POST['phong_moi_id'];
    $ly_do = $_POST['ly_do'];
    
    // Kiểm tra xem đã có yêu cầu nào đang chờ chưa
    $stmt_check_yc = $conn->prepare("SELECT id FROM LichSuChuyenPhong WHERE sinh_vien_id = ? AND trang_thai = 'ChoDuyet'");
    $stmt_check_yc->execute([$sinh_vien_id]);
    if ($stmt_check_yc->fetch()) {
        $loi = "Bạn đang có một yêu cầu chuyển phòng chờ duyệt. Vui lòng chờ kết quả trước khi gửi yêu cầu mới.";
    } else {
        try {
            $conn->prepare("INSERT INTO LichSuChuyenPhong (sinh_vien_id, phong_cu_id, phong_moi_id, ly_do, trang_thai) VALUES (?, ?, ?, ?, 'ChoDuyet')")
                 ->execute([$sinh_vien_id, $hop_dong_hien_tai['phong_id'], $phong_moi_id, $ly_do]);
            $thong_bao = "Đã gửi yêu cầu chuyển phòng thành công. Vui lòng chờ Admin duyệt.";
        } catch (PDOException $e) {
            $loi = "Lỗi gửi yêu cầu chuyển phòng: " . $e->getMessage();
        }
    }
}

// Lấy danh sách phòng trống
$danh_sach_phong = $conn->query("
    SELECT p.*, (SELECT COUNT(*) FROM HopDong h WHERE h.phong_id = p.id AND h.trang_thai = 'HieuLuc') as dang_o 
    FROM Phong p 
    WHERE p.trang_thai = 'Trong' OR p.trang_thai = 'Day'
    HAVING dang_o < p.so_giuong_toi_da
    ORDER BY p.khu_nha, p.so_phong
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn Phòng - KTX Student</title>
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
                <a href="user_phong.php" class="nav-item active"><i class="fas fa-bed" style="width:20px;"></i> Đăng ký ở KTX</a>
                <a href="user_thong_tin_phong.php" class="nav-item"><i class="fas fa-info-circle" style="width:20px;"></i> Thông tin phòng</a>
                <a href="xem_hop_dong.php" class="nav-item"><i class="fas fa-file-signature" style="width:20px;"></i> Hợp đồng</a>
                <a href="user_hoa_don.php" class="nav-item"><i class="fas fa-credit-card" style="width:20px;"></i> Thanh toán</a>
                <a href="user_thong_bao.php" class="nav-item"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
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
                <div style="font-weight: 600; font-size: 1.25rem;">Phòng & Chỗ Ở</div>
                
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
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $thong_bao; ?></div>
                <?php endif; ?>
                <?php if (isset($loi)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $loi; ?></div>
                <?php endif; ?>

                <?php if ($hop_dong_hien_tai && $hop_dong_hien_tai['trang_thai'] == 'ChoDuyet'): ?>
                    <div class="card" style="max-width: 600px; margin: 0 auto; text-align: center;">
                        <div class="card-body">
                            <i class="fas fa-clock text-warning" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <h2 style="margin-bottom: 0.5rem; color: var(--warning);">Đang chờ duyệt</h2>
                            <p class="text-muted-light">Yêu cầu đăng ký phòng <strong><?php echo htmlspecialchars($hop_dong_hien_tai['khu_nha'] . ' - ' . $hop_dong_hien_tai['so_phong']); ?></strong> của bạn đang được Admin xét duyệt tiền cọc.</p>
                            <p>Vui lòng chờ trong thời gian ngắn. Sau khi duyệt, hợp đồng sẽ có hiệu lực.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if ($hop_dong_hien_tai && $hop_dong_hien_tai['trang_thai'] == 'HieuLuc'): ?>
                        <div class="card mb-4" style="text-align: center;">
                            <div class="card-body">
                                <i class="fas fa-home text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <h2 style="margin-bottom: 0.5rem;">Bạn đang ở phòng: <?php echo htmlspecialchars($hop_dong_hien_tai['khu_nha'] . ' - ' . $hop_dong_hien_tai['so_phong']); ?></h2>
                                <p class="text-muted-light">Mã Hợp Đồng: <strong>#<?php echo $hop_dong_hien_tai['id']; ?></strong> | Ngày hết hạn: <strong><?php echo date('d/m/Y', strtotime($hop_dong_hien_tai['ngay_ket_thuc'])); ?></strong></p>
                                <div style="margin-top: 1rem;">
                                    <a href="xem_hop_dong.php" class="btn btn-outline"><i class="fas fa-file-signature"></i> Xem chi tiết hợp đồng</a>
                                    <a href="user_lich_su_chuyen.php" class="btn btn-secondary"><i class="fas fa-history"></i> Lịch sử chuyển phòng</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h3 style="margin:0;">Danh sách Phòng Trống có thể <?php echo $hop_dong_hien_tai ? 'chuyển đến' : 'đăng ký'; ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-3">
                                <?php if(count($danh_sach_phong) == 0): ?>
                                    <div style="grid-column: span 3;" class="text-center text-muted-light">Hiện tại KTX đã hết phòng trống. Bạn vui lòng quay lại sau.</div>
                                <?php else: ?>
                                    <?php foreach ($danh_sach_phong as $phong): ?>
                                        <?php if($hop_dong_hien_tai && $hop_dong_hien_tai['phong_id'] == $phong['id']) continue; ?>
                                        <div style="border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; transition: var(--transition);" onmouseover="this.style.borderColor='var(--primary-color)';" onmouseout="this.style.borderColor='var(--border-light)';">
                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
                                                <h3 style="margin:0; color: var(--primary-color);"><i class="fas fa-door-closed"></i> <?php echo htmlspecialchars($phong['so_phong']); ?></h3>
                                                <span class="badge <?php echo $phong['loai_phong'] == 'VIP' ? 'badge-warning' : 'badge-primary'; ?>"><?php echo $phong['loai_phong']; ?></span>
                                            </div>
                                            <p style="margin:0 0 0.5rem 0; color: var(--text-muted-light);"><i class="fas fa-building" style="width:20px;"></i> Khu nhà: <strong><?php echo htmlspecialchars($phong['khu_nha']); ?></strong></p>
                                            <p style="margin:0 0 0.5rem 0; color: var(--text-muted-light);"><i class="fas fa-users" style="width:20px;"></i> Chỗ trống: <strong><?php echo $phong['dang_o']; ?> / <?php echo $phong['so_giuong_toi_da']; ?></strong></p>
                                            <p style="margin:0 0 1rem 0; color: var(--text-muted-light);"><i class="fas fa-money-bill" style="width:20px;"></i> Giá: <strong style="color: var(--text-light);"><?php echo number_format($phong['gia_phong']); ?> đ/tháng</strong></p>
                                            
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button type="button" class="btn btn-secondary" style="flex: 1; padding: 0.5rem;" onclick="showRoomDetails('<?php echo $phong['loai_phong']; ?>', '<?php echo number_format($phong['gia_phong']); ?>')"><i class="fas fa-info-circle"></i> Xem TT</button>
                                                <?php if($hop_dong_hien_tai): ?>
                                                    <button type="button" class="btn btn-outline" style="flex: 1; padding: 0.5rem;" onclick="openTransferModal('<?php echo $phong['id']; ?>', '<?php echo htmlspecialchars($phong['khu_nha'] . ' - ' . $phong['so_phong']); ?>')">Chuyển phòng</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline" style="flex: 1; padding: 0.5rem;" onclick="openRegisterModal('<?php echo $phong['id']; ?>', '<?php echo htmlspecialchars($phong['khu_nha'] . ' - ' . $phong['so_phong']); ?>', '<?php echo $sv['mssv']; ?>')">Đăng ký</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
                </main>
        
        <!-- Bottom Nav for Mobile -->
        <nav class="bottom-nav">
            <a href="user_dashboard.php" class="bottom-nav-item">
                <i class="fas fa-home"></i>
                <span>Trang chủ</span>
            </a>
            <a href="user_phong.php" class="bottom-nav-item active">
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

    <!-- Modal Xem Thông Tin Phòng -->
    <style>
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-content {
        background: var(--bg-color, #fff);
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        padding: 1.5rem;
        position: relative;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .modal-close {
        position: absolute;
        top: 15px; right: 15px;
        cursor: pointer;
        font-size: 1.2rem;
        color: var(--text-muted);
    }
    .room-img-modal {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    .room-amenities li {
        margin-bottom: 0.5rem;
        display: flex;
        align-items: flex-start;
        font-size: 0.95rem;
    }
    .room-amenities li i {
        width: 25px;
        color: var(--primary-color);
        margin-top: 3px;
    }
    </style>

    <div class="modal-overlay" id="roomModal" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <i class="fas fa-times modal-close" onclick="closeRoomModal()"></i>
            <h3 id="modalTitle" style="margin-top:0; color: var(--primary-color);">Thông tin phòng</h3>
            <img id="modalImg" src="" alt="Room" class="room-img-modal">
            <div id="modalPrice" style="font-size: 1.3rem; font-weight: bold; color: var(--danger-color, #e3342f); margin-bottom: 1rem;"></div>
            <ul id="modalAmenities" class="room-amenities" style="list-style: none; padding: 0;">
                <!-- Filled by JS -->
            </ul>
            <div class="service-price" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border-light); font-size: 0.9rem;">
                <strong>Điện:</strong> 3.500 đ/kWh<br>
                <strong>Nước:</strong> 15.000 đ/m³<br>
                <span id="modalServiceNote" style="font-size: 0.8rem; color: var(--text-muted-light);"></span>
            </div>
            <button class="btn btn-secondary mt-3" style="width: 100%;" onclick="closeRoomModal()">Đóng</button>
        </div>
    </div>

    <!-- Modal Xác Nhận Đăng Ký -->
    <div class="modal-overlay" id="registerModal" onclick="closeRegisterModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <i class="fas fa-times modal-close" onclick="closeRegisterModal()"></i>
            <h3 style="margin-top:0; color: var(--primary-color);">Xác Nhận Đăng Ký Phòng</h3>
            <p style="font-size: 1.1rem;">Phòng đã chọn: <strong id="regRoomName" style="color: var(--danger-color);"></strong></p>
            <div class="alert alert-info" style="margin-bottom: 1rem; text-align: left; font-size: 0.95rem;">
                <strong style="display:block; margin-bottom: 0.5rem;">Vui lòng chuyển khoản tiền cọc 500.000đ:</strong>
                Ngân hàng: <strong>Vietcombank</strong><br>
                Số tài khoản: <strong>1234567890</strong><br>
                Chủ tài khoản: <strong>BAN QUAN LY KTX</strong><br>
                Nội dung CK: <strong id="regCode" style="color: var(--danger-color);"></strong>
            </div>
            <form method="POST" action="user_phong.php" enctype="multipart/form-data" style="text-align: left;">
                <input type="hidden" name="phong_id" id="regPhongId" value="">
                <div class="form-group">
                    <label class="form-label">Tải lên biên lai chuyển khoản (Bắt buộc):</label>
                    <input type="file" name="minh_chung" accept="image/*" class="form-control" required style="padding: 0.5rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Hoàn Tất Đăng Ký & Gửi Yêu Cầu</button>
            </form>
        </div>
    </div>

    <!-- Modal Chuyển Phòng -->
    <div class="modal-overlay" id="transferModal" onclick="closeTransferModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <i class="fas fa-times modal-close" onclick="closeTransferModal()"></i>
            <h3 style="margin-top:0; color: var(--primary-color);">Yêu Cầu Chuyển Phòng</h3>
            <p style="font-size: 1.1rem;">Bạn muốn chuyển sang phòng: <strong id="transRoomName" style="color: var(--danger-color);"></strong></p>
            <div class="alert alert-info" style="margin-bottom: 1rem; text-align: left; font-size: 0.95rem;">
                Khi yêu cầu được duyệt, hợp đồng hiện tại của bạn sẽ được chuyển sang phòng mới. Mọi chi phí sẽ được tính toán lại theo giá phòng mới từ tháng sau.
            </div>
            <form method="POST" action="user_phong.php" style="text-align: left;">
                <input type="hidden" name="action" value="chuyen_phong">
                <input type="hidden" name="phong_moi_id" id="transPhongId" value="">
                <div class="form-group">
                    <label class="form-label">Lý do xin chuyển phòng (Bắt buộc):</label>
                    <textarea name="ly_do" class="form-control" rows="3" required placeholder="Ví dụ: Phòng hiện tại xa khu học, muốn sang phòng VIP..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Gửi Yêu Cầu Chuyển Phòng</button>
            </form>
        </div>
    </div>

    <script>
    const roomData = {
        'Thuong': {
            title: 'Phòng Tiêu Chuẩn',
            img: 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&q=80&w=400',
            amenities: [
                '<i class="fas fa-bed"></i> Giường tầng tiêu chuẩn (4-6 người/phòng)',
                '<i class="fas fa-fan"></i> Quạt trần & Quạt treo tường',
                '<i class="fas fa-box"></i> Tủ đồ dùng chung lớn',
                '<i class="fas fa-chair"></i> Bàn học chung, không gian mở',
                '<i class="fas fa-restroom"></i> Nhà vệ sinh riêng trong phòng'
            ],
            note: '(Chi phí điện nước chia đều theo số người)'
        },
        'VIP': {
            title: 'Phòng VIP (Cao Cấp)',
            img: 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&q=80&w=400',
            amenities: [
                '<i class="fas fa-bed"></i> Giường đơn cao cấp (1-2 người/phòng)',
                '<i class="fas fa-snowflake"></i> Máy lạnh (Điều hòa) 2 chiều',
                '<i class="fas fa-lock"></i> Tủ đồ cá nhân riêng có khóa an toàn',
                '<i class="fas fa-laptop-house"></i> Bàn học cá nhân rộng rãi',
                '<i class="fas fa-shower"></i> Bình nóng lạnh & Máy giặt chung khu'
            ],
            note: '(Chi phí điện nước tính theo công tơ riêng)'
        }
    };

    function showRoomDetails(type, price) {
        const data = roomData[type] || roomData['Thuong'];
        document.getElementById('modalTitle').innerText = data.title;
        document.getElementById('modalImg').src = data.img;
        document.getElementById('modalPrice').innerText = price + ' đ / tháng';
        
        const ul = document.getElementById('modalAmenities');
        ul.innerHTML = '';
        data.amenities.forEach(function(item) {
            ul.innerHTML += '<li>' + item + '</li>';
        });
        
        document.getElementById('modalServiceNote').innerText = data.note;
        document.getElementById('roomModal').classList.add('active');
    }

    function closeRoomModal() {
        document.getElementById('roomModal').classList.remove('active');
    }

    function openRegisterModal(phongId, roomName, mssv) {
        document.getElementById('regPhongId').value = phongId;
        document.getElementById('regRoomName').innerText = roomName;
        document.getElementById('regCode').innerText = mssv + ' COC PHONG ' + roomName;
        document.getElementById('registerModal').classList.add('active');
    }

    function closeRegisterModal() {
        document.getElementById('registerModal').classList.remove('active');
    }

    function openTransferModal(phongId, roomName) {
        document.getElementById('transPhongId').value = phongId;
        document.getElementById('transRoomName').innerText = roomName;
        document.getElementById('transferModal').classList.add('active');
    }

    function closeTransferModal() {
        document.getElementById('transferModal').classList.remove('active');
    }

    function closeModal(e) {
        if (e.target === document.getElementById('roomModal')) {
            closeRoomModal();
        }
        if (e.target === document.getElementById('registerModal')) {
            closeRegisterModal();
        }
        if (e.target === document.getElementById('transferModal')) {
            closeTransferModal();
        }
    }
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
