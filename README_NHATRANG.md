# 🌊 HUY CNC NHA TRANG — DIGITAL FABRICATION STUDIO
## SỔ TAY BÀN GIAO KIẾN TRÚC MỚI (STUDIO APP-PORTAL)

> **Dự án**: Nền Tảng Chế Tác Kim Khí Kỹ Thuật Số — Chi nhánh Nha Trang, Khánh Hòa  
> **Thư mục dự án**: `C:\xampp\htdocs\huycnc_nhatrang`  
> **Địa chỉ chạy local**: [http://localhost/huycnc_nhatrang/](http://localhost/huycnc_nhatrang/)  
> **Ngày tái thiết kế kiến trúc**: 13/10/2026  

---

## ⚡ 1. SỰ KHÁC BIỆT CỐT LÕI VỀ CẤU TRÚC SO VỚI WEBSITE CHÍNH (HÀ NỘI)

Website chi nhánh Nha Trang đã được **đập đi xây lại cấu trúc 100%**, chuyển đổi từ mô hình *"Website giới thiệu công ty truyền thống"* của Hà Nội sang mô hình **"Digital Fabrication Studio (Cổng Tương Tác Xưởng Kỹ Thuật Số)"**:

| Tiêu Chí | Website Gốc Hà Nội (`huycnc_redesign`) | Website MỚI Nha Trang (`huycnc_nhatrang`) |
| :--- | :--- | :--- |
| **Mô hình kiến trúc** | Brochure web tĩnh 7 trang rời rạc cuộn dọc dài | **Interactive Studio Portal & Bento Grid Hub** |
| **Hệ thống điều hướng** | Menu cổ điển: Trang chủ, Giới thiệu, Sản phẩm, Báo giá, Blog, Dự án, Liên hệ | **Studio Dock Navigation chuyên sâu**:<br>• **Xưởng Trực Tuyến** (`index.html`)<br>• **Giải Pháp Ngành** (`solutions.html`)<br>• **Bảng Tra Vật Liệu** (`materials.html`)<br>• **Tra Cứu Tiến Độ** (`tracking.html`)<br>• **Thư Viện Mẫu** (`products.html`)<br>• **Liên Hệ Xưởng** (`contact.html`) |
| **Hero Trang Chủ** | Banner ảnh tĩnh / Video nền giới thiệu chung chung | **Interactive Studio Configurator Workbench**: Khách chọn phôi Inox 316 / Nhôm / Đồng, kéo thanh độ dày (0.5 - 20mm), tự động tính dung sai và nhảy giá dự toán thời gian thực ngay trên màn hình chính! |
| **Phân loại nội dung** | Phân loại theo công nghệ máy: Phay, tiện, laser, mica | **Phân loại theo Giải pháp Ngành biển Khánh Hòa** (`solutions.html`):<br>1. *Khách sạn & Resort Biển* (chữ nổi Inox 316 kháng muối, vách nghệ thuật, LED IP68)<br>2. *Cơ khí Hàng hải & Cảng biển* (chi tiết máy tàu thuyền, bu lông neo)<br>3. *Showroom Yến sào & Cửa hàng đặc sản* (kệ mica 5 tầng không vết dán, chữ hút nổi)<br>4. *Gia công phụ trợ KCN Suối Dầu & Ninh Hòa* |
| **Công cụ kỹ thuật mới** | Không có bảng so sánh kỹ thuật | **Marine Material Matrix** (`materials.html`): Bảng tra tương tác so sánh Inox 316 vs Inox 304 vs Nhôm 6061 vs Đồng thau (Độ kháng mặn, độ bền kéo, chi phí) |
| **Tính năng độc quyền** | Không có tra cứu tiến độ | **Live Order Tracker** (`tracking.html`): Khách hàng nhập mã đơn (ví dụ: `HCNC-NT-2026-0042`) để theo dõi quy trình 5 bước sản xuất trực tiếp tại xưởng |

---

## 🚀 2. HƯỚNG DẪN KHỞI CHẠY TRÊN XAMPP

1. **Khởi động XAMPP**: Bật **Apache** và **MySQL**.
2. **Nhập Database**:
   - Mở [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
   - Tạo DB: `huycnc_nhatrang_db` (utf8mb4_unicode_ci).
   - Import file: `C:\xampp\htdocs\huycnc_nhatrang\database.sql`.
3. **Trải nghiệm các Hub chính**:
   - **Xưởng Trực Tuyến (Trang chủ)**: [http://localhost/huycnc_nhatrang/index.html](http://localhost/huycnc_nhatrang/index.html)
   - **Giải Pháp Theo Ngành**: [http://localhost/huycnc_nhatrang/solutions.html](http://localhost/huycnc_nhatrang/solutions.html)
   - **Bảng Tra Vật Liệu**: [http://localhost/huycnc_nhatrang/materials.html](http://localhost/huycnc_nhatrang/materials.html)
   - **Tra Cứu Tiến Độ Sản Xuất**: [http://localhost/huycnc_nhatrang/tracking.html](http://localhost/huycnc_nhatrang/tracking.html)
   - **Thư Viện Sản Phẩm**: [http://localhost/huycnc_nhatrang/products.html](http://localhost/huycnc_nhatrang/products.html)
   - **Báo Giá 4 Bước CAD**: [http://localhost/huycnc_nhatrang/quote.html](http://localhost/huycnc_nhatrang/quote.html)
   - **Đăng Nhập Quản Trị**: [http://localhost/huycnc_nhatrang/admin-login.html](http://localhost/huycnc_nhatrang/admin-login.html)
     - *Email*: `admin@huycnc-nhatrang.com`
     - *Mật khẩu*: `Admin@123456`
