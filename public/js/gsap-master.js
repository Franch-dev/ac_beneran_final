/**
 * gsap-master.js
 * GSAP + ScrollTrigger orchestration module.
 * Hero timelines, scroll-triggered entrance animations, floating loops,
 * and mobile matchMedia guards.
 *
 * Loaded as <script type="module"> — no build step required.
 */

// ---------------------------------------------------------------------------
// 4.1 — Resolve GSAP and ScrollTrigger (CDN / window fallback)
// ---------------------------------------------------------------------------
const _gsap = (typeof gsap !== 'undefined') ? gsap : window.gsap;
const _ScrollTrigger = (typeof ScrollTrigger !== 'undefined') ? ScrollTrigger : window.ScrollTrigger;

if (!_gsap) {
  console.warn('[gsap-master] GSAP not found. Animations disabled.');
} else {
  if (_ScrollTrigger) {
    _gsap.registerPlugin(_ScrollTrigger);
    _ScrollTrigger.config({ limitCallbacks: true });
  }

  // -------------------------------------------------------------------------
  // DOMContentLoaded — hero entrance + dashboard summary cards + pill float
  // -------------------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', () => {

    // -----------------------------------------------------------------------
    // 4.2 — Hero entrance: .hero-content + .hero-support-card stagger
    // -----------------------------------------------------------------------
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
      const heroTl = _gsap.timeline();

      heroTl.from('.hero-content', {
        y: 60,
        opacity: 0,
        duration: 1,
        ease: 'power3.out',
      });

      // 4.8 guard: only stagger support cards if they exist
      const heroSupportCards = document.querySelectorAll('.hero-support-card');
      if (heroSupportCards.length) {
        heroTl.from('.hero-support-card', {
          y: 40,
          opacity: 0,
          duration: 0.7,
          ease: 'power3.out',
          stagger: 0.15,
        }, '-=0.5');
      }
    }

    // -----------------------------------------------------------------------
    // 4.7 — Dashboard summary cards entrance
    // -----------------------------------------------------------------------
    if (document.querySelector('.summary-card')) {
      _gsap.from('.summary-card', {
        y: 20,
        opacity: 0,
        duration: 0.7,
        ease: 'power2.out',
        stagger: 0.08,
      });
    }

    // -----------------------------------------------------------------------
    // 4.6 — Continuous floating loop on .hero-service-pill (y: -6 → 6)
    // -----------------------------------------------------------------------
    if (document.querySelector('.hero-service-pill')) {
      _gsap.fromTo(
        '.hero-service-pill',
        { y: -6 },
        {
          y: 6,
          duration: 2.8,
          ease: 'sine.inOut',
          repeat: -1,
          yoyo: true,
        }
      );
    }

    // -----------------------------------------------------------------------
    // ScrollTrigger animations (only if plugin is available)
    // -----------------------------------------------------------------------
    if (!_ScrollTrigger) return;

    // -----------------------------------------------------------------------
    // 4.3 — Catalog cards ScrollTrigger
    // -----------------------------------------------------------------------
    if (document.querySelector('.catalog-section') && document.querySelector('.catalog-card')) {
      _gsap.from('.catalog-card', {
        y: 40,
        opacity: 0,
        duration: 0.7,
        ease: 'power3.out',
        stagger: 0.1,
        scrollTrigger: {
          trigger: '.catalog-section',
          start: 'top 85%',
        },
      });
    }

    // -----------------------------------------------------------------------
    // 4.4 — Pricing cards ScrollTrigger (#harga)
    // -----------------------------------------------------------------------
    if (document.querySelector('#harga') && document.querySelector('.pricing-card')) {
      _gsap.from('.pricing-card', {
        y: 40,
        opacity: 0,
        duration: 0.7,
        ease: 'power3.out',
        stagger: 0.12,
        scrollTrigger: {
          trigger: '#harga',
          start: 'top 85%',
        },
      });
    }

    // -----------------------------------------------------------------------
    // 4.5 — Contact cards ScrollTrigger (#kontak)
    // -----------------------------------------------------------------------
    if (document.querySelector('#kontak') && document.querySelector('.contact-card')) {
      _gsap.from('.contact-card', {
        y: 30,
        opacity: 0,
        duration: 0.7,
        ease: 'power3.out',
        stagger: 0.12,
        scrollTrigger: {
          trigger: '#kontak',
          start: 'top 85%',
        },
      });
    }

    // -----------------------------------------------------------------------
    // 4.9 — ScrollTrigger.matchMedia: disable parallax/pin on mobile
    // -----------------------------------------------------------------------
    _ScrollTrigger.matchMedia({
      '(max-width: 767px)': () => {
        // Disable any parallax pin effects on mobile to prevent layout shifts.
        // Kill all pinned ScrollTriggers on mobile viewports.
        _ScrollTrigger.getAll().forEach((st) => {
          if (st.pin) {
            st.kill();
          }
        });
      },
    });

  }); // end DOMContentLoaded

} // end if (_gsap)
