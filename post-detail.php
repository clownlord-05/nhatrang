<?php
/**
 * Huy CNC Nha Trang — Blog Post Detail
 * Dynamic render via PDO with static fallback
 */
require_once __DIR__ . '/backend/config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : 'so-sanh-inox-304-va-316-moi-truong-bien';
$post = null;

// Try fetch from MySQL
try {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = :slug AND status = 'published' LIMIT 1");
    $stmt->execute(['slug' => $slug]);
    $post = $stmt->fetch();
    if ($post) {
        // Increment views
        $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = :id")->execute(['id' => $post['id']]);
    }
} catch (Exception $e) {
    // Database connection error, use fallback
    $post = null;
}

// Fallback articles
$fallbackArticles = [
    'so-sanh-inox-304-va-316-moi-truong-bien' => [
        'title' => 'So Sánh Inox 304 và Inox 316 Trong Môi Trường Gió Biển Nha Trang',
        'category' => 'Vật Liệu Chống Ăn Mòn',
        'published_at' => '2026-10-12 08:30:00',
        'author' => 'Kỹ Sư Trưởng Huy CNC',
        'thumbnail' => 'uploads/products/catalogue-sp01-display.webp',
        'excerpt' => 'Khí hậu biển Nha Trang với độ ẩm cao và nồng độ muối lớn là thử thách khắc nghiệt đối với kim loại. Tìm hiểu tại sao Inox 316 là giải pháp tối ưu cho biển hiệu và phụ kiện ven biển.',
        'content' => '
          <p class="lead" style="font-size:1.1rem;color:var(--nt-text-dim);line-height:1.8;margin-bottom:1.5rem">
            Nha Trang là thành phố biển nổi tiếng với tốc độ phát triển du lịch, khách sạn và resort mạnh mẽ. Tuy nhiên, hơi muối trong không khí và độ ẩm cao luôn là "kẻ thù số một" của các công trình kim loại, biển quảng cáo ngoài trời và thiết bị cơ khí. Việc lựa chọn sai loại inox có thể khiến công trình xuất hiện vết ố vàng, rỗ bề mặt chỉ sau vài tháng lắp đặt.
          </p>

          <h3 style="font-size:1.4rem;margin:2rem 0 1rem;color:var(--nt-text)">1. Thành Phần Hóa Học: Sự Khác Biệt Cốt Lõi</h3>
          <p style="color:var(--nt-text-dim);line-height:1.8;margin-bottom:1rem">
            Cả Inox 304 và Inox 316 đều thuộc nhóm thép không gỉ Austenitic. Tuy nhiên, sự khác biệt mấu chốt nằm ở nguyên tố <strong>Molybdenum (Mo)</strong>:
          </p>
          <ul style="color:var(--nt-text-dim);line-height:1.8;margin-bottom:1.5rem;padding-left:1.5rem">
            <li><strong>Inox 304:</strong> Chứa khoảng 18% Crom và 8% Niken. Hoàn toàn không có hoặc chỉ chứa lượng vết Molybdenum. Phù hợp cho môi trường nội thất, đô thị trong đất liền.</li>
            <li><strong>Inox 316:</strong> Chứa 16-18% Crom, 10-14% Niken và bổ sung đặc biệt từ <strong>2% đến 3% Molybdenum</strong>. Chính thành phần Molybdenum này tạo nên khả năng kháng ăn mòn rỗ (pitting corrosion) vượt trội trước ion Clorua (muối biển).</li>
          </ul>

          <div style="background:var(--nt-bg-card);border:1px solid var(--nt-bdr);border-radius:12px;padding:1.5rem;margin:2rem 0">
            <h4 style="color:var(--nt-teal);margin-bottom:.75rem">Bảng So Sánh Nhanh Inox 304 vs Inox 316:</h4>
            <table style="width:100%;border-collapse:collapse;font-size:.9rem">
              <thead>
                <tr style="border-bottom:1px solid var(--nt-bdr-subtle);text-align:left;color:var(--nt-text)">
                  <th style="padding:.5rem">Tiêu chí</th>
                  <th style="padding:.5rem">Inox 304</th>
                  <th style="padding:.5rem">Inox 316</th>
                </tr>
              </thead>
              <tbody style="color:var(--nt-text-dim)">
                <tr style="border-bottom:1px solid var(--nt-bdr-subtle)"><td style="padding:.5rem">Chống muối biển</td><td style="padding:.5rem">Khá (1 - 3 năm bắt đầu ố nhẹ)</td><td style="padding:.5rem;color:var(--nt-teal);font-weight:700">Tuyệt đối (trên 10 năm)</td></tr>
                <tr style="border-bottom:1px solid var(--nt-bdr-subtle)"><td style="padding:.5rem">Khả năng gia công cắt laser</td><td style="padding:.5rem">Dễ cắt, mép đẹp</td><td style="padding:.5rem">Cần laser công suất cao hơn</td></tr>
                <tr style="border-bottom:1px solid var(--nt-bdr-subtle)"><td style="padding:.5rem">Giá thành phôi</td><td style="padding:.5rem">Tiết kiệm hơn ~25-35%</td><td style="padding:.5rem">Cao hơn do Niken &amp; Molypden</td></tr>
                <tr><td style="padding:.5rem">Ứng dụng đề xuất</td><td style="padding:.5rem">Biển hiệu trong nhà, cách biển > 2km</td><td style="padding:.5rem">Biển hiệu bờ biển Trần Phú, Cam Ranh, chi tiết tàu thuyền</td></tr>
              </tbody>
            </table>
          </div>

          <h3 style="font-size:1.4rem;margin:2rem 0 1rem;color:var(--nt-text)">2. Lời Khuyên Từ Chuyên Gia Gia Công Huy CNC</h3>
          <p style="color:var(--nt-text-dim);line-height:1.8;margin-bottom:1rem">
            Nếu công trình của bạn nằm cách bờ biển dưới 1km (khu vực đường Trần Phú, Phạm Văn Đồng, Bãi Dài Cam Ranh), hãy ưu tiên chọn Inox 316 cho toàn bộ bề mặt và đinh ốc liên kết. Sự đầu tư ban đầu chênh lệch khoảng 25-30% sẽ giúp bạn tiết kiệm chi phí bảo trì và thay mới biển quảng cáo hàng trăm triệu đồng trong tương lai.
          </p>
          <p style="color:var(--nt-text-dim);line-height:1.8">
            Tại xưởng Huy CNC Nha Trang, chúng tôi luôn kiểm tra phôi inox bằng thuốc thử và máy quang phổ trước khi đưa lên bàn cắt laser fiber nhằm bảo đảm 100% đúng mác thép đã cam kết với khách hàng.
          </p>
        '
    ]
];

