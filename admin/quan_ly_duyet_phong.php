<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Xử lý duyệt Đăng ký phòng mới
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'duyet_hop_dong') {
    $hd_id = $_POST['hd_id'];
    $trang_thai = $_POST['trang_thai']; // 'HieuLuc' hoặc 'TuChoi' (chúng ta có thể xóa nếu từ chối, hoặc set 'ThanhLy')
    
    if ($trang_thai == 'HieuLuc') {
        $conn->prepare("UPDATE HopDong SET trang_thai = 'HieuLuc' WHERE id = ?")->execute([$hd_id]);
        $thong_bao = "Đã duyệt hợp đồng thành công!";
    } else {
        $conn->prepare("DELETE FROM HopDong WHERE id = ?")->execute([$hd_id]);
        $thong_bao = "Đã từ chối (xóa) yêu cầu đăng ký phòng.";
    }
}

// Xử lý duyệt Chuyển phòng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'duyet_chuyen_phong') {
    $ls_id = $_POST['ls_id'];
    $trang_thai = $_POST['trang_thai']; // 'DaDuyet' hoặc 'TuChoi'
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("SELECT sinh_vien_id, phong_moi_id FROM LichSuChuyenPhong WHERE id = ?");
        $stmt->execute([$ls_id]);
        $ls = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trang_thai == 'DaDuyet' && $ls) {
            // Cập nhật hợp đồng hiện tại sang phòng mới
            $conn->prepare("UPDATE HopDong SET phong_id = ? WHERE sinh_vien_id = ? AND trang_thai = 'HieuLuc'")->execute([$ls['phong_moi_id'], $ls['sinh_vien_id']]);
            // Cập nhật trạng thái lịch sử
            $conn->prepare("UPDATE LichSuChuyenPhong SET trang_thai = 'DaDuyet', ngay_duyet = NOW() WHERE id = ?")->execute([$ls_id]);
            $thong_bao = "Đã duyệt yêu cầu chuyển phòng thành công!";
        } else {
            $conn->prepare("UPDATE LichSuChuyenPhong SET trang_thai = 'TuChoi', ngay_duyet = NOW() WHERE id = ?")->execute([$ls_id]);
            $thong_bao = "Đã từ chối yêu cầu chuyển phòng.";
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        $loi = "Lỗi xử lý: " . $e->getMessage();
    }
}

// Lấy tất cả lịch sử và yêu cầu, kèm biến tìm kiếm chung
$tu_khoa = $_GET['q'] ?? '';

// Lấy danh sách hợp đồng chờ duyệt
$sql_hd = "
    SELECT h.*, s.ho_ten, s.mssv, p.khu_nha, p.so_phong 
    FROM HopDong h 
    JOIN SinhVien s ON h.sinh_vien_id = s.id 
    JOIN Phong p ON h.phong_id = p.id 
    WHERE h.trang_thai = 'ChoDuyet' 
";
if (!empty($tu_khoa)) {
    $sql_hd .= " AND (s.ho_ten LIKE :q OR s.mssv LIKE :q OR p.so_phong LIKE :q) ";
}
$sql_hd .= " ORDER BY h.id DESC";

$stmt_hd = $conn->prepare($sql_hd);
if (!empty($tu_khoa)) {
    $stmt_hd->bindValue(':q', "%$tu_khoa%");
}
$stmt_hd->execute();
$hop_dong_cho_duyet = $stmt_hd->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách yêu cầu chuyển phòng chờ duyệt
$sql_cp = "
    SELECT ls.*, s.ho_ten, s.mssv, pc.khu_nha as khu_cu, pc.so_phong as phong_cu, pm.khu_nha as khu_moi, pm.so_phong as phong_moi 
    FROM LichSuChuyenPhong ls 
    JOIN SinhVien s ON ls.sinh_vien_id = s.id 
    JOIN Phong pc ON ls.phong_cu_id = pc.id 
    JOIN Phong pm ON ls.phong_moi_id = pm.id 
    WHERE ls.trang_thai = 'ChoDuyet' 
";
if (!empty($tu_khoa)) {
    $sql_cp .= " AND (s.ho_ten LIKE :q OR s.mssv LIKE :q OR pc.so_phong LIKE :q OR pm.so_phong LIKE :q) ";
}
$sql_cp .= " ORDER BY ls.id ASC";

$stmt_cp = $conn->prepare($sql_cp);
if (!empty($tu_khoa)) {
    $stmt_cp->bindValue(':q', "%$tu_khoa%");
}
$stmt_cp->execute();
$chuyen_phong_cho_duyet = $stmt_cp->fetchAll(PDO::FETCH_ASSOC);

