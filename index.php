<?php
session_start();
require_once "includes/CauHinhCoSoDuLieu.php";

$loi = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new CauHinhCoSoDuLieu();
    $conn = $db->layKetNoi();

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM TaiKhoan WHERE username = :username LIMIT 1");
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] == 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: user/user_dashboard.php");
                }
                exit;
            } else {
                $loi = "Mật khẩu không chính xác.";
            }
        } else {
            $loi = "Tài khoản không tồn tại.";
        }
    } else {
        $loi = "Vui lòng nhập đầy đủ thông tin.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản Lý Ký Túc Xá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
        <script>
        // Khôi phục Dark Mode nếu đã bật
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card card glass-panel">
            <div class="card-header text-center" style="border-bottom: none; padding-bottom: 0;">
                <h2 style="color: var(--primary-color); margin-bottom: 0.5rem;"><i class="fas fa-building"></i> KTX Manager</h2>
                <p class="text-muted-light">Đăng nhập vào hệ thống</p>
            </div>
            <div class="card-body">
                <?php if (!empty($loi)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($loi); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php">
                    <div class="form-group">
                        <label class="form-label" for="username">Tên đăng nhập</label>
                        <div style="position: relative;">
                            <i class="fas fa-user" style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted-light);"></i>
                            <input type="text" id="username" name="username" class="form-control" style="padding-left: 2.5rem;" required autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <div style="position: relative;">
                            <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted-light);"></i>
                            <input type="password" id="password" name="password" class="form-control" style="padding-left: 2.5rem;" required>
                        </div>
                    </div>
                    
                    <div class="form-group d-flex justify-content-between align-items-center mt-4">
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-muted-light); cursor: pointer;">
                            <input type="checkbox"> Ghi nhớ đăng nhập
                        </label>
                        <a href="#" style="color: var(--primary-color); text-decoration: none; font-size: 0.875rem;">Quên mật khẩu?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-4" style="width: 100%;">
                        Đăng nhập <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
            <div class="card-footer text-center">
                <p style="font-size: 0.875rem; color: var(--text-muted-light); margin: 0;">Sinh viên mới? <a href="dang_ky.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Đăng ký lưu trú</a></p>
            </div>
        </div>
    </div>
</body>
</html>