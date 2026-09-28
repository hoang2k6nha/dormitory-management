CREATE DATABASE IF NOT EXISTS quan_ly_ky_tuc_xa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quan_ly_ky_tuc_xa;

-- 1. TaiKhoan
CREATE TABLE IF NOT EXISTS TaiKhoan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user', 'guest') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. SinhVien
CREATE TABLE IF NOT EXISTS SinhVien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mssv VARCHAR(20) UNIQUE NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    lop VARCHAR(50),
    que_quan VARCHAR(255),
    sdt VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    tai_khoan_id INT,
    FOREIGN KEY (tai_khoan_id) REFERENCES TaiKhoan(id) ON DELETE SET NULL
);

-- 3. Phong
CREATE TABLE IF NOT EXISTS Phong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    khu_nha VARCHAR(50) NOT NULL,
    so_phong VARCHAR(20) NOT NULL,
    loai_phong ENUM('Thuong', 'VIP') DEFAULT 'Thuong',
    so_giuong_toi_da INT DEFAULT 4,
    trang_thai ENUM('Trong', 'Day', 'DangBaoTri') DEFAULT 'Trong',
    gia_phong DECIMAL(10, 2) NOT NULL DEFAULT 0,
    UNIQUE KEY (khu_nha, so_phong)
);

-- 4. ThietBi
CREATE TABLE IF NOT EXISTS ThietBi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phong_id INT NOT NULL,
    ten_thiet_bi VARCHAR(100) NOT NULL,
    so_luong INT DEFAULT 1,
    tinh_trang ENUM('Tot', 'Hong', 'BaoTri') DEFAULT 'Tot',
    FOREIGN KEY (phong_id) REFERENCES Phong(id) ON DELETE CASCADE
);

-- 5. DangKy
CREATE TABLE IF NOT EXISTS DangKy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    khoa VARCHAR(50),
    khoa_hoc VARCHAR(50),
    loai_phong_mong_muon ENUM('Thuong', 'VIP'),
    email VARCHAR(100) NOT NULL,
    sdt VARCHAR(15),
    trang_thai_duyet ENUM('ChoDuyet', 'DaDuyet', 'TuChoi') DEFAULT 'ChoDuyet',
    trang_thai_coc ENUM('ChuaCoc', 'DaCoc') DEFAULT 'ChuaCoc',
    ngay_dang_ky TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. HopDong
CREATE TABLE IF NOT EXISTS HopDong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sinh_vien_id INT NOT NULL,
    phong_id INT NOT NULL,
    ngay_bat_dau DATE NOT NULL,
    ngay_ket_thuc DATE NOT NULL,
    trang_thai ENUM('HieuLuc', 'HetHan', 'ThanhLy') DEFAULT 'HieuLuc',
    FOREIGN KEY (sinh_vien_id) REFERENCES SinhVien(id),
    FOREIGN KEY (phong_id) REFERENCES Phong(id)
);

-- 7. DienNuoc
CREATE TABLE IF NOT EXISTS DienNuoc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phong_id INT NOT NULL,
    thang INT NOT NULL,
    nam INT NOT NULL,
    chi_so_dien_cu INT DEFAULT 0,
    chi_so_dien_moi INT NOT NULL,
    chi_so_nuoc_cu INT DEFAULT 0,
    chi_so_nuoc_moi INT NOT NULL,
    don_gia_dien DECIMAL(10,2) DEFAULT 3500,
    don_gia_nuoc DECIMAL(10,2) DEFAULT 15000,
    FOREIGN KEY (phong_id) REFERENCES Phong(id)
);

-- 8. HoaDon
CREATE TABLE IF NOT EXISTS HoaDon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sinh_vien_id INT NOT NULL,
    loai_hoa_don ENUM('TienPhong', 'DienNuoc', 'Phat', 'TongHop') NOT NULL,
    so_tien DECIMAL(15, 2) NOT NULL,
    noi_dung TEXT,
    thang INT NOT NULL,
    nam INT NOT NULL,
    trang_thai ENUM('ChuaThanhToan', 'DaThanhToan') DEFAULT 'ChuaThanhToan',
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sinh_vien_id) REFERENCES SinhVien(id)
);

-- 9. ViPham
CREATE TABLE IF NOT EXISTS ViPham (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sinh_vien_id INT NOT NULL,
    noi_dung_vi_pham TEXT NOT NULL,
    hinh_thuc_ky_luat VARCHAR(255) NOT NULL,
    tien_phat DECIMAL(15, 2) DEFAULT 0,
    ngay_vi_pham DATE NOT NULL,
    FOREIGN KEY (sinh_vien_id) REFERENCES SinhVien(id)
);

-- 10. HoTro
CREATE TABLE IF NOT EXISTS HoTro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sinh_vien_id INT NOT NULL,
    tieu_de VARCHAR(255) NOT NULL,
    noi_dung TEXT NOT NULL,
    trang_thai ENUM('ChoXuLy', 'DangXuLy', 'DaXuLy') DEFAULT 'ChoXuLy',
    ngay_gui TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sinh_vien_id) REFERENCES SinhVien(id)
);

-- 11. ThongBao
CREATE TABLE IF NOT EXISTS ThongBao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tieu_de VARCHAR(255) NOT NULL,
    noi_dung TEXT NOT NULL,
    ngay_dang TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 12. NhatKyHoatDong
CREATE TABLE IF NOT EXISTS NhatKyHoatDong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    hanh_dong VARCHAR(255) NOT NULL,
    chi_tiet TEXT,
    thoi_gian TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES TaiKhoan(id)
);

-- Insert sample admin account (password: admin123, you should use bcrypt in application)
-- Using a dummy bcrypted password for 'admin123' -> $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO TaiKhoan (username, password, role) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Tối ưu hóa Database (Indexes) cho 100+ người dùng đồng thời
CREATE INDEX idx_hopdong_trangthai ON HopDong(trang_thai);
CREATE INDEX idx_hopdong_phong_id ON HopDong(phong_id);
CREATE INDEX idx_hopdong_sinh_vien_id ON HopDong(sinh_vien_id);
CREATE INDEX idx_hoadon_trangthai ON HoaDon(trang_thai);
CREATE INDEX idx_hoadon_sinh_vien_id ON HoaDon(sinh_vien_id);
CREATE INDEX idx_phong_trangthai ON Phong(trang_thai);
CREATE INDEX idx_hotro_trangthai ON HoTro(trang_thai);
CREATE INDEX idx_diennuoc_thangnam ON DienNuoc(thang, nam);
