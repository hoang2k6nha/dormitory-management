<?php
// cli_tasks.php
// Tự động chạy nền bằng Cron / Task Scheduler

require_once __DIR__ . '/includes/CauHinhCoSoDuLieu.php';

// Chỉ cho phép chạy từ Command Line (CLI)
if (php_sapi_name() !== 'cli') {
    die("Access denied. Kịch bản này chỉ được chạy trên CLI.");
}

echo "[" . date('Y-m-d H:i:s') . "] Bắt đầu chạy các tác vụ nền KTX...\n";

try {
    $db = new CauHinhCoSoDuLieu();
    $conn = $db->layKetNoi();

    // 1. Dọn dẹp thông báo cũ hơn 6 tháng
    $stmt1 = $conn->query("DELETE FROM ThongBao WHERE ngay_dang < DATE_SUB(NOW(), INTERVAL 180 DAY)");
    echo "- Đã dọn dẹp các thông báo cũ. Số dòng ảnh hưởng: " . $stmt1->rowCount() . "\n";

    // 2. Tự động chuyển trạng thái Hợp đồng từ 'HieuLuc' sang 'HetHan' nếu quá ngày kết thúc
    $stmt2 = $conn->query("UPDATE HopDong SET trang_thai = 'HetHan' WHERE trang_thai = 'HieuLuc' AND ngay_ket_thuc < CURRENT_DATE");
    echo "- Đã khóa các hợp đồng hết hạn. Số dòng ảnh hưởng: " . $stmt2->rowCount() . "\n";

    // 3. (Tùy chọn) Gửi thông báo nợ quá hạn
    
} catch (PDOException $e) {
    echo "- LỖI DATABASE: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Hoàn thành tác vụ!\n";
?>
