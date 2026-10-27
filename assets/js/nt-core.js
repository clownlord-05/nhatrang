/* =====================================================
   HUY CNC NHA TRANG — Core JavaScript
   Navigation, Language, Scroll, Animations
   ===================================================== */
'use strict';

// ─── UTILITIES ────────────────────────────────────────
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

function debounce(fn, ms = 200) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ─── HEADER SCROLL ────────────────────────────────────
function initHeader() {
  const header = $('#site-header');
  if (!header) return;
  const onScroll = () => {
    header.classList.toggle('scrolled', window.scrollY > 40);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

// ─── MOBILE HAMBURGER & STUDIO DOCK DRAWER ─────────────
function initMobileMenu() {
  const btn = $('#hamburger');
  const dock = $('.studio-nav-dock') || $('#nav-links');
  if (!btn || !dock) return;

  // Create backdrop if not exists
  let backdrop = $('.mobile-nav-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'mobile-nav-backdrop';
    document.body.appendChild(backdrop);
  }

  function setMenuState(open) {
    dock.classList.toggle('open', open);
    btn.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    backdrop.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
  }

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = !dock.classList.contains('open');
    setMenuState(isOpen);
  });

  backdrop.addEventListener('click', () => {
    setMenuState(false);
  });

  // Close when clicking outside dock and hamburger
  document.addEventListener('click', (e) => {
    if (dock.classList.contains('open') && !dock.contains(e.target) && !btn.contains(e.target)) {
      setMenuState(false);
    }
  });

  // Close on any menu link click
  $$('.dock-link, .nt-nav-link', dock).forEach(a => {
    a.addEventListener('click', () => {
      setMenuState(false);
    });
  });

  // Close on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && dock.classList.contains('open')) {
      setMenuState(false);
    }
  });
}

// ─── LANGUAGE PICKER ──────────────────────────────────
const LANG_FLAGS = { vi:'flag_vi.svg', en:'flag_en.svg', ko:'flag_ko.svg', zh:'flag_zh.svg', ja:'flag_ja.svg', de:'flag_de.svg' };

