/**
 * lenis-setup.js
 * Smooth scroll controller using @studio-freight/lenis.
 * Provides a single RAF loop shared with GSAP ticker.
 * Exports pauseScroll / resumeScroll and exposes them on window
 * for cross-module access (e.g. SidebarManager in app.js).
 *
 * Feature: ui-ux-overhaul
 */

// ─── Internal singleton reference ────────────────────────────────────────────
let _lenis = null;

// ─── 3.6 Exported helpers (always valid; no-ops when Lenis failed to load) ───
export function pauseScroll() {
  if (_lenis) _lenis.stop();
}

export function resumeScroll() {
  if (_lenis) _lenis.start();
}

// Expose on window for cross-module access (task 12.3 requirement)
window.pauseScroll = pauseScroll;
window.resumeScroll = resumeScroll;

// ─── 3.1 Import Lenis inside try/catch for graceful fallback ─────────────────
try {
  // Dynamic import keeps the try/catch effective for module-resolution failures
  const { default: Lenis } = await import('@studio-freight/lenis');

  // ── 3.2 Instantiate with duration: 1.2 and cubic-ease easing ────────────────
  _lenis = new Lenis({
    duration: 1.2,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
  });

  // ── 3.3 Single driver: GSAP ticker OR rAF — never both (prevents double scroll updates).
  if (typeof gsap !== 'undefined' && gsap.ticker) {
    gsap.ticker.add((time) => _lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);
  } else {
    function raf(time) {
      _lenis.raf(time);
      requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);
  }

  // ── 3.5 Anchor link interception ────────────────────────────────────────────
  const attachAnchors = () => {
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener('click', (e) => {
        const target = anchor.getAttribute('href');
        if (!target || target === '#') return;
        e.preventDefault();
        _lenis.scrollTo(target);
      });
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachAnchors);
  } else {
    attachAnchors();
  }

} catch (err) {
  // ── 3.7 On import failure: warn and exit gracefully ──────────────────────────
  // Native scroll continues uninterrupted; pauseScroll/resumeScroll become no-ops.
  console.warn('Lenis failed to load, using native scroll', err);
}

// Re-export the internal reference so other modules can import { lenis }
// It will be null if Lenis failed to load.
export { _lenis as lenis };
