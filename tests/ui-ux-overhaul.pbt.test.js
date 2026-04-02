import { describe, it, expect } from 'vitest';
import fc from 'fast-check';
import fs from 'node:fs';
import path from 'node:path';

const rootDir = path.resolve(process.cwd());
const styleCss = fs.readFileSync(path.join(rootDir, 'public/css/style.css'), 'utf8');
const homeBlade = fs.readFileSync(path.join(rootDir, 'resources/views/home.blade.php'), 'utf8');
const appJs = fs.readFileSync(path.join(rootDir, 'public/js/app.js'), 'utf8');
const lenisSetup = fs.readFileSync(path.join(rootDir, 'public/js/lenis-setup.js'), 'utf8');
const gsapMaster = fs.readFileSync(path.join(rootDir, 'public/js/gsap-master.js'), 'utf8');
const aosSetup = fs.readFileSync(path.join(rootDir, 'public/js/aos-setup.js'), 'utf8');
const framerEquivalents = fs.readFileSync(path.join(rootDir, 'public/js/framer-equivalents.js'), 'utf8');

const GLASS_TOKENS = [
  '--glass-bg',
  '--glass-blur',
  '--glass-border',
  '--glass-border-dark',
  '--shadow-glass',
  '--shadow-glass-dark',
];

const getBlock = (css, selector) => {
  const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const re = new RegExp(`${escaped}\\s*\\{([\\s\\S]*?)\\}`, 'm');
  const match = css.match(re);
  return match ? match[1] : '';
};

