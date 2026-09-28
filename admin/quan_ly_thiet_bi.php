<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'them_thiet_bi') {
        $phong_id = $_POST['phong_id'];
        $ten_thiet_bi = $_POST['ten_thiet_bi'];
        $so_luong = $_POST['so_luong'];
        $tinh_trang = $_POST['tinh_trang'];
        
        try {
            $conn->prepare("INSERT INTO ThietBi (phong_id, ten_thiet_bi, so_luong, tinh_trang) VALUES (?, ?, ?, ?)")->execute([$phong_id, $ten_thiet_bi, $so_luong, $tinh_trang]);
            $thong_bao = "Thêm thiết bị thành công!";
        } catch(PDOException $e) {
            $loi = "Lỗi thêm thiết bị: " . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'cap_nhat') {
        $tb_id = $_POST['tb_id'];
        $tinh_trang = $_POST['tinh_trang'];
        try {
            $conn->prepare("UPDATE ThietBi SET tinh_trang = ? WHERE id = ?")->execute([$tinh_trang, $tb_id]);
            $thong_bao = "Cập nhật tình trạng thiết bị thành công!";
        } catch(PDOException $e) {
            $loi = "Lỗi cập nhật thiết bị: " . $e->getMessage();
        }
    }
}

$danh_sach_phong = $conn->query("SELECT id, khu_nha, so_phong FROM Phong ORDER BY khu_nha, so_phong")->fetchAll(PDO::FETCH_ASSOC);

$tu_khoa_tb = $_GET['q_tb'] ?? '';
$sql_tb = "SELECT tb.*, p.khu_nha, p.so_phong 
           FROM ThietBi tb 
           JOIN Phong p ON tb.phong_id = p.id ";
