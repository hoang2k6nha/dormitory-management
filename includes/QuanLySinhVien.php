<?php
class QuanLySinhVien {
    private $ket_noi;
    private $ten_bang = "SinhVien";

    public function __construct($co_so_du_lieu) {
        $this->ket_noi = $co_so_du_lieu;
    }

    // Thêm mới sinh viên thủ công hoặc từ vòng lặp Excel (AD_04)
    public function themSinhVien($ho_va_ten, $ma_so_sinh_vien, $lop_hoc, $que_quan, $so_dien_thoai, $ma_tai_khoan) {
        $cau_lenh = "INSERT INTO " . $this->ten_bang . " (ho_va_ten, ma_so_sinh_vien, lop_hoc, que_quan, so_dien_thoai, ma_tai_khoan) 
                    VALUES (:ho_va_ten, :ma_so_sinh_vien, :lop_hoc, :que_quan, :so_dien_thoai, :ma_tai_khoan)";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);

        $tien_trinh->bindParam(":ho_va_ten", $ho_va_ten);
        $tien_trinh->bindParam(":ma_so_sinh_vien", $ma_so_sinh_vien);
        $tien_trinh->bindParam(":lop_hoc", $lop_hoc);
        $tien_trinh->bindParam(":que_quan", $que_quan);
        $tien_trinh->bindParam(":so_dien_thoai", $so_dien_thoai);
        $tien_trinh->bindParam(":ma_tai_khoan", $ma_tai_khoan);

        return $tien_trinh->execute();
    }

    // Tra cứu nâng cao bằng bộ lọc từ khóa (MSSV, Tên, Lớp)
    public function timKiemSinhVien($tu_khoa) {
        $cau_lenh = "SELECT * FROM " . $this->ten_bang . " 
                    WHERE ma_so_sinh_vien LIKE :tu_khoa 
                    OR ho_va_ten LIKE :tu_khoa 
                    OR lop_hoc LIKE :tu_khoa";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);
        $tu_khoa_tim_kiem = "%{$tu_khoa}%";
        $tien_trinh->bindParam(":tu_khoa", $tu_khoa_tim_kiem);
        $tien_trinh->execute();
        
        return $tien_trinh->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>