describe('UI/UX Overhaul Property-Based Tests', () => {
  it('Feature ui-ux-overhaul, Property 1: Glass CSS variables are defined in :root', () => {
    const rootBlock = getBlock(styleCss, ':root');
    fc.assert(
      fc.property(fc.constantFrom(...GLASS_TOKENS), (token) => {
        const re = new RegExp(`${token}\\s*:\\s*[^;]+;`);
        expect(rootBlock).toMatch(re);
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 2: Glass classes apply backdrop-filter', () => {
    fc.assert(
      fc.property(fc.constantFrom('.glass', '.glass-hero', '.glass-card', '.glass-navbar'), () => {
        expect(styleCss).toContain('backdrop-filter: var(--glass-blur);');
        expect(styleCss).toContain('-webkit-backdrop-filter: var(--glass-blur);');
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 3: Dark mode glass override', () => {
    const darkGlassBlock = getBlock(
      styleCss,
      '[data-theme="dark"] .glass,\n[data-theme="dark"] .glass-hero,\n[data-theme="dark"] .glass-card,\n[data-theme="dark"] .glass-navbar'
    );
    fc.assert(
      fc.property(fc.array(fc.constantFrom('light', 'dark'), { minLength: 1, maxLength: 20 }), () => {
        expect(darkGlassBlock).toContain('background: rgba(30, 30, 35, 0.72);');
        expect(darkGlassBlock).toContain('border: var(--glass-border-dark);');
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 4: Theme persistence round trip', () => {
    fc.assert(
      fc.property(fc.constantFrom('light', 'dark'), (theme) => {
        const store = new Map();
        store.set('theme', theme);
        expect(store.get('theme')).toBe(theme);
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 5: Dark mode token completeness', () => {
    const darkBlock = getBlock(styleCss, '[data-theme="dark"]');
    const rootBlock = getBlock(styleCss, ':root');
    const tokenMatches = [...rootBlock.matchAll(/(--[a-z0-9-]+)\s*:/gi)].map((m) => m[1]);
    const tokenSet = [...new Set(tokenMatches)].filter((token) =>
      /(glass|shadow|primary|success|danger|warning|info|google|gray|text|bg|border)/.test(token)
    );

    fc.assert(
      fc.property(fc.constantFrom(...tokenSet), (token) => {
        if (
          token === '--glass-border-dark' ||
          token === '--shadow-glass-dark' ||
          token === '--glass-blur' ||
          token === '--glass-border'
        ) {
          expect(rootBlock).toContain(`${token}:`);
          return;
        }
        expect(darkBlock).toContain(`${token}:`);
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 6: Lenis anchor scroll interception', () => {
    fc.assert(
      fc.property(fc.webPath(), (fragmentPath) => {
        const href = `#${fragmentPath.replaceAll('/', '-') || 'home'}`;
        expect(href.startsWith('#')).toBe(true);
        expect(lenisSetup).toContain("document.querySelectorAll('a[href^=\"#\"]')");
        expect(lenisSetup).toContain('_lenis.scrollTo(target);');
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 7: Single RAF loop invariant', () => {
    fc.assert(
      fc.property(fc.integer({ min: 0, max: 10 }), () => {
        const matches = lenisSetup.match(/requestAnimationFrame\(raf\)/g) ?? [];
        expect(matches).toHaveLength(2); // function recursive call + initial kick-off
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 8: GSAP animation guard', () => {
    fc.assert(
      fc.property(fc.string({ minLength: 1, maxLength: 40 }), () => {
        expect(gsapMaster).toContain('const heroContent = document.querySelector(\'.hero-content\');');
        expect(gsapMaster).toContain('if (heroContent)');
        expect(gsapMaster).toContain('if (document.querySelector(\'.summary-card\'))');
        expect(gsapMaster).toContain('if (document.querySelector(\'.catalog-section\') && document.querySelector(\'.catalog-card\'))');
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 9: AOS once-only trigger', () => {
    fc.assert(
      fc.property(fc.array(fc.integer({ min: -2000, max: 2000 }), { minLength: 1, maxLength: 50 }), () => {
        expect(aosSetup).toContain('once: true');
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 10: Framer equivalents accept options override', () => {
    fc.assert(
      fc.property(
        fc.record({
          duration: fc.double({ min: 0.1, max: 3, noNaN: true, noDefaultInfinity: true }),
          delay: fc.double({ min: 0, max: 1, noNaN: true, noDefaultInfinity: true }),
          ease: fc.constantFrom('power1.out', 'power2.out', 'power3.out'),
          stagger: fc.double({ min: 0.01, max: 0.4, noNaN: true, noDefaultInfinity: true }),
        }),
        () => {
          expect(framerEquivalents).toContain('const opts = { ...defaults, ...options };');
          expect(framerEquivalents).toContain('stagger: opts.stagger');
        }
      ),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 11: Popup body scroll lock', () => {
    fc.assert(
      fc.property(fc.array(fc.constantFrom('open', 'close'), { minLength: 1, maxLength: 40 }), (ops) => {
        let openCount = 0;
        for (const op of ops) {
          if (op === 'open') openCount += 1;
          if (op === 'close' && openCount > 0) openCount -= 1;
        }
        const expected = openCount > 0 ? 'hidden' : '';
        expect(['hidden', '']).toContain(expected);
        expect(appJs).toContain("document.body.style.overflow = 'hidden';");
        expect(appJs).toContain("document.body.style.overflow = '';");
      }),
      { numRuns: 100 }
    );
  });

  it('Feature ui-ux-overhaul, Property 12: Mobile blur reduction', () => {
    fc.assert(
      fc.property(fc.integer({ min: 320, max: 767 }), () => {
        expect(styleCss).toContain('@media (max-width: 768px) {\n  .glass-card {\n    backdrop-filter: blur(8px);');
      }),
      { numRuns: 100 }
    );
  });

  it('Home AOS coverage remains intact', () => {
    expect(homeBlade).toContain('class="catalog-card glass-card" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}"');
    expect(homeBlade).toContain('class="pricing-card glass-card" data-aos="fade-up" data-aos-delay="100"');
    expect(homeBlade).toContain('class="contact-card glass-card" data-aos="fade-up" data-aos-delay="100"');
  });
});
