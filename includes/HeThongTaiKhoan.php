<?php
class HeThongTaiKhoan {
    private $ket_noi;
    private $ten_bang = "TaiKhoan";

    public function __construct($co_so_du_lieu) {
        $this->ket_noi = $co_so_du_lieu;
    }

    // Chức năng cấp tài khoản tự động (DK_05)
    public function taoTaiKhoan($ten_dang_nhap, $mat_khau_khoi_dau, $vai_tro) {
        $cau_lenh = "INSERT INTO " . $this->ten_bang . " (ten_dang_nhap, mat_khau_ma_hoa, vai_tro) VALUES (:ten_dang_nhap, :mat_khau_ma_hoa, :vai_tro)";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);

        // Mã hóa mật khẩu một chiều sử dụng Bcrypt theo yêu cầu bảo mật
        $mat_khau_ma_hoa = password_hash($mat_khau_khoi_dau, PASSWORD_BCRYPT);

        $tien_trinh->bindParam(":ten_dang_nhap", $ten_dang_nhap);
        $tien_trinh->bindParam(":mat_khau_ma_hoa", $mat_khau_ma_hoa);
        $tien_trinh->bindParam(":vai_tro", $vai_tro);

        if($tien_trinh->execute()) {
            return $this->ket_noi->lastInsertId();
        }
        return false;
    }

    // Đăng nhập hệ thống & kiểm tra thông tin xác thực
    public function dangNhap($ten_dang_nhap, $mat_khau) {
        $cau_lenh = "SELECT ma_tai_khoan, ten_dang_nhap, mat_khau_ma_hoa, vai_tro FROM " . $this->ten_bang . " WHERE ten_dang_nhap = :ten_dang_nhap LIMIT 0,1";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);
        $tien_trinh->bindParam(":ten_dang_nhap", $ten_dang_nhap);
        $tien_trinh->execute();

        if($tien_trinh->rowCount() > 0) {
            $hang = $tien_trinh->fetch(PDO::FETCH_ASSOC);
            if(password_verify($mat_khau, $hang['mat_khau_ma_hoa'])) {
                return $hang; // Trả về thông tin tài khoản và vai trò (Admin/Sinh viên)
            }
        }
        return false;
    }

    // Xem và chỉnh sửa thông tin tài khoản, đổi mật khẩu (US_01)
    public function doiMatKhau($ma_tai_khoan, $mat_khau_moi) {
        $cau_lenh = "UPDATE " . $this->ten_bang . " SET mat_khau_ma_hoa = :mat_khau_ma_hoa WHERE ma_tai_khoan = :ma_tai_khoan";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);
        
        $mat_khau_ma_hoa = password_hash($mat_khau_moi, PASSWORD_BCRYPT);
        $tien_trinh->bindParam(":mat_khau_ma_hoa", $mat_khau_ma_hoa);
        $tien_trinh->bindParam(":ma_tai_khoan", $ma_tai_khoan);
        
        return $tien_trinh->execute();
    }
}
?>