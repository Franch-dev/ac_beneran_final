/**
 * GSAP MOTION PRIMITIVES — Design System Motion Library
 * Centralized spring physics, stagger patterns, ScrollTrigger reveals
 * Mirrors framer-motion variants for dashboard/monitoring UIs.
 * Loaded globally via app.blade.php @stack('scripts')
 */

const { gsap, ScrollTrigger } = window;

// Guard: GSAP unavailable
if (!gsap) {
  console.warn('[motion-primitives] GSAP missing');
  window.motion = { spring: () => {}, stagger: () => {}, reveal: () => {} };
  window.motionTokens = {};
} else {

  gsap.registerPlugin(ScrollTrigger);

  // === MOTION TOKENS (from design-tokens.css) ===
  window.motionTokens = {
    spring: { duration: 0.6, ease: 'back.out(1.7)' },
    elastic: { duration: 0.8, ease: 'elastic.out(1, 0.5)' },
    smooth: { duration: 0.8, ease: 'power3.out' },
    fast: 0.25,
    stagger: { amount: 0.12, from: 'start', ease: 'power2.out' },
  };

  // === 1. SPRING HOVER (Elastic card hover) ===
  window.motion.spring = function(target, options = {}) {
    const config = {
      scale: 1.02,
      y: -4,
      duration: window.motionTokens.spring.duration,
      ease: window.motionTokens.spring.ease,
      ...options
    };

    gsap.set(target, { transformOrigin: 'center center' });
    gsap.to(target, config);
  };

  // === 2. STAGGER REVEAL (Hero KPI, Masjid cards) ===
  window.motion.stagger = function(parentSelector, childSelector, options = {}) {
    const config = {
      y: 40,
      opacity: 0,
      duration: 0.7,
      stagger: window.motionTokens.stagger,
      ease: 'power3.out',
      ...options
    };

    gsap.from(`${parentSelector} ${childSelector}`, config);
  };

  // === 3. SCROLL REVEAL (Replace AOS for stagger-group/item) ===
  window.motion.reveal = function() {
    gsap.utils.toArray('[data-stagger-group]').forEach((group) => {
      ScrollTrigger.create({
        trigger: group,
        start: 'top 85%',
        onEnter: () => {
          gsap.from(group.querySelectorAll('[data-stagger-item]'), {
            y: 40,
            opacity: 0,
            duration: 0.7,
            stagger: {
              amount: 0.15,
              from: 'top',
              ease: 'power3.out'
            },
            overwrite: 'auto'
          });
        }
      });
    });
  };

  // === 4. ELASTIC FOCUS RING ===
  window.motion.focusRing = function() {
    gsap.utils.toArray('.btn, .form-input:focus, [tabindex]:focus').forEach((el) => {
      el.addEventListener('focus', () => {
        gsap.to(el, {
          scale: 1.02,
          duration: 0.2,
          ease: 'elastic.out(1, 0.5)'
        });
      });
      el.addEventListener('blur', () => {
        gsap.to(el, { scale: 1, duration: 0.15 });
      });
    });
  };

  // === INIT ON LOAD ===
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      window.motion.reveal();
      window.motion.focusRing();
    });
  } else {
    window.motion.reveal();
    window.motion.focusRing();
  }

  // === AUTO-APPLY TO DASHBOARD ELEMENTS ===
  const dashboardObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        // Hero KPI stagger
        if (entry.target.matches('.ops-kpi-grid, .summary-grid')) {
          window.motion.stagger(entry.target, '[data-stagger-item]');
        }
        // Masjid cards
        if (entry.target.matches('.ops-card-grid')) {
          window.motion.stagger(entry.target, '.masjid-card');
        }
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.ops-kpi-grid, .summary-grid, .ops-card-grid').forEach((el) => {
    dashboardObserver.observe(el);
  });
}

// Export for explicit use
window.motionPrimitivesLoaded = true;

