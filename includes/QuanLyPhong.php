<?php
class QuanLyPhong {
    private $ket_noi;
    private $ten_bang = "Phong";

    public function __construct($co_so_du_lieu) {
        $this->ket_noi = $co_so_du_lieu;
    }

    // Tạo mới phòng ở và cấu hình sức chứa (AD_05)
    public function taoPhongMoi($khu_nha, $so_phong, $loai_phong, $so_giuong_toi_da, $trang_thai) {
        $cau_lenh = "INSERT INTO " . $this->ten_bang . " (khu_nha, so_phong, loai_phong, so_giuong_toi_da, trang_thai_hien_tai) 
                    VALUES (:khu_nha, :so_phong, :loai_phong, :so_giuong_toi_da, :trang_thai)";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);

        $tien_trinh->bindParam(":khu_nha", $khu_nha);
        $tien_trinh->bindParam(":so_phong", $so_phong);
        $tien_trinh->bindParam(":loai_phong", $loai_phong);
        $tien_trinh->bindParam(":so_giuong_toi_da", $so_giuong_toi_da);
        $tien_trinh->bindParam(":trang_thai", $trang_thai);

        return $tien_trinh->execute();
    }

    // Xem danh sách phòng trống phục vụ chức năng đăng ký (US_02)
    public function layDanhSachPhongTrong() {
        $cau_lenh = "SELECT * FROM " . $this->ten_bang . " WHERE trang_thai_hien_tai = 'Trống' OR trang_thai_hien_tai = 'Còn chỗ'";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);
        $tien_trinh->execute();
        
        return $tien_trinh->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>