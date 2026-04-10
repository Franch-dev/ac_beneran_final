/**
 * LIQUID GLASS MICRO-INTERACTIONS 2026
 * Fluid UX: ripple hovers, click morphs, scroll-triggered liquid flows,
 * navbar scroll detection, and cursor-tracking refractions.
 *
 * ─ Performance-first: uses IntersectionObserver, requestAnimationFrame,
 *   passive listeners, and respects prefers-reduced-motion.
 * ─ All animations are under 300ms per the constraints.
 * ─ Low-end device detection reduces GPU-heavy effects.
 */

(function liquidGlassInit() {
  'use strict';

  // ── Guard: Respect reduced-motion ──────────────────────────────
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (prefersReducedMotion.matches) return;

  // ── Low-end device detection ───────────────────────────────────
  const isLowEnd =
    navigator.hardwareConcurrency <= 2 ||
    (navigator.deviceMemory && navigator.deviceMemory <= 2) ||
    window.matchMedia('(max-width: 480px)').matches;

  // ──────────────────────────────────────────────────────────────
  //  1. RIPPLE HOVER EFFECT
  //  Adds a liquid ripple on click for all interactive elements
  // ──────────────────────────────────────────────────────────────
  function createRipple(e) {
    const target = e.currentTarget;
    const rect = target.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const size = Math.max(rect.width, rect.height) * 2;

    const ripple = document.createElement('span');
    ripple.className = 'lg-ripple__wave';
    ripple.style.cssText = `
      left: ${x}px;
      top: ${y}px;
      width: ${size}px;
      height: ${size}px;
    `;

    target.appendChild(ripple);

    ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
    // Safety cleanup
    setTimeout(() => { if (ripple.parentNode) ripple.remove(); }, 500);
  }

  // Attach ripple to all buttons and interactive cards
  function attachRipples() {
    const rippleSelectors = [
      '.btn',
      '.btn-primary',
      '.btn-secondary',
      '.btn-outline',
      '.btn-success',
      '.btn-danger',
      '.btn-info',
      '.btn-warning',
      '.glass-button',
      '.glass-button-primary',
      '.lg-btn',
      '.nav-link',
      '.sidebar-link',
      '.sidebar-action-btn',
      '.catalog-card',
    ];

    const elements = document.querySelectorAll(rippleSelectors.join(','));
    elements.forEach((el) => {
      if (el.dataset.lgRipple) return; // Already bound
      el.dataset.lgRipple = '1';
      el.style.position = el.style.position || 'relative';
      el.style.overflow = 'hidden';
      el.addEventListener('click', createRipple, { passive: true });
    });
  }

  // ──────────────────────────────────────────────────────────────
  //  2. SCROLL-TRIGGERED LIQUID FLOW REVEAL
  //  Uses IntersectionObserver for performant scroll-triggered anims
  // ──────────────────────────────────────────────────────────────
  function initScrollReveal() {
    const revealTargets = document.querySelectorAll(
      '.glass-card, .masjid-card, .pricing-card, .contact-card, .catalog-card, ' +
      '.fcard, .feature-card, .summary-card, .hero-support-card, ' +
      '.section-header, .hero-content, .hero-visual, ' +
      '.lg-card, .lg-scroll-reveal, .ui-reveal, ' +
      '[data-lg-reveal]'
    );

    if (!revealTargets.length) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            // For elements using ui-reveal class
            if (entry.target.classList.contains('lg-scroll-reveal') ||
                entry.target.classList.contains('ui-reveal')) {
              entry.target.style.opacity = '1';
              entry.target.style.transform = 'translateY(0) scale(1)';
            }
            observer.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.12,
        rootMargin: '0px 0px -60px 0px',
      }
    );

    revealTargets.forEach((el, index) => {
      // Only add scroll reveal class to elements that don't already have AOS
      if (!el.hasAttribute('data-aos') &&
          !el.classList.contains('lg-scroll-reveal') &&
          !el.classList.contains('ui-reveal')) {
        el.classList.add('lg-scroll-reveal');
        el.style.setProperty('--lg-reveal-delay', `${Math.min(index * 50, 300)}ms`);
      }

      // Always observe for is-visible class
      observer.observe(el);
    });
  }

  // ──────────────────────────────────────────────────────────────
  //  3. NAVBAR SCROLL STATE
  //  Adds scrolled class for enhanced glass effect
  // ──────────────────────────────────────────────────────────────
  function initNavbarScroll() {
    const navbar = document.querySelector('.navbar, .glass-navbar, .lg-navbar');
    if (!navbar) return;

    let ticking = false;
    let lastScrollY = 0;

    function updateNavbar() {
      const scrollY = window.scrollY || window.pageYOffset;

      if (scrollY > 20) {
        navbar.classList.add('lg-navbar--scrolled');
        navbar.style.boxShadow = `
          0 2px 6px rgba(0, 0, 0, 0.04),
          0 8px 24px rgba(0, 0, 0, 0.06),
          inset 0 1px 0 rgba(255, 255, 255, 0.35)
        `;
      } else {
        navbar.classList.remove('lg-navbar--scrolled');
        navbar.style.boxShadow = '';
      }

      lastScrollY = scrollY;
      ticking = false;
    }

    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(updateNavbar);
        ticking = true;
      }
    }, { passive: true });

    // Initial check
    updateNavbar();
  }

  // ──────────────────────────────────────────────────────────────
  //  4. CURSOR-TRACKING REFRACTION (desktop only)
  //  Moves the inner glow highlight based on cursor position
  // ──────────────────────────────────────────────────────────────
  function initCursorRefraction() {
    if (isLowEnd || window.matchMedia('(max-width: 768px)').matches) return;

    const cards = document.querySelectorAll(
      '.hero-card, .pricing-featured, .login-card, .lg-card--featured, [data-lg-refraction]'
    );

    cards.forEach((card) => {
      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;

        card.style.setProperty('--liquid-morph-x', `${x}%`);
        card.style.setProperty('--liquid-morph-y', `${y}%`);
        card.style.setProperty('--liquid-glow-opacity', '0.2');
      }, { passive: true });

      card.addEventListener('mouseleave', () => {
        card.style.setProperty('--liquid-glow-opacity', '0');
        card.style.setProperty('--liquid-morph-x', '50%');
        card.style.setProperty('--liquid-morph-y', '30%');
      }, { passive: true });
    });
  }

  // ──────────────────────────────────────────────────────────────
  //  5. LIQUID GLASS TOAST HELPER
  //  Usage: liquidGlass.toast('Message', 'success|error|info')
  // ──────────────────────────────────────────────────────────────
  function showLGToast(message, type = 'info', duration = 4000) {
    const icons = {
      success: 'fas fa-check',
      error: 'fas fa-exclamation-triangle',
      info: 'fas fa-info-circle',
    };

    const toast = document.createElement('div');
    toast.className = `lg-toast lg-toast--${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = `
      <div class="lg-toast__icon"><i class="${icons[type] || icons.info}"></i></div>
      <div class="lg-toast__message">${message}</div>
      <button class="lg-toast__close" aria-label="Close notification">&times;</button>
    `;

    document.body.appendChild(toast);

    // Close handlers
    const close = () => {
      toast.classList.add('lg-toast--exit');
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
      setTimeout(() => { if (toast.parentNode) toast.remove(); }, 400);
    };

    toast.querySelector('.lg-toast__close').addEventListener('click', close);
    setTimeout(close, duration);
  }

  // ──────────────────────────────────────────────────────────────
  //  6. STAGGER ANIMATION FOR GRID CHILDREN
  //  Auto-applies staggered reveal delays to grid items
  // ──────────────────────────────────────────────────────────────
  function initStaggerGrids() {
    const grids = document.querySelectorAll(
      '.cards-grid, .catalog-grid, .pricing-grid, .contact-grid, ' +
      '.feature-grid, .summary-grid, .urgency-grid, .hero-service-support-grid, ' +
      '[data-lg-stagger]'
    );

    grids.forEach((grid) => {
      const children = Array.from(grid.children);
      children.forEach((child, i) => {
        if (!child.hasAttribute('data-aos')) {
          child.style.setProperty('--lg-reveal-delay', `${Math.min(i * 60, 300)}ms`);
        }
      });
    });
  }

  // ──────────────────────────────────────────────────────────────
  //  7. LIQUID EDGE MORPH ON HOVER (subtle border-radius shift)
  // ──────────────────────────────────────────────────────────────
  function initEdgeMorph() {
    if (isLowEnd) return;

    const morphTargets = document.querySelectorAll(
      '.glass-card, .masjid-card, .pricing-card, .catalog-card, .lg-card--hoverable'
    );

    morphTargets.forEach((el) => {
      el.addEventListener('mouseenter', () => {
        el.style.borderRadius = '28px';
      }, { passive: true });

      el.addEventListener('mouseleave', () => {
        el.style.borderRadius = '';
      }, { passive: true });
    });
  }

  // ──────────────────────────────────────────────────────────────
  //  INITIALIZATION
  // ──────────────────────────────────────────────────────────────
  function boot() {
    attachRipples();
    initScrollReveal();
    initNavbarScroll();
    initCursorRefraction();
    initStaggerGrids();
    initEdgeMorph();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // Re-initialize after dynamic content loads (SPA/AJAX)
  const bodyObserver = new MutationObserver(() => {
    attachRipples();
  });
  bodyObserver.observe(document.body, { childList: true, subtree: true });

  // Expose toast API globally
  window.liquidGlass = {
    toast: showLGToast,
    reinit: boot,
  };
})();
