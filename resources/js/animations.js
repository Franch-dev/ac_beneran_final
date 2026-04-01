import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import AOS from 'aos';
import 'aos/dist/aos.css';

gsap.registerPlugin(ScrollTrigger);

/* ── Lenis smooth-scroll (fail-safe) ── */
try {
  const { default: Lenis } = await import('@studio-freight/lenis');
const lenis = new Lenis({
    duration: 0.8,
    easing: (t) => t<.5 ? 4*t*t*t : (t-1)*(2*t-2)*(2*t-2)+1, // smooth cubic
  });

  function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
  }
  requestAnimationFrame(raf);
} catch (e) {
  console.warn('[Forkis] Lenis smooth-scroll unavailable:', e.message);
}

/* ── AOS ── */
AOS.init({
  duration: 800,
  easing: 'ease-out-cubic',
  once: true,
  offset: 120,
});

/* ── Counter animation ── */
const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

const animateCounter = (counter) => {
  const rawTarget = counter.getAttribute('data-target') || '0';
  const target = parseFloat(rawTarget) || 0;
  const decimals = rawTarget.includes('.') ? 1 : 0;
  const duration = 1200;
  const startTime = performance.now();

  const update = (time) => {
    const elapsed = Math.min((time - startTime) / duration, 1);
    const value = target * easeOutCubic(elapsed);
    counter.textContent = decimals ? value.toFixed(1) : Math.round(value);

    if (elapsed < 1) {
      requestAnimationFrame(update);
    }
  };

  requestAnimationFrame(update);
};

const counterObserver = new IntersectionObserver(
  (entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.5 }
);

document.querySelectorAll('.counter').forEach((counter) => {
  counterObserver.observe(counter);
});

/* ── GSAP scroll-triggered animations (NO opacity — AOS handles visibility) ── */
gsap.from('.hero-content', {
  y: 40,
  duration: 1,
  ease: 'power3.out',
  delay: 0.1,
});

gsap.from('.hero-service-card', {
  scrollTrigger: {
    trigger: '.hero-service-card',
    start: 'top 90%',
  },
  y: 30,
  duration: 1,
  ease: 'power3.out',
});

gsap.from('.catalog-card', {
  scrollTrigger: {
    trigger: '.catalog-section',
    start: 'top 85%',
  },
  y: 30,
  duration: 0.8,
  stagger: 0.12,
  ease: 'power3.out',
});

gsap.from('.pricing-card', {
  scrollTrigger: {
    trigger: '#harga',
    start: 'top 90%',
  },
  y: 30,
  duration: 0.85,
  stagger: 0.12,
  ease: 'power3.out',
});

gsap.from('.contact-card', {
  scrollTrigger: {
    trigger: '#kontak',
    start: 'top 90%',
  },
  y: 30,
  duration: 0.85,
  stagger: 0.12,
  ease: 'power3.out',
});

/* ── Floating pill (decorative, no visibility impact) ── */
gsap.to('.hero-service-pill', {
  y: [-6, 6, -6],
  duration: 2.8,
  ease: 'sine.inOut',
  repeat: -1,
  yoyo: true,
});

export { gsap, ScrollTrigger };
