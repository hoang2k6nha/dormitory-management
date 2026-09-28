<?php
class CauHinhCoSoDuLieu {
    private $may_chu = "localhost";
    private $ten_co_so_du_lieu = "quan_ly_ky_tuc_xa";
    private $tai_khoan = "root";
    private $mat_khau = "";
    public $ket_noi;

    public function layKetNoi() {
        $this->ket_noi = null;
        try {
            $tuy_chon = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true // Giữ kết nối (Connection pooling) tối ưu cho 100+ user
            ];
            $this->ket_noi = new PDO(
                "mysql:host=" . $this->may_chu . ";dbname=" . $this->ten_co_so_du_lieu . ";charset=utf8mb4",
                $this->tai_khoan,
                $this->mat_khau,
                $tuy_chon
            );
        } catch(PDOException $ngoai_le) {
            die("Lỗi kết nối cơ sở dữ liệu: " . $ngoai_le->getMessage());
        }
        return $this->ket_noi;
    }
}
?>