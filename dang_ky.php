<?php
require_once "includes/CauHinhCoSoDuLieu.php";

$thong_bao = "";
$loi = "";

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

// Lấy giá phòng tham khảo từ CSDL
$gia_phong_thuong = $conn->query("SELECT MAX(gia_phong) FROM Phong WHERE loai_phong = 'Thuong'")->fetchColumn() ?: 500000;
$gia_phong_vip = $conn->query("SELECT MAX(gia_phong) FROM Phong WHERE loai_phong = 'VIP'")->fetchColumn() ?: 1500000;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $khoa = $_POST['khoa'] ?? '';
    $khoa_hoc = $_POST['khoa_hoc'] ?? '';
    $loai_phong = $_POST['loai_phong'] ?? '';
    $email = $_POST['email'] ?? '';
    $sdt = $_POST['sdt'] ?? '';

    if (!empty($ho_ten) && !empty($email) && !empty($loai_phong)) {
        // Kiểm tra email trùng
        $stmt_check = $conn->prepare("SELECT email FROM SinhVien WHERE email = ? UNION SELECT email FROM DangKy WHERE email = ?");
        $stmt_check->execute([$email, $email]);
        if ($stmt_check->fetch()) {
            $loi = "Email này đã được đăng ký! Vui lòng sử dụng email khác hoặc đăng nhập nếu bạn đã có tài khoản.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO DangKy (ho_ten, khoa, khoa_hoc, loai_phong_mong_muon, email, sdt) VALUES (:ho_ten, :khoa, :khoa_hoc, :loai_phong, :email, :sdt)");
                $stmt->execute([
                    ':ho_ten' => $ho_ten,
                    ':khoa' => $khoa,
                    ':khoa_hoc' => $khoa_hoc,
                    ':loai_phong' => $loai_phong,
                    ':email' => $email,
                    ':sdt' => $sdt
                ]);
                $thong_bao = "Đăng ký thành công! Vui lòng chờ Ban Quản Lý duyệt hồ sơ.";
            } catch (PDOException $e) {
                $loi = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    } else {
        $loi = "Vui lòng nhập đầy đủ thông tin bắt buộc và chọn loại phòng.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký lưu trú - Quản Lý Ký Túc Xá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Khôi phục Dark Mode nếu đã bật
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <style>
        .room-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .room-label {
            cursor: pointer;
            display: block;
        }
        .room-label input[type="radio"] {
            display: none;
        }
        .room-card {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
            height: 100%;
            background: var(--bg-color);
        }
        .room-label:hover .room-card {
            border-color: #a0aec0;
            transform: translateY(-2px);
        }
        .room-label input[type="radio"]:checked + .room-card {
            border-color: var(--primary-color);
            background-color: rgba(21, 67, 136, 0.05);
            box-shadow: 0 4px 15px rgba(21, 67, 136, 0.15);
        }
        .room-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .room-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .room-label input[type="radio"]:checked + .room-card .room-title {
            color: var(--primary-color);
        }
        .room-title i.fa-check-circle {
            display: none;
            color: var(--primary-color);
        }
        .room-label input[type="radio"]:checked + .room-card .room-title i.fa-check-circle {
            display: inline-block;
        }
        .room-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--danger-color, #e3342f);
            margin-bottom: 0.5rem;
        }
        .room-amenities {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .room-amenities li {
            margin-bottom: 0.4rem;
            display: flex;
            align-items: flex-start;
        }
        .room-amenities li i {
            width: 20px;
            color: var(--primary-color);
            margin-top: 3px;
        }
        .service-price {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--border-color);
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper" style="padding: 2rem 0;">
        <div class="auth-card card glass-panel" style="max-width: 800px; width: 95%;">
            <div class="card-header text-center" style="border-bottom: none; padding-bottom: 0;">
                <h2 style="color: var(--primary-color); margin-bottom: 0.5rem;"><i class="fas fa-edit"></i> Đăng Ký Lưu Trú</h2>
                <p class="text-muted-light">Dành cho Tân Sinh Viên / Khách</p>
            </div>
            <div class="card-body">
                <?php if (!empty($thong_bao)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($thong_bao); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($loi)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($loi); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="dang_ky.php">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Thông tin cá nhân</h3>
                    <div class="grid grid-cols-2">
                        <div class="form-group">
                            <label class="form-label" for="ho_ten">Họ và tên *</label>
                            <input type="text" id="ho_ten" name="ho_ten" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sdt">Số điện thoại *</label>
                            <input type="text" id="sdt" name="sdt" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email liên hệ *</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="grid grid-cols-2">
                        <div class="form-group">
                            <label class="form-label" for="khoa">Khoa</label>
                            <input type="text" id="khoa" name="khoa" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="khoa_hoc">Khóa học (VD: K64)</label>
                            <input type="text" id="khoa_hoc" name="khoa_hoc" class="form-control">
                        </div>
                    </div>

                    <h3 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Lựa chọn phòng ở</h3>
                    
                    <div class="room-selection">
                        <!-- Phòng Thường -->
                        <label class="room-label">
                            <input type="radio" name="loai_phong" value="Thuong" required>
                            <div class="room-card">
                                <img src="https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&q=80&w=400" alt="Phòng Thường" class="room-img">
                                <div class="room-title">Phòng Tiêu Chuẩn <i class="fas fa-check-circle"></i></div>
                                <div class="room-price"><?php echo number_format($gia_phong_thuong); ?> đ / tháng</div>
                                <ul class="room-amenities">
                                    <li><i class="fas fa-bed"></i> Giường tầng tiêu chuẩn (4-6 người/phòng)</li>
                                    <li><i class="fas fa-fan"></i> Quạt trần & Quạt treo tường</li>
                                    <li><i class="fas fa-box"></i> Tủ đồ dùng chung lớn</li>
                                    <li><i class="fas fa-chair"></i> Bàn học chung, không gian mở</li>
                                    <li><i class="fas fa-restroom"></i> Nhà vệ sinh riêng trong phòng</li>
                                </ul>
                                <div class="service-price">
                                    <strong>Điện:</strong> 3.500 đ/kWh<br>
                                    <strong>Nước:</strong> 15.000 đ/m³<br>
                                    <span class="text-muted" style="font-size: 0.8rem;">(Chi phí điện nước chia đều theo số người)</span>
                                </div>
                            </div>
                        </label>

                        <!-- Phòng VIP -->
                        <label class="room-label">
                            <input type="radio" name="loai_phong" value="VIP" required>
                            <div class="room-card">
                                <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&q=80&w=400" alt="Phòng VIP" class="room-img">
                                <div class="room-title">Phòng VIP (Cao Cấp) <i class="fas fa-check-circle"></i></div>
                                <div class="room-price"><?php echo number_format($gia_phong_vip); ?> đ / tháng</div>
                                <ul class="room-amenities">
                                    <li><i class="fas fa-bed"></i> Giường đơn cao cấp (1-2 người/phòng)</li>
                                    <li><i class="fas fa-snowflake"></i> Máy lạnh (Điều hòa) 2 chiều</li>
                                    <li><i class="fas fa-lock"></i> Tủ đồ cá nhân riêng có khóa an toàn</li>
                                    <li><i class="fas fa-laptop-house"></i> Bàn học cá nhân rộng rãi</li>
                                    <li><i class="fas fa-shower"></i> Bình nóng lạnh & Máy giặt chung khu</li>
                                </ul>
                                <div class="service-price">
                                    <strong>Điện:</strong> 3.500 đ/kWh<br>
                                    <strong>Nước:</strong> 15.000 đ/m³<br>
                                    <span class="text-muted" style="font-size: 0.8rem;">(Chi phí điện nước tính theo công tơ riêng)</span>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-4" style="width: 100%; font-size: 1.1rem; padding: 0.75rem;">
                        Gửi Yêu Cầu Đăng Ký Lưu Trú <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
            <div class="card-footer text-center">
                <p style="font-size: 0.875rem; color: var(--text-muted-light); margin: 0;">Đã có tài khoản? <a href="index.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>
</body>
</html>