if (!empty($tu_khoa_tb)) {
    $sql_tb .= " WHERE tb.ten_thiet_bi LIKE :q OR p.khu_nha LIKE :q OR p.so_phong LIKE :q ";
}
$sql_tb .= " ORDER BY p.khu_nha, p.so_phong";
$stmt = $conn->prepare($sql_tb);
if (!empty($tu_khoa_tb)) {
    $stmt->execute([':q' => "%$tu_khoa_tb%"]);
} else {
    $stmt->execute();
}
$thiet_bis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Thiết Bị - KTX Admin</title>
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
                <a href="quan_ly_thiet_bi.php" class="nav-item active"><i class="fas fa-desktop" style="width:20px;"></i> Thiết bị</a>
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
                <i class="fas fa-ellipsis-v" style="margin-left: auto; cursor:pointer;"></i>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Quản Lý Thiết Bị & Tài Sản</div>
            </header>
            
            <div class="content-area">
                <?php if (isset($thong_bao)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($thong_bao); ?></div>
                <?php endif; ?>
                <?php if (isset($loi)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-3">
                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: 1rem;">
                            <h3 style="margin:0;"><i class="fas fa-desktop text-primary"></i> Danh sách Tài Sản</h3>
                            
                            <form method="GET" action="quan_ly_thiet_bi.php" style="display:flex; gap: 0.5rem; margin:0;">
                                <input type="text" name="q_tb" class="form-control" placeholder="Tìm Thiết bị, Phòng..." value="<?php echo htmlspecialchars($tu_khoa_tb); ?>" style="width: 200px; padding: 0.4rem 0.75rem;">
                                <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 1rem;"><i class="fas fa-search"></i> Tìm</button>
                                <?php if($tu_khoa_tb): ?>
                                    <a href="quan_ly_thiet_bi.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Xóa</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="card-body" style="padding: 1rem;">
                            <?php 
                            // Group assets by room
                            $grouped_tb = [];
                            foreach ($thiet_bis as $tb) {
                                $room_key = $tb['khu_nha'] . ' - ' . $tb['so_phong'];
                                if (!isset($grouped_tb[$room_key])) {
                                    $grouped_tb[$room_key] = [];
                                }
                                $grouped_tb[$room_key][] = $tb;
                            }
                            ?>
                            
                            <?php if(empty($grouped_tb)): ?>
                                <div class="text-center text-muted-light" style="padding: 2rem;">Không tìm thấy thiết bị nào.</div>
                            <?php else: ?>
                                <?php foreach ($grouped_tb as $room_name => $items): ?>
                                    <?php $room_id_hash = md5($room_name); ?>
                                    <div style="margin-bottom: 1rem; border: 1px solid var(--border-light); border-radius: 8px; overflow: hidden;">
                                        <div style="background: #f8fafc; padding: 1rem; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: background 0.3s;" 
                                             onclick="var el = document.getElementById('room_<?php echo $room_id_hash; ?>'); var icon = document.getElementById('icon_<?php echo $room_id_hash; ?>'); if(el.style.display === 'none'){ el.style.display = 'block'; icon.className = 'fas fa-chevron-up'; } else { el.style.display = 'none'; icon.className = 'fas fa-chevron-down'; }"
                                             onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='#f8fafc';">
                                            <span style="color: var(--primary-color);"><i class="fas fa-door-closed"></i> Phòng: <?php echo htmlspecialchars($room_name); ?> <span class="badge badge-secondary" style="margin-left: 0.5rem; font-size: 0.75rem;"><?php echo count($items); ?> tài sản</span></span>
                                            <i id="icon_<?php echo $room_id_hash; ?>" class="fas fa-chevron-down" style="color: var(--text-muted);"></i>
                                        </div>
                                        <div id="room_<?php echo $room_id_hash; ?>" class="table-container" style="display: none; border-top: 1px solid var(--border-light);">
                                            <table style="margin: 0; box-shadow: none;">
                                                <thead>
                                                    <tr>
                                                        <th>Tên Thiết Bị</th>
                                                        <th>Số Lượng</th>
                                                        <th>Tình Trạng</th>
                                                        <th>Cập Nhật</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items as $tb): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($tb['ten_thiet_bi']); ?></td>
                                                        <td><?php echo htmlspecialchars($tb['so_luong']); ?></td>
                                                        <td>
                                                            <?php 
                                                            if ($tb['tinh_trang'] == 'Tot') echo '<span class="badge badge-success">Tốt</span>';
                                                            elseif ($tb['tinh_trang'] == 'BaoTri') echo '<span class="badge badge-warning">Bảo Trì</span>';
                                                            else echo '<span class="badge badge-danger">Hỏng</span>';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <form method="POST" style="display:flex; gap:0.5rem; align-items:center;">
                                                                <input type="hidden" name="action" value="cap_nhat">
                                                                <input type="hidden" name="tb_id" value="<?php echo $tb['id']; ?>">
                                                                <select name="tinh_trang" class="form-control" style="padding: 0.25rem; font-size: 0.75rem; width: auto;">
                                                                    <option value="Tot" <?php if($tb['tinh_trang']=='Tot') echo 'selected'; ?>>Tốt</option>
                                                                    <option value="BaoTri" <?php if($tb['tinh_trang']=='BaoTri') echo 'selected'; ?>>Bảo trì</option>
                                                                    <option value="Hong" <?php if($tb['tinh_trang']=='Hong') echo 'selected'; ?>>Hỏng</option>
                                                                </select>
                                                                <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem;"><i class="fas fa-save"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 style="margin:0;"><i class="fas fa-plus-circle text-primary"></i> Khai Báo Tài Sản</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="them_thiet_bi">
                                <div class="form-group">
                                    <label class="form-label">Chọn Phòng</label>
                                    <select name="phong_id" class="form-control" required>
                                        <option value="">-- Chọn phòng --</option>
                                        <?php foreach($danh_sach_phong as $p): ?>
                                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['khu_nha'].' - '.$p['so_phong']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tên Thiết Bị</label>
                                    <input type="text" name="ten_thiet_bi" class="form-control" required placeholder="Ví dụ: Quạt trần, Giường tầng, Tủ quần áo...">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Số Lượng</label>
                                    <input type="number" name="so_luong" class="form-control" value="1" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tình Trạng Ban Đầu</label>
                                    <select name="tinh_trang" class="form-control">
                                        <option value="Tot">Tốt (Mới)</option>
                                        <option value="BaoTri">Đang bảo trì</option>
                                        <option value="Hong">Đã hỏng</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-plus"></i> Khai báo</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
