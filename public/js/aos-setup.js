/**
 * aos-setup.js
 * AOS (Animate On Scroll) initialization module.
 * Configures global AOS defaults and applies data-aos attribute-driven scroll reveals.
 * Runs after DOM is ready and before GSAP master animations begin.
 *
 * Feature: ui-ux-overhaul
 */

// ─── Resolve AOS (CDN / window fallback) ─────────────────────────────────────
const _AOS = (typeof AOS !== 'undefined') ? AOS : window.AOS;

if (!_AOS) {
  console.warn('[aos-setup] AOS not found. Scroll reveals disabled.');
} else {
  // ─── 5.3 Ensure AOS init runs after DOM is ready ───────────────────────────
  const initAOS = () => {
    // ─── 5.1 Initialize AOS with specified configuration ─────────────────────
    _AOS.init({
      duration: 800,
      easing: 'ease-out-cubic',
      once: true,
      offset: 120,
    });

    // ─── 5.2 Add matchMedia check for mobile viewport ────────────────────────
    // If viewport < 768px, re-init AOS with offset: 60
    if (window.matchMedia('(max-width: 767px)').matches) {
      _AOS.init({
        duration: 800,
        easing: 'ease-out-cubic',
        once: true,
        offset: 60,
      });
    }
  };

  // Run after DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAOS);
  } else {
    initAOS();
  }
}
