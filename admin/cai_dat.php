<?php
session_start();
require_once "../includes/CauHinhCoSoDuLieu.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$db = new CauHinhCoSoDuLieu();
$conn = $db->layKetNoi();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $thong_bao = "Đã lưu cài đặt hệ thống thành công!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài Đặt - KTX Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        // Check theme on load
        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('theme') || 'light';
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
                document.getElementById('darkModeToggle').checked = true;
            }
            
            document.getElementById('darkModeToggle').addEventListener('change', function(e) {
                if (e.target.checked) {
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.body.classList.remove('dark-mode');
                    localStorage.setItem('theme', 'light');
                }
            });
        });
    </script>
    <style>
        /* CSS nội bộ cho Dark Mode (Có thể đưa vào style.css sau nếu cần) */
        body.dark-mode {
            background-color: #121212 !important;
            color: #e0e0e0 !important;
        }
        body.dark-mode .card {
            background-color: #1e1e1e !important;
            border-color: #333 !important;
            color: #e0e0e0 !important;
        }
        body.dark-mode .card-header, body.dark-mode .topbar {
            background-color: #1e1e1e !important;
            border-color: #333 !important;
            color: #fff !important;
        }
        body.dark-mode table th {
            background-color: #2c2c2c !important;
            color: #fff !important;
            border-color: #444 !important;
        }
        body.dark-mode table td {
            border-color: #333 !important;
            color: #ccc !important;
        }
        body.dark-mode .form-control {
            background-color: #2c2c2c !important;
            border-color: #444 !important;
            color: #fff !important;
        }
    </style>
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
                <a href="thong_bao.php" class="nav-item"><i class="fas fa-bell" style="width:20px;"></i> Thông báo</a>
                <a href="bao_cao.php" class="nav-item"><i class="fas fa-chart-bar" style="width:20px;"></i> Báo cáo</a>
                <a href="cai_dat.php" class="nav-item active"><i class="fas fa-cog" style="width:20px;"></i> Cài đặt</a>
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
                <div style="font-weight: 600; font-size: 1.25rem;">Cài Đặt Hệ Thống</div>
            </header>
            
            <div class="content-area">
                <?php if (isset($thong_bao)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($thong_bao); ?></div>
                <?php endif; ?>

                <div class="card" style="max-width: 800px; margin: 0 auto;">
                    <div class="card-header" style="border:none;">Thông số cấu hình chung</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Tên Ký Túc Xá</label>
                                <input type="text" class="form-control" value="Ký Túc Xá Khu A" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Đơn giá Điện (VNĐ/kWh)</label>
                                <input type="number" class="form-control" value="3500" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Đơn giá Nước (VNĐ/Khối)</label>
                                <input type="number" class="form-control" value="25000" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email hệ thống</label>
                                <input type="email" class="form-control" value="admin@ktx.edu.vn">
                            </div>

                            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 2rem 0;">
                            
                            <h4 style="margin-bottom: 1rem;">Giao diện</h4>
                            <div class="form-group" style="display:flex; align-items:center; gap: 1rem;">
                                <label class="switch">
                                    <input type="checkbox" id="darkModeToggle">
                                    <span class="slider round"></span>
                                </label>
                                <label for="darkModeToggle" style="margin:0; cursor:pointer;">Bật Chế độ Tối (Dark Mode)</label>
                            </div>

                            <div style="margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        
        <style>
            /* CSS Toggle Switch */
            .switch {
              position: relative;
              display: inline-block;
              width: 50px;
              height: 24px;
            }
            .switch input { 
              opacity: 0;
              width: 0;
              height: 0;
            }
            .slider {
              position: absolute;
              cursor: pointer;
              top: 0;
              left: 0;
              right: 0;
              bottom: 0;
              background-color: #ccc;
              transition: .4s;
            }
            .slider:before {
              position: absolute;
              content: "";
              height: 18px;
              width: 18px;
              left: 3px;
              bottom: 3px;
              background-color: white;
              transition: .4s;
            }
            input:checked + .slider {
              background-color: #154388;
            }
            input:checked + .slider:before {
              transform: translateX(26px);
            }
            .slider.round {
              border-radius: 24px;
            }
            .slider.round:before {
              border-radius: 50%;
            }
        </style>
        
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
            <a href="thong_bao.php" class="bottom-nav-item">
                <i class="fas fa-bell"></i>
                <span>Thông báo</span>
            </a>
            <a href="cai_dat.php" class="bottom-nav-item active">
                <i class="fas fa-cog"></i>
                <span>Cài đặt</span>
            </a>
        </nav>
    </div>
</body>
</html>