function initLangPicker() {
  const btn      = $('#lang-btn');
  const dropdown = $('#lang-dropdown');
  const flag     = $('#lang-flag');
  if (!btn || !dropdown) return;

  const savedLang = localStorage.getItem('nt_lang') || 'vi';
  if (flag && LANG_FLAGS[savedLang]) {
    flag.src = `assets/images/${LANG_FLAGS[savedLang]}`;
    flag.alt = savedLang.toUpperCase();
  }

  btn.addEventListener('click', e => {
    e.stopPropagation();
    const isOpen = dropdown.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen);
  });

  $$('.nt-lang-opt', dropdown).forEach(opt => {
    opt.addEventListener('click', () => {
      const lang = opt.dataset.lang;
      localStorage.setItem('nt_lang', lang);
      if (flag && LANG_FLAGS[lang]) {
        flag.src = `assets/images/${LANG_FLAGS[lang]}`;
        flag.alt = lang.toUpperCase();
      }
      dropdown.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      // Trigger translation if available
      if (window.NTTranslate) window.NTTranslate.apply(lang);
    });
  });

  document.addEventListener('click', e => {
    if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
}

// ─── SCROLL TO TOP ────────────────────────────────────
function initScrollTop() {
  const btn = $('#scroll-top');
  if (!btn) return;
  window.addEventListener('scroll', () => {
    btn.classList.toggle('show', window.scrollY > 400);
  }, { passive: true });
  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

// ─── TOAST NOTIFICATIONS ──────────────────────────────
window.showToast = function(msg, type = 'success', duration = 3000) {
  const toast = $('#toast');
  if (!toast) return;
  const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="${icon}" aria-hidden="true"></i><span>${msg}</span>`;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), duration);
};

// ─── FEATURED PRODUCTS (HOME) ─────────────────────────
async function loadHomeFeaturedProducts() {
  const grid = $('#home-products-grid');
  if (!grid) return;
  try {
    const res  = await fetch('backend/api/products.php?featured=1&limit=8');
    const data = await res.json();
    if (!data.success || !data.data.length) {
      grid.innerHTML = buildFallbackProducts();
      return;
    }
    grid.innerHTML = data.data.map(p => buildProductCard(p)).join('');
  } catch {
    grid.innerHTML = buildFallbackProducts();
  }
}

function buildProductCard(p) {
  const img  = p.image || 'assets/images/placeholder.jpg';
  const cat  = p.category || 'cnc';
  const slug = p.slug || p.id;
  return `
  <a href="product-detail.html?slug=${slug}" class="card product-card shimmer-border" data-reveal="up">
    <div class="product-card-img">
      <img src="${img}" alt="${p.name}" loading="lazy" decoding="async" width="400" height="300">
      <span class="badge product-card-cat">${catLabel(cat)}</span>
    </div>
    <div class="product-card-body">
      <h3 class="product-card-title">${p.name}</h3>
      <p class="product-card-desc">${p.description ? p.description.substring(0,100)+'…' : ''}</p>
      <div class="product-card-footer">
        <span class="product-card-price">${p.price > 0 ? formatPrice(p.price) : 'Liên hệ báo giá'}</span>
        <span class="btn btn-ghost btn-sm">Xem chi tiết</span>
      </div>
    </div>
  </a>`;
}

function buildFallbackProducts() {
  const items = [
    { name:'Biển Chữ Nổi Inox', cat:'bienqc', img:'uploads/products/catalogue-sp01-card.webp', desc:'Chữ nổi inox 304/316 ăn mòn hoặc cắt laser, sơn màu theo yêu cầu' },
    { name:'Gia Công CNC Nhôm', cat:'cnc',    img:'uploads/products/catalogue-sp02-card.webp', desc:'Phay CNC nhôm 6061, 7075 độ chính xác ±0.01mm, số lượng lớn' },
    { name:'Cắt Laser Inox Tấm', cat:'laser', img:'uploads/products/catalogue-sp03-card.webp', desc:'Cắt laser fiber inox từ 0.5mm đến 20mm, dung sai cao, mép sắc' },
    { name:'Kệ Mica Trong Suốt', cat:'mica',  img:'uploads/products/catalogue-sp04-card.webp', desc:'Kệ mica trong, màu, uốn nhiệt theo bản vẽ. Phù hợp showroom' },
    { name:'Khắc Laser Tên Công Ty', cat:'quatang', img:'uploads/products/catalogue-sp05-card.webp', desc:'Khắc logo, tên công ty lên kim loại, gỗ, pha lê làm quà tặng' },
    { name:'Biển Hộp Đèn LED', cat:'bienqc', img:'uploads/products/catalogue-sp06-card.webp', desc:'Hộp đèn mica mặt sáng, LED đều, chống nước IP65 dùng ngoài trời' },
    { name:'Gia Công Inox 316', cat:'cnc',    img:'uploads/products/catalogue-sp07-card.webp', desc:'Gia công CNC inox 316 chống ăn mòn, dùng trong ngành thực phẩm' },
    { name:'Cắt Chữ Inox Rỗng', cat:'laser', img:'uploads/products/catalogue-sp08-card.webp', desc:'Chữ inox rỗng cắt laser, hàn vào nền, sơn tĩnh điện nhiều màu' },
  ];
  return items.map((p,i) => `
  <a href="products.html" class="card product-card shimmer-border" data-reveal="up" data-delay="${(i%4)*100}">
    <div class="product-card-img">
      <img src="${p.img}" alt="${p.name}" loading="lazy" decoding="async" width="400" height="300"
           onerror="this.src='assets/images/anhxuong-display.webp'">
      <span class="badge product-card-cat">${catLabel(p.cat)}</span>
    </div>
    <div class="product-card-body">
      <h3 class="product-card-title">${p.name}</h3>
      <p class="product-card-desc">${p.desc}</p>
      <div class="product-card-footer">
        <span class="product-card-price">Liên hệ báo giá</span>
        <span class="btn btn-ghost btn-sm">Xem chi tiết</span>
      </div>
    </div>
  </a>`).join('');
}

// ─── HOME BLOG PREVIEW ────────────────────────────────
async function loadHomeBlog() {
  const grid = $('#home-blog-grid');
  if (!grid) return;
  try {
    const res  = await fetch('backend/api/posts.php?status=published&limit=3');
    const data = await res.json();
    if (!data.success || !data.data.length) {
      grid.innerHTML = '<p class="text-muted" style="grid-column:1/-1;text-align:center;padding:2rem">Chưa có bài viết. <a href="admin.html" style="color:var(--nt-teal)">Thêm bài viết</a></p>';
      return;
    }
    grid.innerHTML = data.data.map(p => buildBlogCard(p)).join('');
  } catch {
    grid.innerHTML = '';
  }
}

function buildBlogCard(p) {
  const thumb = p.thumbnail || 'assets/images/anhxuong-display.webp';
  const date  = p.published_at ? new Date(p.published_at).toLocaleDateString('vi-VN') : '';
  return `
  <a href="post-detail.php?slug=${p.slug}" class="card blog-card" data-reveal="up">
    <div class="blog-card-img">
      <img src="${thumb}" alt="${p.title}" loading="lazy" decoding="async" width="400" height="225">
    </div>
    <div class="blog-card-body">
      <div class="blog-card-meta">
        <span><i class="fas fa-calendar-alt" aria-hidden="true"></i> ${date}</span>
        <span class="badge badge-coral">${p.category || 'Kỹ thuật'}</span>
      </div>
      <h3 class="blog-card-title">${p.title}</h3>
      <p class="blog-card-excerpt">${p.excerpt || ''}</p>
    </div>
  </a>`;
}

// ─── HELPERS ──────────────────────────────────────────
function catLabel(cat) {
  const map = { cnc:'CNC', laser:'Laser', bienqc:'Biển QC', mica:'Mica', inox:'Inox', quatang:'Quà tặng', general:'Tổng hợp' };
  return map[cat] || cat;
}
function formatPrice(n) {
  return new Intl.NumberFormat('vi-VN', { style:'currency', currency:'VND', maximumFractionDigits:0 }).format(n);
}

// ─── INIT ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initHeader();
  initMobileMenu();
  initLangPicker();
  initScrollTop();

  // Page-specific
  if (document.querySelector('.home-page')) {
    loadHomeFeaturedProducts();
    loadHomeBlog();
  }
});
