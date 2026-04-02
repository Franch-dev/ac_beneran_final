import AOS from 'aos';
import 'aos/dist/aos.css';

/**
 * AOS animations + counters. Native smooth scroll (no Lenis).
 */

function bootAnimations() {
  initAOS();
  initCounters();
}

function initAOS() {
  AOS.init({
    duration: 800,
    easing: 'ease-out-cubic',
    once: true,
    offset: window.matchMedia('(max-width: 767px)').matches ? 60 : 120,
  });

  // Native anchor handling
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (e) => {
      const target = anchor.getAttribute('href');
      if (!target || target === '#' || target === '#!') return;
      const el = document.querySelector(target);
      if (!el) return;
      e.preventDefault();
      el.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
    });
  });
}

const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

function animateCounter(counter) {
  const rawTarget = counter.getAttribute('data-target') || '0';
  const target = parseFloat(rawTarget) || 0;
  const decimals = rawTarget.includes('.') ? 1 : 0;
  const duration = 1200;
  const startTime = performance.now();

  function update(time) {
    const elapsed = Math.min((time - startTime) / duration, 1);
    const value = target * easeOutCubic(elapsed);
    counter.textContent = decimals ? value.toFixed(1) : String(Math.round(value));

    if (elapsed < 1) {
      requestAnimationFrame(update);
    }
  }

  requestAnimationFrame(update);
}

function initCounters() {
  const counterObserver = new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.45 }
  );

  document.querySelectorAll('.counter').forEach((counter) => {
    counterObserver.observe(counter);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootAnimations);
} else {
  bootAnimations();
}