if (!$post && isset($fallbackArticles[$slug])) {
    $post = $fallbackArticles[$slug];
} elseif (!$post) {
    $post = reset($fallbackArticles);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="192x192" href="icon-192.png">
  <title><?= htmlspecialchars($post['title']) ?> | Huy CNC Nha Trang</title>
  <meta name="description" content="<?= htmlspecialchars($post['excerpt'] ?? '') ?>">
  <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($post['excerpt'] ?? '') ?>">
  <link rel="canonical" href="https://huycnc-nhatrang.com/post-detail.php?slug=<?= htmlspecialchars($slug) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <!-- Font Awesome 6 Icons (Local full font + CDN fallback) -->
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <!-- Design System & Studio Stylesheets -->
  <link rel="stylesheet" href="assets/css/nt-tokens.css">
  <link rel="stylesheet" href="assets/css/nt-base.css">
  <link rel="stylesheet" href="assets/css/nt-components.css">
  <link rel="stylesheet" href="assets/css/nt-pages.css">
  <link rel="stylesheet" href="assets/css/nt-studio.css">
  <link rel="stylesheet" href="assets/css/nt-animations.css">
</head>
<body class="blog-detail-page">

<!-- HEADER -->
<header class="nt-header" id="site-header">
  <nav class="nt-nav">
    <a href="index.html" class="nt-logo" aria-label="Huy CNC Nha Trang">
      <img src="assets/images/logo-small.webp" alt="Huy CNC Nha Trang" width="148" height="44" loading="eager">
      <span class="nt-logo-badge">Nha Trang</span>
    </a>
        <!-- STUDIO NAV DOCK (Giới Thiệu đặt trước Xưởng Trực Tuyến) -->
    <div class="studio-nav-dock">
      <a href="about.html" class="dock-link"><i class="fas fa-building"></i> Giới Thiệu</a>
      <a href="index.html" class="dock-link"><i class="fas fa-microchip"></i> Xưởng Trực Tuyến</a>
      <a href="solutions.html" class="dock-link"><i class="fas fa-cubes"></i> Giải Pháp Ngành</a>
      <a href="materials.html" class="dock-link"><i class="fas fa-layer-group"></i> Bảng Tra Vật Liệu</a>
      <a href="products.html" class="dock-link"><i class="fas fa-th-large"></i> Thư Viện Mẫu</a>
      <a href="contact.html" class="dock-link"><i class="fas fa-headset"></i> Liên Hệ</a>
    </div>
    <div class="nt-nav-actions">
      <a href="quote.html" class="btn btn-primary btn-sm"><i class="fas fa-calculator"></i> Báo Giá</a>
      <button class="nt-hamburger" id="hamburger" type="button" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </nav>
</header>

<main>
<!-- ARTICLE HEADER -->
<section style="padding:calc(var(--nt-header-h) + 2rem) 2rem 2.5rem;background:var(--nt-bg-dark);border-bottom:1px solid var(--nt-bdr-subtle)">
  <div style="width:min(100%,820px);margin-inline:auto">
    <nav class="breadcrumbs" aria-label="Breadcrumb" style="margin-bottom:1rem">
      <a href="index.html">Trang Chủ</a>
      <span class="sep">/</span>
      <a href="blog.html">Blog</a>
      <span class="sep">/</span>
      <span class="current"><?= htmlspecialchars($post['category'] ?? 'Kỹ Thuật') ?></span>
    </nav>
    <span class="badge badge-coral"><?= htmlspecialchars($post['category'] ?? 'Kỹ Thuật') ?></span>
    <h1 style="font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:900;line-height:1.2;margin:1rem 0"><?= htmlspecialchars($post['title']) ?></h1>
    <div style="display:flex;align-items:center;gap:1.5rem;color:var(--nt-text-muted);font-size:.85rem;font-family:var(--nt-font-mono)">
      <span><i class="fas fa-user-circle"></i> <?= htmlspecialchars($post['author'] ?? 'Huy CNC Team') ?></span>
      <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($post['published_at'] ?? 'now')) ?></span>
    </div>
  </div>