// Lấy tất cả lịch sử chuyển phòng
$sql_ls = "SELECT ls.*, s.ho_ten, s.mssv, s.sdt, pc.khu_nha as khu_cu, pc.so_phong as phong_cu, pm.khu_nha as khu_moi, pm.so_phong as phong_moi 
          FROM LichSuChuyenPhong ls 
          JOIN SinhVien s ON ls.sinh_vien_id = s.id 
          JOIN Phong pc ON ls.phong_cu_id = pc.id 
          JOIN Phong pm ON ls.phong_moi_id = pm.id 
          WHERE ls.trang_thai != 'ChoDuyet'";

if (!empty($tu_khoa)) {
    $sql_ls .= " AND (s.ho_ten LIKE :q OR s.mssv LIKE :q OR s.sdt LIKE :q)";
}
$sql_ls .= " ORDER BY ls.id DESC";

$stmt_ls = $conn->prepare($sql_ls);
if (!empty($tu_khoa)) {
    $stmt_ls->bindValue(':q', "%$tu_khoa%");
}
$stmt_ls->execute();
$lich_su_all = $stmt_ls->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt Đăng Ký & Chuyển Phòng - KTX Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .proof-img { max-width: 150px; border-radius: 4px; cursor: pointer; border: 1px solid var(--border-color); }
        .proof-img:hover { opacity: 0.8; }
        
        /* Image Modal */
        .img-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; }
        .img-modal img { max-width: 90%; max-height: 90%; border-radius: 8px; }
        .img-modal .close { position: absolute; top: 20px; right: 30px; color: white; font-size: 2rem; cursor: pointer; }
    </style>
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
                <a href="quan_ly_duyet_phong.php" class="nav-item active"><i class="fas fa-check-square" style="width:20px;"></i> Duyệt ĐK & Chuyển</a>
                <a href="quan_ly_sinh_vien.php" class="nav-item"><i class="fas fa-users" style="width:20px;"></i> Sinh viên & HĐ</a>
                <a href="quan_ly_dich_vu.php" class="nav-item"><i class="fas fa-bolt" style="width:20px;"></i> Điện Nước</a>
                <a href="quan_ly_hoa_don.php" class="nav-item"><i class="fas fa-file-invoice-dollar" style="width:20px;"></i> Hóa đơn</a>
                <a href="thong_bao.php" class="nav-item"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
                <a href="bao_cao.php" class="nav-item"><i class="fas fa-chart-bar" style="width:20px;"></i> Báo cáo</a>
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
                <div style="font-weight: 600; font-size: 1.25rem;">Quản Lý Yêu Cầu Chỗ Ở</div>
            </header>
            
            <div class="content-area">
                <?php if (isset($thong_bao)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($thong_bao); ?></div>
                <?php endif; ?>
                <?php if (isset($loi)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>

                <!-- Yêu cầu Đăng ký mới -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: 1rem;">
                        <h3 style="margin:0;"><i class="fas fa-user-plus text-primary"></i> Yêu Cầu Đăng Ký Phòng Mới</h3>
                        <form method="GET" style="display:flex; gap: 0.5rem; margin:0;">
                            <input type="text" name="q" class="form-control" placeholder="Tra cứu Tên, MSSV, Phòng..." value="<?php echo htmlspecialchars($tu_khoa); ?>" style="width: 250px;">
                            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
                            <?php if($tu_khoa): ?>
                                <a href="quan_ly_duyet_phong.php" class="btn btn-outline">Xóa lọc</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Sinh Viên</th>
                                        <th>Phòng Đăng Ký</th>
                                        <th>Ngày Gửi</th>
                                        <th>Minh Chứng Cọc</th>
                                        <th>Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($hop_dong_cho_duyet) == 0): ?>
                                        <tr><td colspan="5" class="text-center">Không có yêu cầu đăng ký mới.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($hop_dong_cho_duyet as $hd): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($hd['ho_ten']); ?></strong><br>
                                                <small class="text-muted">MSSV: <?php echo htmlspecialchars($hd['mssv']); ?></small>
                                            </td>
                                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($hd['khu_nha'] . ' - ' . $hd['so_phong']); ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($hd['ngay_bat_dau'])); ?></td>
                                            <td>
                                                <?php if($hd['minh_chung_coc']): ?>
                                                    <img src="<?php echo htmlspecialchars($hd['minh_chung_coc']); ?>" class="proof-img" onclick="showImgModal(this.src)">
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa tải lên</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <form method="POST" style="margin:0;">
                                                        <input type="hidden" name="action" value="duyet_hop_dong">
                                                        <input type="hidden" name="hd_id" value="<?php echo $hd['id']; ?>">
                                                        <input type="hidden" name="trang_thai" value="HieuLuc">
                                                        <button type="submit" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;" onclick="return confirm('Xác nhận đã nhận tiền cọc và duyệt phát hành hợp đồng?');">Duyệt & Phát Hành HĐ</button>
                                                    </form>
                                                    <form method="POST" style="margin:0;">
                                                        <input type="hidden" name="action" value="duyet_hop_dong">
                                                        <input type="hidden" name="hd_id" value="<?php echo $hd['id']; ?>">
                                                        <input type="hidden" name="trang_thai" value="TuChoi">
                                                        <button type="submit" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; color: var(--danger); border-color: var(--danger);" onclick="return confirm('Từ chối và xóa yêu cầu này?');"><i class="fas fa-times"></i></button>
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

                <!-- Yêu cầu Chuyển phòng -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: 1rem;">
                        <h3 style="margin:0;"><i class="fas fa-exchange-alt text-warning"></i> Yêu Cầu Chuyển Phòng</h3>
                        <form method="GET" style="display:flex; gap: 0.5rem; margin:0;">
                            <input type="text" name="q" class="form-control" placeholder="Tra cứu Tên, MSSV, Phòng..." value="<?php echo htmlspecialchars($tu_khoa); ?>" style="width: 250px;">
                            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
                            <?php if($tu_khoa): ?>
                                <a href="quan_ly_duyet_phong.php" class="btn btn-outline">Xóa lọc</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Sinh Viên</th>
                                        <th>Phòng Đang Ở</th>
                                        <th>Xin Chuyển Đến</th>
                                        <th>Lý Do</th>
                                        <th>Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($chuyen_phong_cho_duyet) == 0): ?>
                                        <tr><td colspan="5" class="text-center">Không có yêu cầu chuyển phòng.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($chuyen_phong_cho_duyet as $ls): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ls['ho_ten']); ?></strong><br>
                                                <small class="text-muted">MSSV: <?php echo htmlspecialchars($ls['mssv']); ?></small>
                                            </td>
                                            <td><span class="badge badge-secondary"><?php echo htmlspecialchars($ls['khu_cu'] . ' - ' . $ls['phong_cu']); ?></span></td>
                                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($ls['khu_moi'] . ' - ' . $ls['phong_moi']); ?></span></td>
                                            <td><?php echo htmlspecialchars($ls['ly_do']); ?></td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <form method="POST" style="margin:0;">
                                                        <input type="hidden" name="action" value="duyet_chuyen_phong">
                                                        <input type="hidden" name="ls_id" value="<?php echo $ls['id']; ?>">
                                                        <input type="hidden" name="trang_thai" value="DaDuyet">
                                                        <button type="submit" class="btn btn-warning" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;" onclick="return confirm('Duyệt cho phép sinh viên chuyển sang phòng mới? Hợp đồng sẽ tự cập nhật.');"><i class="fas fa-check"></i> Duyệt Chuyển</button>
                                                    </form>
                                                    <form method="POST" style="margin:0;">
                                                        <input type="hidden" name="action" value="duyet_chuyen_phong">
                                                        <input type="hidden" name="ls_id" value="<?php echo $ls['id']; ?>">
                                                        <input type="hidden" name="trang_thai" value="TuChoi">
                                                        <button type="submit" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; color: var(--danger); border-color: var(--danger);" onclick="return confirm('Từ chối yêu cầu chuyển phòng?');"><i class="fas fa-times"></i></button>
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

                <!-- Lịch sử chuyển phòng -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 style="margin:0;"><i class="fas fa-history text-secondary"></i> Lịch Sử Đổi Phòng</h3>
                        <form method="GET" style="display:flex; gap: 0.5rem; margin:0;">
                            <input type="text" name="q" class="form-control" placeholder="Tra cứu Tên, MSSV, SĐT..." value="<?php echo htmlspecialchars($tu_khoa); ?>" style="width: 250px;">
                            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
                            <?php if($tu_khoa): ?>
                                <a href="quan_ly_duyet_phong.php" class="btn btn-outline">Xóa lọc</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ngày Duyệt</th>
                                        <th>Sinh Viên</th>
                                        <th>Từ Phòng</th>
                                        <th>Đến Phòng</th>
                                        <th>Trạng Thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($lich_su_all) == 0): ?>
                                        <tr><td colspan="5" class="text-center">Chưa có lịch sử.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($lich_su_all as $ls): ?>
                                        <tr>
                                            <td><?php echo $ls['ngay_duyet'] ? date('d/m/Y H:i', strtotime($ls['ngay_duyet'])) : '-'; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ls['ho_ten']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($ls['mssv']); ?> - <?php echo htmlspecialchars($ls['sdt']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($ls['khu_cu'] . ' - ' . $ls['phong_cu']); ?></td>
                                            <td><?php echo htmlspecialchars($ls['khu_moi'] . ' - ' . $ls['phong_moi']); ?></td>
                                            <td>
                                                <?php if($ls['trang_thai'] == 'DaDuyet'): ?>
                                                    <span class="badge badge-success">Thành công</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Đã từ chối</span>
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
        </main>
    </div>

    <!-- Modal Xem Ảnh -->
    <div class="img-modal" id="imgModal" onclick="this.style.display='none'">
        <i class="fas fa-times close"></i>
        <img id="fullImg" src="">
    </div>
    <script>
        function showImgModal(src) {
            document.getElementById('fullImg').src = src;
            document.getElementById('imgModal').style.display = 'flex';
        }
    </script>
</body>
</html>
