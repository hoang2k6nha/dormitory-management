<?php
class QuanLyVanHanh {
    private $ket_noi;

    public function __construct($co_so_du_lieu) {
        $this->ket_noi = $co_so_du_lieu;
    }

    // Ghi chỉ số điện nước và tự động sinh hóa đơn phát sinh tiền phí (AD_07, AD_08)
    public function nhapDienNuocVaSinhHoaDon($ma_phong, $thang, $nam, $chi_so_cu, $chi_so_moi, $don_gia, $ma_hop_dong, $tien_phong, $tien_phat) {
        try {
            // Kích hoạt tính toàn vẹn giao dịch (ACID) ngăn rủi ro dữ liệu sai lệch
            $this->ket_noi->beginTransaction();

            // 1. Lưu chỉ số điện nước vào bảng DienNuoc
            $cau_lenh_dien_nuoc = "INSERT INTO DienNuoc (ma_phong, thang_ap_dung, nam_ap_dung, chi_so_cu, chi_so_moi, don_gia) 
                                   VALUES (:ma_phong, :thang, :nam, :chi_so_cu, :chi_so_moi, :don_gia)";
            $tien_trinh_dien_nuoc = $this->ket_noi->prepare($cau_lenh_dien_nuoc);
            $tien_trinh_dien_nuoc->execute([
                ':ma_phong' => $ma_phong, ':thang' => $thang, ':nam' => $nam,
                ':chi_so_cu' => $chi_so_cu, ':chi_so_moi' => $chi_so_moi, ':don_gia' => $don_gia
            ]);

            // Tự động tính toán thành tiền dựa trên chỉ số đầu/cuối
            $tien_dien_nuoc = ($chi_so_moi - $chi_so_cu) * $don_gia;
            $tong_chi_phi = $tien_phong + $tien_dien_nuoc + $tien_phat;

            // 2. Tự sinh hóa đơn tổng hợp (HoaDon)
            $cau_lenh_hoa_don = "INSERT INTO HoaDon (ma_hop_dong, tong_chi_phi, tien_phong, tien_dien_nuoc, tien_phat, trang_thai_thanh_toan) 
                                 VALUES (:ma_hop_dong, :tong_chi_phi, :tien_phong, :tien_dien_nuoc, :tien_phat, 'Chưa thu')";
            $tien_trinh_hoa_don = $this->ket_noi->prepare($cau_lenh_hoa_don);
            $tien_trinh_hoa_don->execute([
                ':ma_hop_dong' => $ma_hop_dong, ':tong_chi_phi' => $tong_chi_phi,
                ':tien_phong' => $tien_phong, ':tien_dien_nuoc' => $tien_dien_nuoc, ':tien_phat' => $tien_phat
            ]);

            $this->ket_noi->commit();
            return true;
        } catch (Exception $loi) {
            $this->ket_noi->rollBack();
            return false;
        }
    }

    // Tiếp nhận Ticket phản ánh sự cố từ sinh viên (US_04, AD_10)
    public function taoTicketHoTro($ma_sinh_vien, $noi_dung) {
        $cau_lenh = "INSERT INTO HoTro (ma_sinh_vien, noi_dung_phan_anh, trang_thai_xu_ly) VALUES (:ma_sinh_vien, :noi_dung, 'Chờ tiếp nhận')";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);
        $tien_trinh->bindParam(":ma_sinh_vien", $ma_sinh_vien);
        $tien_trinh->bindParam(":noi_dung", $noi_dung);
        
        return $tien_trinh->execute();
    }

    // Ghi nhận lịch sử vi phạm nội quy kỷ luật (AD_09)
    public function lapBienBanViPham($ma_sinh_vien, $chi_tiet, $hinh_thuc, $ngay) {
        $cau_lenh = "INSERT INTO ViPham (ma_sinh_vien, chi_tiet_vi_pham, hinh_thuc_ky_luat, ngay_vi_pham) 
                    VALUES (:ma_sinh_vien, :chi_tiet, :hinh_thuc, :ngay)";
        $tien_trinh = $this->ket_noi->prepare($cau_lenh);
        return $tien_trinh->execute([
            ':ma_sinh_vien' => $ma_sinh_vien, ':chi_tiet' => $chi_tiet,
            ':hinh_thuc' => $hinh_thuc, ':ngay' => $ngay
        ]);
    }
}
?>