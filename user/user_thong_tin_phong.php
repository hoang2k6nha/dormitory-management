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

// Lấy phòng hiện tại của SV
$stmt_sv = $conn->prepare("SELECT s.id, h.phong_id, p.khu_nha, p.so_phong, p.loai_phong, p.so_giuong_toi_da, p.gia_phong 
                           FROM SinhVien s 
                           JOIN HopDong h ON s.id = h.sinh_vien_id AND h.trang_thai = 'HieuLuc' 
                           JOIN Phong p ON h.phong_id = p.id 
                           WHERE s.tai_khoan_id = ?");
$stmt_sv->execute([$user_id]);
$phong = $stmt_sv->fetch(PDO::FETCH_ASSOC);

if (!$phong) {
    die("Bạn chưa có phòng hoặc hợp đồng đã hết hạn.");
}

// Lấy danh sách bạn cùng phòng
$stmt_ban = $conn->prepare("SELECT s.ho_ten, s.mssv, s.lop 
                            FROM HopDong h 
                            JOIN SinhVien s ON h.sinh_vien_id = s.id 
                            WHERE h.phong_id = ? AND h.trang_thai = 'HieuLuc' AND s.id != ?");
$stmt_ban->execute([$phong['phong_id'], $phong['id']]);
$ban_cung_phong = $stmt_ban->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách trang thiết bị
$stmt_tb = $conn->prepare("SELECT ten_thiet_bi, so_luong, tinh_trang FROM ThietBi WHERE phong_id = ?");
$stmt_tb->execute([$phong['phong_id']]);
$thiet_bi = $stmt_tb->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Phòng - KTX Student</title>
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
                <a href="user_thong_tin_ca_nhan.php" class="nav-item"><i class="fas fa-user" style="width:20px;"></i> Thông tin cá nhân</a>
                <a href="user_phong.php" class="nav-item"><i class="fas fa-bed" style="width:20px;"></i> Đăng ký ở KTX</a>
                <a href="user_thong_tin_phong.php" class="nav-item active"><i class="fas fa-info-circle" style="width:20px;"></i> Thông tin phòng</a>
                <a href="xem_hop_dong.php" class="nav-item"><i class="fas fa-file-signature" style="width:20px;"></i> Hợp đồng</a>
                <a href="user_hoa_don.php" class="nav-item"><i class="fas fa-credit-card" style="width:20px;"></i> Thanh toán</a>
                <a href="user_thong_bao.php" class="nav-item"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
                <a href="user_ho_tro.php" class="nav-item"><i class="fas fa-headset" style="width:20px;"></i> Hỗ trợ</a>
                <a href="../logout.php" class="nav-item mt-4"><i class="fas fa-sign-out-alt" style="width:20px;"></i> Đăng xuất</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Xem thông tin phòng - <?php echo htmlspecialchars($phong['khu_nha'] . ' - ' . $phong['so_phong']); ?></div>
            </header>
            
            <div class="content-area">
                <div class="grid grid-cols-2">
                    <div class="card">
                        <div class="card-header" style="border:none;">Thông tin chung</div>
                        <div class="card-body">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Khu nhà</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 500; text-align:right;"><?php echo htmlspecialchars($phong['khu_nha']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Số phòng</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 500; text-align:right;"><?php echo htmlspecialchars($phong['so_phong']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Loại phòng</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 500; text-align:right;"><?php echo htmlspecialchars($phong['loai_phong']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Số giường</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 500; text-align:right;"><?php echo htmlspecialchars($phong['so_giuong_toi_da']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0; border:none; color: var(--text-muted);">Giá phòng</td>
                                    <td style="padding: 0.5rem 0; border:none; font-weight: 500; text-align:right;"><?php echo number_format($phong['gia_phong']); ?> VNĐ</td>
                                </tr>
                            </table>
                            <div style="margin-top: 1.5rem;">
                                <a href="user_ho_tro.php?type=doi_phong" class="btn btn-outline" style="width: 100%; border-color: var(--warning); color: var(--warning);">
                                    <i class="fas fa-exchange-alt"></i> Xin chuyển phòng
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header" style="border:none;">Danh sách bạn cùng phòng</div>
                        <div class="card-body" style="padding-top:0;">
                            <?php if (count($ban_cung_phong) == 0): ?>
                                <p class="text-muted" style="text-align: center; margin-top: 1rem;">Bạn đang ở một mình trong phòng này.</p>
                            <?php else: ?>
                                <table style="width: 100%;">
                                    <?php foreach ($ban_cung_phong as $ban): ?>
                                    <tr>
                                        <td style="padding: 1rem 0; border-bottom: 1px dashed var(--border-color);">
                                            <div style="display:flex; align-items:center; gap: 1rem;">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($ban['ho_ten']); ?>&background=e2e8f0&color=64748b" style="width:40px; height:40px; border-radius:50%;">
                                                <div>
                                                    <div style="font-weight: 600; font-size: 0.875rem; color: var(--text-dark);"><?php echo htmlspecialchars($ban['ho_ten']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">MSSV: <?php echo htmlspecialchars($ban['mssv'] ?? ''); ?> | Lớp: <?php echo htmlspecialchars($ban['lop'] ?? ''); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>
                        </div>
                        </div>
                    </div>

                    <!-- Hình ảnh nội thất và thiết bị -->
                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header" style="border:none;">
                            <h3 style="margin:0;"><i class="fas fa-couch text-primary"></i> Nội thất & Trang thiết bị</h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2" style="gap: 2rem; align-items: start;">
                                <div>
                                    <img src="https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&q=80&w=800" alt="Nội thất phòng" style="width: 100%; border-radius: 8px; object-fit: cover; height: 250px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                    <p class="text-center text-muted" style="margin-top: 0.5rem; font-size: 0.875rem;">Hình ảnh minh họa phòng tiêu chuẩn</p>
                                </div>
                                <div>
                                    <?php if(count($thiet_bi) == 0): ?>
                                        <p class="text-muted">Chưa có thông tin trang thiết bị cho phòng này.</p>
                                    <?php else: ?>
                                        <ul style="list-style: none; padding: 0; margin: 0;">
                                            <?php foreach($thiet_bi as $tb): ?>
                                            <li style="display:flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                                <span><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> <?php echo htmlspecialchars($tb['ten_thiet_bi']); ?></span>
                                                <span>
                                                    <strong>x<?php echo $tb['so_luong']; ?></strong> 
                                                    <?php 
                                                        if($tb['tinh_trang'] == 'Tot') echo '<span class="badge badge-success" style="margin-left:0.5rem;">Tốt</span>';
                                                        elseif($tb['tinh_trang'] == 'BaoTri') echo '<span class="badge badge-warning" style="margin-left:0.5rem;">Bảo trì</span>';
                                                        else echo '<span class="badge badge-danger" style="margin-left:0.5rem;">Hỏng</span>';
                                                    ?>
                                                </span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header" style="border:none;">
                            <h3 style="margin:0;"><i class="fas fa-scroll text-primary"></i> Nội Quy Ký Túc Xá</h3>
                        </div>
                        <div class="card-body">
                            <ul style="line-height: 1.8; color: var(--text-dark); margin-left: 1rem;">
                                <li><strong>Điều 1:</strong> Giữ gìn vệ sinh chung, không xả rác bừa bãi trong phòng và hành lang.</li>
                                <li><strong>Điều 2:</strong> Không sử dụng các thiết bị điện sinh nhiệt lớn (bếp ga mini, bếp điện, nồi lẩu) trong phòng để phòng chống cháy nổ.</li>
                                <li><strong>Điều 3:</strong> Không tàng trữ, sử dụng chất cấm, rượu bia, cờ bạc dưới mọi hình thức.</li>
                                <li><strong>Điều 4:</strong> Tuân thủ giờ giấc KTX: Đóng cửa lúc 23h00 và mở cửa lúc 05h00 sáng.</li>
                                <li><strong>Điều 5:</strong> Không tự ý tiếp khách trong phòng ở, khách đến thăm phải đăng ký tại phòng Bảo vệ.</li>
                                <li><strong>Điều 6:</strong> Đóng phí phòng và điện nước đúng thời hạn quy định (trước ngày 10 hàng tháng).</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
