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

// Search Query
$tu_khoa = $_GET['q'] ?? '';

$sql = "SELECT ls.*, pc.khu_nha as khu_cu, pc.so_phong as phong_cu, pm.khu_nha as khu_moi, pm.so_phong as phong_moi 
        FROM LichSuChuyenPhong ls
        LEFT JOIN Phong pc ON ls.phong_cu_id = pc.id
        LEFT JOIN Phong pm ON ls.phong_moi_id = pm.id
        WHERE ls.sinh_vien_id = :sv_id";

if (!empty($tu_khoa)) {
    $sql .= " AND (ls.ly_do LIKE :q OR pm.so_phong LIKE :q)";
}
$sql .= " ORDER BY ls.id DESC";

$stmt_ls = $conn->prepare($sql);
$stmt_ls->bindValue(':sv_id', $sinh_vien_id);
if (!empty($tu_khoa)) {
    $stmt_ls->bindValue(':q', "%$tu_khoa%");
}
$stmt_ls->execute();
$lich_su = $stmt_ls->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Chuyển Phòng - KTX Student</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
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
                <a href="user_phong.php" class="nav-item active"><i class="fas fa-bed" style="width:20px;"></i> Đăng ký ở KTX</a>
                <a href="xem_hop_dong.php" class="nav-item"><i class="fas fa-file-signature" style="width:20px;"></i> Hợp đồng</a>
                <a href="../logout.php" class="nav-item mt-4"><i class="fas fa-sign-out-alt" style="width:20px;"></i> Đăng xuất</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div style="font-weight: 600; font-size: 1.25rem;">Lịch Sử Chuyển Phòng</div>
            </header>
            
            <div class="content-area">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 style="margin:0;"><i class="fas fa-history text-primary"></i> Yêu Cầu Chuyển Phòng Của Bạn</h3>
                        <form method="GET" style="display:flex; gap: 0.5rem; margin:0;">
                            <input type="text" name="q" class="form-control" placeholder="Tìm lý do, số phòng..." value="<?php echo htmlspecialchars($tu_khoa); ?>" style="width: 250px;">
                            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Tìm</button>
                            <a href="user_phong.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Quay lại</a>
                        </form>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ngày Yêu Cầu</th>
                                        <th>Từ Phòng</th>
                                        <th>Đến Phòng</th>
                                        <th>Lý do chuyển</th>
                                        <th>Trạng Thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($lich_su) == 0): ?>
                                        <tr><td colspan="5" class="text-center text-muted-light">Không có lịch sử chuyển phòng.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($lich_su as $ls): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($ls['ngay_yeu_cau'])); ?></td>
                                            <td>
                                                <?php echo $ls['khu_cu'] ? htmlspecialchars($ls['khu_cu'] . ' - ' . $ls['phong_cu']) : '<span class="text-muted">Không có</span>'; ?>
                                            </td>
                                            <td style="font-weight: 600; color: var(--primary-color);">
                                                <?php echo htmlspecialchars($ls['khu_moi'] . ' - ' . $ls['phong_moi']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($ls['ly_do']); ?></td>
                                            <td>
                                                <?php if($ls['trang_thai'] == 'ChoDuyet'): ?>
                                                    <span class="badge badge-warning">Đang chờ duyệt</span>
                                                <?php elseif($ls['trang_thai'] == 'DaDuyet'): ?>
                                                    <span class="badge badge-success">Thành công</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Bị từ chối</span>
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
</body>
</html>