</section>

<!-- ARTICLE BODY -->
<section class="section" style="background:var(--nt-bg-deep);padding-top:2rem">
  <div style="width:min(100%,820px);margin-inline:auto">
    <div style="border-radius:12px;overflow:hidden;margin-bottom:2.5rem;aspect-ratio:16/9;background:var(--nt-bg-mid)">
      <img src="<?= htmlspecialchars($post['thumbnail'] ?? 'uploads/products/catalogue-sp01-display.webp') ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:100%;object-fit:cover" onerror="this.src='assets/images/home-banner-display.webp'">
    </div>

    <!-- MAIN TEXT -->
    <div class="article-content" style="font-size:1.02rem;color:var(--nt-text-dim);line-height:1.8">
      <?= $post['content'] ?>
    </div>

    <!-- AUTHOR SIGNATURE BOX -->
    <div style="background:var(--nt-bg-card);border:1px solid var(--nt-bdr);border-radius:12px;padding:1.5rem;margin:3rem 0;display:flex;align-items:center;gap:1.25rem">
      <div style="width:60px;height:60px;border-radius:50%;background:var(--nt-bg-surface);color:var(--nt-teal);display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0">
        <i class="fas fa-hard-hat"></i>
      </div>
      <div>
        <h4 style="font-weight:800;font-size:1rem;color:var(--nt-text)">Huy CNC Nha Trang — Đội Ngũ Kỹ Thuật</h4>
        <p style="font-size:.85rem;color:var(--nt-text-muted);margin-top:.25rem">Chuyên gia công cơ khí chính xác, tư vấn vật liệu và giải pháp thi công quảng cáo tiêu chuẩn cao tại Khánh Hòa.</p>
      </div>
    </div>

    <!-- BACK TO BLOG -->
    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:1.5rem;border-top:1px solid var(--nt-bdr-subtle)">
      <a href="blog.html" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Danh Sách Bài Viết</a>
      <a href="quote.html" class="btn btn-primary btn-sm"><i class="fas fa-calculator"></i> Nhận Báo Giá Dự Án</a>
    </div>
  </div>
</section>
</main>

<!-- FOOTER -->
<footer class="nt-footer">
  <div class="nt-footer-grid">
    <div>
      <div class="nt-footer-logo"><img src="assets/images/logo-small.webp" alt="Huy CNC Nha Trang" width="130" height="40" loading="lazy"></div>
      <p class="nt-footer-tagline">Chi nhánh Nha Trang — Khánh Hòa. Gia công CNC, cắt laser, biển quảng cáo chuyên nghiệp.</p>
    </div>
    <div>
      <h4 class="nt-footer-col-title">Dịch Vụ</h4>
      <nav class="nt-footer-links">
        <a href="products.html?cat=cnc" class="nt-footer-link">Gia Công CNC</a>
        <a href="products.html?cat=laser" class="nt-footer-link">Cắt Laser Fiber</a>
        <a href="products.html?cat=bienqc" class="nt-footer-link">Biển Quảng Cáo</a>
      </nav>
    </div>
    <div>
      <h4 class="nt-footer-col-title">Điều Hướng</h4>
      <nav class="nt-footer-links">
        <a href="index.html" class="nt-footer-link">Trang Chủ</a>
        <a href="products.html" class="nt-footer-link">Sản Phẩm</a>
        <a href="quote.html" class="nt-footer-link">Báo Giá</a>
        <a href="contact.html" class="nt-footer-link">Liên Hệ</a>
      </nav>
    </div>
    <div>
      <h4 class="nt-footer-col-title">Liên Hệ</h4>
      <div class="nt-footer-contact-item"><i class="fas fa-map-marker-alt"></i><span>[Địa chỉ chi nhánh Nha Trang]</span></div>
      <div class="nt-footer-contact-item"><i class="fas fa-phone"></i><span><a href="tel:+84xxxxxxxxx" style="color:var(--nt-coral)">0xxx xxx xxx</a></span></div>
    </div>
  </div>
  <div class="nt-footer-bottom">
    <p class="nt-footer-copy">© 2025 Huy CNC Nha Trang. Bảo lưu mọi quyền.</p>
  </div>
</footer>

<script src="assets/js/nt-core.js" defer></script>
</body>
</html>
