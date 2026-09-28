<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$sv_id = $_GET['sv_id'] ?? 0;
if ($role == 'user') {
    // Nếu là user thì tự động lấy sv_id của chính mình
    $stmt_sv = $conn->prepare("SELECT id FROM SinhVien WHERE tai_khoan_id = ?");
    $stmt_sv->execute([$user_id]);
    $sv_id = $stmt_sv->fetchColumn();
}

if (!$sv_id) {
    die("Không tìm thấy sinh viên.");
}

// Lấy thông tin sinh viên và hợp đồng
$sql = "SELECT s.*, h.id as hd_id, h.ngay_bat_dau, h.ngay_ket_thuc, h.trang_thai as hd_trang_thai, p.khu_nha, p.so_phong, p.gia_phong
        FROM SinhVien s 
        LEFT JOIN HopDong h ON s.id = h.sinh_vien_id AND h.trang_thai = 'HieuLuc'
        LEFT JOIN Phong p ON h.phong_id = p.id
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$sv_id]);
$hop_dong = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hop_dong || empty($hop_dong['hd_id'])) {
    $error = "Sinh viên này hiện tại chưa có hợp đồng phòng nào đang có hiệu lực.";
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Hợp Đồng - KTX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .contract-paper {
            background: white;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
            color: #333;
            line-height: 1.6;
        }
        .contract-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #333;
            padding-bottom: 1rem;
        }
    </style>
        <script>
        // Khôi phục Dark Mode nếu đã bật
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body class="<?php echo $role == 'admin' ? 'admin-theme' : 'user-theme'; ?>">
    <div class="dashboard-layout">
        <!-- Nút Back dùng chung -->
        <div style="position: absolute; top: 1rem; left: 1rem; z-index: 1000;">
            <button onclick="history.back()" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Quay lại</button>
        </div>

        <main class="main-content" style="margin-left: 0; display:flex; justify-content:center; padding: 4rem 1rem; background: var(--bg-light);">
            
            <?php if(isset($error)): ?>
                <div class="alert alert-warning" style="max-width: 600px; width: 100%; text-align: center;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; display:block; margin-bottom:1rem; opacity:0.5;"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
            <div class="contract-paper">
                <div class="contract-header">
                    <h2 style="margin: 0 0 0.5rem 0; text-transform: uppercase;">Cộng Hòa Xã Hội Chủ Nghĩa Việt Nam</h2>
                    <h3 style="margin: 0; font-weight: 500;">Độc lập - Tự do - Hạnh phúc</h3>
                    <div style="margin-top: 2rem;">
                        <h1 style="font-size: 1.5rem; text-transform: uppercase;">HỢP ĐỒNG THUÊ CHỖ Ở KÝ TÚC XÁ</h1>
                        <p style="margin:0;">Số: HD2024-<?php echo str_pad($hop_dong['hd_id'], 4, '0', STR_PAD_LEFT); ?></p>
                    </div>
                </div>

                <div class="contract-body">
                    <p>Hôm nay, ngày <?php echo date('d', strtotime($hop_dong['ngay_bat_dau'])); ?> tháng <?php echo date('m', strtotime($hop_dong['ngay_bat_dau'])); ?> năm <?php echo date('Y', strtotime($hop_dong['ngay_bat_dau'])); ?>, chúng tôi gồm:</p>
                    
                    <h4 style="margin: 1.5rem 0 0.5rem 0;">BÊN A (Bên cho thuê)</h4>
                    <p><strong>Ban Quản Lý Ký Túc Xá</strong></p>
                    <p>Đại diện: Ban Quản Lý (Admin)</p>

                    <h4 style="margin: 1.5rem 0 0.5rem 0;">BÊN B (Bên thuê)</h4>
                    <p><strong>Họ và Tên:</strong> <?php echo htmlspecialchars($hop_dong['ho_ten'] ?? ''); ?></p>
                    <p><strong>MSSV:</strong> <?php echo htmlspecialchars($hop_dong['mssv'] ?? ''); ?> | <strong>Lớp:</strong> <?php echo htmlspecialchars($hop_dong['lop'] ?? ''); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($hop_dong['sdt'] ?? ''); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($hop_dong['email'] ?? ''); ?></p>

                    <h4 style="margin: 1.5rem 0 0.5rem 0;">ĐIỀU KHOẢN HỢP ĐỒNG</h4>
                    <p>Bên A đồng ý cho Bên B thuê chỗ ở tại Ký túc xá với chi tiết như sau:</p>
                    <ul style="list-style-type: none; padding-left: 0; margin-top: 0.5rem;">
                        <li style="padding: 0.5rem; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 0.25rem;">
                            <strong>Phòng:</strong> <?php echo htmlspecialchars($hop_dong['khu_nha'] . ' - ' . $hop_dong['so_phong']); ?>
                        </li>
                        <li style="padding: 0.5rem; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 0.25rem;">
                            <strong>Giá thuê:</strong> <?php echo number_format($hop_dong['gia_phong']); ?> VNĐ/Tháng
                        </li>
                        <li style="padding: 0.5rem; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 0.25rem;">
                            <strong>Thời hạn hợp đồng:</strong> Từ ngày <?php echo date('d/m/Y', strtotime($hop_dong['ngay_bat_dau'])); ?> đến ngày <?php echo date('d/m/Y', strtotime($hop_dong['ngay_ket_thuc'])); ?>
                        </li>
                        <li style="padding: 0.5rem; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                            <strong>Trạng thái:</strong> CÓ HIỆU LỰC
                        </li>
                    </ul>

                    <p style="margin-top: 1.5rem;">Bên B cam kết chấp hành nghiêm chỉnh nội quy KTX, đóng phí đầy đủ đúng hạn. Nếu vi phạm, Bên A có quyền đơn phương chấm dứt hợp đồng.</p>

                    <div style="display: flex; justify-content: space-between; margin-top: 3rem; text-align: center;">
                        <div>
                            <p><strong>ĐẠI DIỆN BÊN B</strong></p>
                            <p style="font-style: italic; color: #666;">(Đã ký điện tử)</p>
                            <p style="margin-top: 2rem; font-weight: 500;"><?php echo htmlspecialchars($hop_dong['ho_ten'] ?? ''); ?></p>
                        </div>
                        <div>
                            <p><strong>ĐẠI DIỆN BÊN A</strong></p>
                            <p style="font-style: italic; color: #666;">(Đã duyệt)</p>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/14/Approved_stamp.svg/1024px-Approved_stamp.svg.png" style="width: 100px; opacity: 0.6; position: absolute; transform: rotate(-15deg); margin-top: -10px;">
                            <p style="margin-top: 2rem; font-weight: 500;">Ban Quản Lý</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
