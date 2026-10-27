/* =====================================================
   HUY CNC NHA TRANG — Scroll Animations & Counter
   ===================================================== */
'use strict';

// ─── INTERSECTION OBSERVER — REVEAL ──────────────────
function initScrollReveal() {
  if (!('IntersectionObserver' in window)) {
    document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('revealed'));
    return;
  }
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('[data-reveal]').forEach(el => observer.observe(el));
}

// ─── COUNTER ANIMATION ────────────────────────────────
function animateCountUp(el) {
  const target   = parseInt(el.dataset.target, 10) || 0;
  const duration = 1800;
  const start    = performance.now();

  function tick(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    // Ease out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.floor(eased * target).toLocaleString('vi-VN');
    if (progress < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

function initCounters() {
  if (!('IntersectionObserver' in window)) {
    document.querySelectorAll('.count-up').forEach(el => {
      el.textContent = parseInt(el.dataset.target, 10).toLocaleString('vi-VN');
    });
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && !entry.target.dataset.counted) {
        entry.target.dataset.counted = '1';
        animateCountUp(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.4 });

  document.querySelectorAll('.count-up').forEach(el => observer.observe(el));
}

// ─── TEAL DIVIDER GROW ────────────────────────────────
function initDividerGrow() {
  if (!('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('grown'); observer.unobserve(e.target); }
    });
  }, { threshold: 0.5 });
  document.querySelectorAll('.teal-line').forEach(el => observer.observe(el));
}

// ─── LAZY IMAGES (fallback for browsers w/o native lazy) ─
function initLazyImages() {
  if ('loading' in HTMLImageElement.prototype) return;
  const imgs = document.querySelectorAll('img[loading="lazy"]');
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const img = e.target;
        img.src = img.dataset.src || img.src;
        obs.unobserve(img);
      }
    });
  });
  imgs.forEach(img => obs.observe(img));
}

// ─── PARALLAX HERO ────────────────────────────────────
function initParallax() {
  const heroImg = document.querySelector('.hero-nt-bg img');
  if (!heroImg || window.matchMedia('(prefers-reduced-motion:reduce)').matches) return;
  window.addEventListener('scroll', () => {
    const y = window.scrollY;
    heroImg.style.transform = `translateY(${y * 0.25}px)`;
  }, { passive: true });
}

// ─── IMAGE ONERROR FALLBACK ───────────────────────────
function initImageFallbacks() {
  document.querySelectorAll('img').forEach(img => {
    if (!img.dataset.fallback) {
      img.addEventListener('error', () => {
        img.src = 'assets/images/anhxuong-display.webp';
        img.dataset.fallback = '1';
      }, { once: true });
    }
  });
}

// ─── INIT ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initScrollReveal();
  initCounters();
  initDividerGrow();
  initLazyImages();
  initParallax();
  initImageFallbacks();
});
